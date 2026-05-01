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

Route::get('/room/{room}', function (Request $request, int $room) {
    if (!Schema::hasTable('vendor_property_room_categories')) {
        abort(404);
    }

    $roomRow = DB::table('vendor_property_room_categories')->where('id', $room)->first();
    if (!$roomRow) {
        abort(404);
    }

    $propertyRow = VendorPropertyCompatibilityReader::loadPropertyById((int) ($roomRow->vendor_property_id ?? 0));

    if (!$propertyRow) {
        abort(404);
    }

    $roomPayload = (array) Cache::remember(
        'room_profile:payload:v2:' . (int) $roomRow->id,
        now()->addMinutes(3),
        static function () use ($roomRow, $propertyRow) {
            $roomMedia = collect();
            if (Schema::hasTable('vendor_listing_media')) {
                $roomMedia = DB::table('vendor_listing_media')
                    ->where('entity_type', 'room')
                    ->where('entity_id', (int) $roomRow->id)
                    ->orderByDesc('is_primary')
                    ->orderByDesc('created_at')
                    ->limit(40)
                    ->get();
            }

            $roomFeatures = collect(preg_split('/[,\n]+/', (string) ($roomRow->amenities ?? '')) ?: [])
                ->merge(collect(preg_split('/[,\n]+/', (string) ($roomRow->bathroom_amenities ?? '')) ?: []))
                ->map(static fn ($v) => trim((string) $v))
                ->filter(static fn ($v) => $v !== '')
                ->unique()
                ->values();

            $propertyDetails = json_decode((string) ($propertyRow->listing_details ?? ''), true);
            if (!is_array($propertyDetails)) {
                $propertyDetails = [];
            }

            $transferOptions = collect($propertyDetails['transfer_options'] ?? [])->values();
            if ($transferOptions->isEmpty()) {
                $transferOptions = collect([
                    ['code' => 'shared_speedboat', 'label' => 'Shared Speedboat', 'adult_charge' => 35, 'child_charge' => 20],
                    ['code' => 'private_speedboat', 'label' => 'Private Speedboat', 'adult_charge' => 120, 'child_charge' => 80],
                    ['code' => 'seaplane', 'label' => 'Seaplane', 'adult_charge' => 420, 'child_charge' => 280],
                ]);
            }

            $transferOptions = $transferOptions->map(function ($option) {
                $code = trim((string) ($option['code'] ?? Str::slug((string) ($option['label'] ?? 'transfer'))));
                $label = trim((string) ($option['label'] ?? 'Transfer Option'));
                $baseCharge = (float) ($option['base_charge'] ?? 0);
                $adultCharge = (float) ($option['adult_charge'] ?? ($option['charge'] ?? 0));
                $childCharge = (float) ($option['child_charge'] ?? 0);

                return [
                    'code' => $code,
                    'label' => $label,
                    'base_charge' => $baseCharge,
                    'adult_charge' => $adultCharge,
                    'child_charge' => $childCharge,
                ];
            })->values();

            $pricingConfig = [
                'tax_rate' => (float) ($propertyDetails['tax_rate'] ?? 16),
                'discount_percent' => (float) ($propertyDetails['promotion_discount_percent'] ?? 0),
            ];

            $bookingPolicies = [
                'inclusives' => collect($propertyDetails['inclusives'] ?? [])->map(static fn ($v) => trim((string) $v))->filter()->values()->all(),
                'cancellation_policy' => trim((string) ($propertyDetails['cancellation_policy'] ?? '')),
                'check_in_time' => trim((string) ($propertyDetails['check_in_time'] ?? '')),
                'check_out_time' => trim((string) ($propertyDetails['check_out_time'] ?? '')),
                'child_policy' => trim((string) ($propertyDetails['child_policy'] ?? '')),
                'house_rules' => trim((string) ($propertyDetails['house_rules'] ?? '')),
                'minimum_nights' => isset($propertyDetails['minimum_nights']) && is_numeric($propertyDetails['minimum_nights'])
                    ? (int) $propertyDetails['minimum_nights']
                    : null,
            ];

            return [
                'room_media' => $roomMedia->all(),
                'room_features' => $roomFeatures->all(),
                'transfer_options' => $transferOptions->all(),
                'pricing_config' => $pricingConfig,
                'booking_policies' => $bookingPolicies,
            ];
        }
    );

    $roomMedia = collect((array) ($roomPayload['room_media'] ?? []));
    $roomFeatures = collect((array) ($roomPayload['room_features'] ?? []))->values();
    $transferOptions = collect((array) ($roomPayload['transfer_options'] ?? []))->values();
    $pricingConfig = is_array($roomPayload['pricing_config'] ?? null) ? $roomPayload['pricing_config'] : [];
    $bookingPolicies = is_array($roomPayload['booking_policies'] ?? null) ? $roomPayload['booking_policies'] : [];

    $mediaUrl = static function ($media, string $variant = 'banner'): ?string {
        $filePath = trim((string) ($media->file_path ?? ''));
        if ($filePath !== '' && (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://'))) {
            $resolved = $filePath;
            $resolved = trim((string) $resolved);
            if ($resolved !== '') {
                if (str_starts_with($resolved, 'http://')) {
                    $resolved = 'https://' . ltrim(substr($resolved, 7), '/');
                }

                return $resolved;
            }
        }

        $mediaId = (int) ($media->id ?? 0);
        if ($mediaId > 0) {
            return '/media/vendor/' . $mediaId . '/' . $variant;
        }

        return null;
    };

    $sessionGuestName = trim((string) session('portal_customer_user', ''));
    $nameParts = preg_split('/\s+/', $sessionGuestName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $prefillFirstName = (string) ($nameParts[0] ?? '');
    $prefillLastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';
    $selectedNightlyRate = (float) $request->query('rate_nightly', 0);
    if (!is_finite($selectedNightlyRate) || $selectedNightlyRate <= 0) {
        $selectedNightlyRate = 0.0;
    }
    $selectedMealPlan = trim((string) $request->query('meal_plan', ''));

    return view('room-profile', [
        'room' => $roomRow,
        'property' => $propertyRow,
        'roomMedia' => $roomMedia,
        'roomFeatures' => $roomFeatures,
        'transferOptions' => $transferOptions,
        'pricingConfig' => $pricingConfig,
        'bookingPolicies' => $bookingPolicies,
        'mediaUrl' => $mediaUrl,
        'prefill' => [
            'checkin' => trim((string) $request->query('checkin', '')),
            'checkout' => trim((string) $request->query('checkout', '')),
            'adults' => max(1, (int) $request->query('adults', 2)),
            'children' => max(0, (int) $request->query('children', 0)),
            'primary_first_name' => $prefillFirstName,
            'primary_last_name' => $prefillLastName,
            'primary_nationality' => '',
            'primary_email' => trim((string) session('portal_customer_email', '')),
            'primary_mobile' => '',
            'selected_nightly_rate' => $selectedNightlyRate,
            'selected_meal_plan' => $selectedMealPlan,
        ],
    ]);
});