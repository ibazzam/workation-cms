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

if (!function_exists('workationApiBase')) {
    function workationApiBase(): string
    {
        return rtrim((string) env('WORKATION_API_BASE_URL', 'https://api.workation.mv'), '/');
    }
}

if (!function_exists('portalConfig')) {
    function portalConfig(string $portal): array
    {
        if ($portal === 'admin') {
            return [
                'session_key' => 'portal_admin_authenticated',
                'name' => 'Admin',
                'allowed_roles' => ['ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE', 'ADMIN_FINACE', 'ADMIN_MEDIA'],
            ];
        }

        if ($portal === 'customer') {
            return [
                'session_key' => 'portal_customer_authenticated',
                'name' => 'Member',
                'allowed_roles' => [],
            ];
        }

        return [
            'session_key' => 'portal_vendor_authenticated',
            'name' => 'Vendor',
            'allowed_roles' => ['VENDOR'],
        ];
    }
}

if (!function_exists('portalRoutePath')) {
    function portalRoutePath(string $portal): string
    {
        if ($portal === 'admin') {
            return adminPortalEntryPath();
        }

        if ($portal === 'customer') {
            return '/customer';
        }

        return '/vendor';
    }
}

if (!function_exists('adminPortalEntryPath')) {
    function adminPortalEntryPath(?string $page = null): string
    {
        $configuredPath = firstNonEmptyEnv([
            'PORTAL_ADMIN_ENTRY_PATH',
            'WORKATION_ADMIN_ENTRY_PATH',
            'ADMIN_ENTRY_PATH',
        ]);

        $slug = trim($configuredPath, " \t\n\r\0\x0B/");
        if ($slug === '') {
            $slug = 'ops-console-3k9m2q7x';
        }

        $basePath = '/' . $slug;
        $normalizedPage = strtolower(trim((string) ($page ?? '')));

        return $normalizedPage === '' ? $basePath : ($basePath . '/' . rawurlencode($normalizedPage));
    }
}

if (!function_exists('firstNonEmptyEnv')) {
    function firstNonEmptyEnv(array $keys): string
    {
        foreach ($keys as $key) {
            $value = trim((string) env($key, ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}

if (!function_exists('workationReservationPaymentNotes')) {
    function workationReservationPaymentNotes(object $reservationRow): array
    {
        $notes = json_decode((string) ($reservationRow->notes ?? ''), true);
        return is_array($notes) ? $notes : [];
    }
}

if (!function_exists('workationApplyReservationPaymentEvent')) {
    function workationApplyReservationPaymentEvent(object $reservationRow, array $event): array
    {
        $reservationId = (int) ($reservationRow->id ?? 0);
        if ($reservationId <= 0) {
            return ['status' => 'invalid'];
        }

        $eventId = trim((string) ($event['event_id'] ?? ''));
        $intentId = trim((string) ($event['intent_id'] ?? ''));
        $reference = trim((string) ($event['reference'] ?? ''));
        $status = strtolower(trim((string) ($event['status'] ?? 'failed')));
        $paidStatuses = ['paid', 'success', 'succeeded', 'confirmed', 'complete', 'completed', 'captured', 'settled'];
        $cancelledStatuses = ['cancelled', 'canceled', 'void', 'voided', 'expired', 'refunded'];
        $isPaidStatus = in_array($status, $paidStatuses, true);
        $isCancelledStatus = in_array($status, $cancelledStatuses, true);

        if ($eventId !== '' && trim((string) ($reservationRow->payment_webhook_event_id ?? '')) === $eventId) {
            return ['status' => 'duplicate'];
        }

        $paymentStatus = $isPaidStatus ? 'paid' : 'unpaid';
        $resolvedReservationStatus = (string) ($reservationRow->status ?? 'pending');
        $expectedPayoutAt = $isPaidStatus
            ? ReservationSettlementCalculator::expectedPayoutAt(
                $reservationRow->payment_collected_at ?? now(),
                (string) ($reservationRow->payment_gateway ?? ''),
                null
            )
            : null;
        if ($isPaidStatus) {
            $resolvedReservationStatus = 'confirmed';
        } elseif ($isCancelledStatus) {
            $resolvedReservationStatus = 'cancelled';
        }

        DB::table('vendor_reservations')
            ->where('id', $reservationId)
            ->update([
                'payment_status' => $paymentStatus,
                'status' => $resolvedReservationStatus,
                'payment_reference' => $reference !== '' ? $reference : (string) ($reservationRow->payment_reference ?? ''),
                'payment_intent_id' => $intentId !== '' ? $intentId : (string) ($reservationRow->payment_intent_id ?? ''),
                'payment_verified_at' => $isPaidStatus ? now() : ($reservationRow->payment_verified_at ?? null),
                'payment_collected_at' => $isPaidStatus
                    ? ($reservationRow->payment_collected_at ?? now())
                    : ($reservationRow->payment_collected_at ?? null),
                'payout_expected_at' => $isPaidStatus
                    ? (($reservationRow->payout_expected_at ?? null) ?: $expectedPayoutAt)
                    : ($reservationRow->payout_expected_at ?? null),
                'payment_webhook_event_id' => $eventId !== '' ? $eventId : (string) ($reservationRow->payment_webhook_event_id ?? ''),
                'payment_webhook_received_at' => now(),
                'payment_error' => $isPaidStatus ? null : trim((string) ($event['error'] ?? 'Payment failed verification.')),
                'updated_at' => now(),
            ]);

        if ($isPaidStatus) {
            $vendorUserId = (int) ($reservationRow->vendor_user_id ?? 0);
            $vendorPropertyId = (int) ($reservationRow->vendor_property_id ?? 0);
            $vendorEmail = $vendorUserId > 0
                ? (string) (DB::table('users')->where('id', $vendorUserId)->value('email') ?? '')
                : '';
            $customerEmail = trim((string) ($reservationRow->customer_email ?? ''));
            $bookingRef = '#' . $reservationId;
            $customerName = (string) ($reservationRow->customer_name ?? 'Guest');
            $totalAmt = number_format((float) ($reservationRow->total_amount ?? 0), 2);
            $payoutAmt = number_format((float) ($reservationRow->vendor_payout_amount ?? 0), 2);
            $commissionAmt = number_format((float) ($reservationRow->commission_amount ?? 0), 2);
            $gatewayFeeAmt = number_format((float) ($reservationRow->gateway_fee_amount ?? 0), 2);
            $currency = strtoupper((string) ($reservationRow->currency ?? 'MVR'));
            $startAt = trim((string) ($reservationRow->start_at ?? ''));
            $endAt = trim((string) ($reservationRow->end_at ?? ''));
            $dateInfo = $startAt !== '' ? ('Service Dates: ' . $startAt . ($endAt !== '' ? ' to ' . $endAt : '')) : null;
            $invoiceAttachment = null;
            $confirmationAttachment = null;
            try {
                $invoiceAttachment = [
                    'name' => workationReservationPdfFilename($reservationRow, 'invoice'),
                    'mime' => 'application/pdf',
                    'data' => workationRenderReservationPdfBinary($reservationRow, 'invoice'),
                ];
                $confirmationAttachment = [
                    'name' => workationReservationPdfFilename($reservationRow, 'confirmation'),
                    'mime' => 'application/pdf',
                    'data' => workationRenderReservationPdfBinary($reservationRow, 'confirmation'),
                ];
            } catch (\Throwable $e) {
                Log::warning('Failed generating reservation PDF attachments.', [
                    'reservation_id' => $reservationId,
                    'error' => $e->getMessage(),
                ]);
            }

            $emailAttachments = [];
            foreach ([$invoiceAttachment, $confirmationAttachment] as $attachment) {
                if (is_array($attachment) && isset($attachment['data']) && is_string($attachment['data']) && $attachment['data'] !== '') {
                    $emailAttachments[] = $attachment;
                }
            }

            $emailBody = implode("\n", array_filter([
                'Dear Vendor,',
                '',
                'A payment has been confirmed for one of your bookings.',
                '',
                'Booking Reference: ' . $bookingRef,
                'Customer: ' . $customerName,
                $dateInfo,
                '',
                'Payment Summary (' . $currency . '):',
                '  Total Collected:   ' . $totalAmt,
                '  Commission:        ' . $commissionAmt,
                '  Gateway Fee:       ' . $gatewayFeeAmt,
                '  Your Net Payout:   ' . $payoutAmt,
                '',
                'Your payout will be included in the next scheduled payout run.',
                '',
                'Thank you,',
                'Workation Team',
            ], static fn ($l) => $l !== null));
            workationSendVendorEmailSafe($vendorEmail, 'Payment Confirmed – Booking ' . $bookingRef, $emailBody, $emailAttachments);

            $customerEmailBody = implode("\n", array_filter([
                'Dear ' . ($customerName !== '' ? $customerName : 'Customer') . ',',
                '',
                'Your payment has been confirmed.',
                '',
                'Booking Reference: ' . $bookingRef,
                $dateInfo,
                'Amount Paid: ' . $currency . ' ' . $totalAmt,
                '',
                'You can review this booking in your customer portal under My Bookings.',
                'Invoice and reservation confirmation PDF are attached for your records.',
                '',
                'Thank you for booking with Workation.',
                'Workation Team',
            ], static fn ($l) => $l !== null));
            workationSendVendorEmailSafe($customerEmail, 'Reservation Confirmed + Invoice – Booking ' . $bookingRef, $customerEmailBody, $emailAttachments);

            $adminBody = implode("\n", array_filter([
                'Dear Admin Team,',
                '',
                'A reservation payment has been confirmed.',
                '',
                'Booking Reference: ' . $bookingRef,
                'Customer: ' . $customerName,
                'Customer Email: ' . ($customerEmail !== '' ? $customerEmail : 'N/A'),
                'Vendor Email: ' . ($vendorEmail !== '' ? $vendorEmail : 'N/A'),
                $dateInfo,
                '',
                'Payment Summary (' . $currency . '):',
                '  Total Collected:   ' . $totalAmt,
                '  Commission:        ' . $commissionAmt,
                '  Gateway Fee:       ' . $gatewayFeeAmt,
                '  Net Payout:        ' . $payoutAmt,
                '',
                'This notice was generated automatically from the payment event pipeline.',
                '',
                'Workation System',
            ], static fn ($l) => $l !== null));
            foreach (workationReservationAdminEmails() as $adminEmail) {
                if ($adminEmail === strtolower(trim($vendorEmail)) || $adminEmail === strtolower(trim($customerEmail))) {
                    continue;
                }
                workationSendVendorEmailSafe((string) $adminEmail, 'Admin Notice – Payment Confirmed ' . $bookingRef, $adminBody, $emailAttachments);
            }

            // Block availability slots now that payment is confirmed
            if ($vendorUserId > 0 && $vendorPropertyId > 0 && $startAt !== '' && $endAt !== '') {
                $reservationNotes = [];
                try {
                    $decoded = json_decode((string) ($reservationRow->notes ?? ''), true);
                    $reservationNotes = is_array($decoded) ? $decoded : [];
                } catch (\Throwable $e) {
                    // Ignore JSON decode errors
                }

                $startDate = Carbon::parse($startAt)->startOfDay();
                $endDate = Carbon::parse($endAt)->startOfDay();

                // Check if this is an accommodation booking (has room_id)
                $roomCategoryId = isset($reservationNotes['room_id']) ? (int) $reservationNotes['room_id'] : 0;
                if ($roomCategoryId > 0) {
                    // Accommodation booking - block dates for the room
                    workationReserveAvailabilitySlots(
                        $vendorUserId,
                        $vendorPropertyId,
                        $startDate,
                        $endDate,
                        1,
                        $roomCategoryId,
                        null,
                        'accommodation',
                        null
                    );
                } elseif (isset($reservationNotes['category_key'])) {
                    // Category booking (services) - block dates for the service
                    $categoryKey = (string) $reservationNotes['category_key'];
                    $unitsRequested = isset($reservationNotes['units_requested']) ? (int) $reservationNotes['units_requested'] : 1;
                    $normalizedCategoryKey = str_replace('-', '_', $categoryKey);
                    workationReserveAvailabilitySlots(
                        $vendorUserId,
                        $vendorPropertyId,
                        $startDate,
                        $endDate,
                        $unitsRequested,
                        null,
                        null,
                        $normalizedCategoryKey,
                        null
                    );
                }
            }
        }

        return ['status' => $paymentStatus];
    }
}

if (!function_exists('workationSendVendorEmailSafe')) {
    function workationSendVendorEmailSafe(string $to, string $subject, string $body, array $attachments = []): void
    {
        if ($to === '' || !str_contains($to, '@')) {
            return;
        }
        try {
            \Illuminate\Support\Facades\Mail::raw($body, static function ($msg) use ($to, $subject, $attachments): void {
                $msg->to($to)->subject($subject);
                foreach ($attachments as $attachment) {
                    $binary = is_array($attachment) ? ($attachment['data'] ?? null) : null;
                    $name = is_array($attachment) ? (string) ($attachment['name'] ?? 'document.pdf') : 'document.pdf';
                    $mime = is_array($attachment) ? (string) ($attachment['mime'] ?? 'application/pdf') : 'application/pdf';
                    if (!is_string($binary) || $binary === '') {
                        continue;
                    }

                    $msg->attachData($binary, $name, ['mime' => $mime]);
                }
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('workationSendVendorEmailSafe: failed to send email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

if (!function_exists('workationReservationAdminEmails')) {
    function workationReservationAdminEmails(): array
    {
        $configured = collect(explode(',', (string) env('WORKATION_BOOKING_ADMIN_EMAILS', '')))
            ->map(static fn ($value) => strtolower(trim((string) $value)))
            ->filter(static fn ($value) => $value !== '' && str_contains($value, '@'))
            ->values();

        $roleEmails = collect();
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'portal_role') && Schema::hasColumn('users', 'email')) {
            $roleEmails = DB::table('users')
                ->whereIn('portal_role', ['ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE', 'ADMIN_FINACE'])
                ->whereNotNull('email')
                ->pluck('email')
                ->map(static fn ($value) => strtolower(trim((string) $value)))
                ->filter(static fn ($value) => $value !== '' && str_contains($value, '@'))
                ->values();
        }

        return $configured
            ->merge($roleEmails)
            ->unique()
            ->values()
            ->all();
    }
}

if (!function_exists('workationNotifyReservationStakeholders')) {
    function workationNotifyReservationStakeholders(object $reservationRow, string $subject, string $body, array $attachments = [], array $skipEmails = []): void
    {
        $skipMap = collect($skipEmails)
            ->map(static fn ($value) => strtolower(trim((string) $value)))
            ->filter(static fn ($value) => $value !== '')
            ->flip();

        $vendorEmail = '';
        $vendorUserId = (int) ($reservationRow->vendor_user_id ?? 0);
        if ($vendorUserId > 0 && Schema::hasTable('users')) {
            $vendorEmail = strtolower(trim((string) (DB::table('users')->where('id', $vendorUserId)->value('email') ?? '')));
        }

        $recipients = collect([
            strtolower(trim((string) ($reservationRow->customer_email ?? ''))),
            $vendorEmail,
        ])
            ->merge(workationReservationAdminEmails())
            ->filter(static fn ($email) => $email !== '' && str_contains($email, '@'))
            ->filter(static fn ($email) => !$skipMap->has($email))
            ->unique()
            ->values();

        foreach ($recipients as $recipient) {
            workationSendVendorEmailSafe((string) $recipient, $subject, $body, $attachments);
        }
    }
}

if (!function_exists('workationBuildReservationDocumentData')) {
    function workationBuildReservationDocumentData(object $reservationRow, string $documentType = 'invoice'): array
    {
        $notes = workationReservationPaymentNotes($reservationRow);
        $currency = strtoupper(trim((string) ($reservationRow->payment_currency ?? $reservationRow->currency ?? 'MVR')));
        $subtotal = (float) ($notes['subtotal_amount'] ?? $notes['room_subtotal'] ?? $reservationRow->total_amount ?? 0);
        $serviceCharge = (float) ($notes['service_charge_total'] ?? 0);
        $transferCharge = (float) ($notes['transfer_charge_total'] ?? $notes['transfer_charge'] ?? 0);
        $totalTax = (float) ($notes['total_tax_amount'] ?? $notes['tax_amount'] ?? 0);
        $invoiceTotal = (float) ($notes['invoice_total_amount'] ?? $reservationRow->total_amount ?? 0);
        $taxLines = collect($notes['tax_lines'] ?? [])->filter(static fn ($line) => is_array($line))->map(static function (array $line): array {
            $label = trim((string) ($line['label'] ?? $line['name'] ?? $line['type'] ?? 'Tax'));
            $amount = (float) ($line['amount'] ?? $line['value'] ?? 0);
            return [
                'label' => $label !== '' ? $label : 'Tax',
                'amount' => max(0, $amount),
            ];
        })->filter(static fn (array $line): bool => $line['amount'] > 0)->values()->all();

        return [
            'document_type' => $documentType,
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'branding' => workationBrandingProfile(),
            'reservation_id' => (int) ($reservationRow->id ?? 0),
            'booking_reference' => 'RSV-' . str_pad((string) ((int) ($reservationRow->id ?? 0)), 6, '0', STR_PAD_LEFT),
            'customer_name' => (string) ($reservationRow->customer_name ?? 'Guest Customer'),
            'customer_email' => (string) ($reservationRow->customer_email ?? ''),
            'status' => strtoupper(trim((string) ($reservationRow->status ?? 'pending'))),
            'payment_status' => strtoupper(trim((string) ($reservationRow->payment_status ?? 'unpaid'))),
            'payment_gateway' => strtoupper(trim((string) ($reservationRow->payment_gateway ?? ''))),
            'payment_reference' => trim((string) ($reservationRow->payment_reference ?? '')),
            'start_at' => trim((string) ($reservationRow->start_at ?? '')),
            'end_at' => trim((string) ($reservationRow->end_at ?? '')),
            'currency' => $currency,
            'subtotal_amount' => max(0, $subtotal),
            'service_charge_total' => max(0, $serviceCharge),
            'transfer_charge_total' => max(0, $transferCharge),
            'total_tax_amount' => max(0, $totalTax),
            'invoice_total_amount' => max(0, $invoiceTotal),
            'tax_lines' => $taxLines,
        ];
    }
}

if (!function_exists('workationRenderReservationPdfBinary')) {
    function workationRenderReservationPdfBinary(object $reservationRow, string $documentType = 'invoice'): string
    {
        // Keep test runs stable: PDF rendering is integration-heavy and can exceed
        // memory in CI/local test runners without adding signal to payment logic tests.
        if (app()->runningUnitTests()) {
            return '%PDF-1.4\n% Workation test placeholder PDF\n';
        }

        if (!class_exists('\\Barryvdh\\DomPDF\\Facade\\Pdf')) {
            throw new \RuntimeException('PDF renderer is not available.');
        }

        $data = workationBuildReservationDocumentData($reservationRow, $documentType);
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('documents.reservation-pdf', $data)
            ->setPaper('a4')
            ->output();
    }
}

if (!function_exists('workationReservationPdfFilename')) {
    function workationReservationPdfFilename(object $reservationRow, string $documentType = 'invoice'): string
    {
        $reservationId = (int) ($reservationRow->id ?? 0);
        $suffix = strtolower(trim($documentType)) === 'confirmation' ? 'reservation-confirmation' : 'invoice';
        return 'workation-' . $suffix . '-rsv-' . str_pad((string) max(1, $reservationId), 6, '0', STR_PAD_LEFT) . '.pdf';
    }
}

if (!function_exists('workationDateSeries')) {
    function workationDateSeries(Carbon $start, Carbon $endExclusive): array
    {
        $dates = [];
        $cursor = $start->copy()->startOfDay();
        $last = $endExclusive->copy()->startOfDay();

        while ($cursor->lessThan($last)) {
            $dates[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return $dates;
    }
}

if (!function_exists('workationDerivedListingBasePrice')) {
    function workationDerivedListingBasePrice(object $property): float
    {
        $existingBasePrice = isset($property->base_price) ? (float) ($property->base_price ?? 0) : 0.0;

        $rawDetails = $property->listing_details ?? ($property->details ?? null);
        $details = null;
        if (is_string($rawDetails) && trim($rawDetails) !== '') {
            $decoded = json_decode($rawDetails, true);
            $details = is_array($decoded) ? $decoded : null;
        }

        $normalizePrice = static function ($value): float {
            if (is_numeric($value)) {
                return (float) $value;
            }

            if (is_string($value)) {
                $normalized = trim($value);
                if ($normalized === '') {
                    return 0.0;
                }

                $normalized = str_replace(',', '', $normalized);
                $normalized = preg_replace('/[^0-9.\-]+/', '', $normalized) ?? '';
                if ($normalized === '' || !is_numeric($normalized)) {
                    return 0.0;
                }

                return (float) $normalized;
            }

            return 0.0;
        };

        $candidateKeys = [
            'base_price',
            'starting_price',
            'from_price',
            'starting_from_price',
            'price_per_night',
            'base_price_per_night',
            'price_per_day',
            'daily_rate',
            'hourly_rate',
            'adult_price',
            'price_per_adult',
            'child_price',
            'price_per_child',
            'infant_price',
            'price_per_infant',
            'per_person_rate',
            'per_pax_rate',
            'per_trip_rate',
            'starting_hourly_rate',
            'starting_daily_rate',
            'adult_rate',
            'child_rate',
            'adult_charge',
            'child_charge',
            'trip_rate',
            'trip_price',
            'hourly_price',
            'daily_price',
            'base_charge',
            'booking_fee',
            'service_fee',
            'platform_fee',
            'price',
            'rate',
            'cost',
            'meal_plan_room_only_price',
            'meal_plan_breakfast_price',
            'meal_plan_half_board_price',
            'meal_plan_full_board_price',
            'meal_plan_all_inclusive_price',
        ];

        $candidates = [];
        if ($existingBasePrice > 0) {
            $candidates[] = $existingBasePrice;
        }

        if (!is_array($details) || $details === []) {
            return $candidates === [] ? 0.0 : (float) min($candidates);
        }

        foreach ($candidateKeys as $key) {
            $normalized = $normalizePrice($details[$key] ?? null);
            if ($normalized > 0) {
                $candidates[] = $normalized;
            }
        }

        foreach (['pricing', 'pricing_config', 'price_config'] as $nestedKey) {
            if (!is_array($details[$nestedKey] ?? null)) {
                continue;
            }

            foreach ($candidateKeys as $key) {
                $normalized = $normalizePrice($details[$nestedKey][$key] ?? null);
                if ($normalized > 0) {
                    $candidates[] = $normalized;
                }
            }
        }

        // Fallback: recursively scan nested payloads for numeric fields that are likely pricing.
        // Guard against common non-price numeric keys like total_beds, max_guests, ratings, counts, etc.
        $collectNestedPriceCandidates = static function ($value, int $depth = 0) use (&$collectNestedPriceCandidates, &$candidates, $normalizePrice): void {
            if ($depth > 5) {
                return;
            }

            if (is_array($value)) {
                foreach ($value as $nestedKey => $nestedValue) {
                    if (is_array($nestedValue) || is_object($nestedValue)) {
                        $collectNestedPriceCandidates($nestedValue, $depth + 1);
                        continue;
                    }

                    $keyText = strtolower(trim((string) $nestedKey));
                    if ($keyText === '') {
                        continue;
                    }

                    $keyTokens = array_values(array_filter(explode('_', (string) (preg_replace('/[^a-z0-9]+/', '_', $keyText) ?? ''))));
                    if ($keyTokens === []) {
                        continue;
                    }

                    $priceTokens = [
                        'price',
                        'fare',
                        'charge',
                        'cost',
                        'fee',
                        'rate',
                        'amount',
                        'subtotal',
                        'total',
                        'nightly',
                        'daily',
                        'hourly',
                    ];
                    $excludeTokens = [
                        'rating',
                        'review',
                        'reviews',
                        'star',
                        'stars',
                        'bed',
                        'beds',
                        'guest',
                        'guests',
                        'room',
                        'rooms',
                        'count',
                        'qty',
                        'quantity',
                        'capacity',
                        'occupancy',
                        'distance',
                        'latitude',
                        'longitude',
                        'lat',
                        'lng',
                        'adults',
                        'children',
                        'infants',
                        'nights',
                        'days',
                        'hours',
                        'minutes',
                        'duration',
                    ];

                    $hasPriceToken = false;
                    foreach ($keyTokens as $token) {
                        if (in_array($token, $priceTokens, true)) {
                            $hasPriceToken = true;
                            break;
                        }
                    }

                    $hasExcludedToken = false;
                    foreach ($keyTokens as $token) {
                        if (in_array($token, $excludeTokens, true)) {
                            $hasExcludedToken = true;
                            break;
                        }
                    }

                    $looksLikePriceField = $hasPriceToken && !$hasExcludedToken;

                    if (!$looksLikePriceField) {
                        continue;
                    }

                    $normalized = $normalizePrice($nestedValue);
                    if ($normalized > 0) {
                        $candidates[] = $normalized;
                    }
                }

                return;
            }

            if (is_object($value)) {
                $collectNestedPriceCandidates((array) $value, $depth + 1);
            }
        };

        $collectNestedPriceCandidates($details);

        if ($candidates === []) {
            return 0.0;
        }

        return (float) min($candidates);
    }
}

if (!function_exists('workationPropertyLookupIds')) {
    function workationPropertyLookupIds(object $row): array
    {
        $candidates = [
            (int) ($row->id ?? 0),
            (int) ($row->dedicated_row_id ?? 0),
            (int) ($row->vendor_property_id ?? 0),
            (int) ($row->property_id ?? 0),
            (int) ($row->legacy_property_id ?? 0),
            (int) ($row->source_property_id ?? 0),
            (int) ($row->parent_property_id ?? 0),
        ];

        return array_values(array_filter(array_unique($candidates), static fn (int $id): bool => $id > 0));
    }
}

if (!function_exists('workationOverlappingReservationCount')) {
    function workationOverlappingReservationCount(int $vendorUserId, int $vendorPropertyId, Carbon $start, Carbon $endExclusive, ?int $roomId = null, ?int $serviceId = null): int
    {
        if ($vendorUserId <= 0 || $vendorPropertyId <= 0 || !Schema::hasTable('vendor_reservations')) {
            return 0;
        }

        $query = DB::table('vendor_reservations')
            ->where('vendor_user_id', $vendorUserId)
            ->where('vendor_property_id', $vendorPropertyId)
            ->whereNotIn('status', ['cancelled', 'rejected', 'expired', 'failed'])
            ->where('start_at', '<', $endExclusive->copy()->startOfDay())
            ->where('end_at', '>', $start->copy()->startOfDay());

        if (Schema::hasColumn('vendor_reservations', 'payment_status')) {
            $query->where('payment_status', 'paid');
        }

        if ($serviceId !== null && $serviceId > 0 && Schema::hasColumn('vendor_reservations', 'vendor_service_id')) {
            $query->where('vendor_service_id', $serviceId);
        }

        if ($roomId !== null && $roomId > 0) {
            $roomNeedle = '"room_id":' . $roomId;
            $query->where(function ($roomQuery) use ($roomNeedle) {
                $roomQuery->where('notes', 'like', '%' . $roomNeedle . '%')
                    ->orWhereNull('notes');
            });
        }

        return (int) $query->count();
    }
}

if (!function_exists('workationSlotAvailabilityCheck')) {
    function workationSlotAvailabilityCheck(int $vendorUserId, int $vendorPropertyId, Carbon $start, Carbon $endExclusive, int $unitsRequested = 1, ?int $roomCategoryId = null, ?int $serviceId = null, ?string $listingCategory = null, ?string $routeName = null): array
    {
        if ($vendorUserId <= 0 || $vendorPropertyId <= 0 || !Schema::hasTable('vendor_availability_slots')) {
            return ['ok' => true, 'reason' => null, 'checked' => false];
        }

        $slotDates = workationDateSeries($start, $endExclusive);
        if ($slotDates === []) {
            return ['ok' => true, 'reason' => null, 'checked' => false];
        }

        $hasRoomColumn = Schema::hasColumn('vendor_availability_slots', 'vendor_room_category_id');
        $hasListingCategoryColumn = Schema::hasColumn('vendor_availability_slots', 'listing_category');
        $hasRouteNameColumn = Schema::hasColumn('vendor_availability_slots', 'route_name');

        $normalizedListingCategory = trim((string) ($listingCategory ?? ''));
        $normalizedRouteName = trim((string) ($routeName ?? ''));

        $slotsQuery = DB::table('vendor_availability_slots')
            ->where('vendor_user_id', $vendorUserId)
            ->where('vendor_property_id', $vendorPropertyId)
            ->whereIn('slot_date', $slotDates);

        if ($serviceId !== null && $serviceId > 0 && Schema::hasColumn('vendor_availability_slots', 'vendor_service_id')) {
            $slotsQuery->where('vendor_service_id', $serviceId);
        }

        if ($roomCategoryId !== null && $roomCategoryId > 0 && $hasRoomColumn) {
            $slotsQuery->where(function ($query) use ($roomCategoryId) {
                $query->where('vendor_room_category_id', $roomCategoryId)
                    ->orWhereNull('vendor_room_category_id');
            });
        }

        if ($normalizedListingCategory !== '' && $hasListingCategoryColumn) {
            $slotsQuery->where(function ($query) use ($normalizedListingCategory) {
                $query->where('listing_category', $normalizedListingCategory)
                    ->orWhereNull('listing_category')
                    ->orWhere('listing_category', '');
            });
        }

        if ($normalizedRouteName !== '' && $hasRouteNameColumn) {
            $slotsQuery->where(function ($query) use ($normalizedRouteName) {
                $query->where('route_name', $normalizedRouteName)
                    ->orWhereNull('route_name')
                    ->orWhere('route_name', '');
            });
        }

        $slotColumns = ['slot_date', 'inventory', 'reserved_count', 'is_closed'];
        if ($hasRoomColumn) {
            $slotColumns[] = 'vendor_room_category_id';
        }
        if (Schema::hasColumn('vendor_availability_slots', 'vendor_service_id')) {
            $slotColumns[] = 'vendor_service_id';
        }
        if ($hasListingCategoryColumn) {
            $slotColumns[] = 'listing_category';
        }
        if ($hasRouteNameColumn) {
            $slotColumns[] = 'route_name';
        }

        $slots = $slotsQuery->get($slotColumns);
        if ($slots->isEmpty()) {
            return ['ok' => true, 'reason' => null, 'checked' => false];
        }

        $byDate = $slots->groupBy(static fn ($slot) => (string) ($slot->slot_date ?? ''));
        foreach ($slotDates as $slotDate) {
            $candidates = collect($byDate->get($slotDate, []));
            if ($candidates->isEmpty()) {
                continue;
            }

            $blockedSlot = $candidates->first(static fn ($slot) => (bool) ($slot->is_closed ?? false));
            if ($blockedSlot) {
                return ['ok' => false, 'reason' => 'blocked', 'checked' => true, 'date' => $slotDate];
            }

            $slot = $candidates->first(function ($candidate) use ($hasRoomColumn, $roomCategoryId, $hasListingCategoryColumn, $normalizedListingCategory, $hasRouteNameColumn, $normalizedRouteName) {
                if ($hasRoomColumn && $roomCategoryId !== null && $roomCategoryId > 0) {
                    if ((int) ($candidate->vendor_room_category_id ?? 0) !== $roomCategoryId) {
                        return false;
                    }
                }

                if ($hasListingCategoryColumn && $normalizedListingCategory !== '') {
                    if (trim((string) ($candidate->listing_category ?? '')) !== $normalizedListingCategory) {
                        return false;
                    }
                }

                if ($hasRouteNameColumn && $normalizedRouteName !== '') {
                    if (trim((string) ($candidate->route_name ?? '')) !== $normalizedRouteName) {
                        return false;
                    }
                }

                return true;
            });

            if (!$slot) {
                $slot = $candidates->first(function ($candidate) use ($hasRoomColumn, $roomCategoryId) {
                    if ($hasRoomColumn && $roomCategoryId !== null && $roomCategoryId > 0) {
                        return (int) ($candidate->vendor_room_category_id ?? 0) === 0;
                    }

                    return true;
                });
            }

            if (!$slot) {
                continue;
            }

            $inventory = max(0, (int) ($slot->inventory ?? 0));
            $reservedCount = max(0, (int) ($slot->reserved_count ?? 0));
            if ($inventory > 0 && ($reservedCount + max(1, $unitsRequested)) > $inventory) {
                return ['ok' => false, 'reason' => 'inventory', 'checked' => true, 'date' => $slotDate];
            }
        }

        return ['ok' => true, 'reason' => null, 'checked' => true];
    }
}

if (!function_exists('workationReserveAvailabilitySlots')) {
    function workationReserveAvailabilitySlots(int $vendorUserId, int $vendorPropertyId, Carbon $start, Carbon $endExclusive, int $units = 1, ?int $roomCategoryId = null, ?int $serviceId = null, ?string $listingCategory = null, ?string $routeName = null): void
    {
        if ($vendorUserId <= 0 || $vendorPropertyId <= 0 || $units <= 0 || !Schema::hasTable('vendor_availability_slots')) {
            return;
        }

        $hasRoomColumn = Schema::hasColumn('vendor_availability_slots', 'vendor_room_category_id');
        $hasListingCategoryColumn = Schema::hasColumn('vendor_availability_slots', 'listing_category');
        $hasRouteNameColumn = Schema::hasColumn('vendor_availability_slots', 'route_name');
        $normalizedListingCategory = trim((string) ($listingCategory ?? ''));
        $normalizedRouteName = trim((string) ($routeName ?? ''));

        foreach (workationDateSeries($start, $endExclusive) as $slotDate) {
            $baseQuery = DB::table('vendor_availability_slots')
                ->where('vendor_user_id', $vendorUserId)
                ->where('vendor_property_id', $vendorPropertyId)
                ->where('slot_date', $slotDate);

            if ($serviceId !== null && $serviceId > 0 && Schema::hasColumn('vendor_availability_slots', 'vendor_service_id')) {
                $baseQuery->where('vendor_service_id', $serviceId);
            }

            $updatePayload = [
                'reserved_count' => DB::raw('COALESCE(reserved_count, 0) + ' . (int) $units),
                'updated_at' => now(),
            ];

            if ($roomCategoryId !== null && $roomCategoryId > 0 && $hasRoomColumn) {
                $exactQuery = clone $baseQuery;
                $exactQuery->where('vendor_room_category_id', $roomCategoryId);

                if ($normalizedListingCategory !== '' && $hasListingCategoryColumn) {
                    $exactQuery->where('listing_category', $normalizedListingCategory);
                }

                if ($normalizedRouteName !== '' && $hasRouteNameColumn) {
                    $exactQuery->where('route_name', $normalizedRouteName);
                }

                $updated = $exactQuery->update($updatePayload);
                if ($updated > 0) {
                    continue;
                }

                $fallbackQuery = clone $baseQuery;
                $fallbackQuery->whereNull('vendor_room_category_id');

                if ($normalizedListingCategory !== '' && $hasListingCategoryColumn) {
                    $fallbackQuery->where(function ($query) use ($normalizedListingCategory) {
                        $query->where('listing_category', $normalizedListingCategory)
                            ->orWhereNull('listing_category')
                            ->orWhere('listing_category', '');
                    });
                }

                if ($normalizedRouteName !== '' && $hasRouteNameColumn) {
                    $fallbackQuery->where(function ($query) use ($normalizedRouteName) {
                        $query->where('route_name', $normalizedRouteName)
                            ->orWhereNull('route_name')
                            ->orWhere('route_name', '');
                    });
                }

                $fallbackQuery->update($updatePayload);
                continue;
            }

            if ($normalizedListingCategory !== '' && $hasListingCategoryColumn) {
                $baseQuery->where('listing_category', $normalizedListingCategory);
            }

            if ($normalizedRouteName !== '' && $hasRouteNameColumn) {
                $baseQuery->where('route_name', $normalizedRouteName);
            }

            $baseQuery->update($updatePayload);
        }
    }
}

if (!function_exists('bootstrapPasswordMatches')) {
    function bootstrapPasswordMatches(string $expected, string $provided): bool
    {
        if ($expected === '') {
            return false;
        }

        $isHash = str_starts_with($expected, '$2y$') || str_starts_with($expected, '$argon2');
        if ($isHash) {
            return Hash::check($provided, $expected);
        }

        return hash_equals($expected, $provided);
    }
}

if (!function_exists('normalizePortalRoleValue')) {
    function normalizePortalRoleValue(string $role): string
    {
        $normalized = strtoupper(trim($role));
        $normalized = preg_replace('/[^A-Z0-9]+/', '_', $normalized) ?? $normalized;
        $normalized = trim($normalized, '_');

        $aliases = [
            'ADMIN_FINACE' => 'ADMIN_FINANCE',
            'ADMINFINACE' => 'ADMIN_FINANCE',
            'ADMINFINANCE' => 'ADMIN_FINANCE',
            'ADMINMEDIA' => 'ADMIN_MEDIA',
            'MEDIA_ADMIN' => 'ADMIN_MEDIA',
        ];

        return $aliases[$normalized] ?? $normalized;
    }
}

if (!function_exists('generatePortalUsernameFromEmail')) {
    function generatePortalUsernameFromEmail(string $email): string
    {
        $baseUsername = \Illuminate\Support\Str::of(strtolower((string) \Illuminate\Support\Str::before($email, '@')))
            ->replaceMatches('/[^a-z0-9_]+/', '_')
            ->trim('_')
            ->value();

        if ($baseUsername === '') {
            $baseUsername = 'user';
        }

        $username = $baseUsername;
        $suffix = 1;
        while (\App\Models\User::where('username', $username)->exists()) {
            $username = $baseUsername . '_' . $suffix;
            $suffix++;
        }

        return $username;
    }
}

if (!function_exists('sendPortalPasswordResetFallbackMail')) {
    function sendPortalPasswordResetFallbackMail(string $email, string $portal, string $resetUrl, ?string $displayName = null): bool
    {
        $normalizedPortal = in_array($portal, ['admin', 'vendor', 'customer'], true) ? $portal : 'admin';
        $portalLabel = ucfirst($normalizedPortal);
        $name = trim((string) $displayName);
        $greeting = $name !== '' ? "Hi {$name}," : 'Hello,';

        workationSendBrandedMail($email, 'Reset Your ' . $portalLabel . ' Password | Workation', [
            'preheader' => 'Reset your ' . $portalLabel . ' password securely.',
            'headline' => 'Password reset request',
            'intro' => 'We received a request to reset your ' . $portalLabel . ' account password on Workation.',
            'statusLabel' => 'Action required',
            'statusTone' => 'warning',
            'bodyLines' => [
                $greeting,
                'Use the link below to set a new password. This link expires in 60 minutes.',
                'If you did not request a password reset, you can safely ignore this email.',
            ],
            'metaRows' => [
                'Portal' => $portalLabel,
                'Reset link' => $resetUrl,
                'Expires' => '60 minutes',
            ],
            'ctaUrl' => $resetUrl,
            'ctaLabel' => 'Reset Password',
        ]);

        return true;
    }
}

if (!function_exists('supportedVendorSocialProviders')) {
    function supportedVendorSocialProviders(): array
    {
        return ['google', 'facebook', 'apple'];
    }
}

if (!function_exists('supportedCustomerSocialProviders')) {
    function supportedCustomerSocialProviders(): array
    {
        return ['google', 'facebook'];
    }
}

if (!function_exists('portalOAuthIntentSessionKey')) {
    function portalOAuthIntentSessionKey(string $provider): string
    {
        return 'portal_oauth_intent_' . strtolower(trim($provider));
    }
}

if (!function_exists('customerPostAuthRedirectSessionKey')) {
    function customerPostAuthRedirectSessionKey(): string
    {
        return 'customer_post_auth_redirect';
    }
}

if (!function_exists('normalizeCustomerPostAuthRedirect')) {
    function normalizeCustomerPostAuthRedirect(?string $target): ?string
    {
        $value = trim((string) $target);
        if ($value === '') {
            return null;
        }

        // Allow in-app relative URLs only.
        if (str_starts_with($value, '/')) {
            if (str_starts_with($value, '//') || str_starts_with($value, '/portal/')) {
                return null;
            }
            return $value;
        }

        // Allow absolute URLs only when host matches APP_URL.
        $host = strtolower((string) parse_url($value, PHP_URL_HOST));
        $appHost = strtolower((string) parse_url((string) config('app.url', ''), PHP_URL_HOST));
        if ($host === '' || $appHost === '' || $host !== $appHost) {
            return null;
        }

        $path = (string) parse_url($value, PHP_URL_PATH);
        if ($path === '' || str_starts_with($path, '/portal/')) {
            return null;
        }

        $query = (string) parse_url($value, PHP_URL_QUERY);
        return $query !== '' ? ($path . '?' . $query) : $path;
    }
}

if (!function_exists('rememberCustomerPostAuthRedirect')) {
    function rememberCustomerPostAuthRedirect(Request $request): void
    {
        $candidate = normalizeCustomerPostAuthRedirect((string) $request->query('continue', ''));

        if ($candidate === null) {
            $candidate = normalizeCustomerPostAuthRedirect((string) $request->headers->get('referer', ''));
        }

        if ($candidate !== null) {
            $request->session()->put(customerPostAuthRedirectSessionKey(), $candidate);
        }
    }
}

if (!function_exists('consumeCustomerPostAuthRedirect')) {
    function consumeCustomerPostAuthRedirect(Request $request, string $fallback = '/'): string
    {
        $stored = normalizeCustomerPostAuthRedirect((string) $request->session()->pull(customerPostAuthRedirectSessionKey(), ''));
        return $stored ?: $fallback;
    }
}

if (!function_exists('customerSocialRedirectUrl')) {
    function customerSocialRedirectUrl(string $provider): string
    {
        $provider = strtolower(trim($provider));

        return (string) config(
            'services.' . $provider . '.customer_redirect',
            (string) config('services.' . $provider . '.redirect', url('/portal/customer/oauth/' . $provider . '/callback'))
        );
    }
}

if (!function_exists('isCustomerSocialProviderConfigured')) {
    function isCustomerSocialProviderConfigured(string $provider): bool
    {
        return match ($provider) {
            'google' => trim((string) config('services.google.client_id', '')) !== ''
                && trim((string) config('services.google.client_secret', '')) !== '',
            'facebook' => trim((string) config('services.facebook.client_id', '')) !== ''
                && trim((string) config('services.facebook.client_secret', '')) !== '',
            default => false,
        };
    }
}

if (!function_exists('customerSocialProviderColumn')) {
    function customerSocialProviderColumn(string $provider): string
    {
        return match (strtolower(trim($provider))) {
            'google' => 'google_oauth_id',
            'facebook' => 'facebook_oauth_id',
            default => '',
        };
    }
}

if (!function_exists('customerVerificationStateCacheKey')) {
    function customerVerificationStateCacheKey(string $email): string
    {
        return 'customer_email_verified:' . sha1(strtolower(trim($email)));
    }
}

if (!function_exists('customerProfileMetaCacheKey')) {
    function customerProfileMetaCacheKey(string $customerId): string
    {
        return 'customer_profile_meta:' . sha1(trim($customerId));
    }
}

if (!function_exists('customerTableName')) {
    function customerTableName(): string
    {
        return (new \App\Models\Customer())->getTable();
    }
}

if (!function_exists('customerConnectionName')) {
    function customerConnectionName(): ?string
    {
        return (new \App\Models\Customer())->getConnectionName();
    }
}

if (!function_exists('customerSchemaHasColumn')) {
    function customerSchemaHasColumn(string $column): bool
    {
        static $columnCache = [];

        if (array_key_exists($column, $columnCache)) {
            return $columnCache[$column];
        }

        $connection = customerConnectionName();
        $table = customerTableName();

        $exists = $connection
            ? Schema::connection($connection)->hasColumn($table, $column)
            : Schema::hasColumn($table, $column);

        $columnCache[$column] = $exists;

        return $exists;
    }
}

if (!function_exists('customerTableInsert')) {
    function customerTableInsert(array $payload): void
    {
        $connection = customerConnectionName();
        $table = customerTableName();

        if ($connection) {
            DB::connection($connection)->table($table)->insert($payload);
            return;
        }

        DB::table($table)->insert($payload);
    }
}

if (!function_exists('customerVerificationTokenCacheKey')) {
    function customerVerificationTokenCacheKey(string $email): string
    {
        return 'customer_email_verify_token:' . sha1(strtolower(trim($email)));
    }
}

if (!function_exists('customerEmailIsVerified')) {
    function customerEmailIsVerified(\App\Models\Customer $customer): bool
    {
        if (customerSchemaHasColumn('email_verified_at') && !empty($customer->email_verified_at)) {
            return true;
        }

        if (customerSchemaHasColumn('emailVerifiedAt') && !empty($customer->emailVerifiedAt)) {
            return true;
        }

        if (customerSchemaHasColumn('emailVerified') && (bool) ($customer->emailVerified ?? false)) {
            return true;
        }

        $email = strtolower(trim((string) ($customer->email ?? '')));
        if ($email === '') {
            return false;
        }

        return (bool) cache()->get(customerVerificationStateCacheKey($email), false);
    }
}

if (!function_exists('customerMarkEmailVerified')) {
    function customerMarkEmailVerified(\App\Models\Customer $customer): void
    {
        $email = strtolower(trim((string) ($customer->email ?? '')));
        if ($email === '') {
            return;
        }

        $now = now();
        $dirty = false;

        if (customerSchemaHasColumn('email_verified_at') && empty($customer->email_verified_at)) {
            $customer->email_verified_at = $now;
            $dirty = true;
        }
        if (customerSchemaHasColumn('emailVerifiedAt') && empty($customer->emailVerifiedAt)) {
            $customer->emailVerifiedAt = $now;
            $dirty = true;
        }
        if (customerSchemaHasColumn('emailVerified') && !(bool) ($customer->emailVerified ?? false)) {
            $customer->emailVerified = true;
            $dirty = true;
        }

        if ($dirty) {
            $customer->save();
        }

        cache()->forever(customerVerificationStateCacheKey($email), true);
        cache()->forget(customerVerificationTokenCacheKey($email));
    }
}

if (!function_exists('customerIssueEmailVerificationToken')) {
    function customerIssueEmailVerificationToken(string $email): string
    {
        $normalizedEmail = strtolower(trim($email));
        $token = Str::random(64);

        cache()->put(customerVerificationTokenCacheKey($normalizedEmail), [
            'hash' => Hash::make($token),
            'created_at' => now()->toIso8601String(),
        ], now()->addHours(24));

        return $token;
    }
}

if (!function_exists('sendCustomerPortalRegistrationNotification')) {
    function sendCustomerPortalRegistrationNotification(string $email, string $name, bool $requireVerification = false): ?string
    {
        $recipient = strtolower(trim($email));
        if ($recipient === '') {
            return null;
        }

        $displayName = trim($name) !== '' ? trim($name) : 'Customer';
        $verificationToken = $requireVerification ? customerIssueEmailVerificationToken($recipient) : '';
        $verificationUrl = $verificationToken !== ''
            ? url('/portal/customer/verify-email?email=' . rawurlencode($recipient) . '&token=' . rawurlencode($verificationToken))
            : '';

        try {
            workationSendBrandedMail($recipient, 'Workation Member Account Verification', [
                'preheader' => 'Verify your customer account to start booking.',
                'headline' => 'Welcome to Workation',
                'intro' => 'Your Workation member account has been created successfully.',
                'statusLabel' => 'Verify email',
                'statusTone' => 'info',
                'bodyLines' => array_values(array_filter([
                    'Hi ' . $displayName . ',',
                    $verificationUrl !== ''
                        ? 'Before signing in, verify your email address using the secure link below.'
                        : 'You can now sign in to your customer portal and start booking experiences.',
                    'If you did not create this account, please contact support immediately.',
                ])),
                'metaRows' => $verificationUrl !== '' ? [
                    'Verification link' => $verificationUrl,
                    'Expires' => '24 hours',
                ] : [],
                'ctaUrl' => $verificationUrl,
                'ctaLabel' => 'Verify Email Address',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to send customer portal registration email.', [
                'email' => $recipient,
                'error' => $e->getMessage(),
            ]);
        }

        return $verificationToken !== '' ? $verificationToken : null;
    }
}

if (!function_exists('findCustomerByEmail')) {
    function findCustomerByEmail(string $email): ?\App\Models\Customer
    {
        $normalized = strtolower(trim($email));
        if ($normalized === '') {
            return null;
        }

        $query = \App\Models\Customer::query();
        $customer = $query->where('email', $normalized)->first();

        if ($customer) {
            return $customer;
        }

        return \App\Models\Customer::query()
            ->whereRaw('LOWER(email) = ?', [$normalized])
            ->first();
    }
}

if (!function_exists('findActiveVendorByEmail')) {
    function findActiveVendorByEmail(string $email): ?\App\Models\User
    {
        $normalized = strtolower(trim($email));
        if ($normalized === '') {
            return null;
        }

        $vendor = \App\Models\User::query()
            ->where('email', $normalized)
            ->where('portal_enabled', true)
            ->whereIn('portal_role', ['VENDOR', 'vendor'])
            ->first();

        if ($vendor) {
            return $vendor;
        }

        return \App\Models\User::query()
            ->whereRaw('LOWER(email) = ?', [$normalized])
            ->where('portal_enabled', true)
            ->whereRaw('UPPER(portal_role) = ?', ['VENDOR'])
            ->first();
    }
}

if (!function_exists('upsertCustomerFromVendorIdentity')) {
    function upsertCustomerFromVendorIdentity(\App\Models\User $vendorUser, string $password): ?\App\Models\Customer
    {
        $email = strtolower(trim((string) $vendorUser->email));
        if ($email === '') {
            return null;
        }

        $customer = findCustomerByEmail($email);

        if (!$customer) {
            $now = now();
            $payload = [
                'email' => $email,
                'name' => trim((string) $vendorUser->name) !== '' ? trim((string) $vendorUser->name) : 'Customer',
                'password' => Hash::make($password),
            ];

            if (customerSchemaHasColumn('id')) {
                $payload['id'] = (string) Str::uuid();
            }
            if (customerSchemaHasColumn('createdAt')) {
                $payload['createdAt'] = $now;
            }
            if (customerSchemaHasColumn('updatedAt')) {
                $payload['updatedAt'] = $now;
            }
            if (customerSchemaHasColumn('created_at')) {
                $payload['created_at'] = $now;
            }
            if (customerSchemaHasColumn('updated_at')) {
                $payload['updated_at'] = $now;
            }

            if (customerSchemaHasColumn('email_verified_at')) {
                $payload['email_verified_at'] = $now;
            }
            if (customerSchemaHasColumn('emailVerifiedAt')) {
                $payload['emailVerifiedAt'] = $now;
            }
            if (customerSchemaHasColumn('emailVerified')) {
                $payload['emailVerified'] = true;
            }

            customerTableInsert($payload);
            $customer = findCustomerByEmail($email);
        }

        if (!$customer) {
            return null;
        }

        $needsSave = false;
        if (!Hash::check($password, (string) $customer->password)) {
            $customer->password = Hash::make($password);
            $needsSave = true;
        }
        if (trim((string) $customer->name) === '' && trim((string) $vendorUser->name) !== '') {
            $customer->name = trim((string) $vendorUser->name);
            $needsSave = true;
        }

        if ($needsSave) {
            $customer->save();
        }

        customerMarkEmailVerified($customer);

        return $customer;
    }
}

if (!function_exists('syncVendorPasswordFromCustomer')) {
    function syncVendorPasswordFromCustomer(\App\Models\User $vendorUser, string $password): void
    {
        if (Hash::check($password, (string) $vendorUser->password)) {
            return;
        }

        $vendorUser->password = Hash::make($password);
        $vendorUser->save();
    }
}

if (!function_exists('provisionCustomerAccountFromBooking')) {
    function provisionCustomerAccountFromBooking(string $email, string $name): ?\App\Models\Customer
    {
        $normalizedEmail = strtolower(trim($email));
        if ($normalizedEmail === '') {
            return null;
        }

        $displayName = trim($name) !== '' ? trim($name) : 'Customer';
        $customer = findCustomerByEmail($normalizedEmail);
        $created = false;

        if (!$customer) {
            $now = now();
            $payload = [
                'email' => $normalizedEmail,
                'name' => $displayName,
                'password' => Hash::make(Str::random(40)),
            ];

            if (customerSchemaHasColumn('id')) {
                $payload['id'] = (string) Str::uuid();
            }
            if (customerSchemaHasColumn('createdAt')) {
                $payload['createdAt'] = $now;
            }
            if (customerSchemaHasColumn('updatedAt')) {
                $payload['updatedAt'] = $now;
            }
            if (customerSchemaHasColumn('created_at')) {
                $payload['created_at'] = $now;
            }
            if (customerSchemaHasColumn('updated_at')) {
                $payload['updated_at'] = $now;
            }

            customerTableInsert($payload);
            $customer = findCustomerByEmail($normalizedEmail);
            $created = true;
        }

        if (!$customer) {
            return null;
        }

        if (trim((string) ($customer->name ?? '')) === '' && $displayName !== '') {
            $customer->name = $displayName;
            $customer->save();
        }

        if ($created) {
            sendCustomerPortalRegistrationNotification($normalizedEmail, $displayName, true);

            try {
                $token = Password::broker('customer_users')->createToken($customer);
                $customer->sendPasswordResetNotification($token);
            } catch (\Throwable $e) {
                Log::warning('Failed to send customer password setup link after booking.', [
                    'email' => $normalizedEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $customer;
    }
}

if (!function_exists('vendorSocialRedirectUrl')) {
    function vendorSocialRedirectUrl(string $provider): string
    {
        return (string) config('services.' . $provider . '.redirect', url('/portal/vendor/oauth/' . $provider . '/callback'));
    }
}

if (!function_exists('isVendorSocialProviderConfigured')) {
    function isVendorSocialProviderConfigured(string $provider): bool
    {
        return match ($provider) {
            'google' => trim((string) config('services.google.client_id', '')) !== ''
                && trim((string) config('services.google.client_secret', '')) !== '',
            'facebook' => trim((string) config('services.facebook.client_id', '')) !== ''
                && trim((string) config('services.facebook.client_secret', '')) !== '',
            'apple' => trim((string) config('services.apple.client_id', '')) !== ''
                && trim((string) config('services.apple.team_id', '')) !== ''
                && trim((string) config('services.apple.key_id', '')) !== ''
                && trim((string) config('services.apple.private_key', '')) !== '',
            default => false,
        };
    }
}

if (!function_exists('vendorSocialHealthSnapshot')) {
    function vendorSocialHealthSnapshot(): array
    {
        $appUrl = rtrim((string) config('app.url', ''), '/');
        $appHost = strtolower((string) parse_url($appUrl, PHP_URL_HOST));

        $providers = [];
        foreach (supportedVendorSocialProviders() as $provider) {
            $redirect = vendorSocialRedirectUrl($provider);
            $redirectHost = strtolower((string) parse_url($redirect, PHP_URL_HOST));

            $providers[$provider] = [
                'configured' => isVendorSocialProviderConfigured($provider),
                'redirect' => $redirect,
                'redirect_uses_https' => str_starts_with(strtolower($redirect), 'https://'),
                'redirect_host_matches_app' => $appHost !== '' && $redirectHost === $appHost,
            ];
        }

        return [
            'ok' => true,
            'app_url' => $appUrl,
            'providers' => $providers,
        ];
    }
}

if (!function_exists('vendorEmailOtpCacheKey')) {
    function vendorEmailOtpCacheKey(string $email): string
    {
        return 'vendor_email_otp:' . sha1(strtolower(trim($email)));
    }
}

if (!function_exists('portalCanonicalHostRedirect')) {
    function portalCanonicalHostRedirect(Request $request): ?\Illuminate\Http\RedirectResponse
    {
        if (strtolower((string) config('app.env', 'production')) !== 'production') {
            return null;
        }

        $appUrl = trim((string) config('app.url', ''));
        $canonicalHost = strtolower((string) parse_url($appUrl, PHP_URL_HOST));
        if ($canonicalHost === '') {
            return null;
        }

        $requestHost = strtolower((string) $request->getHost());
        if ($requestHost === '' || $requestHost === $canonicalHost) {
            return null;
        }

        if (!in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return null;
        }

        $canonicalScheme = strtolower((string) parse_url($appUrl, PHP_URL_SCHEME));
        if ($canonicalScheme === '') {
            $canonicalScheme = $request->getScheme();
        }

        return redirect()->to($canonicalScheme . '://' . $canonicalHost . $request->getRequestUri(), 302);
    }
}

if (!function_exists('canReviewVendorRegistrations')) {
    function canReviewVendorRegistrations(): bool
    {
        if (!session('portal_admin_authenticated', false)) {
            return false;
        }

        $role = normalizePortalRoleValue((string) session('portal_admin_role', ''));
        return in_array($role, ['ADMIN_SUPER', 'ADMIN', 'ADMIN_CARE'], true);
    }
}

if (!function_exists('currentPortalAdminRole')) {
    function currentPortalAdminRole(): string
    {
        return normalizePortalRoleValue((string) session('portal_admin_role', ''));
    }
}

if (!function_exists('canManageVendorUsers')) {
    function canManageVendorUsers(): bool
    {
        if (!session('portal_admin_authenticated', false)) {
            return false;
        }

        $role = currentPortalAdminRole();
        return in_array($role, ['ADMIN_SUPER', 'ADMIN', 'ADMIN_CARE'], true);
    }
}

if (!function_exists('canCreateVendorUsers')) {
    function canCreateVendorUsers(): bool
    {
        if (!session('portal_admin_authenticated', false)) {
            return false;
        }

        $role = currentPortalAdminRole();
        return in_array($role, ['ADMIN_SUPER', 'ADMIN'], true);
    }
}

if (!function_exists('canApproveVendorRegistrationRequest')) {
    function canApproveVendorRegistrationRequest(): bool
    {
        if (!session('portal_admin_authenticated', false)) {
            return false;
        }

        $role = currentPortalAdminRole();
        return in_array($role, ['ADMIN_SUPER', 'ADMIN'], true);
    }
}

if (!function_exists('canApproveVendorDeleteRequest')) {
    function canApproveVendorDeleteRequest(): bool
    {
        if (!session('portal_admin_authenticated', false)) {
            return false;
        }

        return currentPortalAdminRole() === 'ADMIN_SUPER';
    }
}

if (!function_exists('canRequestVendorDeleteApproval')) {
    function canRequestVendorDeleteApproval(): bool
    {
        if (!session('portal_admin_authenticated', false)) {
            return false;
        }

        return in_array(currentPortalAdminRole(), ['ADMIN_SUPER', 'ADMIN'], true);
    }
}

if (!function_exists('canModeratePortalFinance')) {
    function canModeratePortalFinance(): bool
    {
        if (!session('portal_admin_authenticated', false)) {
            return false;
        }

        return in_array(currentPortalAdminRole(), ['ADMIN_SUPER', 'ADMIN_FINANCE'], true);
    }
}

if (!function_exists('canModerateListings')) {
    function canModerateListings(): bool
    {
        if (!session('portal_admin_authenticated', false)) {
            return false;
        }

        return in_array(currentPortalAdminRole(), ['ADMIN_SUPER', 'ADMIN', 'ADMIN_CARE'], true);
    }
}

if (!function_exists('canManageContent')) {
    function canManageContent(): bool
    {
        if (!session('portal_admin_authenticated', false)) {
            return false;
        }

        return in_array(currentPortalAdminRole(), ['ADMIN_SUPER', 'ADMIN_MEDIA'], true);
    }
}

if (!function_exists('canEditorialReview')) {
    function canEditorialReview(): bool
    {
        if (!session('portal_admin_authenticated', false)) {
            return false;
        }

        return currentPortalAdminRole() === 'ADMIN_SUPER';
    }
}

if (!function_exists('portalFinancePolicySettingKey')) {
    function portalFinancePolicySettingKey(): string
    {
        return 'reservation_tax_transfer_policy';
    }
}

if (!function_exists('portalFinanceLoadReservationPolicy')) {
    function portalFinanceLoadReservationPolicy(): array
    {
        return ReservationPricingPolicy::loadPolicy();
    }
}

if (!function_exists('portalFinanceSaveReservationPolicy')) {
    function portalFinanceSaveReservationPolicy(array $policy, ?int $actorUserId = null): void
    {
        if (!Schema::hasTable('portal_finance_settings')) {
            return;
        }

        DB::table('portal_finance_settings')->updateOrInsert(
            ['setting_key' => portalFinancePolicySettingKey()],
            [
                'value_decimal' => null,
                'value_string' => null,
                'value_json' => json_encode(ReservationPricingPolicy::normalizePolicy($policy)),
                'updated_by_user_id' => $actorUserId,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}

if (!function_exists('portalFinanceTaxComponents')) {
    function portalFinanceTaxComponents(?array $policy = null): array
    {
        $effectivePolicy = ReservationPricingPolicy::normalizePolicy($policy ?? portalFinanceLoadReservationPolicy());
        $components = $effectivePolicy['tax_components'] ?? [];

        return is_array($components) ? array_values($components) : [];
    }
}

if (!function_exists('portalFinanceUpsertTaxComponent')) {
    function portalFinanceUpsertTaxComponent(array $component, ?int $actorUserId = null): array
    {
        $policy = portalFinanceLoadReservationPolicy();
        $existing = portalFinanceTaxComponents($policy);
        $normalized = ReservationPricingPolicy::normalizeTaxComponents([$component]);
        if ($normalized === []) {
            return $policy;
        }

        $candidate = $normalized[0];
        $code = (string) ($candidate['code'] ?? '');

        $updated = [];
        $replaced = false;
        foreach ($existing as $row) {
            if (!is_array($row)) {
                continue;
            }

            if ((string) ($row['code'] ?? '') === $code) {
                $updated[] = $candidate;
                $replaced = true;
            } else {
                $updated[] = $row;
            }
        }

        if (!$replaced) {
            $updated[] = $candidate;
        }

        $policy['tax_components'] = array_values($updated);
        portalFinanceSaveReservationPolicy($policy, $actorUserId);

        return $policy;
    }
}

if (!function_exists('portalFinanceDeleteTaxComponent')) {
    function portalFinanceDeleteTaxComponent(string $code, ?int $actorUserId = null): array
    {
        $policy = portalFinanceLoadReservationPolicy();
        $existing = portalFinanceTaxComponents($policy);
        $code = strtolower(trim($code));

        $policy['tax_components'] = array_values(array_filter($existing, static function ($row) use ($code): bool {
            return is_array($row) && strtolower(trim((string) ($row['code'] ?? ''))) !== $code;
        }));

        portalFinanceSaveReservationPolicy($policy, $actorUserId);

        return $policy;
    }
}

if (!function_exists('portalActionRequestsEnabled')) {
    function portalActionRequestsEnabled(): bool
    {
        return Schema::hasTable('portal_admin_action_requests');
    }
}

if (!function_exists('createPortalActionRequest')) {
    function createPortalActionRequest(
        string $actionType,
        ?int $targetUserId,
        ?int $targetRegistrationId,
        ?string $targetIdentifier,
        ?string $reason,
        ?array $payload = null
    ): int {
        return (int) DB::table('portal_admin_action_requests')->insertGetId([
            'action_type' => $actionType,
            'requested_by_user_id' => is_numeric(session('portal_admin_user_id')) ? (int) session('portal_admin_user_id') : null,
            'requested_by_role' => (string) session('portal_admin_role', ''),
            'target_user_id' => $targetUserId,
            'target_registration_id' => $targetRegistrationId,
            'target_identifier' => $targetIdentifier,
            'reason' => $reason,
            'payload' => $payload ? json_encode($payload) : null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

if (!function_exists('portalAdminAuditLog')) {
    function portalAdminAuditLog(string $action, array $context = []): void
    {
        if (!Schema::hasTable('portal_admin_audit_logs')) {
            return;
        }

        $actorUserId = session('portal_admin_user_id');
        $actorRole = session('portal_admin_role');
        $actorName = session('portal_admin_user');

        $targetUserId = $context['target_user_id'] ?? null;
        $targetIdentifier = $context['target_identifier'] ?? null;
        $targetRole = $context['target_role'] ?? null;
        unset($context['target_user_id'], $context['target_identifier'], $context['target_role']);

        try {
            DB::table('portal_admin_audit_logs')->insert([
                'actor_user_id' => is_numeric($actorUserId) ? (int) $actorUserId : null,
                'actor_name' => is_string($actorName) ? $actorName : null,
                'actor_role' => is_string($actorRole) ? $actorRole : null,
                'action' => $action,
                'target_user_id' => is_numeric($targetUserId) ? (int) $targetUserId : null,
                'target_identifier' => is_string($targetIdentifier) ? $targetIdentifier : null,
                'target_role' => is_string($targetRole) ? $targetRole : null,
                'details' => empty($context) ? null : json_encode($context),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to write portal admin audit log.', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

if (!function_exists('vendorMediaStorageUrlFromPath')) {
    function vendorMediaStorageUrlFromPath(?string $path): ?string
    {
        $normalized = trim(str_replace('\\', '/', (string) $path));
        if ($normalized === '') {
            return null;
        }

        // Preserve already-resolved app media URLs as-is.
        if (str_starts_with($normalized, '/media/')) {
            return $normalized;
        }
        if (str_starts_with($normalized, '/storage/')) {
            $storagePath = ltrim(substr($normalized, strlen('/storage/')), '/');
            if ($storagePath === '') {
                return null;
            }

            return '/media/portal-public/' . implode('/', array_map('rawurlencode', explode('/', $storagePath)));
        }

        if (str_starts_with($normalized, 'http://')) {
            return 'https://' . ltrim(substr($normalized, 7), '/');
        }

        if (str_starts_with($normalized, 'https://')) {
            return $normalized;
        }

        if (preg_match('#/storage/app/public/(.+)$#i', $normalized, $matches) === 1) {
            $normalized = (string) ($matches[1] ?? '');
        } elseif (preg_match('#/public/storage/(.+)$#i', $normalized, $matches) === 1) {
            $normalized = (string) ($matches[1] ?? '');
        }

        $normalized = ltrim($normalized, '/');
        if (str_starts_with($normalized, 'public/')) {
            $normalized = substr($normalized, 7);
        }
        if (str_starts_with($normalized, 'storage/')) {
            $normalized = substr($normalized, 8);
        }

        $normalized = ltrim($normalized, '/');

        if ($normalized === '') {
            return null;
        }

        return '/media/portal-public/' . implode('/', array_map('rawurlencode', explode('/', $normalized)));
    }
}

if (!function_exists('getAvailableCategories')) {
    function getAvailableCategories(): array
    {
        $defaultCategories = [];
        
        // Get all categories from the uniform icon system
        $allCategoryIcons = UniformIconSystem::getAllCategoryIcons();
        foreach ($allCategoryIcons as $key => $info) {
            $defaultCategories[$key] = [
                'label' => $info['label'] ?? ucfirst(str_replace('_', ' ', $key)),
                'icon' => $info['icon'] ?? 'fa-solid fa-location-dot',
                'subtitle' => match ($key) {
                    'accommodation' => 'Hotels, villas, guesthouses',
                    'marine-transport' => 'Speedboats, ferries, and water transfers',
                    'land-transport' => 'Cars, vans, and local ground transfers',
                    'excursion' => 'Diving, snorkel, island tours',
                    'remote_workspace' => 'Wi-Fi, desks, quiet corners',
                    'conference_room' => 'Meeting and event spaces',
                    'resort_day_visit' => 'Day access and passes',
                    'restaurant' => 'Dining and local cuisine',
                    'vehicle_rental' => 'Cars, bikes, vans and more',
                    default => '',
                },
                'color' => $info['color'] ?? '#0f6179',
            ];
        }

        try {
            $dbCategories = VendorPropertyCompatibilityReader::allActiveListings(600)
                ->pluck('listing_category')
                ->filter(static fn ($cat) => !empty(trim((string) $cat)))
                ->map(static fn ($cat) => strtolower(trim((string) $cat)))
                ->unique()
                ->values();

            if ($dbCategories->isEmpty()) {
                return $defaultCategories;
            }

            $extraCategories = $dbCategories
                ->reject(static fn ($key) => array_key_exists($key, $defaultCategories))
                ->mapWithKeys(static fn ($key) => [
                    $key => [
                        'label' => ucfirst(str_replace(['_', '-'], ' ', $key)),
                        'icon' => 'fa-solid fa-location-dot',
                        'subtitle' => '',
                        'color' => '#0f6179',
                    ],
                ])
                ->toArray();

            return array_merge($defaultCategories, $extraCategories);
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch available categories', ['error' => $e->getMessage()]);
            return $defaultCategories;
        }
    }
}

require __DIR__ . '/web/home.php';
Route::get('/privacy-policy', function () {
    return response()->view('privacy-policy');
});

Route::get('/terms-of-service', function () {
    return response()->view('terms-of-service');
});

Route::get('/things-to-do', function () {
    return redirect('/catalog/excursion?sort=most_wanted');
});

require __DIR__ . '/web/blog.php';

require __DIR__ . '/web/media.php';
require __DIR__ . '/web/customer.php';
Route::get('/media/vendor/{media}/{variant?}', function (int $media, ?string $variant = 'banner') {
        $placeholderResponse = static function () {
                $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="900" height="520" viewBox="0 0 900 520">
    <defs>
        <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#d7ebf8"/>
            <stop offset="100%" stop-color="#c7deef"/>
        </linearGradient>
    </defs>
    <rect width="900" height="520" fill="url(#g)"/>
    <text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle" fill="#406582" font-family="Arial" font-size="34">Image unavailable</text>
</svg>
SVG;

                return response($svg, 404, [
                        'Content-Type' => 'image/svg+xml; charset=UTF-8',
                        'Cache-Control' => 'no-store',
                ]);
        };

    if (!Schema::hasTable('vendor_listing_media')) {
                return $placeholderResponse();
    }

    $mediaRecord = DB::table('vendor_listing_media')
        ->where('id', $media)
        ->first(['file_path', 'mime_type']);

    if (!$mediaRecord) {
        return $placeholderResponse();
    }

    $originalPath = trim((string) ($mediaRecord->file_path ?? ''));
    if ($originalPath === '') {
        return $placeholderResponse();
    }

    if (str_starts_with($originalPath, 'http://') || str_starts_with($originalPath, 'https://')) {
        $remoteCandidates = [$originalPath];
        if (str_starts_with($originalPath, 'http://')) {
            $remoteCandidates[] = 'https://' . ltrim(substr($originalPath, 7), '/');
        }

        foreach (array_unique($remoteCandidates) as $remoteUrl) {
            try {
                $remoteResponse = Http::retry(1, 200)
                    ->timeout(10)
                    ->withHeaders([
                        'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                        'User-Agent' => 'WorkationMediaProxy/1.0',
                    ])
                    ->get($remoteUrl);
            } catch (\Throwable $exception) {
                continue;
            }

            if (!$remoteResponse->successful()) {
                continue;
            }

            $remoteBody = $remoteResponse->body();
            if ($remoteBody === '') {
                continue;
            }

            $remoteContentType = trim((string) $remoteResponse->header('Content-Type', ''));
            if ($remoteContentType === '') {
                $remoteContentType = (string) ($mediaRecord->mime_type ?? 'image/jpeg');
            }

            return response($remoteBody, 200, [
                'Content-Type' => $remoteContentType,
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        return $placeholderResponse();
    }

    $normalizedVariant = strtolower(trim((string) $variant));
    if (!in_array($normalizedVariant, ['banner', 'thumb'], true)) {
        $normalizedVariant = 'banner';
    }

    $candidatePath = $originalPath;
    if ($normalizedVariant === 'thumb') {
        $candidatePath = preg_replace('/-banner(\.[a-z0-9]+)$/i', '-thumb$1', $originalPath) ?? $originalPath;
    } else {
        $candidatePath = preg_replace('/-thumb(\.[a-z0-9]+)$/i', '-banner$1', $originalPath) ?? $originalPath;
    }

    // Some legacy rows have only one generated variant. Try the opposite variant as a fallback.
    $alternateVariantPath = $normalizedVariant === 'thumb'
        ? (preg_replace('/-thumb(\.[a-z0-9]+)$/i', '-banner$1', $originalPath) ?? $originalPath)
        : (preg_replace('/-banner(\.[a-z0-9]+)$/i', '-thumb$1', $originalPath) ?? $originalPath);

    $normalizeDiskPath = static function (string $path): string {
        $normalized = trim(str_replace('\\', '/', $path));
        if ($normalized === '') {
            return '';
        }

        if (preg_match('#/storage/app/public/(.+)$#i', $normalized, $matches) === 1) {
            $normalized = (string) ($matches[1] ?? '');
        } elseif (preg_match('#/public/storage/(.+)$#i', $normalized, $matches) === 1) {
            $normalized = (string) ($matches[1] ?? '');
        }

        $normalized = ltrim($normalized, '/');
        if (str_starts_with($normalized, 'public/')) {
            $normalized = substr($normalized, 7);
        }
        if (str_starts_with($normalized, 'storage/')) {
            $normalized = substr($normalized, 8);
        }

        return ltrim($normalized, '/');
    };

    // Also try the public storage symlink as an absolute path, so the proxy can serve
    // files that live at public/storage/... even when nginx blocks direct /storage/ access.
    $publicStoragePath = static function (string $relPath) use ($normalizeDiskPath): string {
        $rel = $normalizeDiskPath($relPath);
        return $rel !== '' ? public_path('storage/' . $rel) : '';
    };

    $candidatePaths = collect([
        $candidatePath,
        $alternateVariantPath,
        $originalPath,
        $normalizeDiskPath($candidatePath),
        $normalizeDiskPath($alternateVariantPath),
        $normalizeDiskPath($originalPath),
        $publicStoragePath($candidatePath),
        $publicStoragePath($alternateVariantPath),
        $publicStoragePath($originalPath),
    ])->map(static fn ($path) => trim((string) $path))
      ->filter(static fn ($path) => $path !== '')
      ->unique()
      ->values()
      ->all();

    $resolvedBinary = null;
    $resolvedMimeType = '';

    $configuredMediaDisk = trim((string) config('filesystems.vendor_media_disk', 'public'));
    $diskNames = array_values(array_unique(array_filter([
        $configuredMediaDisk !== '' ? $configuredMediaDisk : null,
        'public',
    ])));

    foreach ($diskNames as $diskName) {
        try {
            $disk = Storage::disk($diskName);
        } catch (\Throwable $exception) {
            continue;
        }

        foreach ($candidatePaths as $path) {
            if (!$disk->exists($path)) {
                continue;
            }

            $resolvedBinary = $disk->get($path);
            $resolvedMimeType = (string) ($disk->mimeType($path) ?: '');
            break 2;
        }
    }

    if ($resolvedBinary === null) {
        $localDisk = Storage::disk('local');
        foreach ($candidatePaths as $path) {
            foreach ([$path, 'public/' . ltrim($path, '/')] as $localPath) {
                if (!$localDisk->exists($localPath)) {
                    continue;
                }

                $resolvedBinary = $localDisk->get($localPath);
                $resolvedMimeType = (string) ($localDisk->mimeType($localPath) ?: '');
                break 2;
            }
        }
    }

    if ($resolvedBinary === null) {
        foreach ($candidatePaths as $path) {
            $absolutePath = str_replace('\\', '/', (string) $path);
            if (preg_match('#^[A-Za-z]:/#', $absolutePath) !== 1 && !str_starts_with($absolutePath, '/')) {
                continue;
            }

            if (!is_file($absolutePath) || !is_readable($absolutePath)) {
                continue;
            }

            $absoluteBinary = @file_get_contents($absolutePath);
            if ($absoluteBinary === false) {
                continue;
            }

            $resolvedBinary = $absoluteBinary;
            $absoluteMime = @mime_content_type($absolutePath);
            $resolvedMimeType = is_string($absoluteMime) ? $absoluteMime : '';
            break;
        }
    }

    if ($resolvedBinary === null) {
        return $placeholderResponse();
    }

    $mimeType = $resolvedMimeType !== '' ? $resolvedMimeType : ((string) ($mediaRecord->mime_type ?? 'image/jpeg'));

    return response($resolvedBinary, 200, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
});

require __DIR__ . '/web/admin.php';
require __DIR__ . '/web/portal-auth.php';
Route::prefix('api/atoll-island')->group(function () {
    Route::get('atolls', [\App\Http\Controllers\AtollIslandApiController::class, 'getAllAtolls']);
    Route::get('atolls/{atoll}/islands', [\App\Http\Controllers\AtollIslandApiController::class, 'getIslandsByAtoll']);
    Route::get('atolls/{atoll}/stats', [\App\Http\Controllers\AtollIslandApiController::class, 'getAtollStats']);
    Route::get('islands/{island}', [\App\Http\Controllers\AtollIslandApiController::class, 'getIslandWithMedia']);
    Route::get('islands', [\App\Http\Controllers\AtollIslandApiController::class, 'getFeaturedIslands']);
});

// Keep these endpoints available only in testing for legacy feature-test coverage.
if (app()->environment('testing')) {
    Route::prefix('api')->group(function () {
        Route::get('workations', [\App\Http\Controllers\WorkationController::class, 'index']);
        Route::get('workations/{workation}', [\App\Http\Controllers\WorkationController::class, 'show']);
        Route::post('workations', [\App\Http\Controllers\WorkationController::class, 'store']);
        Route::put('workations/{workation}', [\App\Http\Controllers\WorkationController::class, 'update']);
        Route::delete('workations/{workation}', [\App\Http\Controllers\WorkationController::class, 'destroy']);

        Route::post('transport/holds', [\App\Http\Controllers\TransportHoldController::class, 'store']);
        Route::post('transport/holds/{hold}/confirm', [\App\Http\Controllers\TransportHoldController::class, 'confirm']);
        Route::post('transport/holds/{hold}/release', [\App\Http\Controllers\TransportHoldController::class, 'release']);
    });
}
