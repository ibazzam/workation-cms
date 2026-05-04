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

if (!function_exists('workationDetectVisitorResidency')) {
    /**
     * Detect visitor residency from geo IP headers.
     * Returns 'local_resident' for Maldivian visitors (country code MV),
     * 'foreign_national' for all other visitors.
     * Priority: Cloudflare CF-IPCountry → X-Country-Code → fallback foreign.
     */
    function workationDetectVisitorResidency(Request $request): string
    {
        $countryCode = strtoupper(trim((string) (
            $request->header('CF-IPCountry')
            ?? $request->header('X-Country-Code')
            ?? $request->header('X-GeoIP-Country')
            ?? ''
        )));

        if ($countryCode === 'MV') {
            return 'local_resident';
        }

        return 'foreign_national';
    }
}

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

    $visitorResidency = workationDetectVisitorResidency($request);
    $visitorIsLocal = $visitorResidency === 'local_resident';
    $mvrUsdRate = (float) env('MVR_USD_RATE', 15.42);

    // Resolve effective nightly rate: use meal-plan + residency to pick the right column.
    $selectedMealPlan = strtolower(trim((string) $request->query('meal_plan', 'room_only')));
    $mealPlanRateColumns = [
        'room only'     => ['foreign' => 'meal_plan_room_only_price', 'local' => 'meal_plan_room_only_price_local'],
        'room_only'     => ['foreign' => 'meal_plan_room_only_price', 'local' => 'meal_plan_room_only_price_local'],
        'bb'            => ['foreign' => 'meal_plan_bb_price',        'local' => 'meal_plan_bb_price_local'],
        'hb'            => ['foreign' => 'meal_plan_hb_price',        'local' => 'meal_plan_hb_price_local'],
        'fb'            => ['foreign' => 'meal_plan_fb_price',        'local' => 'meal_plan_fb_price_local'],
        'all inclusive' => ['foreign' => 'meal_plan_ai_price',        'local' => 'meal_plan_ai_price_local'],
        'ai'            => ['foreign' => 'meal_plan_ai_price',        'local' => 'meal_plan_ai_price_local'],
    ];
    $rateCols = $mealPlanRateColumns[$selectedMealPlan] ?? $mealPlanRateColumns['room_only'];
    $foreignRate = (float) ($roomRow->{$rateCols['foreign']} ?? $roomRow->base_price ?? 0);
    $localRate   = (float) ($roomRow->{$rateCols['local']} ?? 0);
    if ($foreignRate <= 0) {
        $foreignRate = (float) ($roomRow->base_price ?? $propertyRow->base_price ?? 0);
    }
    $resolvedNightlyRate = ($visitorIsLocal && $localRate > 0) ? $localRate : $foreignRate;
        $mealPlanRateColumns = [
            'room only'     => ['foreign_usd' => 'meal_plan_room_only_price_usd', 'foreign' => 'meal_plan_room_only_price', 'local' => 'meal_plan_room_only_price_local'],
            'room_only'     => ['foreign_usd' => 'meal_plan_room_only_price_usd', 'foreign' => 'meal_plan_room_only_price', 'local' => 'meal_plan_room_only_price_local'],
            'bb'            => ['foreign_usd' => 'meal_plan_bb_price_usd',        'foreign' => 'meal_plan_bb_price',        'local' => 'meal_plan_bb_price_local'],
            'hb'            => ['foreign_usd' => 'meal_plan_hb_price_usd',        'foreign' => 'meal_plan_hb_price',        'local' => 'meal_plan_hb_price_local'],
            'fb'            => ['foreign_usd' => 'meal_plan_fb_price_usd',        'foreign' => 'meal_plan_fb_price',        'local' => 'meal_plan_fb_price_local'],
            'all inclusive' => ['foreign_usd' => 'meal_plan_ai_price_usd',        'foreign' => 'meal_plan_ai_price',        'local' => 'meal_plan_ai_price_local'],
            'ai'            => ['foreign_usd' => 'meal_plan_ai_price_usd',        'foreign' => 'meal_plan_ai_price',        'local' => 'meal_plan_ai_price_local'],
        ];
        $rateCols = $mealPlanRateColumns[$selectedMealPlan] ?? $mealPlanRateColumns['room_only'];
        $foreignUsdRate = (float) ($roomRow->{$rateCols['foreign_usd']} ?? 0);
        $foreignMvrRate = (float) ($roomRow->{$rateCols['foreign']} ?? $roomRow->base_price ?? 0);
        $localRate      = (float) ($roomRow->{$rateCols['local']} ?? 0);
        if ($foreignUsdRate <= 0 && $foreignMvrRate <= 0) {
            $foreignMvrRate = (float) ($roomRow->base_price ?? $propertyRow->base_price ?? 0);
            $foreignUsdRate = $mvrUsdRate > 0 ? round($foreignMvrRate / $mvrUsdRate, 2) : 0.0;
        }
        // Foreign visitors see USD; local residents see MVR.
        if ($visitorIsLocal) {
            $resolvedNightlyRate = $localRate > 0 ? $localRate : ($mvrUsdRate > 0 ? round($foreignMvrRate, 2) : $foreignMvrRate);
            $displayCurrency = 'MVR';
        } else {
            $resolvedNightlyRate = $foreignUsdRate > 0 ? $foreignUsdRate : ($mvrUsdRate > 0 ? round($foreignMvrRate / $mvrUsdRate, 2) : $foreignMvrRate);
            $displayCurrency = 'USD';
        }

    // Allow rate_nightly from query param to override (e.g., clicked Reserve from property page).
    $selectedNightlyRate = (float) $request->query('rate_nightly', 0);
    if (!is_finite($selectedNightlyRate) || $selectedNightlyRate <= 0) {
        $selectedNightlyRate = $resolvedNightlyRate;
    }

    return view('room-profile', [
        'room' => $roomRow,
        'property' => $propertyRow,
        'roomMedia' => $roomMedia,
        'roomFeatures' => $roomFeatures,
        'transferOptions' => $transferOptions,
        'pricingConfig' => $pricingConfig,
        'bookingPolicies' => $bookingPolicies,
        'mediaUrl' => $mediaUrl,
        'visitorResidency' => $visitorResidency,
        'mvrUsdRate' => $mvrUsdRate,
            'visitorResidency' => $visitorResidency,
            'mvrUsdRate' => $mvrUsdRate,
            'displayCurrency' => $displayCurrency,
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
            'selected_meal_plan' => trim((string) $request->query('meal_plan', '')),
            'guest_residency' => $visitorResidency,
        ],
    ]);
});