<?php

use App\Models\User;
use App\Models\BlogPost;
use App\Support\CheckoutPaymentRouter;
use App\Support\ReservationPricingPolicy;
use App\Support\ReservationSettlementCalculator;
use App\Support\UniformIconSystem;
use App\Support\VendorPropertyCompatibilityReader;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Socialite\Facades\Socialite;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

Route::post('/booking/reserve', function (Request $request) {
    $payload = $request->validate([
        'property_id' => ['required', 'integer', 'min:1'],
        'room_id' => ['required', 'integer', 'min:1'],
        'checkin' => ['required', 'date', 'after_or_equal:today'],
        'checkout' => ['required', 'date', 'after:checkin'],
        'adults' => ['required', 'integer', 'min:1', 'max:20'],
        'children' => ['nullable', 'integer', 'min:0', 'max:20'],
        'primary_first_name' => ['required', 'string', 'max:80'],
        'primary_last_name' => ['required', 'string', 'max:80'],
        'primary_nationality' => ['required', 'string', 'max:120'],
        'guest_residency' => ['nullable', Rule::in(['local_resident', 'foreign_national'])],
        'primary_email' => ['required', 'email', 'max:190'],
        'primary_mobile' => ['required', 'string', 'max:40', 'regex:/^\+?[0-9][0-9\s\-()]{5,39}$/'],
        'additional_guest_details' => ['nullable', 'string', 'max:4000'],
        'transfer_option' => ['nullable', 'string', 'max:80'],
        'transfer_charge' => ['nullable', 'numeric', 'min:0'],
        'room_subtotal' => ['nullable', 'numeric', 'min:0'],
        'discount_amount' => ['nullable', 'numeric', 'min:0'],
        'tax_amount' => ['nullable', 'numeric', 'min:0'],
        'total_amount' => ['nullable', 'numeric', 'min:0'],
    ], [
        'primary_first_name.required' => 'Primary guest first name is required.',
        'primary_last_name.required' => 'Primary guest last name is required.',
        'primary_nationality.required' => 'Primary guest nationality is required.',
        'primary_email.required' => 'Primary guest email is required.',
        'primary_email.email' => 'Please enter a valid email address for the primary guest.',
        'primary_mobile.required' => 'Primary guest mobile is required.',
        'primary_mobile.regex' => 'Please enter a valid primary guest mobile number.',
        'checkin.after_or_equal' => 'Check-in date cannot be in the past.',
        'checkout.after' => 'Checkout date must be after check-in date.',
    ], [
        'primary_first_name' => 'primary guest first name',
        'primary_last_name' => 'primary guest last name',
        'primary_nationality' => 'primary guest nationality',
        'primary_email' => 'primary guest email',
        'primary_mobile' => 'primary guest mobile',
    ]);

    $propertyRow = VendorPropertyCompatibilityReader::loadPropertyById((int) $payload['property_id']);
    $roomRow = Schema::hasTable('vendor_property_room_categories')
        ? DB::table('vendor_property_room_categories')->where('id', (int) $payload['room_id'])->first()
        : null;

    if (!$propertyRow || !$roomRow) {
        abort(404);
    }

    // Listing-level publish gate: only approved listings can accept bookings.
    if (isset($propertyRow->listing_moderation_status)) {
        $listingModerationStatus = strtolower(trim((string) ($propertyRow->listing_moderation_status ?? 'draft')));
        if ($listingModerationStatus !== 'approved') {
            return back()->withErrors(['booking' => 'This listing is not yet available for bookings. It is currently under review or pending approval.']);
        }
    }

    $checkin = Carbon::parse((string) $payload['checkin']);
    $checkout = Carbon::parse((string) $payload['checkout']);
    $bookingStart = $checkin->copy()->startOfDay();
    $bookingEndExclusive = $checkout->copy()->startOfDay();

    $slotAvailability = workationSlotAvailabilityCheck(
        (int) ($propertyRow->vendor_user_id ?? 0),
        (int) ($propertyRow->id ?? 0),
        $bookingStart,
        $bookingEndExclusive,
        1,
        (int) ($roomRow->id ?? 0),
        null,
        'accommodation',
        null
    );

    if (($slotAvailability['ok'] ?? true) !== true) {
        $slotDate = (string) ($slotAvailability['date'] ?? 'selected dates');
        $slotReason = (string) ($slotAvailability['reason'] ?? '');
        if ($slotReason === 'blocked') {
            return back()->withErrors(['booking' => 'This room is unavailable on ' . $slotDate . ' (blocked by the operator: sold out/scratched/unavailable). Please choose different dates.'])->withInput();
        }

        return back()->withErrors(['booking' => 'This room is sold out on ' . $slotDate . '. Please choose different dates.'])->withInput();
    }

    $overlapCount = workationOverlappingReservationCount(
        (int) ($propertyRow->vendor_user_id ?? 0),
        (int) ($propertyRow->id ?? 0),
        $bookingStart,
        $bookingEndExclusive,
        (int) ($roomRow->id ?? 0),
        null
    );

    if ($overlapCount > 0) {
        return back()->withErrors(['booking' => 'This room is already reserved for the selected dates. Please choose another room or dates.'])->withInput();
    }

    $nights = max(1, $checkin->diffInDays($checkout));
    $adults = (int) $payload['adults'];
    $children = (int) ($payload['children'] ?? 0);
    $guestCount = $adults + $children;
    $nightlyRate = (float) ($roomRow->base_price ?? $propertyRow->base_price ?? 0);
    $roomSubtotal = $nightlyRate * $nights;

    $propertyDetails = json_decode((string) ($propertyRow->listing_details ?? ''), true);
    if (!is_array($propertyDetails)) {
        $propertyDetails = [];
    }

    $discountPercent = (float) ($propertyDetails['promotion_discount_percent'] ?? 0);
    $transferOptionCode = trim((string) ($payload['transfer_option'] ?? ''));
    $transferRateMatrix = is_array($propertyDetails['transfer_rate_matrix'] ?? null)
        ? $propertyDetails['transfer_rate_matrix']
        : [];
    $legacyTransferRates = is_array($propertyDetails['transfer_rates'] ?? null)
        ? $propertyDetails['transfer_rates']
        : [];
    $transferOptions = collect($propertyDetails['transfer_options'] ?? [])->map(function ($option) use ($transferRateMatrix, $legacyTransferRates, $propertyDetails) {
        if (is_array($option)) {
            $code = strtolower(trim((string) ($option['code'] ?? '')));
            return $option + ['code' => $code];
        }

        $code = strtolower(trim((string) $option));
        $matrix = is_array($transferRateMatrix[$code] ?? null) ? $transferRateMatrix[$code] : [];
        $legacyRate = is_numeric($legacyTransferRates[$code] ?? null) ? (float) $legacyTransferRates[$code] : 0;

        return [
            'code' => $code,
            'label' => Str::headline(str_replace('_', ' ', $code)),
            'local_adult_charge' => (float) ($matrix['local_adult_charge'] ?? 0),
            'local_child_charge' => (float) ($matrix['local_child_charge'] ?? 0),
            'foreign_adult_charge' => (float) ($matrix['foreign_adult_charge'] ?? $legacyRate),
            'foreign_child_charge' => (float) ($matrix['foreign_child_charge'] ?? 0),
            'base_charge_local' => (float) ($propertyDetails['transfer_base_local'] ?? 0),
            'base_charge_foreign' => (float) ($propertyDetails['transfer_base_foreign'] ?? 0),
            'adult_charge' => $legacyRate,
            'child_charge' => 0,
        ];
    })->values()->all();
    $guestResidency = strtolower(trim((string) ($payload['guest_residency'] ?? '')));
    if (!in_array($guestResidency, ['local_resident', 'foreign_national'], true)) {
        $guestResidency = ReservationPricingPolicy::isForeigner((string) ($payload['primary_nationality'] ?? ''), null)
            ? 'foreign_national'
            : 'local_resident';
    }

    $vendorTaxOverrides = [];
    if (isset($propertyDetails['vendor_tax_overrides']) && is_array($propertyDetails['vendor_tax_overrides'])) {
        $vendorTaxOverrides = $propertyDetails['vendor_tax_overrides'];
    }

    $roomCount = Schema::hasTable('vendor_property_room_categories')
        ? (int) DB::table('vendor_property_room_categories')->where('vendor_property_id', (int) $propertyRow->id)->count()
        : 0;

    $pricing = ReservationPricingPolicy::calculate([
        'listing_category' => 'accommodation',
        'subtotal_amount' => $roomSubtotal,
        'discount_percent' => $discountPercent,
        'adults' => $adults,
        'children' => $children,
        'infants' => (int) ($payload['infants'] ?? 0),
        'nights' => $nights,
        'room_count' => $roomCount,
        'primary_nationality' => (string) ($payload['primary_nationality'] ?? ''),
        'guest_residency' => $guestResidency,
        'transfer_option' => $transferOptionCode,
        'property_transfer_options' => $transferOptions,
        'transfer_charge_override' => $payload['transfer_charge'] ?? null,
        'vendor_tax_overrides' => $vendorTaxOverrides,
        // Vendor-managed selling prices are inclusive; tax/service/government charges are extracted backward for display.
        'prices_include_tax' => true,
    ]);

    $discountAmount = (float) ($pricing['discount_amount'] ?? 0);
    $taxAmount = (float) ($pricing['total_tax_amount'] ?? 0);
    $transferCharge = (float) ($pricing['transfer_charge_total'] ?? 0);
    $totalAmount = (float) ($pricing['invoice_total_amount'] ?? 0);

    $primaryFirstName = Str::title(trim((string) preg_replace('/\s+/', ' ', (string) $payload['primary_first_name'])));
    $primaryLastName = Str::title(trim((string) preg_replace('/\s+/', ' ', (string) $payload['primary_last_name'])));
    $primaryNationality = Str::title(trim((string) preg_replace('/\s+/', ' ', (string) $payload['primary_nationality'])));
    $primaryEmail = Str::lower(trim((string) $payload['primary_email']));
    $mobileRaw = trim((string) $payload['primary_mobile']);
    $primaryMobile = preg_replace('/[^0-9+]/', '', $mobileRaw) ?? $mobileRaw;
    $primaryMobile = preg_replace('/^\++/', '+', $primaryMobile) ?? $primaryMobile;
    $customerName = trim($primaryFirstName . ' ' . $primaryLastName);
    $customerEmail = $primaryEmail;
    $additionalGuestDetails = trim((string) ($payload['additional_guest_details'] ?? ''));

    $paymentQuote = CheckoutPaymentRouter::buildPaymentQuote([
        'primary_nationality' => $primaryNationality,
        'guest_residency' => $guestResidency,
        'reservation_currency' => strtoupper(trim((string) ($roomRow->currency ?? $propertyRow->currency ?? 'MVR'))),
        'amount' => $totalAmount,
    ]);

    provisionCustomerAccountFromBooking($customerEmail, $customerName);

    $reservationId = null;
    if (Schema::hasTable('vendor_reservations')) {
        $reservationId = (int) DB::table('vendor_reservations')->insertGetId([
            'vendor_user_id' => (int) ($propertyRow->vendor_user_id ?? 0),
            'vendor_property_id' => (int) $propertyRow->id,
            'vendor_service_id' => null,
            'customer_name' => $customerName !== '' ? $customerName : 'Guest Customer',
            'customer_email' => $customerEmail !== '' ? $customerEmail : 'guest@workation.local',
            'start_at' => $checkin->copy()->startOfDay(),
            'end_at' => $checkout->copy()->startOfDay(),
            'guests' => max(1, $guestCount),
            'total_amount' => $totalAmount,
            'currency' => strtoupper(trim((string) ($roomRow->currency ?? $propertyRow->currency ?? 'MVR'))),
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'notes' => json_encode([
                'room_id' => (int) $roomRow->id,
                'room_name' => (string) ($roomRow->name ?? 'Room'),
                'adults' => $adults,
                'children' => $children,
                'primary_first_name' => $primaryFirstName,
                'primary_last_name' => $primaryLastName,
                'primary_nationality' => $primaryNationality,
                'guest_residency' => $guestResidency,
                'primary_email' => $primaryEmail,
                'primary_mobile' => $primaryMobile,
                'additional_guest_details' => $additionalGuestDetails,
                'transfer_option' => (string) ($pricing['transfer_option'] ?? $transferOptionCode),
                'transfer_option_label' => (string) ($pricing['transfer_option_label'] ?? ''),
                'property_transfer_options' => $transferOptions,
                'transfer_charge' => $transferCharge,
                'transfer_charge_total' => $transferCharge,
                'transfer_local_adult_rate' => (float) ($pricing['transfer_local_adult_rate'] ?? 0),
                'transfer_local_child_rate' => (float) ($pricing['transfer_local_child_rate'] ?? 0),
                'transfer_foreign_adult_rate' => (float) ($pricing['transfer_foreign_adult_rate'] ?? 0),
                'transfer_foreign_child_rate' => (float) ($pricing['transfer_foreign_child_rate'] ?? 0),
                'transfer_applied_adult_rate' => (float) ($pricing['transfer_applied_adult_rate'] ?? 0),
                'transfer_applied_child_rate' => (float) ($pricing['transfer_applied_child_rate'] ?? 0),
                'nightly_rate' => $nightlyRate,
                'nights' => $nights,
                'room_subtotal' => $roomSubtotal,
                'subtotal_amount' => (float) ($pricing['subtotal_amount'] ?? $roomSubtotal),
                'discount_percent' => (float) ($pricing['discount_percent'] ?? $discountPercent),
                'discount_amount' => (float) ($pricing['discount_amount'] ?? $discountAmount),
                'discounted_subtotal' => (float) ($pricing['discounted_subtotal'] ?? max(0, $roomSubtotal - $discountAmount)),
                'service_charge_rate_percent' => (float) ($pricing['service_charge_rate_percent'] ?? 0),
                'service_charge_total' => (float) ($pricing['service_charge_total'] ?? 0),
                'green_tax_rate_per_person_per_night' => (float) ($pricing['green_tax_rate_per_person_per_night'] ?? 0),
                'green_tax_total' => (float) ($pricing['green_tax_total'] ?? 0),
                'tgst_rate_percent' => (float) ($pricing['tgst_rate_percent'] ?? 0),
                'tgst_total' => (float) ($pricing['tgst_total'] ?? 0),
                'gst_rate_percent' => (float) ($pricing['gst_rate_percent'] ?? 0),
                'gst_total' => (float) ($pricing['gst_total'] ?? 0),
                'total_tax_amount' => (float) ($pricing['total_tax_amount'] ?? $taxAmount),
                'tax_amount' => (float) ($pricing['total_tax_amount'] ?? $taxAmount),
                'tax_lines' => $pricing['tax_lines'] ?? [],
                'invoice_total_amount' => (float) ($pricing['invoice_total_amount'] ?? $totalAmount),
                'quote_source_currency' => (string) ($paymentQuote['source_currency'] ?? ''),
                'quote_source_amount' => (float) ($paymentQuote['source_amount'] ?? 0),
                'quote_payment_currency' => (string) ($paymentQuote['currency'] ?? ''),
                'quote_payment_amount' => (float) ($paymentQuote['amount'] ?? 0),
                'quote_gateway' => (string) ($paymentQuote['gateway'] ?? ''),
                'quote_provider' => (string) ($paymentQuote['provider'] ?? ''),
                'quote_gateway_label' => (string) ($paymentQuote['gateway_label'] ?? ''),
                'quote_provider_label' => (string) ($paymentQuote['provider_label'] ?? ''),
                'quote_fx_rate' => (float) ($paymentQuote['fx_rate'] ?? 1),
                'quote_fx_base_currency' => (string) ($paymentQuote['fx_base_currency'] ?? 'MVR'),
                'quote_quoted_at' => (string) ($paymentQuote['quoted_at'] ?? now()->toIso8601String()),
                'vendor_tax_overrides' => $vendorTaxOverrides,
                'policy_snapshot' => $pricing['policy_snapshot'] ?? [],
                'inclusives' => $propertyDetails['inclusives'] ?? [],
                'cancellation_policy' => (string) ($propertyDetails['cancellation_policy'] ?? ''),
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $checkoutUrl = '/booking/checkout'
        . ($reservationId ? ('/' . $reservationId) : '')
        . '?property_id=' . (int) $propertyRow->id
        . '&room_id=' . (int) $roomRow->id
        . '&checkin=' . urlencode((string) $payload['checkin'])
        . '&checkout=' . urlencode((string) $payload['checkout'])
        . '&adults=' . $adults
        . '&children=' . $children
        . '&primary_first_name=' . urlencode($primaryFirstName)
        . '&primary_last_name=' . urlencode($primaryLastName)
        . '&primary_nationality=' . urlencode($primaryNationality)
        . '&guest_residency=' . urlencode($guestResidency)
        . '&primary_email=' . urlencode($primaryEmail)
        . '&primary_mobile=' . urlencode($primaryMobile)
        . '&additional_guest_details=' . urlencode($additionalGuestDetails)
        . '&transfer_option=' . urlencode($transferOptionCode)
        . '&transfer_charge=' . urlencode((string) $transferCharge)
        . '&room_subtotal=' . urlencode((string) $roomSubtotal)
        . '&discount_amount=' . urlencode((string) $discountAmount)
        . '&tax_amount=' . urlencode((string) $taxAmount)
        . '&discount_percent=' . urlencode((string) $discountPercent)
        . '&tax_rate=' . urlencode((string) (($pricing['gst_rate_percent'] ?? 0) + ($pricing['tgst_rate_percent'] ?? 0)))
        . '&tax_lines=' . urlencode(json_encode($pricing['tax_lines'] ?? []))
        . '&total=' . urlencode((string) $totalAmount)
        . '&inclusives=' . urlencode(json_encode($propertyDetails['inclusives'] ?? []))
        . '&cancellation_policy=' . urlencode((string) ($propertyDetails['cancellation_policy'] ?? ''));

    return redirect($checkoutUrl);
});

Route::get('/category-booking/{category}/{property}', function (Request $request, string $category, int $property) {
    $categoryMap = [
        'accommodation' => ['label' => 'Accommodation', 'start_label' => 'Check-in Date', 'end_label' => 'Check-out Date'],
        'marine-transport' => ['label' => 'Marine Transport', 'start_label' => 'Travel Date', 'end_label' => 'Return Date'],
        'land-transport' => ['label' => 'Land Transport', 'start_label' => 'Travel Date', 'end_label' => 'Return Date'],
        'excursion' => ['label' => 'Excursion', 'start_label' => 'Excursion Date', 'end_label' => 'Return Date'],
        'remote_workspace' => ['label' => 'Remote Workspace', 'start_label' => 'Start Date', 'end_label' => 'End Date'],
        'conference_room' => ['label' => 'Conference & Meeting Spaces', 'start_label' => 'Event Date', 'end_label' => 'Event End Date'],
        'resort_day_visit' => ['label' => 'Resort Day Visit', 'start_label' => 'Visit Date', 'end_label' => 'Return Date'],
        'restaurant' => ['label' => 'Restaurant', 'start_label' => 'Reservation Date & Time', 'end_label' => 'Expected Departure Date & Time'],
        'vehicle_rental' => ['label' => 'Vehicle Rental', 'start_label' => 'Pickup Date', 'end_label' => 'Return Date'],
    ];

    $categoryFieldMap = [
        'accommodation' => [
            ['key' => 'rooms', 'label' => 'Rooms', 'type' => 'number', 'required' => true, 'min' => 1],
        ],
        'marine-transport' => [
            ['key' => 'origin_point', 'label' => 'From', 'type' => 'text', 'required' => true],
            ['key' => 'destination_point', 'label' => 'To', 'type' => 'text', 'required' => true],
        ],
        'land-transport' => [
            ['key' => 'origin_point', 'label' => 'From', 'type' => 'text', 'required' => true],
            ['key' => 'destination_point', 'label' => 'To', 'type' => 'text', 'required' => true],
        ],
        'excursion' => [
            // Activity type is implied by selected listing on this page.
        ],
        'remote_workspace' => [
            ['key' => 'workspace_type', 'label' => 'Workspace Type', 'type' => 'text', 'required' => true],
        ],
        'conference_room' => [
            ['key' => 'event_type', 'label' => 'Event Type', 'type' => 'select', 'required' => true, 'options' => ['meeting' => 'Meeting', 'training' => 'Training', 'seminar' => 'Seminar', 'conference' => 'Conference', 'workshop' => 'Workshop']],
            ['key' => 'expected_capacity', 'label' => 'Expected Attendees', 'type' => 'number', 'required' => true, 'min' => 1],
        ],
        'resort_day_visit' => [
            ['key' => 'visit_package', 'label' => 'Visit Package', 'type' => 'text', 'required' => true],
        ],
        'restaurant' => [],
        'vehicle_rental' => [
            ['key' => 'vehicle_type', 'label' => 'Vehicle Type', 'type' => 'text', 'required' => true],
            ['key' => 'pickup_location', 'label' => 'Pickup Location', 'type' => 'text', 'required' => true],
            ['key' => 'dropoff_location', 'label' => 'Drop-off Location', 'type' => 'text', 'required' => true],
        ],
    ];

    $categoryKey = strtolower(trim($category));
    if (!array_key_exists($categoryKey, $categoryMap)) {
        abort(404);
    }

    // Map URL slug to DB value (hyphens -> underscores)
    $dbCategoryKey = str_replace('-', '_', $categoryKey);

    $categoryFields = collect($categoryFieldMap[$categoryKey] ?? [])->values();

    $propertyRow = VendorPropertyCompatibilityReader::loadPropertyById($property);
    if (!$propertyRow) {
        abort(404);
    }

    $listingStatus = strtolower(trim((string) ($propertyRow->status ?? 'inactive')));
    if ($listingStatus !== 'active') {
        abort(404);
    }

    $listingCategory = strtolower(trim(str_replace('-', '_', (string) ($propertyRow->listing_category ?? ''))));
    if ($listingCategory !== '' && $listingCategory !== $dbCategoryKey) {
        abort(404);
    }

    $listingDetails = json_decode((string) ($propertyRow->listing_details ?? ''), true);
    if (!is_array($listingDetails)) {
        $listingDetails = [];
    }

    $transferRateMatrix = is_array($listingDetails['transfer_rate_matrix'] ?? null)
        ? $listingDetails['transfer_rate_matrix']
        : [];
    $legacyTransferRates = is_array($listingDetails['transfer_rates'] ?? null)
        ? $listingDetails['transfer_rates']
        : [];
    $transferOptions = collect($listingDetails['transfer_options'] ?? [])->map(function ($option) use ($transferRateMatrix, $legacyTransferRates, $listingDetails) {
        if (is_array($option)) {
            $code = strtolower(trim((string) ($option['code'] ?? '')));
            return $option + ['code' => $code];
        }

        $code = strtolower(trim((string) $option));
        if ($code === '') {
            return null;
        }

        $matrix = is_array($transferRateMatrix[$code] ?? null) ? $transferRateMatrix[$code] : [];
        $legacyRate = is_numeric($legacyTransferRates[$code] ?? null) ? (float) $legacyTransferRates[$code] : 0;

        return [
            'code' => $code,
            'label' => Str::headline(str_replace('_', ' ', $code)),
            'local_adult_charge' => (float) ($matrix['local_adult_charge'] ?? 0),
            'local_child_charge' => (float) ($matrix['local_child_charge'] ?? 0),
            'foreign_adult_charge' => (float) ($matrix['foreign_adult_charge'] ?? $legacyRate),
            'foreign_child_charge' => (float) ($matrix['foreign_child_charge'] ?? 0),
            'base_charge_local' => (float) ($listingDetails['transfer_base_local'] ?? 0),
            'base_charge_foreign' => (float) ($listingDetails['transfer_base_foreign'] ?? 0),
            'adult_charge' => $legacyRate,
            'child_charge' => 0,
        ];
    })->filter(static fn ($option) => is_array($option) && trim((string) ($option['code'] ?? '')) !== '')->values();

    $propertyMedia = collect();
    if (Schema::hasTable('vendor_listing_media')) {
        $propertyMedia = DB::table('vendor_listing_media')
            ->where('entity_type', 'property')
            ->where('entity_id', (int) ($propertyRow->id ?? 0))
            ->orderByDesc('is_primary')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    $extractStringList = static function ($value): array {
        if (is_array($value)) {
            return collect($value)
                ->map(static fn ($item) => trim((string) $item))
                ->filter(static fn ($item) => $item !== '')
                ->values()
                ->all();
        }

        if (!is_string($value)) {
            return [];
        }

        return collect(preg_split('/[,\n]+/', $value) ?: [])
            ->map(static fn ($item) => trim((string) $item))
            ->filter(static fn ($item) => $item !== '')
            ->values()
            ->all();
    };

    $firstNonEmptyList = static function (array $sources, callable $extractor): array {
        foreach ($sources as $source) {
            $items = $extractor($source);
            if (!empty($items)) {
                return $items;
            }
        }

        return [];
    };

    $highlights = $firstNonEmptyList([
        $listingDetails['highlights'] ?? null,
        $listingDetails['key_highlights'] ?? null,
        $listingDetails['features'] ?? null,
    ], $extractStringList);

    if (empty($highlights)) {
        $highlights = match ($categoryKey) {
            'transport' => ['Fast booking confirmation', 'Flexible route support', 'Local operator coordination'],
            'excursion' => ['Curated local experiences', 'Safety-first operators', 'Flexible trip planning'],
            'remote_workspace' => ['Work-friendly environment', 'Reliable utility setup', 'Quiet productivity zones'],
            'resort_day_visit' => ['Day access convenience', 'Resort facilities included', 'Family-friendly options'],
            'restaurant' => ['Island-inspired dining', 'Reservation support', 'Group-friendly seating'],
            'vehicle_rental' => ['Clean and ready vehicles', 'Flexible pickup points', 'Simple booking flow'],
            default => ['Guest-focused service', 'Verified local operator', 'Easy booking process'],
        };
    }

    $servicesAndAmenities = $firstNonEmptyList([
        $listingDetails['amenities'] ?? null,
        $listingDetails['facilities'] ?? null,
        $listingDetails['services'] ?? null,
        $listingDetails['service_features'] ?? null,
    ], $extractStringList);

    $restaurantMenuItems = $firstNonEmptyList([
        $listingDetails['menu_items'] ?? null,
        $listingDetails['menu'] ?? null,
        $listingDetails['restaurant_menu'] ?? null,
        $listingDetails['dishes'] ?? null,
    ], $extractStringList);

    $descriptionText = trim((string) (
        $listingDetails['description']
        ?? $listingDetails['overview']
        ?? $propertyRow->description
        ?? ''
    ));

    if ($descriptionText === '') {
        $descriptionText = 'This listing is managed by a verified local operator and includes practical service details for straightforward planning. Availability, guest preferences, and service notes can be finalized during checkout.';
    }

    $vendorPolicy = [
        'opening_hours' => trim((string) (
            $listingDetails['opening_hours']
            ?? $listingDetails['operating_hours']
            ?? $listingDetails['business_hours']
            ?? ''
        )),
        'closing_hours' => trim((string) (
            $listingDetails['closing_hours']
            ?? $listingDetails['close_time']
            ?? $listingDetails['closing_time']
            ?? ''
        )),
        'cancellation_policy' => trim((string) (
            $listingDetails['cancellation_policy']
            ?? $listingDetails['cancellation_terms']
            ?? $listingDetails['cancellation']
            ?? ''
        )),
        'other_rules' => $extractStringList(
            $listingDetails['rules']
            ?? $listingDetails['house_rules']
            ?? $listingDetails['policies']
            ?? $listingDetails['terms']
            ?? $listingDetails['additional_rules']
            ?? null
        ),
    ];

    $taxRate = (float) ($listingDetails['tax_rate'] ?? 16);
    $discountPercent = (float) ($listingDetails['promotion_discount_percent'] ?? 0);

    $excursionBasePrice = (float) ($propertyRow->base_price ?? 0);
    $excursionAdultPrice = (float) ($listingDetails['adult_price'] ?? $listingDetails['price_per_adult'] ?? $excursionBasePrice);
    $excursionChildPrice = (float) ($listingDetails['child_price'] ?? $listingDetails['price_per_child'] ?? round($excursionAdultPrice * 0.5, 2));
    $excursionInfantPrice = (float) ($listingDetails['infant_price'] ?? $listingDetails['price_per_infant'] ?? 0);

    $sessionGuestName = trim((string) session('portal_customer_user', ''));
    $nameParts = preg_split('/\s+/', $sessionGuestName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $prefillFirstName = (string) ($nameParts[0] ?? '');
    $prefillLastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';

    $todayDate = now()->toDateString();
    $unavailableDates = [
        'blocked' => [],
        'reserved' => [],
    ];

    if (Schema::hasTable('vendor_availability_slots')) {
        $slotQuery = DB::table('vendor_availability_slots')
            ->where('vendor_property_id', (int) ($propertyRow->id ?? 0))
            ->whereDate('slot_date', '>=', $todayDate);

        if (Schema::hasColumn('vendor_availability_slots', 'listing_category')) {
            $slotQuery->where(function ($query) use ($dbCategoryKey) {
                $query->where('listing_category', $dbCategoryKey)
                    ->orWhereNull('listing_category')
                    ->orWhere('listing_category', '');
            });
        }

        $slotRows = $slotQuery->limit(1200)->get(['slot_date', 'inventory', 'reserved_count', 'is_closed']);
        foreach ($slotRows as $slotRow) {
            $slotDate = trim((string) ($slotRow->slot_date ?? ''));
            if ($slotDate === '') {
                continue;
            }

            if ((bool) ($slotRow->is_closed ?? false)) {
                $unavailableDates['blocked'][$slotDate] = true;
                continue;
            }

            $inventory = max(0, (int) ($slotRow->inventory ?? 0));
            $reservedCount = max(0, (int) ($slotRow->reserved_count ?? 0));
            if ($inventory > 0 && $reservedCount >= $inventory) {
                $unavailableDates['reserved'][$slotDate] = true;
            }
        }
    }

    if (Schema::hasTable('vendor_reservations')) {
        $reservationQuery = DB::table('vendor_reservations')
            ->where('vendor_property_id', (int) ($propertyRow->id ?? 0))
            ->whereNotIn('status', ['cancelled', 'rejected', 'expired', 'failed'])
            ->whereDate('end_at', '>=', $todayDate);

        if (Schema::hasColumn('vendor_reservations', 'listing_category')) {
            $reservationQuery->where(function ($query) use ($dbCategoryKey) {
                $query->where('listing_category', $dbCategoryKey)
                    ->orWhereNull('listing_category')
                    ->orWhere('listing_category', '');
            });
        }

        $reservationRows = $reservationQuery->limit(500)->get(['start_at', 'end_at']);
        foreach ($reservationRows as $reservationRow) {
            try {
                $startDay = Carbon::parse((string) ($reservationRow->start_at ?? ''))->startOfDay();
                $endExclusive = Carbon::parse((string) ($reservationRow->end_at ?? ''))->startOfDay();
            } catch (\Throwable $ignored) {
                continue;
            }

            if ($endExclusive->lessThanOrEqualTo($startDay)) {
                $endExclusive = $startDay->copy()->addDay();
            }

            foreach (workationDateSeries($startDay, $endExclusive) as $slotDate) {
                if ($slotDate >= $todayDate) {
                    $unavailableDates['reserved'][$slotDate] = true;
                }
            }
        }
    }

    $unavailableDates['blocked'] = array_values(array_keys($unavailableDates['blocked']));
    $unavailableDates['reserved'] = array_values(array_keys($unavailableDates['reserved']));

    return view('category-booking', [
        'categoryKey' => $categoryKey,
        'categoryLabel' => (string) ($categoryMap[$categoryKey]['label'] ?? 'Category'),
        'categoryFields' => $categoryFields,
        'dateLabels' => [
            'start' => (string) ($categoryMap[$categoryKey]['start_label'] ?? 'Service Start Date'),
            'end' => (string) ($categoryMap[$categoryKey]['end_label'] ?? 'Service End Date'),
        ],
        'property' => $propertyRow,
        'propertyMedia' => $propertyMedia,
        'highlights' => $highlights,
        'servicesAndAmenities' => $servicesAndAmenities,
        'restaurantMenuItems' => $restaurantMenuItems,
        'descriptionText' => $descriptionText,
        'vendorPolicy' => $vendorPolicy,
        'pricingConfig' => [
            'tax_rate' => $taxRate,
            'discount_percent' => $discountPercent,
            'adult_price' => $excursionAdultPrice,
            'child_price' => $excursionChildPrice,
            'infant_price' => $excursionInfantPrice,
        ],
        'prefill' => [
            'service_start_date' => trim((string) $request->query('service_start_date', '')),
            'service_end_date' => trim((string) $request->query('service_end_date', '')),
            'adults' => max(1, (int) $request->query('adults', 2)),
            'children' => max(0, (int) $request->query('children', 0)),
            'infants' => max(0, (int) $request->query('infants', 0)),
            'primary_first_name' => $prefillFirstName,
            'primary_last_name' => $prefillLastName,
            'primary_nationality' => '',
            'guest_residency' => trim((string) $request->query('guest_residency', 'foreign_national')),
            'transfer_option' => trim((string) $request->query('transfer_option', '')),
            'primary_email' => trim((string) session('portal_customer_email', '')),
            'primary_mobile' => '',
            'rooms' => max(1, (int) $request->query('rooms', 1)),
            'origin_point' => trim((string) $request->query('origin_point', '')),
            'destination_point' => trim((string) $request->query('destination_point', '')),
            'excursion_type' => trim((string) $request->query('excursion_type', '')),
            'workspace_type' => trim((string) $request->query('workspace_type', '')),
            'visit_package' => trim((string) $request->query('visit_package', '')),
            'meal_plan' => trim((string) $request->query('meal_plan', '')),
            'vehicle_type' => trim((string) $request->query('vehicle_type', '')),
            'pickup_location' => trim((string) $request->query('pickup_location', '')),
            'dropoff_location' => trim((string) $request->query('dropoff_location', '')),
            'service_notes' => trim((string) $request->query('service_notes', '')),
        ],
        'todayDate' => $todayDate,
        'unavailableDates' => $unavailableDates,
        'transferOptions' => $transferOptions,
    ]);
});

Route::post('/booking/reserve-category', function (Request $request) {
    $categoryMap = [
        'accommodation' => ['label' => 'Accommodation', 'start_label' => 'Check-in', 'end_label' => 'Check-out'],
        'marine-transport' => ['label' => 'Marine Transport', 'start_label' => 'Travel Date', 'end_label' => 'Return Date'],
        'land-transport' => ['label' => 'Land Transport', 'start_label' => 'Travel Date', 'end_label' => 'Return Date'],
        'excursion' => ['label' => 'Excursion', 'start_label' => 'Excursion Date', 'end_label' => 'Return Date'],
        'remote_workspace' => ['label' => 'Remote Workspace', 'start_label' => 'Start Date', 'end_label' => 'End Date'],
        'resort_day_visit' => ['label' => 'Resort Day Visit', 'start_label' => 'Visit Date', 'end_label' => 'Return Date'],
        'restaurant' => ['label' => 'Restaurant', 'start_label' => 'Reservation Date & Time', 'end_label' => 'Expected Departure Date & Time'],
        'vehicle_rental' => ['label' => 'Vehicle Rental', 'start_label' => 'Pickup Date', 'end_label' => 'Return Date'],
    ];

    $categoryFieldRules = [
        'accommodation' => [
            'rooms' => ['required', 'integer', 'min:1', 'max:20'],
        ],
        'marine-transport' => [
            'origin_point' => ['required', 'string', 'max:120'],
            'destination_point' => ['required', 'string', 'max:120'],
        ],
        'land-transport' => [
            'origin_point' => ['required', 'string', 'max:120'],
            'destination_point' => ['required', 'string', 'max:120'],
        ],
        'excursion' => [
            // No extra category fields needed; selected listing already defines the activity.
        ],
        'remote_workspace' => [
            'workspace_type' => ['required', 'string', 'max:120'],
        ],
        'conference_room' => [
            'event_type' => ['required', 'string', 'in:meeting,training,seminar,conference,workshop'],
            'expected_capacity' => ['required', 'integer', 'min:1', 'max:5000'],
            'required_facilities' => ['nullable', 'array'],
            'required_facilities.*' => ['string', 'max:60'],
        ],
        'resort_day_visit' => [
            'visit_package' => ['required', 'string', 'max:120'],
        ],
        'restaurant' => [],
        'vehicle_rental' => [
            'vehicle_type' => ['required', 'string', 'max:120'],
            'pickup_location' => ['required', 'string', 'max:120'],
            'dropoff_location' => ['required', 'string', 'max:120'],
        ],
    ];

    $categoryFieldLabels = [
        'rooms' => 'rooms',
        'origin_point' => 'from location',
        'destination_point' => 'to location',
        'excursion_type' => 'excursion type',
        'workspace_type' => 'workspace type',
        'event_type' => 'event type',
        'expected_capacity' => 'expected capacity',
        'required_facilities' => 'required facilities',
        'visit_package' => 'visit package',
        'meal_plan' => 'meal plan',
        'vehicle_type' => 'vehicle type',
        'pickup_location' => 'pickup location',
        'dropoff_location' => 'drop-off location',
    ];

    $requestedCategoryKey = strtolower(trim((string) $request->input('category_key', '')));
    $requestedCategoryMeta = $categoryMap[$requestedCategoryKey] ?? null;
    $startDateLabel = (string) ($requestedCategoryMeta['start_label'] ?? 'Service start date');
    $endDateLabel = (string) ($requestedCategoryMeta['end_label'] ?? 'Service end date');

    $baseRules = [
        'category_key' => ['required', 'string', 'in:' . implode(',', array_keys($categoryMap))],
        'property_id' => ['required', 'integer', 'min:1'],
        'service_start_date' => ['required', 'date', 'after_or_equal:today'],
        'service_end_date' => ['nullable', 'date', 'after_or_equal:service_start_date'],
        'adults' => ['required', 'integer', 'min:1', 'max:20'],
        'children' => ['nullable', 'integer', 'min:0', 'max:20'],
        'infants' => ['nullable', 'integer', 'min:0', 'max:20'],
        'primary_first_name' => ['required', 'string', 'max:80'],
        'primary_last_name' => ['required', 'string', 'max:80'],
        'primary_nationality' => ['required', 'string', 'max:120'],
        'guest_residency' => ['nullable', Rule::in(['local_resident', 'foreign_national'])],
        'primary_email' => ['required', 'email', 'max:190'],
        'primary_mobile' => ['required', 'string', 'max:40', 'regex:/^\+?[0-9][0-9\s\-()]{5,39}$/'],
        'transfer_option' => ['nullable', 'string', 'max:80'],
        'transfer_charge' => ['nullable', 'numeric', 'min:0'],
        'payment_timing' => ['nullable', 'string', 'max:40'],
        'payment_method' => ['nullable', 'string', 'max:60'],
        'additional_guest_details' => ['nullable', 'string', 'max:4000'],
        'service_notes' => ['nullable', 'string', 'max:4000'],
    ];

    $payload = $request->validate(array_merge($baseRules, $categoryFieldRules[$requestedCategoryKey] ?? []), [
        'primary_first_name.required' => 'Primary guest first name is required.',
        'primary_last_name.required' => 'Primary guest last name is required.',
        'primary_nationality.required' => 'Primary guest nationality is required.',
        'primary_email.required' => 'Primary guest email is required.',
        'primary_email.email' => 'Please enter a valid email address for the primary guest.',
        'primary_mobile.required' => 'Primary guest mobile is required.',
        'primary_mobile.regex' => 'Please enter a valid primary guest mobile number.',
        'service_start_date.required' => $startDateLabel . ' is required.',
        'service_start_date.after_or_equal' => $startDateLabel . ' cannot be in the past.',
        'service_end_date.after_or_equal' => $endDateLabel . ' must be after or equal to ' . strtolower($startDateLabel) . '.',
    ], array_merge([
        'primary_first_name' => 'primary guest first name',
        'primary_last_name' => 'primary guest last name',
        'primary_nationality' => 'primary guest nationality',
        'primary_email' => 'primary guest email',
        'primary_mobile' => 'primary guest mobile',
    ], $categoryFieldLabels));

    $categoryKey = strtolower(trim((string) $payload['category_key']));
    // Normalise hyphenated keys (from URL) to underscored DB values
    $dbCategoryKey = str_replace('-', '_', $categoryKey);
    $propertyRow = VendorPropertyCompatibilityReader::loadPropertyById((int) $payload['property_id']);
    if (!$propertyRow) {
        abort(404);
    }

    $listingStatus = strtolower(trim((string) ($propertyRow->status ?? 'inactive')));
    if ($listingStatus !== 'active') {
        abort(404);
    }

    $listingCategory = strtolower(trim(str_replace('-', '_', (string) ($propertyRow->listing_category ?? ''))));
    if ($listingCategory !== '' && $listingCategory !== $dbCategoryKey) {
        abort(404);
    }

    // Listing-level publish gate: only approved listings can accept bookings.
    if (isset($propertyRow->listing_moderation_status)) {
        $listingModerationStatus = strtolower(trim((string) ($propertyRow->listing_moderation_status ?? 'draft')));
        if ($listingModerationStatus !== 'approved') {
            return back()->withErrors(['booking' => 'This listing is not yet available for bookings. It is currently under review or pending approval.']);
        }
    }

    $listingDetails = json_decode((string) ($propertyRow->listing_details ?? ''), true);
    if (!is_array($listingDetails)) {
        $listingDetails = [];
    }

    $transferOptionCode = strtolower(trim((string) ($payload['transfer_option'] ?? '')));
    $transferRateMatrix = is_array($listingDetails['transfer_rate_matrix'] ?? null)
        ? $listingDetails['transfer_rate_matrix']
        : [];
    $legacyTransferRates = is_array($listingDetails['transfer_rates'] ?? null)
        ? $listingDetails['transfer_rates']
        : [];
    $transferOptions = collect($listingDetails['transfer_options'] ?? [])->map(function ($option) use ($transferRateMatrix, $legacyTransferRates, $listingDetails) {
        if (is_array($option)) {
            $code = strtolower(trim((string) ($option['code'] ?? '')));
            return $option + ['code' => $code];
        }

        $code = strtolower(trim((string) $option));
        if ($code === '') {
            return null;
        }

        $matrix = is_array($transferRateMatrix[$code] ?? null) ? $transferRateMatrix[$code] : [];
        $legacyRate = is_numeric($legacyTransferRates[$code] ?? null) ? (float) $legacyTransferRates[$code] : 0;

        return [
            'code' => $code,
            'label' => Str::headline(str_replace('_', ' ', $code)),
            'local_adult_charge' => (float) ($matrix['local_adult_charge'] ?? 0),
            'local_child_charge' => (float) ($matrix['local_child_charge'] ?? 0),
            'foreign_adult_charge' => (float) ($matrix['foreign_adult_charge'] ?? $legacyRate),
            'foreign_child_charge' => (float) ($matrix['foreign_child_charge'] ?? 0),
            'base_charge_local' => (float) ($listingDetails['transfer_base_local'] ?? 0),
            'base_charge_foreign' => (float) ($listingDetails['transfer_base_foreign'] ?? 0),
            'adult_charge' => $legacyRate,
            'child_charge' => 0,
        ];
    })->filter(static fn ($option) => is_array($option) && trim((string) ($option['code'] ?? '')) !== '')->values()->all();

    $serviceStart = Carbon::parse((string) $payload['service_start_date'])->startOfDay();

    $serviceEndInput = trim((string) ($payload['service_end_date'] ?? ''));
    $serviceEnd = $serviceEndInput !== ''
        ? Carbon::parse($serviceEndInput)->startOfDay()
        : $serviceStart->copy();
    $serviceEndExclusive = $serviceEnd->copy()->addDay()->startOfDay();
    $adults = (int) $payload['adults'];
    $children = (int) ($payload['children'] ?? 0);
    $infants = (int) ($payload['infants'] ?? 0);

    $routeName = '';
    if (in_array($categoryKey, ['marine-transport', 'land-transport'], true)) {
        $origin = trim((string) ($payload['origin_point'] ?? ''));
        $destination = trim((string) ($payload['destination_point'] ?? ''));
        if ($origin !== '' || $destination !== '') {
            $routeName = trim($origin . ' -> ' . $destination);
        }
    }

    $unitsRequested = match ($categoryKey) {
        'accommodation' => max(1, (int) ($payload['rooms'] ?? 1)),
        'conference_room' => max(1, (int) ($payload['expected_capacity'] ?? ($adults + $children))),
        'marine-transport', 'land-transport', 'excursion', 'resort_day_visit', 'restaurant' => max(1, $adults + $children),
        default => 1,
    };

    $slotAvailability = workationSlotAvailabilityCheck(
        (int) ($propertyRow->vendor_user_id ?? 0),
        (int) ($propertyRow->id ?? 0),
        $serviceStart,
        $serviceEndExclusive,
        $unitsRequested,
        null,
        null,
        str_replace('-', '_', $categoryKey),
        $routeName !== '' ? $routeName : null
    );

    if (($slotAvailability['ok'] ?? true) !== true) {
        $slotDate = (string) ($slotAvailability['date'] ?? 'selected dates');
        $slotReason = (string) ($slotAvailability['reason'] ?? '');
        if ($slotReason === 'blocked') {
            return back()->withErrors(['booking' => 'This listing is unavailable on ' . $slotDate . ' (blocked by the operator: sold out/scratched/unavailable). Please choose different dates.'])->withInput();
        }

        return back()->withErrors(['booking' => 'This listing is sold out on ' . $slotDate . '. Please choose different dates.'])->withInput();
    }

    $repeatableCategories = ['excursion', 'resort_day_visit'];
    if (!in_array($categoryKey, $repeatableCategories, true)) {
        $overlapCount = workationOverlappingReservationCount(
            (int) ($propertyRow->vendor_user_id ?? 0),
            (int) ($propertyRow->id ?? 0),
            $serviceStart,
            $serviceEndExclusive,
            null,
            null
        );

        if ($overlapCount > 0) {
            return back()->withErrors(['booking' => 'This listing is already reserved for the selected dates. Please choose another date range.'])->withInput();
        }
    }

    $units = max(1, $serviceStart->diffInDays($serviceEnd) + 1);
    $guestCount = $adults + $children;

    $basePrice = (float) ($propertyRow->base_price ?? 0);
    $serviceSubtotal = $basePrice * $units;
    $discountPercent = (float) ($listingDetails['promotion_discount_percent'] ?? 0);
    $guestResidency = strtolower(trim((string) ($payload['guest_residency'] ?? '')));
    if (!in_array($guestResidency, ['local_resident', 'foreign_national'], true)) {
        $guestResidency = ReservationPricingPolicy::isForeigner((string) ($payload['primary_nationality'] ?? ''), null)
            ? 'foreign_national'
            : 'local_resident';
    }

    $vendorTaxOverrides = [];
    if (isset($listingDetails['vendor_tax_overrides']) && is_array($listingDetails['vendor_tax_overrides'])) {
        $vendorTaxOverrides = $listingDetails['vendor_tax_overrides'];
    }

    $roomCount = Schema::hasTable('vendor_property_room_categories')
        ? (int) DB::table('vendor_property_room_categories')->where('vendor_property_id', (int) $propertyRow->id)->count()
        : 0;

    $pricing = ReservationPricingPolicy::calculate([
        'listing_category' => $categoryKey,
        'subtotal_amount' => $serviceSubtotal,
        'discount_percent' => $discountPercent,
        'adults' => $adults,
        'children' => $children,
        'infants' => $infants,
        'nights' => $units,
        'room_count' => $roomCount,
        'primary_nationality' => (string) ($payload['primary_nationality'] ?? ''),
        'guest_residency' => $guestResidency,
        'transfer_option' => $transferOptionCode,
        'property_transfer_options' => $transferOptions,
        'transfer_charge_override' => $payload['transfer_charge'] ?? null,
        'vendor_tax_overrides' => $vendorTaxOverrides,
        // Vendor-managed selling prices are inclusive; tax/service/government charges are extracted backward for display.
        'prices_include_tax' => true,
    ]);

    $discountAmount = (float) ($pricing['discount_amount'] ?? 0);
    $taxAmount = (float) ($pricing['total_tax_amount'] ?? 0);
    $transferCharge = (float) ($pricing['transfer_charge_total'] ?? 0);
    $totalAmount = (float) ($pricing['invoice_total_amount'] ?? 0);

    $sessionGuestName = trim((string) session('portal_customer_user', ''));
    $sessionNameParts = preg_split('/\s+/', $sessionGuestName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $sessionFirstName = (string) ($sessionNameParts[0] ?? 'Guest');
    $sessionLastName = count($sessionNameParts) > 1 ? implode(' ', array_slice($sessionNameParts, 1)) : 'Customer';
    $sessionEmail = trim((string) session('portal_customer_email', ''));

    $primaryFirstNameRaw = trim((string) ($payload['primary_first_name'] ?? ''));
    $primaryLastNameRaw = trim((string) ($payload['primary_last_name'] ?? ''));
    $primaryNationalityRaw = trim((string) ($payload['primary_nationality'] ?? ''));
    $primaryEmailRaw = trim((string) ($payload['primary_email'] ?? ''));
    $mobileRaw = trim((string) ($payload['primary_mobile'] ?? ''));

    if ($categoryKey === 'excursion') {
        if ($primaryFirstNameRaw === '') {
            $primaryFirstNameRaw = $sessionFirstName;
        }
        if ($primaryLastNameRaw === '') {
            $primaryLastNameRaw = $sessionLastName;
        }
        if ($primaryNationalityRaw === '') {
            $primaryNationalityRaw = 'Not specified';
        }
        if ($primaryEmailRaw === '') {
            $primaryEmailRaw = $sessionEmail !== '' ? $sessionEmail : 'guest@workation.local';
        }
    }

    $primaryFirstName = Str::title(trim((string) preg_replace('/\s+/', ' ', $primaryFirstNameRaw)));
    $primaryLastName = Str::title(trim((string) preg_replace('/\s+/', ' ', $primaryLastNameRaw)));
    $primaryNationality = Str::title(trim((string) preg_replace('/\s+/', ' ', $primaryNationalityRaw)));
    $primaryEmail = Str::lower(trim((string) $primaryEmailRaw));
    $primaryMobile = preg_replace('/[^0-9+]/', '', $mobileRaw) ?? $mobileRaw;
    $primaryMobile = preg_replace('/^\++/', '+', $primaryMobile) ?? $primaryMobile;
    $additionalGuestDetails = trim((string) ($payload['additional_guest_details'] ?? ''));
    $serviceNotes = trim((string) ($payload['service_notes'] ?? ''));

    $customerName = trim($primaryFirstName . ' ' . $primaryLastName);
    $customerEmail = $primaryEmail;
    $categoryLabel = (string) ($categoryMap[$categoryKey]['label'] ?? 'Category');

    $paymentQuote = CheckoutPaymentRouter::buildPaymentQuote([
        'primary_nationality' => $primaryNationality,
        'guest_residency' => $guestResidency,
        'reservation_currency' => strtoupper(trim((string) ($propertyRow->currency ?? 'MVR'))),
        'amount' => $totalAmount,
    ]);

    provisionCustomerAccountFromBooking($customerEmail, $customerName);

    $categoryDetails = [];
    foreach (array_keys($categoryFieldRules[$categoryKey] ?? []) as $fieldKey) {
        $value = $payload[$fieldKey] ?? null;
        if (is_string($value)) {
            $value = trim($value);
        }
        if ($value !== null && $value !== '') {
            $categoryDetails[$fieldKey] = $value;
        }
    }

    $reservationId = null;
    if (Schema::hasTable('vendor_reservations')) {
        $reservationId = (int) DB::table('vendor_reservations')->insertGetId([
            'vendor_user_id' => (int) ($propertyRow->vendor_user_id ?? 0),
            'vendor_property_id' => (int) $propertyRow->id,
            'vendor_service_id' => null,
            'customer_name' => $customerName !== '' ? $customerName : 'Guest Customer',
            'customer_email' => $customerEmail !== '' ? $customerEmail : 'guest@workation.local',
            'start_at' => $serviceStart,
            'end_at' => $serviceEnd,
            'guests' => max(1, $guestCount),
            'total_amount' => $totalAmount,
            'currency' => strtoupper(trim((string) ($propertyRow->currency ?? 'MVR'))),
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'notes' => json_encode([
                'category_key' => $categoryKey,
                'units_requested' => $unitsRequested,
                'category_label' => $categoryLabel,
                'service_label' => $categoryLabel,
                'service_start_date' => $serviceStart->toDateString(),
                'service_end_date' => $serviceEnd->toDateString(),
                'adults' => $adults,
                'children' => $children,
                'infants' => $infants,
                'primary_first_name' => $primaryFirstName,
                'primary_last_name' => $primaryLastName,
                'primary_nationality' => $primaryNationality,
                'guest_residency' => $guestResidency,
                'primary_email' => $primaryEmail,
                'primary_mobile' => $primaryMobile,
                'additional_guest_details' => $additionalGuestDetails,
                'service_notes' => $serviceNotes,
                'category_details' => $categoryDetails,
                'room_subtotal' => $serviceSubtotal,
                'subtotal_amount' => (float) ($pricing['subtotal_amount'] ?? $serviceSubtotal),
                'discount_percent' => (float) ($pricing['discount_percent'] ?? $discountPercent),
                'discount_amount' => (float) ($pricing['discount_amount'] ?? $discountAmount),
                'discounted_subtotal' => (float) ($pricing['discounted_subtotal'] ?? max(0, $serviceSubtotal - $discountAmount)),
                'service_charge_rate_percent' => (float) ($pricing['service_charge_rate_percent'] ?? 0),
                'service_charge_total' => (float) ($pricing['service_charge_total'] ?? 0),
                'green_tax_rate_per_person_per_night' => (float) ($pricing['green_tax_rate_per_person_per_night'] ?? 0),
                'green_tax_total' => (float) ($pricing['green_tax_total'] ?? 0),
                'tgst_rate_percent' => (float) ($pricing['tgst_rate_percent'] ?? 0),
                'tgst_total' => (float) ($pricing['tgst_total'] ?? 0),
                'gst_rate_percent' => (float) ($pricing['gst_rate_percent'] ?? 0),
                'gst_total' => (float) ($pricing['gst_total'] ?? 0),
                'total_tax_amount' => (float) ($pricing['total_tax_amount'] ?? $taxAmount),
                'tax_amount' => (float) ($pricing['total_tax_amount'] ?? $taxAmount),
                'tax_lines' => $pricing['tax_lines'] ?? [],
                'transfer_option' => $transferOptionCode,
                'transfer_option_label' => (string) ($pricing['transfer_option_label'] ?? ''),
                'property_transfer_options' => $transferOptions,
                'transfer_charge' => $transferCharge,
                'transfer_charge_total' => $transferCharge,
                'transfer_local_adult_rate' => (float) ($pricing['transfer_local_adult_rate'] ?? 0),
                'transfer_local_child_rate' => (float) ($pricing['transfer_local_child_rate'] ?? 0),
                'transfer_foreign_adult_rate' => (float) ($pricing['transfer_foreign_adult_rate'] ?? 0),
                'transfer_foreign_child_rate' => (float) ($pricing['transfer_foreign_child_rate'] ?? 0),
                'transfer_applied_adult_rate' => (float) ($pricing['transfer_applied_adult_rate'] ?? 0),
                'transfer_applied_child_rate' => (float) ($pricing['transfer_applied_child_rate'] ?? 0),
                'invoice_total_amount' => (float) ($pricing['invoice_total_amount'] ?? $totalAmount),
                'quote_source_currency' => (string) ($paymentQuote['source_currency'] ?? ''),
                'quote_source_amount' => (float) ($paymentQuote['source_amount'] ?? 0),
                'quote_payment_currency' => (string) ($paymentQuote['currency'] ?? ''),
                'quote_payment_amount' => (float) ($paymentQuote['amount'] ?? 0),
                'quote_gateway' => (string) ($paymentQuote['gateway'] ?? ''),
                'quote_provider' => (string) ($paymentQuote['provider'] ?? ''),
                'quote_gateway_label' => (string) ($paymentQuote['gateway_label'] ?? ''),
                'quote_provider_label' => (string) ($paymentQuote['provider_label'] ?? ''),
                'quote_fx_rate' => (float) ($paymentQuote['fx_rate'] ?? 1),
                'quote_fx_base_currency' => (string) ($paymentQuote['fx_base_currency'] ?? 'MVR'),
                'quote_quoted_at' => (string) ($paymentQuote['quoted_at'] ?? now()->toIso8601String()),
                'vendor_tax_overrides' => $vendorTaxOverrides,
                'policy_snapshot' => $pricing['policy_snapshot'] ?? [],
                'inclusives' => $listingDetails['inclusives'] ?? [],
                'cancellation_policy' => (string) ($listingDetails['cancellation_policy'] ?? ''),
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $checkoutUrl = '/booking/checkout'
        . ($reservationId ? ('/' . $reservationId) : '')
        . '?property_id=' . (int) $propertyRow->id
        . '&category_key=' . urlencode($categoryKey)
        . '&room_id=0'
        . '&checkin=' . urlencode($serviceStart->toDateString())
        . '&checkout=' . urlencode($serviceEnd->toDateString())
        . '&adults=' . $adults
        . '&children=' . $children
        . '&infants=' . $infants
        . '&primary_first_name=' . urlencode($primaryFirstName)
        . '&primary_last_name=' . urlencode($primaryLastName)
        . '&primary_nationality=' . urlencode($primaryNationality)
        . '&guest_residency=' . urlencode($guestResidency)
        . '&primary_email=' . urlencode($primaryEmail)
        . '&primary_mobile=' . urlencode($primaryMobile)
        . '&additional_guest_details=' . urlencode($additionalGuestDetails)
        . '&service_notes=' . urlencode($serviceNotes)
        . '&transfer_option=' . urlencode((string) $transferOptionCode)
        . '&transfer_option_label=' . urlencode((string) ($pricing['transfer_option_label'] ?? ''))
        . '&transfer_charge=' . urlencode((string) $transferCharge)
        . '&transfer_charge_total=' . urlencode((string) $transferCharge)
        . '&transfer_local_adult_rate=' . urlencode((string) ((float) ($pricing['transfer_local_adult_rate'] ?? 0)))
        . '&transfer_local_child_rate=' . urlencode((string) ((float) ($pricing['transfer_local_child_rate'] ?? 0)))
        . '&transfer_foreign_adult_rate=' . urlencode((string) ((float) ($pricing['transfer_foreign_adult_rate'] ?? 0)))
        . '&transfer_foreign_child_rate=' . urlencode((string) ((float) ($pricing['transfer_foreign_child_rate'] ?? 0)))
        . '&transfer_applied_adult_rate=' . urlencode((string) ((float) ($pricing['transfer_applied_adult_rate'] ?? 0)))
        . '&transfer_applied_child_rate=' . urlencode((string) ((float) ($pricing['transfer_applied_child_rate'] ?? 0)))
        . '&room_subtotal=' . urlencode((string) $serviceSubtotal)
        . '&discount_amount=' . urlencode((string) $discountAmount)
        . '&tax_amount=' . urlencode((string) $taxAmount)
        . '&discount_percent=' . urlencode((string) $discountPercent)
        . '&tax_rate=' . urlencode((string) (($pricing['gst_rate_percent'] ?? 0) + ($pricing['tgst_rate_percent'] ?? 0)))
        . '&tax_lines=' . urlencode(json_encode($pricing['tax_lines'] ?? []))
        . '&total=' . urlencode((string) $totalAmount)
        . '&inclusives=' . urlencode(json_encode($listingDetails['inclusives'] ?? []))
        . '&cancellation_policy=' . urlencode((string) ($listingDetails['cancellation_policy'] ?? ''));

    return redirect($checkoutUrl);
});

Route::get('/booking/checkout/{reservation?}', function (Request $request, ?int $reservation = null) {
    $categoryLabelMap = [
        'accommodation' => ['start' => 'Check-in', 'end' => 'Check-out'],
        'transport' => ['start' => 'Travel Date', 'end' => 'Return Date'],
        'excursion' => ['start' => 'Excursion Date', 'end' => 'Return Date'],
        'remote_workspace' => ['start' => 'Start Date', 'end' => 'End Date'],
        'resort_day_visit' => ['start' => 'Visit Date', 'end' => 'Return Date'],
        'restaurant' => ['start' => 'Reservation Date', 'end' => 'End Date'],
        'vehicle_rental' => ['start' => 'Pickup Date', 'end' => 'Return Date'],
    ];

    $reservationRow = null;
    if ($reservation !== null && Schema::hasTable('vendor_reservations')) {
        $reservationRow = DB::table('vendor_reservations')->where('id', $reservation)->first();
    }

    $propertyId = (int) $request->query('property_id', (int) ($reservationRow->vendor_property_id ?? 0));
    $propertyRow = VendorPropertyCompatibilityReader::loadPropertyById($propertyId);

    $roomId = (int) $request->query('room_id', 0);
    $roomName = '';
    if ($reservationRow && !empty($reservationRow->notes)) {
        $notes = json_decode((string) $reservationRow->notes, true);
        if (is_array($notes)) {
            $roomId = (int) ($notes['room_id'] ?? $roomId);
            $roomName = trim((string) ($notes['room_name'] ?? ''));
        }
    }

    $roomRow = Schema::hasTable('vendor_property_room_categories') && $roomId > 0
        ? DB::table('vendor_property_room_categories')->where('id', $roomId)->first()
        : null;

    if ($roomName === '' && $roomRow) {
        $roomName = trim((string) ($roomRow->name ?? 'Room'));
    }

    $reservationNotes = [];
    if ($reservationRow && !empty($reservationRow->notes)) {
        $decoded = json_decode((string) $reservationRow->notes, true);
        if (is_array($decoded)) {
            $reservationNotes = $decoded;
        }
    }

    $inclusivesQuery = trim((string) $request->query('inclusives', ''));
    $inclusives = [];
    if ($inclusivesQuery !== '') {
        $decodedInclusives = json_decode($inclusivesQuery, true);
        if (is_array($decodedInclusives)) {
            $inclusives = collect($decodedInclusives)->map(static fn ($v) => trim((string) $v))->filter()->values()->all();
        }
    }

    if (empty($inclusives) && !empty($reservationNotes['inclusives']) && is_array($reservationNotes['inclusives'])) {
        $inclusives = collect($reservationNotes['inclusives'])->map(static fn ($v) => trim((string) $v))->filter()->values()->all();
    }

    if ($roomName === '') {
        $roomName = trim((string) ($reservationNotes['service_label'] ?? 'Service'));
    }

    $categoryKey = strtolower(trim((string) $request->query('category_key', (string) ($reservationNotes['category_key'] ?? ''))));
    $dateLabels = ['start' => 'Check-in', 'end' => 'Check-out'];
    if ($categoryKey !== '' && array_key_exists($categoryKey, $categoryLabelMap)) {
        $dateLabels = $categoryLabelMap[$categoryKey];
    }

    $cancellationPolicy = trim((string) $request->query('cancellation_policy', ''));
    if ($cancellationPolicy === '') {
        $cancellationPolicy = trim((string) ($reservationNotes['cancellation_policy'] ?? 'Standard cancellation terms apply as per property policy.'));
    }

    $categoryDetailLabels = [
        'rooms' => 'Rooms',
        'transport_mode' => 'Transport Mode',
        'origin_point' => 'From',
        'destination_point' => 'To',
        'excursion_type' => 'Excursion Type',
        'workspace_type' => 'Workspace Type',
        'visit_package' => 'Visit Package',
        'meal_plan' => 'Meal Plan',
        'vehicle_type' => 'Vehicle Type',
        'pickup_location' => 'Pickup Location',
        'dropoff_location' => 'Drop-off Location',
    ];

    $categoryDetails = [];
    if (!empty($reservationNotes['category_details']) && is_array($reservationNotes['category_details'])) {
        foreach ($reservationNotes['category_details'] as $detailKey => $detailValue) {
            $normalizedKey = trim((string) $detailKey);
            $normalizedValue = trim((string) $detailValue);
            if ($normalizedKey === '' || $normalizedValue === '') {
                continue;
            }

            $categoryDetails[] = [
                'label' => (string) ($categoryDetailLabels[$normalizedKey] ?? Str::headline(str_replace('_', ' ', $normalizedKey))),
                'value' => $normalizedValue,
            ];
        }
    }

    $backUrl = '/customer';
    if ($roomRow) {
        $backUrl = '/room/' . (int) ($roomRow->id ?? 0);
    } elseif ($propertyRow && !empty($reservationNotes['category_key'])) {
        $backUrl = '/category-booking/' . urlencode((string) $reservationNotes['category_key']) . '/' . (int) ($propertyRow->id ?? 0);
    }

    $checkoutMediaUrl = null;
    if (Schema::hasTable('vendor_listing_media')) {
        $mediaRow = null;

        if ($roomId > 0) {
            $mediaRow = DB::table('vendor_listing_media')
                ->where('entity_type', 'room')
                ->where('entity_id', $roomId)
                ->orderByDesc('is_primary')
                ->orderByDesc('created_at')
                ->first(['id', 'file_path']);
        }

        if (!$mediaRow && $propertyId > 0) {
            $mediaRow = DB::table('vendor_listing_media')
                ->where('entity_type', 'property')
                ->where('entity_id', $propertyId)
                ->orderByDesc('is_primary')
                ->orderByDesc('created_at')
                ->first(['id', 'file_path']);
        }

        if ($mediaRow) {
            $mediaId = (int) ($mediaRow->id ?? 0);
            if ($mediaId > 0) {
                $checkoutMediaUrl = '/media/vendor/' . $mediaId . '/banner';
            } else {
                $checkoutMediaUrl = vendorMediaStorageUrlFromPath((string) ($mediaRow->file_path ?? ''));
            }
        }
    }

    $paymentContext = [
        'primary_nationality' => $reservationRow
            ? trim((string) ($reservationNotes['primary_nationality'] ?? ''))
            : trim((string) $request->query('primary_nationality', '')),
        'guest_residency' => $reservationRow
            ? trim((string) ($reservationNotes['guest_residency'] ?? ''))
            : trim((string) $request->query('guest_residency', '')),
        'requested_gateway' => $reservationRow
            ? trim((string) ($reservationNotes['quote_provider'] ?? $reservationNotes['quote_gateway'] ?? ''))
            : trim((string) $request->query('payment_gateway', '')),
        'reservation_currency' => strtoupper(trim((string) ($reservationRow->currency ?? $roomRow->currency ?? $propertyRow->currency ?? 'MVR'))),
        'amount' => (float) ($reservationNotes['invoice_total_amount'] ?? $request->query('total', (float) ($reservationRow->total_amount ?? 0))),
    ];
    $paymentPolicy = CheckoutPaymentRouter::buildPaymentPolicy(
        $paymentContext,
        $reservationRow
            ? trim((string) ($reservationNotes['quote_payment_currency'] ?? ''))
            : trim((string) $request->query('payment_currency', ''))
    );

    $quotedPaymentOptions = [];
    foreach ((array) ($paymentPolicy['available_options'] ?? []) as $availableOption) {
        if (!is_array($availableOption)) {
            continue;
        }

        $optionGateway = trim((string) ($availableOption['gateway'] ?? ''));
        $optionCurrency = strtoupper(trim((string) ($availableOption['currency'] ?? '')));
        if ($optionGateway === '' || $optionCurrency === '') {
            continue;
        }

        try {
            $optionQuote = CheckoutPaymentRouter::buildPaymentQuote($paymentContext, $optionCurrency, $optionGateway);
            $availableOption['amount'] = (float) ($optionQuote['amount'] ?? 0);
            $availableOption['fx_rate'] = (float) ($optionQuote['fx_rate'] ?? 1);
            $availableOption['source_currency'] = strtoupper(trim((string) ($optionQuote['source_currency'] ?? '')));
            $availableOption['source_amount'] = (float) ($optionQuote['source_amount'] ?? 0);
        } catch (\InvalidArgumentException $exception) {
            continue;
        }

        $quotedPaymentOptions[] = $availableOption;
    }

    if ($quotedPaymentOptions !== []) {
        $paymentPolicy['available_options'] = $quotedPaymentOptions;
    }

    return view('booking-checkout', [
        'reservation' => $reservationRow,
        'property' => $propertyRow,
        'room' => $roomRow,
        'roomName' => $roomName,
        'reservationNotes' => $reservationNotes,
        'inclusives' => $inclusives,
        'cancellationPolicy' => $cancellationPolicy,
        'categoryDetails' => $categoryDetails,
        'backUrl' => $backUrl,
        'checkoutMediaUrl' => $checkoutMediaUrl,
        'paymentPolicy' => $paymentPolicy,
        'dateLabels' => $dateLabels,
        'summary' => [
            'category_key' => trim((string) $request->query('category_key', (string) ($reservationNotes['category_key'] ?? ''))),
            'checkin' => trim((string) $request->query('checkin', (string) ($reservationNotes['service_start_date'] ?? ''))),
            'checkout' => trim((string) $request->query('checkout', (string) ($reservationNotes['service_end_date'] ?? ''))),
            'adults' => max(1, (int) $request->query('adults', (int) ($reservationNotes['adults'] ?? 1))),
            'children' => max(0, (int) $request->query('children', (int) ($reservationNotes['children'] ?? 0))),
            'infants' => max(0, (int) $request->query('infants', (int) ($reservationNotes['infants'] ?? 0))),
            'primary_first_name' => trim((string) $request->query('primary_first_name', (string) ($reservationNotes['primary_first_name'] ?? ''))),
            'primary_last_name' => trim((string) $request->query('primary_last_name', (string) ($reservationNotes['primary_last_name'] ?? ''))),
            'primary_nationality' => $reservationRow
                ? trim((string) ($reservationNotes['primary_nationality'] ?? ''))
                : trim((string) $request->query('primary_nationality', '')),
            'guest_residency' => $reservationRow
                ? trim((string) ($reservationNotes['guest_residency'] ?? ''))
                : trim((string) $request->query('guest_residency', '')),
            'primary_email' => trim((string) $request->query('primary_email', (string) ($reservationNotes['primary_email'] ?? (string) ($reservationRow->customer_email ?? '')))),
            'primary_mobile' => trim((string) $request->query('primary_mobile', (string) ($reservationNotes['primary_mobile'] ?? ''))),
            'additional_guest_details' => trim((string) $request->query('additional_guest_details', (string) ($reservationNotes['additional_guest_details'] ?? ''))),
            'service_notes' => trim((string) $request->query('service_notes', (string) ($reservationNotes['service_notes'] ?? ''))),
            'transfer_option' => $reservationRow
                ? trim((string) ($reservationNotes['transfer_option'] ?? ''))
                : trim((string) $request->query('transfer_option', '')),
            'transfer_option_label' => $reservationRow
                ? trim((string) ($reservationNotes['transfer_option_label'] ?? ''))
                : trim((string) $request->query('transfer_option_label', '')),
            'property_transfer_options' => is_array($reservationNotes['property_transfer_options'] ?? null)
                ? $reservationNotes['property_transfer_options']
                : [],
            'transfer_charge' => $reservationRow
                ? (float) ($reservationNotes['transfer_charge'] ?? 0)
                : (float) $request->query('transfer_charge', 0),
            'transfer_charge_total' => $reservationRow
                ? (float) ($reservationNotes['transfer_charge_total'] ?? ($reservationNotes['transfer_charge'] ?? 0))
                : (float) $request->query('transfer_charge_total', 0),
            'transfer_local_adult_rate' => (float) $request->query('transfer_local_adult_rate', (float) ($reservationNotes['transfer_local_adult_rate'] ?? 0)),
            'transfer_local_child_rate' => (float) $request->query('transfer_local_child_rate', (float) ($reservationNotes['transfer_local_child_rate'] ?? 0)),
            'transfer_foreign_adult_rate' => (float) $request->query('transfer_foreign_adult_rate', (float) ($reservationNotes['transfer_foreign_adult_rate'] ?? 0)),
            'transfer_foreign_child_rate' => (float) $request->query('transfer_foreign_child_rate', (float) ($reservationNotes['transfer_foreign_child_rate'] ?? 0)),
            'transfer_applied_adult_rate' => (float) $request->query('transfer_applied_adult_rate', (float) ($reservationNotes['transfer_applied_adult_rate'] ?? 0)),
            'transfer_applied_child_rate' => (float) $request->query('transfer_applied_child_rate', (float) ($reservationNotes['transfer_applied_child_rate'] ?? 0)),
            'room_subtotal' => (float) $request->query('room_subtotal', (float) ($reservationNotes['room_subtotal'] ?? ($reservationNotes['subtotal_amount'] ?? 0))),
            'subtotal_amount' => (float) $request->query('subtotal_amount', (float) ($reservationNotes['subtotal_amount'] ?? 0)),
            'discount_amount' => (float) $request->query('discount_amount', (float) ($reservationNotes['discount_amount'] ?? 0)),
            'discounted_subtotal' => (float) $request->query('discounted_subtotal', (float) ($reservationNotes['discounted_subtotal'] ?? 0)),
            'service_charge_rate_percent' => (float) $request->query('service_charge_rate_percent', (float) ($reservationNotes['service_charge_rate_percent'] ?? 0)),
            'service_charge_total' => (float) $request->query('service_charge_total', (float) ($reservationNotes['service_charge_total'] ?? 0)),
            'green_tax_rate_per_person_per_night' => (float) $request->query('green_tax_rate_per_person_per_night', (float) ($reservationNotes['green_tax_rate_per_person_per_night'] ?? 0)),
            'green_tax_total' => (float) $request->query('green_tax_total', (float) ($reservationNotes['green_tax_total'] ?? 0)),
            'tgst_rate_percent' => (float) $request->query('tgst_rate_percent', (float) ($reservationNotes['tgst_rate_percent'] ?? 0)),
            'tgst_total' => (float) $request->query('tgst_total', (float) ($reservationNotes['tgst_total'] ?? 0)),
            'gst_rate_percent' => (float) $request->query('gst_rate_percent', (float) ($reservationNotes['gst_rate_percent'] ?? 0)),
            'gst_total' => (float) $request->query('gst_total', (float) ($reservationNotes['gst_total'] ?? 0)),
            'tax_amount' => (float) $request->query('tax_amount', (float) ($reservationNotes['tax_amount'] ?? ($reservationNotes['total_tax_amount'] ?? 0))),
            'total_tax_amount' => (float) $request->query('total_tax_amount', (float) ($reservationNotes['total_tax_amount'] ?? 0)),
            'tax_lines' => is_array($reservationNotes['tax_lines'] ?? null)
                ? $reservationNotes['tax_lines']
                : (json_decode((string) $request->query('tax_lines', '[]'), true) ?: []),
            'discount_percent' => (float) $request->query('discount_percent', (float) ($reservationNotes['discount_percent'] ?? 0)),
            'tax_rate' => (float) $request->query('tax_rate', (float) ($reservationNotes['tax_rate'] ?? 0)),
            'total' => (float) ($reservationNotes['invoice_total_amount'] ?? $request->query('total', (float) ($reservationRow->total_amount ?? 0))),
            'quote_source_currency' => trim((string) ($reservationNotes['quote_source_currency'] ?? '')),
            'quote_source_amount' => (float) ($reservationNotes['quote_source_amount'] ?? 0),
            'quote_payment_currency' => trim((string) ($reservationNotes['quote_payment_currency'] ?? '')),
            'quote_payment_amount' => (float) ($reservationNotes['quote_payment_amount'] ?? 0),
            'quote_gateway' => trim((string) ($reservationNotes['quote_gateway'] ?? '')),
            'quote_provider' => trim((string) ($reservationNotes['quote_provider'] ?? '')),
            'quote_gateway_label' => trim((string) ($reservationNotes['quote_gateway_label'] ?? '')),
            'quote_provider_label' => trim((string) ($reservationNotes['quote_provider_label'] ?? '')),
            'quote_fx_rate' => (float) ($reservationNotes['quote_fx_rate'] ?? 1),
            'quote_fx_base_currency' => trim((string) ($reservationNotes['quote_fx_base_currency'] ?? 'MVR')),
            'quote_quoted_at' => trim((string) ($reservationNotes['quote_quoted_at'] ?? '')),
        ],
    ]);
});

Route::post('/booking/checkout/{reservation}/payment-intent', function (Request $request, int $reservation) {
    if (!Schema::hasTable('vendor_reservations')) {
        abort(404);
    }

    $reservationRow = DB::table('vendor_reservations')->where('id', $reservation)->first();
    if (!$reservationRow) {
        abort(404);
    }

    $validated = $request->validate([
        'payment_currency' => ['nullable', 'string', 'min:3', 'max:8'],
        'payment_gateway' => ['nullable', 'string', 'min:2', 'max:64'],
        'payment_provider' => ['nullable', 'string', 'min:2', 'max:64'],
        'payment_selection' => ['nullable', 'string', 'max:120'],
        'transfer_option' => ['nullable', 'string', 'max:80'],
        'transfer_option_label' => ['nullable', 'string', 'max:160'],
        'transfer_charge' => ['nullable', 'numeric', 'min:0'],
        'invoice_total_amount' => ['nullable', 'numeric', 'min:0'],
    ]);

    if (in_array(strtolower(trim((string) ($reservationRow->status ?? 'pending'))), ['cancelled', 'canceled'], true)) {
        return back()->withErrors(['payment' => 'Cancelled reservations cannot be paid.']);
    }

    if (strtolower(trim((string) ($reservationRow->payment_status ?? 'unpaid'))) === 'paid') {
        return back()->withErrors(['payment' => 'This reservation is already paid.']);
    }

    $notes = workationReservationPaymentNotes($reservationRow);
    $primaryNationality = trim((string) ($notes['primary_nationality'] ?? ''));
    $guestResidency = strtolower(trim((string) ($notes['guest_residency'] ?? '')));
    if (!in_array($guestResidency, ['local_resident', 'foreign_national'], true)) {
        $guestResidency = ReservationPricingPolicy::isForeigner($primaryNationality, null)
            ? 'foreign_national'
            : 'local_resident';
    }

    $notes['primary_nationality'] = $primaryNationality;
    $notes['guest_residency'] = $guestResidency;
    $notes['transfer_option'] = trim((string) ($notes['transfer_option'] ?? ($validated['transfer_option'] ?? '')));
    $notes['transfer_option_label'] = trim((string) ($notes['transfer_option_label'] ?? ($validated['transfer_option_label'] ?? '')));
    $notes['transfer_charge'] = max(0, (float) ($notes['transfer_charge'] ?? ($validated['transfer_charge'] ?? 0)));
    $notes['transfer_charge_total'] = $notes['transfer_charge'];
    $notes['invoice_total_amount'] = max(0, (float) ($notes['invoice_total_amount'] ?? ($validated['invoice_total_amount'] ?? ($reservationRow->total_amount ?? 0))));

    $requestedGateway = trim((string) ($validated['payment_provider'] ?? ($validated['payment_gateway'] ?? '')));
    $requestedCurrency = trim((string) ($validated['payment_currency'] ?? ''));
    $selection = trim((string) ($validated['payment_selection'] ?? ''));
    if ($selection !== '' && str_contains($selection, '|')) {
        [$selectionGateway, $selectionCurrency] = array_pad(explode('|', $selection, 2), 2, '');
        if ($requestedGateway === '') {
            $requestedGateway = trim((string) $selectionGateway);
        }
        if ($requestedCurrency === '') {
            $requestedCurrency = trim((string) $selectionCurrency);
        }
    }

    if ($requestedGateway === '') {
        $requestedGateway = trim((string) ($notes['quote_provider'] ?? $notes['quote_gateway'] ?? ''));
    }
    if ($requestedCurrency === '') {
        $requestedCurrency = trim((string) ($notes['quote_payment_currency'] ?? ''));
    }

    try {
        $intent = CheckoutPaymentRouter::createIntentPayload([
            'primary_nationality' => $primaryNationality,
            'guest_residency' => $guestResidency,
            'reservation_currency' => (string) ($reservationRow->currency ?? 'MVR'),
            'amount' => (float) ($notes['invoice_total_amount'] ?? ($reservationRow->total_amount ?? 0)),
        ], $requestedCurrency, $requestedGateway);
    } catch (\InvalidArgumentException $exception) {
        return back()->withErrors(['payment' => $exception->getMessage()]);
    }

    $settlement = ReservationSettlementCalculator::calculate(
        (float) ($intent['amount'] ?? 0),
        (string) ($intent['gateway'] ?? ''),
        (string) ($intent['provider'] ?? '')
    );
    $intent['settlement'] = $settlement;

    DB::table('vendor_reservations')
        ->where('id', $reservation)
        ->update([
            'customer_segment' => (string) $intent['segment'],
            'payment_currency' => (string) $intent['currency'],
            'payment_gateway' => (string) $intent['gateway'],
            'payment_intent_id' => (string) $intent['intent_id'],
            'payment_amount' => (float) $intent['amount'],
            'total_amount' => (float) ($notes['invoice_total_amount'] ?? ($reservationRow->total_amount ?? 0)),
            'commission_rate_percent' => (float) ($settlement['commission_rate_percent'] ?? 0),
            'commission_amount' => (float) ($settlement['commission_amount'] ?? 0),
            'gateway_fee_rate_percent' => (float) ($settlement['gateway_fee_rate_percent'] ?? 0),
            'gateway_fee_amount' => (float) ($settlement['gateway_fee_amount'] ?? 0),
            'vendor_payout_amount' => (float) ($settlement['vendor_payout_amount'] ?? 0),
            'payment_error' => null,
            'payment_payload_json' => json_encode($intent),
            'notes' => json_encode($notes),
            'updated_at' => now(),
        ]);

    $checkoutUrl = trim((string) ($intent['checkout_url'] ?? ''));
    if ($checkoutUrl !== '') {
        $query = http_build_query([
            'intent_id' => (string) ($intent['intent_id'] ?? ''),
            'reservation_id' => $reservation,
            'amount' => number_format((float) ($intent['amount'] ?? 0), 2, '.', ''),
            'currency' => (string) ($intent['currency'] ?? ''),
            'provider' => (string) ($intent['provider'] ?? ''),
            'gateway' => (string) ($intent['gateway'] ?? ''),
            'return_url' => url('/booking/checkout/' . $reservation),
            'cancel_url' => url('/booking/checkout/' . $reservation),
            'webhook_url' => url('/booking/payment/webhooks/' . (string) ($intent['gateway'] ?? '')),
        ]);
        $target = $checkoutUrl . (str_contains($checkoutUrl, '?') ? '&' : '?') . $query;

        return redirect()->away($target);
    }

    return redirect('/booking/payment/hosted/' . $reservation . '?intent=' . urlencode((string) $intent['intent_id']));
});

Route::get('/booking/payment/hosted/{reservation}', function (Request $request, int $reservation) {
    if (!Schema::hasTable('vendor_reservations')) {
        abort(404);
    }

    $reservationRow = DB::table('vendor_reservations')->where('id', $reservation)->first();
    if (!$reservationRow) {
        abort(404);
    }

    $intentId = trim((string) $request->query('intent', ''));
    if ($intentId === '' || $intentId !== trim((string) ($reservationRow->payment_intent_id ?? ''))) {
        abort(404);
    }

    $propertyRow = VendorPropertyCompatibilityReader::loadPropertyById((int) ($reservationRow->vendor_property_id ?? 0));

    return view('booking-payment-hosted', [
        'reservation' => $reservationRow,
        'property' => $propertyRow,
        'intentId' => $intentId,
    ]);
});

Route::post('/booking/payment/hosted/{reservation}/complete', function (Request $request, int $reservation) {
    if (!Schema::hasTable('vendor_reservations')) {
        abort(404);
    }

    $reservationRow = DB::table('vendor_reservations')->where('id', $reservation)->first();
    if (!$reservationRow) {
        abort(404);
    }

    $validated = $request->validate([
        'intent_id' => ['required', 'string', 'max:120'],
        'payment_reference' => ['nullable', 'string', 'max:120'],
    ]);

    if ((string) $validated['intent_id'] !== (string) ($reservationRow->payment_intent_id ?? '')) {
        return back()->withErrors(['payment' => 'Payment session no longer matches this reservation.']);
    }

    $payload = json_decode((string) ($reservationRow->payment_payload_json ?? ''), true);
    if (!is_array($payload)) {
        $payload = [];
    }
    $checkoutUrl = trim((string) ($payload['checkout_url'] ?? ''));
    if ($checkoutUrl !== '') {
        $query = http_build_query([
            'intent_id' => (string) ($reservationRow->payment_intent_id ?? ''),
            'reservation_id' => $reservation,
            'amount' => number_format((float) ($reservationRow->payment_amount ?? 0), 2, '.', ''),
            'currency' => strtoupper(trim((string) ($reservationRow->payment_currency ?? 'MVR'))),
            'provider' => (string) ($payload['provider'] ?? ''),
            'gateway' => (string) ($payload['gateway'] ?? ''),
            'return_url' => url('/booking/checkout/' . $reservation),
            'cancel_url' => url('/booking/checkout/' . $reservation),
            'webhook_url' => url('/booking/payment/webhooks/' . (string) ($payload['gateway'] ?? '')),
        ]);
        $target = $checkoutUrl . (str_contains($checkoutUrl, '?') ? '&' : '?') . $query;

        return redirect()->away($target);
    }

    workationApplyReservationPaymentEvent($reservationRow, [
        'event_id' => 'internal_' . Str::lower(Str::random(20)),
        'intent_id' => (string) $validated['intent_id'],
        'reference' => trim((string) ($validated['payment_reference'] ?? ('INT-' . $reservation))),
        'status' => 'paid',
    ]);

    return redirect('/booking/checkout/' . $reservation)->with('portal_notice', 'Payment recorded and reservation confirmed.');
});

Route::post('/booking/payment/webhooks/{gateway}', function (Request $request, string $gateway) {
    if (!Schema::hasTable('vendor_reservations')) {
        abort(404);
    }

    $raw = (string) $request->getContent();
    $signature = trim((string) $request->header('X-Workation-Signature', ''));
    if (!CheckoutPaymentRouter::verifySignature($gateway, $raw, $signature)) {
        return response()->json(['ok' => false, 'message' => 'Invalid signature'], 401);
    }

    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        return response()->json(['ok' => false, 'message' => 'Invalid payload'], 422);
    }

    $reservationId = (int) ($payload['reservation_id'] ?? 0);
    $reservationRow = DB::table('vendor_reservations')->where('id', $reservationId)->first();
    if (!$reservationRow) {
        return response()->json(['ok' => false, 'message' => 'Reservation not found'], 404);
    }

    $result = workationApplyReservationPaymentEvent($reservationRow, [
        'event_id' => (string) ($payload['event_id'] ?? ''),
        'intent_id' => (string) ($payload['intent_id'] ?? ''),
        'reference' => (string) ($payload['reference'] ?? ''),
        'status' => (string) ($payload['status'] ?? 'failed'),
        'error' => (string) ($payload['error'] ?? ''),
    ]);

    return response()->json(['ok' => true, 'result' => $result['status'] ?? 'processed']);
});