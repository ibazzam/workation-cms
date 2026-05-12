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

if (!function_exists('workationBuildGatewayCheckoutPayload')) {
    function workationBuildGatewayCheckoutPayload(string $gateway, array $context): array
    {
        $provider = strtolower(trim((string) ($context['provider'] ?? '')));
        $reservationId = (int) ($context['reservation_id'] ?? 0);
        $intentId = trim((string) ($context['intent_id'] ?? ''));
        $amount = number_format((float) ($context['amount'] ?? 0), 2, '.', '');
        $currency = strtoupper(trim((string) ($context['currency'] ?? 'MVR')));
        $returnUrl = trim((string) ($context['return_url'] ?? ''));
        $cancelUrl = trim((string) ($context['cancel_url'] ?? ''));
        $webhookUrl = trim((string) ($context['webhook_url'] ?? ''));
        $customerEmail = trim((string) ($context['customer_email'] ?? ''));

        $payload = [
            'intent_id' => $intentId,
            'reservation_id' => $reservationId,
            'amount' => $amount,
            'currency' => $currency,
            'provider' => $provider,
            'gateway' => trim((string) $gateway),
            'return_url' => $returnUrl,
            'cancel_url' => $cancelUrl,
            'webhook_url' => $webhookUrl,
            'timestamp' => (string) now()->timestamp,
        ];

        if ($customerEmail !== '') {
            $payload['customer_email'] = $customerEmail;
        }

        if ($provider === 'stripe') {
            $payload += [
                'client_reference_id' => (string) $reservationId,
                'success_url' => $returnUrl,
                'metadata_intent_id' => $intentId,
            ];
            return $payload;
        }

        if (in_array($provider, ['mib', 'bml'], true)) {
            $payload += [
                'merchant_reference' => 'WRK-' . $reservationId . '-' . strtoupper(substr(md5($intentId), 0, 8)),
                'bill_amount' => $amount,
                'bill_currency' => $currency,
                // Some bank flows redirect end-users to callback_url via browser GET.
                // Use return_url for customer redirect and keep webhook_url for server callbacks.
                'callback_url' => $returnUrl,
            ];
            return $payload;
        }

        return $payload;
    }
}

if (!function_exists('workationSignGatewayCheckoutPayload')) {
    function workationSignGatewayCheckoutPayload(string $gateway, array $payload): array
    {
        $gatewayConfig = CheckoutPaymentRouter::gatewayConfig($gateway);
        $signingSecret = trim((string) ($gatewayConfig['checkout_signing_secret'] ?? ''));
        if ($signingSecret === '') {
            return $payload;
        }

        $canonicalPayload = $payload;
        ksort($canonicalPayload);
        $canonical = http_build_query($canonicalPayload, '', '&', PHP_QUERY_RFC3986);
        $payload['signature'] = hash_hmac('sha256', $canonical, $signingSecret);

        return $payload;
    }
}

if (!function_exists('workationCreateStripeCheckoutSession')) {
    function workationCreateStripeCheckoutSession(array $context): ?array
    {
        $gatewayConfig = CheckoutPaymentRouter::gatewayConfig('stripe');
        $secretKey = trim((string) ($gatewayConfig['secret_key'] ?? ''));
        if ($secretKey === '') {
            return null;
        }

        $reservationId = (int) ($context['reservation_id'] ?? 0);
        $intentId = trim((string) ($context['intent_id'] ?? ''));
        $amount = max(0, (float) ($context['amount'] ?? 0));
        $currency = strtolower(trim((string) ($context['currency'] ?? 'usd')));
        $returnUrl = trim((string) ($context['return_url'] ?? ''));
        $cancelUrl = trim((string) ($context['cancel_url'] ?? $returnUrl));
        $customerEmail = trim((string) ($context['customer_email'] ?? ''));

        if ($reservationId <= 0 || $intentId === '' || $returnUrl === '' || $cancelUrl === '' || $amount <= 0 || $currency === '') {
            return null;
        }

        $requestData = [
            'mode' => 'payment',
            'client_reference_id' => (string) $reservationId,
            'success_url' => $returnUrl,
            'cancel_url' => $cancelUrl,
            'line_items[0][quantity]' => '1',
            'line_items[0][price_data][currency]' => $currency,
            'line_items[0][price_data][unit_amount]' => (string) max(1, (int) round($amount * 100)),
            'line_items[0][price_data][product_data][name]' => 'Workation Reservation #' . $reservationId,
            'metadata[reservation_id]' => (string) $reservationId,
            'metadata[intent_id]' => $intentId,
            'payment_intent_data[metadata][reservation_id]' => (string) $reservationId,
            'payment_intent_data[metadata][intent_id]' => $intentId,
        ];

        if ($customerEmail !== '') {
            $requestData['customer_email'] = $customerEmail;
        }

        try {
            $response = Http::withToken($secretKey)
                ->asForm()
                ->post('https://api.stripe.com/v1/checkout/sessions', $requestData);
        } catch (\Throwable $exception) {
            Log::warning('Stripe checkout session request failed', [
                'reservation_id' => $reservationId,
                'intent_id' => $intentId,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        if (!$response->successful()) {
            Log::warning('Stripe checkout session response error', [
                'reservation_id' => $reservationId,
                'intent_id' => $intentId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $json = $response->json();
        if (!is_array($json)) {
            return null;
        }

        $sessionUrl = trim((string) ($json['url'] ?? ''));
        $sessionId = trim((string) ($json['id'] ?? ''));
        if ($sessionUrl === '' || $sessionId === '') {
            return null;
        }

        return [
            'id' => $sessionId,
            'url' => $sessionUrl,
            'status' => trim((string) ($json['status'] ?? '')),
            'payment_status' => trim((string) ($json['payment_status'] ?? '')),
        ];
    }
}

if (!function_exists('workationPaymentSuccessReturnUrl')) {
    function workationPaymentSuccessReturnUrl(int $reservationId, ?string $provider = null): string
    {
        return url('/customer?section=bookings&booking=' . max(1, $reservationId) . '&payment=success');
    }
}

if (!function_exists('workationPaymentFailureReturnUrl')) {
    function workationPaymentFailureReturnUrl(int $reservationId, ?string $provider = null): string
    {
        return url('/customer?section=bookings&booking=' . max(1, $reservationId) . '&booking_status=awaiting_payment&payment=failed');
    }
}

if (!function_exists('workationNormalizedSegmentPricingMatrix')) {
    function workationNormalizedSegmentPricingMatrix(array $listingDetails, float $basePrice = 0.0): array
    {
        $matrix = is_array($listingDetails['pricing_by_segment'] ?? null) ? $listingDetails['pricing_by_segment'] : [];
        $localMatrix = is_array($matrix['local'] ?? null) ? $matrix['local'] : [];
        $foreignMatrix = is_array($matrix['foreign'] ?? null) ? $matrix['foreign'] : [];

        $foreignAdult = (float) ($foreignMatrix['adult'] ?? $listingDetails['adult_price_foreign'] ?? $listingDetails['adult_price'] ?? $listingDetails['price_per_adult'] ?? $basePrice);
        $foreignChild = (float) ($foreignMatrix['child'] ?? $listingDetails['child_price_foreign'] ?? $listingDetails['child_price'] ?? $listingDetails['price_per_child'] ?? round($foreignAdult * 0.5, 2));
        $localAdult = (float) ($localMatrix['adult'] ?? $listingDetails['adult_price_local'] ?? 0);
        $localChild = (float) ($localMatrix['child'] ?? $listingDetails['child_price_local'] ?? 0);

        $foreignFlat = (float) ($foreignMatrix['flat'] ?? $listingDetails['price_foreign'] ?? 0);
        $localFlat = (float) ($localMatrix['flat'] ?? $listingDetails['price_local'] ?? 0);

        return [
            'local' => [
                'adult' => max(0, $localAdult),
                'child' => max(0, $localChild),
                'flat' => max(0, $localFlat),
            ],
            'foreign' => [
                'adult' => max(0, $foreignAdult),
                'child' => max(0, $foreignChild),
                'flat' => max(0, $foreignFlat),
            ],
            'infant' => max(0, (float) ($listingDetails['infant_price'] ?? $listingDetails['price_per_infant'] ?? 0)),
        ];
    }
}

if (!function_exists('workationResolveEffectiveSegmentPricing')) {
    function workationResolveEffectiveSegmentPricing(array $listingDetails, float $basePrice, string $guestResidency): array
    {
        $normalizedResidency = strtolower(trim($guestResidency));
        if (!in_array($normalizedResidency, ['local_resident', 'foreign_national'], true)) {
            $normalizedResidency = 'foreign_national';
        }

        $matrix = workationNormalizedSegmentPricingMatrix($listingDetails, $basePrice);
        $local = $matrix['local'];
        $foreign = $matrix['foreign'];

        $isLocal = $normalizedResidency === 'local_resident';
        $effectiveAdult = $isLocal && $local['adult'] > 0 ? $local['adult'] : $foreign['adult'];
        $effectiveChild = $isLocal && $local['child'] > 0 ? $local['child'] : $foreign['child'];
        $effectiveFlat = $isLocal && $local['flat'] > 0 ? $local['flat'] : $foreign['flat'];

        return [
            'guest_residency' => $normalizedResidency,
            'adult' => max(0, (float) $effectiveAdult),
            'child' => max(0, (float) $effectiveChild),
            'infant' => max(0, (float) ($matrix['infant'] ?? 0)),
            'flat' => max(0, (float) $effectiveFlat),
            'matrix' => $matrix,
        ];
    }
}

if (!function_exists('workationResolveStopSequenceSpan')) {
    function workationResolveStopSequenceSpan(array $stopSequence, string $originStop, string $destinationStop): ?array
    {
        $originNeedle = strtolower(trim($originStop));
        $destinationNeedle = strtolower(trim($destinationStop));
        if ($originNeedle === '' || $destinationNeedle === '' || $originNeedle === $destinationNeedle) {
            return null;
        }

        $normalizedStops = array_values(array_map(
            static fn ($stop): string => strtolower(trim((string) $stop)),
            $stopSequence
        ));

        $originIndexes = [];
        $destinationIndexes = [];
        foreach ($normalizedStops as $idx => $stop) {
            if ($stop === $originNeedle) {
                $originIndexes[] = (int) $idx;
            }
            if ($stop === $destinationNeedle) {
                $destinationIndexes[] = (int) $idx;
            }
        }

        if ($originIndexes === [] || $destinationIndexes === []) {
            return null;
        }

        $bestOrigin = null;
        $bestDestination = null;
        $bestDistance = PHP_INT_MAX;

        foreach ($originIndexes as $originIdx) {
            foreach ($destinationIndexes as $destinationIdx) {
                if ($destinationIdx <= $originIdx) {
                    continue;
                }

                $distance = $destinationIdx - $originIdx;
                if ($distance < $bestDistance) {
                    $bestDistance = $distance;
                    $bestOrigin = $originIdx;
                    $bestDestination = $destinationIdx;
                }
            }
        }

        if ($bestOrigin === null || $bestDestination === null) {
            return null;
        }

        $segments = [];
        for ($s = (int) $bestOrigin; $s < (int) $bestDestination; $s++) {
            $segments[] = $s . ':' . ($s + 1);
        }

        return [
            'origin_index' => (int) $bestOrigin,
            'destination_index' => (int) $bestDestination,
            'segments' => $segments,
        ];
    }
}

    if (!function_exists('workationCreateBmlConnectTransaction')) {
        function workationCreateBmlConnectTransaction(array $context): ?array
        {
            $gatewayKey = trim((string) ($context['gateway'] ?? ''));
            $gatewayConfig = CheckoutPaymentRouter::gatewayConfig($gatewayKey);
            $apiKey = trim((string) ($gatewayConfig['api_key'] ?? ''));
            $appId = trim((string) ($gatewayConfig['app_id'] ?? ''));
            $mode = strtolower(trim((string) ($gatewayConfig['mode'] ?? 'production')));

            if ($apiKey === '' || $appId === '') {
                return null;
            }

            $reservationId = (int) ($context['reservation_id'] ?? 0);
            $intentId = trim((string) ($context['intent_id'] ?? ''));
            $amount = max(0, (float) ($context['amount'] ?? 0));
            $currency = strtoupper(trim((string) ($context['currency'] ?? 'MVR')));
            $redirectUrl = trim((string) ($context['redirect_url'] ?? ''));

            if ($reservationId <= 0 || $intentId === '' || $redirectUrl === '' || $amount <= 0) {
                return null;
            }

            // BML Connect amount is in cents/laars (1.00 MVR = 100 laars).
            $amountInCents = (int) round($amount * 100);
            if ($amountInCents <= 0) {
                return null;
            }

            // Signature: sha1("amount={amount}&currency={currency}&apiKey={apiKey}")
            $signature = sha1('amount=' . $amountInCents . '&currency=' . $currency . '&apiKey=' . $apiKey);

            $baseUrl = $mode === 'sandbox'
                ? 'https://api.uat.merchants.bankofmaldives.com.mv/public/'
                : 'https://api.merchants.bankofmaldives.com.mv/public/';

            $requestBody = [
                'currency'   => $currency,
                'amount'     => $amountInCents,
                'redirectUrl' => $redirectUrl,
                'localId'    => 'WRK-' . $reservationId . '-' . strtoupper(substr(md5($intentId), 0, 8)),
                'appId'      => $appId,
                'apiVersion' => '2.0',
                'appVersion' => 'workation-bml-connect',
                'signMethod' => 'sha1',
                'signature'  => $signature,
            ];

            try {
                $response = Http::withHeaders([
                    'Authorization' => $apiKey,
                    'Accept'        => 'application/json',
                ])->asJson()->post($baseUrl . 'transactions', $requestBody);

                if (!$response->successful()) {
                    Log::warning('BML Connect transaction creation failed', [
                        'reservation_id' => $reservationId,
                        'gateway'        => $gatewayKey,
                        'status'         => $response->status(),
                        'body'           => $response->body(),
                    ]);
                    return null;
                }

                $data = $response->json();
                if (!is_array($data) || empty($data['url'])) {
                    Log::warning('BML Connect transaction response missing URL', [
                        'reservation_id' => $reservationId,
                        'gateway'        => $gatewayKey,
                        'data'           => $data,
                    ]);
                    return null;
                }

                Log::info('BML Connect transaction created', [
                    'reservation_id' => $reservationId,
                    'gateway'        => $gatewayKey,
                    'transaction_id' => $data['id'] ?? null,
                    'state'          => $data['state'] ?? null,
                ]);

                return $data;
            } catch (\Throwable $exception) {
                Log::warning('BML Connect transaction request exception', [
                    'reservation_id' => $reservationId,
                    'gateway'        => $gatewayKey,
                    'error'          => $exception->getMessage(),
                ]);
                return null;
            }
        }
    }

if (!function_exists('workationVerifyStripeWebhookSignature')) {
    function workationVerifyStripeWebhookSignature(string $rawPayload, ?string $signatureHeader, string $secret, int $toleranceSeconds = 300): bool
    {
        $signatureHeader = trim((string) $signatureHeader);
        $secret = trim((string) $secret);
        if ($signatureHeader === '' || $secret === '' || $rawPayload === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $segment) {
            [$k, $v] = array_pad(explode('=', trim((string) $segment), 2), 2, '');
            $k = trim((string) $k);
            $v = trim((string) $v);
            if ($k !== '' && $v !== '') {
                $parts[$k][] = $v;
            }
        }

        $timestamp = isset($parts['t'][0]) ? (int) $parts['t'][0] : 0;
        $v1Signatures = $parts['v1'] ?? [];
        if ($timestamp <= 0 || $v1Signatures === []) {
            return false;
        }

        if (abs(time() - $timestamp) > max(0, $toleranceSeconds)) {
            return false;
        }

        $signedPayload = $timestamp . '.' . $rawPayload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        foreach ($v1Signatures as $candidate) {
            if (hash_equals($expected, (string) $candidate)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('workationIsBmlConnectConfigured')) {
    function workationIsBmlConnectConfigured(string $gatewayKey): bool
    {
        $gatewayConfig = CheckoutPaymentRouter::gatewayConfig($gatewayKey);
        $mode = strtolower(trim((string) ($gatewayConfig['mode'] ?? '')));
        $apiKey = trim((string) ($gatewayConfig['api_key'] ?? ''));
        $appId = trim((string) ($gatewayConfig['app_id'] ?? ''));

        return in_array($mode, ['production', 'sandbox'], true)
            && $apiKey !== ''
            && $appId !== '';
    }
}

if (!function_exists('workationFetchBmlConnectTransactionStatus')) {
    function workationFetchBmlConnectTransactionStatus(string $gatewayKey, string $transactionId): ?array
    {
        $gatewayConfig = CheckoutPaymentRouter::gatewayConfig($gatewayKey);
        $apiKey = trim((string) ($gatewayConfig['api_key'] ?? ''));
        $mode = strtolower(trim((string) ($gatewayConfig['mode'] ?? 'production')));

        if ($transactionId === '' || $apiKey === '' || !in_array($mode, ['production', 'sandbox'], true)) {
            return null;
        }

        $baseUrl = $mode === 'sandbox'
            ? 'https://api.uat.merchants.bankofmaldives.com.mv/public/'
            : 'https://api.merchants.bankofmaldives.com.mv/public/';

        try {
            $response = Http::withHeaders([
                'Authorization' => $apiKey,
                'Accept' => 'application/json',
            ])->get($baseUrl . 'transactions/' . rawurlencode($transactionId));

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            if (!is_array($data)) {
                return null;
            }

            return [
                'state' => strtoupper(trim((string) ($data['state'] ?? ''))),
                'transaction_id' => trim((string) ($data['id'] ?? $transactionId)),
            ];
        } catch (\Throwable $exception) {
            Log::warning('BML Connect transaction status lookup failed', [
                'gateway' => $gatewayKey,
                'transaction_id' => $transactionId,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}

if (!function_exists('workationResolvePropertyVendorUserId')) {
    function workationResolvePropertyVendorUserId(object $propertyRow): int
    {
        $candidates = [
            (int) ($propertyRow->vendor_user_id ?? 0),
            (int) ($propertyRow->owner_user_id ?? 0),
            (int) ($propertyRow->user_id ?? 0),
            (int) ($propertyRow->vendor_id ?? 0),
        ];

        $resolved = collect($candidates)->first(static fn (int $id): bool => $id > 0);
        if (is_int($resolved) && $resolved > 0) {
            return $resolved;
        }

        $propertyId = (int) ($propertyRow->id ?? $propertyRow->vendor_property_id ?? 0);
        if ($propertyId <= 0 || !Schema::hasTable('vendor_properties')) {
            return 0;
        }

        $lookup = DB::table('vendor_properties')->where('id', $propertyId)->first();
        if (!$lookup) {
            return 0;
        }

        foreach (['vendor_user_id', 'owner_user_id', 'user_id', 'vendor_id'] as $column) {
            if (isset($lookup->{$column}) && (int) $lookup->{$column} > 0) {
                return (int) $lookup->{$column};
            }
        }

        return 0;
    }
}

if (!function_exists('workationLiveaboardSalesClosed')) {
    function workationLiveaboardSalesClosed(array $listingDetails): bool
    {
        $stopSaleDate = trim((string) ($listingDetails['journey_stop_sale_date'] ?? ''));
        $autoStop = (bool) ($listingDetails['auto_stop_sale_on_boarding'] ?? true);
        if ($stopSaleDate === '' && $autoStop) {
            $stopSaleDate = trim((string) ($listingDetails['journey_start_date'] ?? ''));
        }

        if ($stopSaleDate === '') {
            return false;
        }

        try {
            $today = Carbon::today();
            return $today->greaterThanOrEqualTo(Carbon::parse($stopSaleDate)->startOfDay());
        } catch (\Throwable $exception) {
            return false;
        }
    }
}

if (!function_exists('workationLiveaboardTransferEligibility')) {
    function workationLiveaboardTransferEligibility(?object $roomRow, ?string $boardingPoint = null): array
    {
        if (!$roomRow) {
            return [
                'eligible' => false,
                'mid_trip_join' => false,
                'transfer_included' => true,
            ];
        }

        $packageTransferIncluded = isset($roomRow->package_transfer_included)
            ? (int) $roomRow->package_transfer_included === 1
            : true;
        $midTripJoinAllowed = isset($roomRow->package_mid_trip_join_allowed)
            ? (int) $roomRow->package_mid_trip_join_allowed === 1
            : false;
        $packageEmbarkPoint = strtolower(trim((string) ($roomRow->package_embark_point ?? '')));
        $selectedBoardingPoint = strtolower(trim((string) ($boardingPoint ?? '')));

        $isMidTripJoin = false;
        if ($selectedBoardingPoint !== '' && $packageEmbarkPoint !== '' && $selectedBoardingPoint !== $packageEmbarkPoint) {
            $isMidTripJoin = true;
        }

        $eligible = !$packageTransferIncluded && $midTripJoinAllowed && $isMidTripJoin;

        return [
            'eligible' => $eligible,
            'mid_trip_join' => $isMidTripJoin,
            'transfer_included' => $packageTransferIncluded,
        ];
    }
}

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
        'meal_plan' => ['nullable', 'string', 'max:40'],
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

    $vendorUserId = workationResolvePropertyVendorUserId($propertyRow);

    if (Schema::hasTable('vendor_reservations')) {
        $existingReservationQuery = DB::table('vendor_reservations')
            ->where('vendor_user_id', $vendorUserId)
            ->where('vendor_property_id', (int) ($propertyRow->id ?? 0))
            ->whereDate('start_at', $bookingStart->toDateString())
            ->whereDate('end_at', $bookingEndExclusive->toDateString())
            ->where('customer_email', Str::lower(trim((string) ($payload['primary_email'] ?? ''))))
            ->whereNotIn('status', ['cancelled', 'rejected', 'expired', 'failed'])
            ->orderByDesc('id');

        if (Schema::hasColumn('vendor_reservations', 'payment_status')) {
            $existingReservationQuery->where('payment_status', '!=', 'paid');
        }

        $existingReservation = $existingReservationQuery
            ->where('notes', 'like', '%"room_id":' . (int) ($roomRow->id ?? 0) . '%')
            ->first(['id']);

        if ($existingReservation) {
            return redirect('/booking/checkout/' . (int) $existingReservation->id . '/transfer');
        }
    }

    $slotAvailability = workationSlotAvailabilityCheck(
        $vendorUserId,
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
        $vendorUserId,
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

    $propertyDetails = json_decode((string) ($propertyRow->listing_details ?? ''), true);
    if (!is_array($propertyDetails)) {
        $propertyDetails = [];
    }

    $listingCategoryKey = strtolower(trim((string) ($propertyRow->listing_category ?? '')));
    if ($listingCategoryKey === 'liveaboard' && workationLiveaboardSalesClosed($propertyDetails)) {
        return back()->withErrors(['booking' => 'This journey is closed for new bookings. Stop sale date has passed.'])->withInput();
    }

    $discountPercent = (float) ($propertyDetails['promotion_discount_percent'] ?? 0);
    $transferOptionCode = strtolower(trim((string) ($payload['transfer_option'] ?? '')));
    if (in_array($transferOptionCode, ['', 'none', 'no_transfer', 'decline', 'declined'], true)) {
        $transferOptionCode = 'none';
    }
    $transferChargeOverride = $transferOptionCode === 'none'
        ? 0.0
        : ($payload['transfer_charge'] ?? null);
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

    $liveaboardTransferEligibility = [
        'eligible' => true,
        'mid_trip_join' => false,
        'transfer_included' => false,
    ];
    if ($listingCategoryKey === 'liveaboard') {
        $liveaboardTransferEligibility = workationLiveaboardTransferEligibility($roomRow, (string) ($payload['boarding_point'] ?? ''));
        if (!($liveaboardTransferEligibility['eligible'] ?? false)) {
            $transferOptions = [];
            $transferOptionCode = 'none';
            $transferChargeOverride = 0.0;
        }
    }
    $guestResidency = strtolower(trim((string) ($payload['guest_residency'] ?? '')));
    if (!in_array($guestResidency, ['local_resident', 'foreign_national'], true)) {
        $guestResidency = ReservationPricingPolicy::isForeigner((string) ($payload['primary_nationality'] ?? ''), null)
            ? 'foreign_national'
            : 'local_resident';
    }

    // Resolve effective nightly rate by meal plan and guest residency.
    $selectedMealPlan = strtolower(trim((string) ($payload['meal_plan'] ?? '')));
    $mealPlanRatePairs = [
        'room_only' => ['meal_plan_room_only_price', 'meal_plan_room_only_price_local'],
        'bb'        => ['meal_plan_bb_price',        'meal_plan_bb_price_local'],
        'hb'        => ['meal_plan_hb_price',        'meal_plan_hb_price_local'],
        'fb'        => ['meal_plan_fb_price',        'meal_plan_fb_price_local'],
        'ai'        => ['meal_plan_ai_price',        'meal_plan_ai_price_local'],
    ];
    [$foreignCol, $localCol] = $mealPlanRatePairs[$selectedMealPlan] ?? $mealPlanRatePairs['room_only'];
    $foreignNightlyRate = (float) ($roomRow->{$foreignCol} ?? $roomRow->base_price ?? $propertyRow->base_price ?? 0);
    if ($foreignNightlyRate <= 0) {
        $foreignNightlyRate = (float) ($roomRow->base_price ?? $propertyRow->base_price ?? 0);
    }
    $localNightlyRate = (float) ($roomRow->{$localCol} ?? 0);
    $nightlyRate = ($guestResidency === 'local_resident' && $localNightlyRate > 0)
        ? $localNightlyRate
        : $foreignNightlyRate;
    $roomSubtotal = $nightlyRate * $nights;

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
        'transfer_charge_override' => $transferChargeOverride,
        'vendor_tax_overrides' => $vendorTaxOverrides,
        'property_currency' => strtoupper(trim((string) ($roomRow->currency ?? $propertyRow->currency ?? 'MVR'))),
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

    try {
        $paymentQuote = CheckoutPaymentRouter::buildPaymentQuote([
            'primary_nationality' => $primaryNationality,
            'guest_residency' => $guestResidency,
            'reservation_currency' => strtoupper(trim((string) ($roomRow->currency ?? $propertyRow->currency ?? 'MVR'))),
            'amount' => $totalAmount,
        ]);
    } catch (\InvalidArgumentException $exception) {
        return back()->withErrors(['payment' => $exception->getMessage()])->withInput();
    }

    provisionCustomerAccountFromBooking($customerEmail, $customerName);

    $reservationId = null;
    if (Schema::hasTable('vendor_reservations')) {
        $reservationId = (int) DB::table('vendor_reservations')->insertGetId([
            'vendor_user_id' => $vendorUserId,
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
                'service_start_date' => $checkin->toDateString(),
                'service_end_date' => $checkout->toDateString(),
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
                'liveaboard_transfer_eligible' => (bool) ($liveaboardTransferEligibility['eligible'] ?? false),
                'liveaboard_mid_trip_join' => (bool) ($liveaboardTransferEligibility['mid_trip_join'] ?? false),
                'liveaboard_package_transfer_included' => (bool) ($liveaboardTransferEligibility['transfer_included'] ?? false),
                'package_embark_point' => trim((string) ($roomRow->package_embark_point ?? '')),
                'package_disembark_point' => trim((string) ($roomRow->package_disembark_point ?? '')),
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

    $showTransferStep = !empty($transferOptions);
    $checkoutUrl = '/booking/checkout'
        . ($reservationId ? ('/' . $reservationId . ($showTransferStep ? '/transfer' : '')) : '')
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


// POST-Redirect-GET: sea-transport detail (and similar) submits a mini booking form via POST.
// This route converts it to a GET redirect so the category-booking GET handler can
// render the page with those values pre-filled via query string.
Route::post('/category-booking/{category}/{property}', function (Request $request, string $category, int $property) {
    $allowed = [
        'route_code', 'boarding_point', 'disembark_point', 'listing_category',
        'travel_date', 'adults', 'children', 'infants', 'guest_residency',
        'origin_point', 'destination_point', 'departure_area', 'departure_time',
        'return_slot', 'selected_seats', 'seat_count', 'rooms',
        'trip_type', 'return_date', 'return_route_code', 'return_boarding_point',
        'return_disembark_point', 'service_start_date', 'service_end_date',
    ];
    $params = array_filter($request->only($allowed), static fn ($v) => $v !== null && $v !== '');
    if (!isset($params['service_start_date']) && !empty($params['travel_date'])) {
        $params['service_start_date'] = (string) $params['travel_date'];
    }
    if (!isset($params['service_end_date']) && !empty($params['return_date'])) {
        $params['service_end_date'] = (string) $params['return_date'];
    }
    if (!isset($params['service_end_date']) && (($params['trip_type'] ?? 'one_way') === 'one_way') && !empty($params['service_start_date'])) {
        $params['service_end_date'] = (string) $params['service_start_date'];
    }
    $qs = !empty($params) ? ('?' . http_build_query($params)) : '';
    return redirect('/category-booking/' . rawurlencode($category) . '/' . (int) $property . $qs);
});

Route::get('/category-booking/{category}/{property}', function (Request $request, string $category, int $property) {
    $categoryMap = [
        'accommodation' => ['label' => 'Accommodation', 'start_label' => 'Check-in Date', 'end_label' => 'Check-out Date'],
        'marine-transport' => ['label' => 'Marine Transport', 'start_label' => 'Travel Date', 'end_label' => 'Return Date'],
        'land-transport' => ['label' => 'Land Transport', 'start_label' => 'Travel Date', 'end_label' => 'Return Date'],
        'excursion' => ['label' => 'Excursion', 'start_label' => 'Excursion Date', 'end_label' => 'Return Date'],
        'water_sports' => ['label' => 'Water Sports', 'start_label' => 'Activity Date', 'end_label' => 'Return Date'],
        'remote_workspace' => ['label' => 'Remote Workspace', 'start_label' => 'Start Date', 'end_label' => 'End Date'],
        'conference_room' => ['label' => 'Conference & Meeting Spaces', 'start_label' => 'Event Date', 'end_label' => 'Event End Date'],
        'resort_day_visit' => ['label' => 'Resort Day Visit', 'start_label' => 'Visit Date', 'end_label' => 'Return Date'],
        'restaurant' => ['label' => 'Restaurant', 'start_label' => 'Reservation Date & Time', 'end_label' => 'Expected Departure Date & Time'],
        'vehicle_rental' => ['label' => 'Vehicle Rental', 'start_label' => 'Pickup Date', 'end_label' => 'Return Date'],
        'sea_transport' => ['label' => 'Sea Transport & Ferries', 'start_label' => 'Travel Date', 'end_label' => 'Return Date'],
        'liveaboard' => ['label' => 'Liveaboard / Safari', 'start_label' => 'Journey Start Date', 'end_label' => 'Journey End Date'],
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
            // Transfer logistics fields (departure details when transfer is included).
            ['key' => 'departure_area', 'label' => 'Departure Area / Jetty (if transfer included)', 'type' => 'text', 'required' => false],
            ['key' => 'departure_time', 'label' => 'Departure Time (if transfer included)', 'type' => 'time', 'required' => false],
            ['key' => 'return_slot', 'label' => 'Return Time Slot (if transfer included)', 'type' => 'time', 'required' => false],
        ],
        'water_sports' => [
            ['key' => 'departure_area', 'label' => 'Departure Area / Jetty (if transfer included)', 'type' => 'text', 'required' => false],
            ['key' => 'departure_time', 'label' => 'Departure Time (if transfer included)', 'type' => 'time', 'required' => false],
            ['key' => 'return_slot', 'label' => 'Return Time Slot (if transfer included)', 'type' => 'time', 'required' => false],
        ],
        'remote_workspace' => [
            ['key' => 'workspace_type', 'label' => 'Workspace Type', 'type' => 'text', 'required' => true],
            ['key' => 'departure_area', 'label' => 'Departure Area / Jetty (if transfer included)', 'type' => 'text', 'required' => false],
            ['key' => 'departure_time', 'label' => 'Departure Time (if transfer included)', 'type' => 'time', 'required' => false],
            ['key' => 'return_slot', 'label' => 'Return Time Slot (if transfer included)', 'type' => 'time', 'required' => false],
        ],
        'conference_room' => [
            ['key' => 'event_type', 'label' => 'Event Type', 'type' => 'select', 'required' => true, 'options' => ['meeting' => 'Meeting', 'training' => 'Training', 'seminar' => 'Seminar', 'conference' => 'Conference', 'workshop' => 'Workshop']],
            ['key' => 'expected_capacity', 'label' => 'Expected Attendees', 'type' => 'number', 'required' => true, 'min' => 1],
            ['key' => 'departure_area', 'label' => 'Departure Area / Jetty (if transfer included)', 'type' => 'text', 'required' => false],
            ['key' => 'departure_time', 'label' => 'Departure Time (if transfer included)', 'type' => 'time', 'required' => false],
            ['key' => 'return_slot', 'label' => 'Return Time Slot (if transfer included)', 'type' => 'time', 'required' => false],
        ],
        'resort_day_visit' => [
            ['key' => 'visit_package', 'label' => 'Visit Package', 'type' => 'text', 'required' => true],
            ['key' => 'departure_area', 'label' => 'Departure Area / Jetty', 'type' => 'text', 'required' => false],
            ['key' => 'departure_time', 'label' => 'Departure Time', 'type' => 'time', 'required' => false],
            ['key' => 'return_slot', 'label' => 'Return Time Slot', 'type' => 'time', 'required' => false],
        ],
        'restaurant' => [
            ['key' => 'departure_area', 'label' => 'Departure Area / Jetty (if applicable)', 'type' => 'text', 'required' => false],
            ['key' => 'departure_time', 'label' => 'Transfer Departure Time (if applicable)', 'type' => 'time', 'required' => false],
            ['key' => 'return_slot', 'label' => 'Return Time (if applicable)', 'type' => 'time', 'required' => false],
        ],
        'vehicle_rental' => [
            ['key' => 'vehicle_type', 'label' => 'Vehicle Type', 'type' => 'text', 'required' => true],
            ['key' => 'pickup_location', 'label' => 'Pickup Location', 'type' => 'text', 'required' => true],
            ['key' => 'dropoff_location', 'label' => 'Drop-off Location', 'type' => 'text', 'required' => true],
            ['key' => 'driver_license_number', 'label' => "Driver's License Number", 'type' => 'text', 'required' => false],
        ],
        'sea_transport' => [
            ['key' => 'selected_seats', 'label' => 'Selected Seats (optional)', 'type' => 'text', 'required' => false],
            ['key' => 'seat_count', 'label' => 'Number of Seats', 'type' => 'number', 'required' => true, 'min' => 1],
        ],
        'liveaboard' => [
            ['key' => 'boarding_point', 'label' => 'Boarding Point', 'type' => 'text', 'required' => true],
            ['key' => 'disembark_point', 'label' => 'Disembarking Point', 'type' => 'text', 'required' => true],
        ],
    ];

    $categoryKey = strtolower(trim($category));
    if (!array_key_exists($categoryKey, $categoryMap)) {
        abort(404);
    }

    // Map URL slug to DB value (hyphens -> underscores)
    $dbCategoryKey = str_replace('-', '_', $categoryKey);

    $categoryFields = collect($categoryFieldMap[$categoryKey] ?? [])->values();

    $propertyRow = VendorPropertyCompatibilityReader::loadPropertyById($property, $dbCategoryKey);
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

    if ($dbCategoryKey === 'liveaboard' && workationLiveaboardSalesClosed($listingDetails)) {
        return back()->withErrors(['booking' => 'This journey is closed for new bookings. Stop sale date has passed.'])->withInput();
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
        $propertyMediaEntityIds = collect(workationPropertyLookupIds($propertyRow))
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $bookingMediaTypeMap = [
            'accommodation' => ['property'],
            'liveaboard' => ['liveaboard', 'property', 'service'],
            'sea_transport' => ['sea_transport', 'sea-transport', 'marine_transport', 'transport'],
            'marine_transport' => ['sea_transport', 'sea-transport', 'marine_transport', 'transport'],
            'land_transport' => ['land_transport', 'land-transport', 'transport'],
            'vehicle_rental' => ['vehicle_rental', 'vehicle-rental', 'vehicle', 'transport'],
            'conference_room' => ['conference_room', 'conference-room', 'meeting_room', 'meeting-room'],
            'remote_workspace' => ['remote_workspace', 'remote-workspace', 'workspace'],
            'water_sports' => ['water_sports', 'water-sports', 'activity'],
            'excursion' => ['excursion', 'activity'],
            'restaurant' => ['restaurant'],
            'resort_day_visit' => ['resort_day_visit', 'resort-day-visit'],
        ];
        $bookingMediaTypes = $bookingMediaTypeMap[$dbCategoryKey] ?? [$dbCategoryKey];
        $bookingVendorUserId = (int) ($propertyRow->vendor_user_id ?? 0);

        $propertyMediaQuery = DB::table('vendor_listing_media')
            ->whereIn('entity_type', $bookingMediaTypes)
            ->whereIn('entity_id', $propertyMediaEntityIds->isNotEmpty() ? $propertyMediaEntityIds->all() : [(int) ($propertyRow->id ?? 0)]);

        if ($bookingVendorUserId > 0) {
            $propertyMediaQuery->where('vendor_user_id', $bookingVendorUserId);
        }

        $propertyMedia = $propertyMediaQuery
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

    $mvrUsdRate = max(0.01, (float) env('MVR_USD_RATE', 15.42));

    $previewGuestResidency = strtolower(trim((string) $request->query('guest_residency', '')));
    if (!in_array($previewGuestResidency, ['local_resident', 'foreign_national'], true)) {
        $previewGuestNationality = trim((string) $request->query('primary_nationality', ''));
        if ($previewGuestNationality !== '') {
            $previewGuestResidency = ReservationPricingPolicy::isForeigner($previewGuestNationality, null)
                ? 'foreign_national'
                : 'local_resident';
        }
    }
    if (!in_array($previewGuestResidency, ['local_resident', 'foreign_national'], true)) {
        // Detect visitor location from Cloudflare/CDN header — local Maldivians show MVR, everyone else USD.
        $cfCountry = strtoupper(trim((string) ($request->header('CF-IPCountry') ?? $request->header('X-Country-Code') ?? $request->header('X-GeoIP-Country') ?? '')));
        $previewGuestResidency = $cfCountry === 'MV' ? 'local_resident' : 'foreign_national';
    }

    $excursionBasePrice = (float) ($propertyRow->base_price ?? 0);
    $effectivePricing = workationResolveEffectiveSegmentPricing($listingDetails, $excursionBasePrice, $previewGuestResidency);
    $excursionAdultPrice = (float) ($effectivePricing['adult'] ?? $excursionBasePrice);
    $excursionChildPrice = (float) ($effectivePricing['child'] ?? max(0, round($excursionAdultPrice * 0.5, 2)));
    $excursionInfantPrice = (float) ($effectivePricing['infant'] ?? 0);
    $effectiveServiceDisplayPrice = (float) ($effectivePricing['flat'] ?? 0);
    if ($effectiveServiceDisplayPrice <= 0) {
        $effectiveServiceDisplayPrice = $excursionBasePrice;
    }
    $pricingMatrix = $effectivePricing['matrix'] ?? [];
    $localFlatPrice = (float) (($pricingMatrix['local']['flat'] ?? null) ?: ($listingDetails['price_local'] ?? 0));
    $foreignFlatPrice = (float) (($pricingMatrix['foreign']['flat'] ?? null) ?: ($listingDetails['price_usd'] ?? $listingDetails['price_foreign'] ?? 0));

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

        if (Schema::hasColumn('vendor_reservations', 'payment_status')) {
            $reservationQuery->where('payment_status', 'paid');
        }

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

    $rentalItems = collect();
    if ($dbCategoryKey === 'water_sports' && Schema::hasTable('vendor_water_sports_rental_items')) {
        $rentalItems = collect(Cache::remember(
            'category_booking:rental_items:v1:' . md5((string) ($propertyRow->id ?? 0)),
            now()->addMinutes(3),
            static function () use ($propertyRow) {
                return DB::table('vendor_water_sports_rental_items')
                    ->where('vendor_property_id', (int) ($propertyRow->id ?? 0))
                    ->where('status', 'active')
                    ->orderBy('equipment_category')->orderBy('name')
                    ->get()->all();
            }
        ));
    }

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
            'display_price' => $effectiveServiceDisplayPrice,
            'price_local_flat' => $localFlatPrice,
            'price_foreign_flat' => $foreignFlatPrice,
            'guest_residency' => $previewGuestResidency,
            'transfer_included' => (bool) ($listingDetails['transfer_included'] ?? false),
            'departure_time_mode' => (string) ($listingDetails['departure_time_mode'] ?? 'fixed'),
            'departure_slots' => (array) ($listingDetails['departure_slots'] ?? []),
            'return_time_mode' => (string) ($listingDetails['return_time_mode'] ?? 'fixed'),
            'return_slots' => (array) ($listingDetails['return_slots'] ?? []),
            'return_time' => (string) ($listingDetails['return_time'] ?? ''),
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
            'guest_residency' => $previewGuestResidency,
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
            'route_code' => trim((string) $request->query('route_code', '')),
            'boarding_point' => trim((string) $request->query('boarding_point', '')),
            'disembark_point' => trim((string) $request->query('disembark_point', '')),
            'selected_seats' => trim((string) $request->query('selected_seats', '')),
            'seat_count' => max(1, (int) $request->query('seat_count', max(1, (int) $request->query('adults', 1)))),
            'trip_type' => trim((string) $request->query('trip_type', 'one_way')),
            'return_route_code' => trim((string) $request->query('return_route_code', '')),
            'return_boarding_point' => trim((string) $request->query('return_boarding_point', '')),
            'return_disembark_point' => trim((string) $request->query('return_disembark_point', '')),
            'service_notes' => trim((string) $request->query('service_notes', '')),
        ],
        'todayDate' => $todayDate,
        'unavailableDates' => $unavailableDates,
        'transferOptions' => $transferOptions,
        'rentalItems' => $rentalItems,
        'mvrUsdRate' => $mvrUsdRate,
        'visitorResidency' => $previewGuestResidency,
    ]);
});

Route::post('/booking/reserve-category', function (Request $request) {
    $categoryMap = [
        'accommodation' => ['label' => 'Accommodation', 'start_label' => 'Check-in', 'end_label' => 'Check-out'],
        'marine-transport' => ['label' => 'Marine Transport', 'start_label' => 'Travel Date', 'end_label' => 'Return Date'],
        'sea_transport' => ['label' => 'Sea Transport & Ferries', 'start_label' => 'Departure Date', 'end_label' => 'Return Date'],
        'land-transport' => ['label' => 'Land Transport', 'start_label' => 'Travel Date', 'end_label' => 'Return Date'],
        'excursion' => ['label' => 'Excursion', 'start_label' => 'Excursion Date', 'end_label' => 'Return Date'],
        'water_sports' => ['label' => 'Water Sports', 'start_label' => 'Activity Date', 'end_label' => 'Return Date'],
        'remote_workspace' => ['label' => 'Remote Workspace', 'start_label' => 'Start Date', 'end_label' => 'End Date'],
        'resort_day_visit' => ['label' => 'Resort Day Visit', 'start_label' => 'Visit Date', 'end_label' => 'Return Date'],
        'restaurant' => ['label' => 'Restaurant', 'start_label' => 'Reservation Date & Time', 'end_label' => 'Expected Departure Date & Time'],
        'vehicle_rental' => ['label' => 'Vehicle Rental', 'start_label' => 'Pickup Date', 'end_label' => 'Return Date'],
        'conference_room' => ['label' => 'Conference & Meeting Spaces', 'start_label' => 'Event Date', 'end_label' => 'Event End Date'],
        'liveaboard' => ['label' => 'Liveaboard / Safari', 'start_label' => 'Journey Start Date', 'end_label' => 'Journey End Date'],
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
            // Transfer logistics: departure details when transfer is included with the activity.
            'departure_area' => ['nullable', 'string', 'max:120'],
            'departure_time' => ['nullable', 'string', 'max:10'],
            'return_slot' => ['nullable', 'string', 'max:10'],
        ],
        'water_sports' => [
            'departure_area' => ['nullable', 'string', 'max:120'],
            'departure_time' => ['nullable', 'string', 'max:10'],
            'return_slot' => ['nullable', 'string', 'max:10'],
        ],
        'remote_workspace' => [
            'workspace_type' => ['required', 'string', 'max:120'],
            'departure_area' => ['nullable', 'string', 'max:120'],
            'departure_time' => ['nullable', 'string', 'max:10'],
            'return_slot' => ['nullable', 'string', 'max:10'],
        ],
        'conference_room' => [
            'event_type' => ['required', 'string', 'in:meeting,training,seminar,conference,workshop'],
            'expected_capacity' => ['required', 'integer', 'min:1', 'max:5000'],
            'required_facilities' => ['nullable', 'array'],
            'required_facilities.*' => ['string', 'max:60'],
            'departure_area' => ['nullable', 'string', 'max:120'],
            'departure_time' => ['nullable', 'string', 'max:10'],
            'return_slot' => ['nullable', 'string', 'max:10'],
        ],
        'resort_day_visit' => [
            'visit_package' => ['nullable', 'string', 'max:120'],
            'departure_area' => ['nullable', 'string', 'max:120'],
            'departure_time' => ['nullable', 'string', 'max:10'],
            'return_slot' => ['nullable', 'string', 'max:10'],
        ],
        'restaurant' => [
            'departure_area' => ['nullable', 'string', 'max:120'],
            'departure_time' => ['nullable', 'string', 'max:10'],
            'return_slot' => ['nullable', 'string', 'max:10'],
        ],
        'vehicle_rental' => [
            'vehicle_type' => ['required', 'string', 'max:120'],
            'pickup_location' => ['required', 'string', 'max:120'],
            'dropoff_location' => ['required', 'string', 'max:120'],
            'driver_license_number' => ['nullable', 'string', 'max:60'],
        ],
        'sea_transport' => [
            'seat_count' => ['required', 'integer', 'min:1', 'max:500'],
            'selected_seats' => ['nullable', 'string', 'max:500'],
            'route_code' => ['nullable', 'string', 'max:80'],
            'boarding_point' => ['nullable', 'string', 'max:120'],
            'disembark_point' => ['nullable', 'string', 'max:120'],
            'trip_type' => ['nullable', Rule::in(['one_way', 'round_trip'])],
            'return_route_code' => ['nullable', 'string', 'max:80'],
            'return_boarding_point' => ['nullable', 'string', 'max:120'],
            'return_disembark_point' => ['nullable', 'string', 'max:120'],
        ],
        'liveaboard' => [
            'boarding_point' => ['required', 'string', 'max:120'],
            'disembark_point' => ['required', 'string', 'max:120'],
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
        'departure_area' => 'departure area',
        'departure_time' => 'departure time',
        'return_slot' => 'return time slot',
        'driver_license_number' => "driver's license number",
        'seat_count' => 'seat count',
        'selected_seats' => 'selected seats',
        'route_code' => 'route code',
        'boarding_point' => 'boarding point',
        'disembark_point' => 'disembark point',
        'trip_type' => 'trip type',
        'return_route_code' => 'return route code',
        'return_boarding_point' => 'return boarding point',
        'return_disembark_point' => 'return disembark point',
    ];

    $requestedCategoryKey = strtolower(trim((string) $request->input('category_key', '')));
    $requestedCategoryMeta = $categoryMap[$requestedCategoryKey] ?? null;
    $startDateLabel = (string) ($requestedCategoryMeta['start_label'] ?? 'Service start date');
    $endDateLabel = (string) ($requestedCategoryMeta['end_label'] ?? 'Service end date');

    $isActivityCategory = $requestedCategoryKey !== 'accommodation';

    $baseRules = [
        'category_key' => ['required', 'string', 'in:' . implode(',', array_keys($categoryMap))],
        'property_id' => ['required', 'integer', 'min:1'],
        'service_start_date' => ['required', 'date', 'after_or_equal:today'],
        'service_end_date' => ['nullable', 'date', 'after_or_equal:service_start_date'],
        'adults' => ['required', 'integer', 'min:1', 'max:20'],
        'children' => ['nullable', 'integer', 'min:0', 'max:20'],
        'infants' => ['nullable', 'integer', 'min:0', 'max:20'],
        'primary_first_name' => $isActivityCategory ? ['nullable', 'string', 'max:80'] : ['required', 'string', 'max:80'],
        'primary_last_name' => $isActivityCategory ? ['nullable', 'string', 'max:80'] : ['required', 'string', 'max:80'],
        'primary_nationality' => $isActivityCategory ? ['nullable', 'string', 'max:120'] : ['required', 'string', 'max:120'],
        'guest_residency' => ['nullable', Rule::in(['local_resident', 'foreign_national'])],
        'primary_email' => $isActivityCategory ? ['nullable', 'email', 'max:190'] : ['required', 'email', 'max:190'],
        'primary_mobile' => $isActivityCategory ? ['nullable', 'string', 'max:40', 'regex:/^\+?[0-9][0-9\s\-()]{5,39}$/'] : ['required', 'string', 'max:40', 'regex:/^\+?[0-9][0-9\s\-()]{5,39}$/'],
        'transfer_option' => ['nullable', 'string', 'max:80'],
        'transfer_charge' => ['nullable', 'numeric', 'min:0'],
        'departure_time' => ['nullable', 'string', 'max:10'],
        'return_slot' => ['nullable', 'string', 'max:10'],
        'payment_timing' => ['nullable', 'string', 'max:40'],
        'payment_method' => ['nullable', 'string', 'max:60'],
        'additional_guest_details' => ['nullable', 'string', 'max:4000'],
        'service_notes' => ['nullable', 'string', 'max:4000'],
        'trip_type' => ['nullable', Rule::in(['one_way', 'round_trip'])],
        'return_route_code' => ['nullable', 'string', 'max:80'],
        'return_boarding_point' => ['nullable', 'string', 'max:120'],
        'return_disembark_point' => ['nullable', 'string', 'max:120'],
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

    if ($categoryKey === 'liveaboard') {
        $transferOptions = [];
        $transferOptionCode = 'none';
    }

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
    $seaBookedSegments = []; // populated later for sea_transport segment capacity tracking
    if (in_array($categoryKey, ['marine-transport', 'land-transport', 'sea_transport'], true)) {
        $origin = trim((string) ($payload['origin_point'] ?? ($listingDetails['departure_point'] ?? '')));
        $destination = trim((string) ($payload['destination_point'] ?? ($listingDetails['arrival_point'] ?? '')));
        if ($origin !== '' || $destination !== '') {
            $routeName = trim($origin . ' -> ' . $destination);
        }
    }
    if ($categoryKey === 'liveaboard') {
        $boardingPt   = trim((string) ($payload['boarding_point'] ?? ''));
        $disembarkPt  = trim((string) ($payload['disembark_point'] ?? ''));
        if ($boardingPt !== '' || $disembarkPt !== '') {
            $routeName = trim($boardingPt . ' -> ' . $disembarkPt);
        }
    }

    $unitsRequested = match ($categoryKey) {
        'accommodation' => max(1, (int) ($payload['rooms'] ?? 1)),
        'conference_room' => max(1, (int) ($payload['expected_capacity'] ?? ($adults + $children))),
        'marine-transport', 'land-transport', 'excursion', 'resort_day_visit', 'restaurant' => max(1, $adults + $children),
        'sea_transport' => max(1, (int) ($payload['seat_count'] ?? ($adults + $children))),
        'liveaboard' => max(1, $adults + $children),
        default => 1,
    };

    $vendorUserId = workationResolvePropertyVendorUserId($propertyRow);

    $slotAvailability = workationSlotAvailabilityCheck(
        $vendorUserId,
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
            $vendorUserId,
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

    $effectiveSegmentPricing = ['adult' => 0.0, 'child' => 0.0, 'infant' => 0.0, 'flat' => 0.0];
    if ($categoryKey !== 'accommodation') {
        $effectiveSegmentPricing = workationResolveEffectiveSegmentPricing($listingDetails, $basePrice, $guestResidency);

        $effectiveAdultPrice = (float) ($effectiveSegmentPricing['adult'] ?? 0);
        $effectiveChildPrice = (float) ($effectiveSegmentPricing['child'] ?? 0);
        $effectiveInfantPrice = (float) ($effectiveSegmentPricing['infant'] ?? 0);
        $effectiveFlatPrice = (float) ($effectiveSegmentPricing['flat'] ?? 0);

        if ($effectiveAdultPrice > 0 || $effectiveChildPrice > 0 || $effectiveInfantPrice > 0) {
            $serviceSubtotal = ($effectiveAdultPrice * $adults)
                + ($effectiveChildPrice * $children)
                + ($effectiveInfantPrice * $infants);
        } elseif ($effectiveFlatPrice > 0) {
            $serviceSubtotal = $effectiveFlatPrice * $units;
        }

        // Sea transport: segment capacity check + per-leg pricing.
        if ($categoryKey === 'sea_transport') {
            $seatCount = max(1, (int) ($payload['seat_count'] ?? ($adults + $children + $infants > 0 ? $adults + $children : 1)));
            $isLocal   = $guestResidency === 'local_resident';
            $tripType  = strtolower(trim((string) ($payload['trip_type'] ?? 'one_way')));
            if (!in_array($tripType, ['one_way', 'round_trip'], true)) {
                $tripType = 'one_way';
            }

            // ── Segment capacity check ────────────────────────────────
            $stStopSequence = is_array($listingDetails['stop_sequence'] ?? null) ? $listingDetails['stop_sequence'] : [];
            $stBoardingPt   = trim((string) ($payload['boarding_point'] ?? ''));
            $stDisembarkPt  = trim((string) ($payload['disembark_point'] ?? ''));
            $stTotalSeats   = (int) ($listingDetails['total_seats'] ?? 0);
            $seaBookedSegments = [];

            if (!empty($stStopSequence) && $stBoardingPt !== '' && $stDisembarkPt !== '' && $stTotalSeats > 0) {
                $segmentSpan = workationResolveStopSequenceSpan($stStopSequence, $stBoardingPt, $stDisembarkPt);
                if (is_array($segmentSpan) && !empty($segmentSpan['segments'])) {
                    $seaBookedSegments = array_values((array) ($segmentSpan['segments'] ?? []));
                    $existingSeaBookings = DB::table('vendor_reservations')
                        ->where('vendor_property_id', (int) $propertyRow->id)
                        ->whereNotIn('status', ['cancelled', 'canceled'])
                        ->where('start_at', '>=', $serviceStart->copy()->startOfDay())
                        ->where('start_at', '<', $serviceStart->copy()->addDay())
                        ->select('notes')
                        ->get();
                    $segmentLoad = [];
                    foreach ($existingSeaBookings as $existingSeaBooking) {
                        $eNotes = json_decode((string) ($existingSeaBooking->notes ?? ''), true);
                        if (!is_array($eNotes)) {
                            continue;
                        }
                        $eSegs  = (array) ($eNotes['booked_segments'] ?? []);
                        $eCount = max(1, (int) ($eNotes['units_requested'] ?? 1));
                        foreach ($eSegs as $seg) {
                            $segmentLoad[$seg] = ($segmentLoad[$seg] ?? 0) + $eCount;
                        }
                    }
                    foreach ($seaBookedSegments as $seg) {
                        if (($segmentLoad[$seg] ?? 0) + $seatCount > $stTotalSeats) {
                            return back()->withErrors(['booking' => 'Not enough seats available on the ' . $stBoardingPt . ' → ' . $stDisembarkPt . ' leg for the selected date. Please choose a different date or reduce the number of passengers.'])->withInput();
                        }
                    }
                }
            }

            // ── Per-leg pricing resolution ────────────────────────────
            $stRouteSchedules   = is_array($listingDetails['route_schedules'] ?? null) ? $listingDetails['route_schedules'] : [];
            $selectedRouteCode  = trim((string) ($payload['route_code'] ?? ''));
            $matchedLeg         = null;
            foreach ($stRouteSchedules as $stLeg) {
                if ($selectedRouteCode !== '' && ($stLeg['route_code'] ?? '') === $selectedRouteCode) {
                    $matchedLeg = $stLeg;
                    break;
                }
                if ($matchedLeg === null
                    && ($stLeg['origin'] ?? '') === $stBoardingPt
                    && ($stLeg['destination'] ?? '') === $stDisembarkPt) {
                    $matchedLeg = $stLeg;
                }
            }
            if ($matchedLeg !== null) {
                $legAdultPrice  = $isLocal ? (float) ($matchedLeg['local_adult']   ?? 0) : (float) ($matchedLeg['foreign_adult']  ?? 0);
                $legChildPrice  = $isLocal ? (float) ($matchedLeg['local_child']   ?? 0) : (float) ($matchedLeg['foreign_child']  ?? 0);
                $legInfantPrice = $isLocal ? (float) ($matchedLeg['local_infant']  ?? 0) : (float) ($matchedLeg['foreign_infant'] ?? 0);
                if ($legAdultPrice > 0 || $legChildPrice > 0) {
                    $serviceSubtotal = ($legAdultPrice * $adults)
                        + ($legChildPrice * $children)
                        + ($legInfantPrice * $infants);
                }
            } else {
                // Fallback to listing-level price per seat.
                $pricePerSeat = $isLocal
                    ? (float) ($listingDetails['local_price'] ?? 0)
                    : (float) ($listingDetails['foreign_price'] ?? 0);
                if ($pricePerSeat > 0) {
                    $serviceSubtotal = $pricePerSeat * $seatCount;
                }
            }

            // Optional round-trip pricing: add a return-leg fare when requested.
            if ($tripType === 'round_trip') {
                $returnRouteCode = trim((string) ($payload['return_route_code'] ?? ''));
                $returnBoarding = trim((string) ($payload['return_boarding_point'] ?? $stDisembarkPt));
                $returnDisembark = trim((string) ($payload['return_disembark_point'] ?? $stBoardingPt));

                $returnLeg = null;
                foreach ($stRouteSchedules as $stLeg) {
                    if ($returnRouteCode !== '' && ($stLeg['route_code'] ?? '') === $returnRouteCode) {
                        $returnLeg = $stLeg;
                        break;
                    }
                    if ($returnLeg === null
                        && ($stLeg['origin'] ?? '') === $returnBoarding
                        && ($stLeg['destination'] ?? '') === $returnDisembark) {
                        $returnLeg = $stLeg;
                    }
                }

                $returnSubtotal = 0.0;
                if ($returnLeg !== null) {
                    $returnAdultPrice  = $isLocal ? (float) ($returnLeg['local_adult']   ?? 0) : (float) ($returnLeg['foreign_adult']  ?? 0);
                    $returnChildPrice  = $isLocal ? (float) ($returnLeg['local_child']   ?? 0) : (float) ($returnLeg['foreign_child']  ?? 0);
                    $returnInfantPrice = $isLocal ? (float) ($returnLeg['local_infant']  ?? 0) : (float) ($returnLeg['foreign_infant'] ?? 0);
                    if ($returnAdultPrice > 0 || $returnChildPrice > 0) {
                        $returnSubtotal = ($returnAdultPrice * $adults)
                            + ($returnChildPrice * $children)
                            + ($returnInfantPrice * $infants);
                    }
                }

                if ($returnSubtotal <= 0) {
                    // Safe fallback when no matching return leg was selected/found.
                    $returnSubtotal = $serviceSubtotal;
                }

                $serviceSubtotal += $returnSubtotal;
            }
        }

        // Liveaboard: pricing matrix lookup
        if ($categoryKey === 'liveaboard') {
            $boardingPt   = trim((string) ($payload['boarding_point'] ?? ''));
            $disembarkPt  = trim((string) ($payload['disembark_point'] ?? ''));
            $routeKey = $boardingPt . '→' . $disembarkPt;
            $pricingMatrix = (array) ($listingDetails['pricing_matrix'] ?? []);
            if (isset($pricingMatrix[$routeKey]) && (float) $pricingMatrix[$routeKey] > 0) {
                $serviceSubtotal = (float) $pricingMatrix[$routeKey];
            }
        }
    }

    $vendorTaxOverrides = [];
    if (isset($listingDetails['vendor_tax_overrides']) && is_array($listingDetails['vendor_tax_overrides'])) {
        $vendorTaxOverrides = $listingDetails['vendor_tax_overrides'];
    }

    $roomCount = Schema::hasTable('vendor_property_room_categories')
        ? (int) DB::table('vendor_property_room_categories')->where('vendor_property_id', (int) $propertyRow->id)->count()
        : 0;

    $transferOptionCode = strtolower(trim((string) $transferOptionCode));
    if (in_array($transferOptionCode, ['', 'none', 'no_transfer', 'decline', 'declined'], true)) {
        $transferOptionCode = 'none';
    }
    $transferChargeOverride = $transferOptionCode === 'none'
        ? 0.0
        : ($payload['transfer_charge'] ?? null);

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
        'transfer_charge_override' => $transferChargeOverride,
        'vendor_tax_overrides' => $vendorTaxOverrides,
        'property_currency' => strtoupper(trim((string) ($propertyRow->currency ?? 'MVR'))),
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

    if ($isActivityCategory) {
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

    if ($categoryKey !== 'accommodation') {
        $departureTime = trim((string) ($payload['departure_time'] ?? ''));
        $returnSlot = trim((string) ($payload['return_slot'] ?? ''));
        if ($departureTime !== '') {
            $categoryDetails['departure_time'] = $departureTime;
        }
        if ($returnSlot !== '') {
            $categoryDetails['return_slot'] = $returnSlot;
        }
    }

    $reservationId = null;
    if (Schema::hasTable('vendor_reservations')) {
        $reservationId = (int) DB::table('vendor_reservations')->insertGetId([
            'vendor_user_id' => $vendorUserId,
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
                'effective_unit_pricing' => [
                    'adult' => (float) ($effectiveSegmentPricing['adult'] ?? 0),
                    'child' => (float) ($effectiveSegmentPricing['child'] ?? 0),
                    'infant' => (float) ($effectiveSegmentPricing['infant'] ?? 0),
                    'flat' => (float) ($effectiveSegmentPricing['flat'] ?? 0),
                ],
                'pricing_by_segment' => $effectiveSegmentPricing['matrix'] ?? [],
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
                'booked_segments' => $seaBookedSegments ?? [],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $showTransferStep = !empty($transferOptions);
    $checkoutUrl = '/booking/checkout'
        . ($reservationId ? ('/' . $reservationId . ($showTransferStep ? '/transfer' : '')) : '')
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
        if ($reservationRow && strtolower(trim((string) ($reservationRow->payment_status ?? 'unpaid'))) === 'paid') {
            return redirect(workationPaymentSuccessReturnUrl((int) ($reservationRow->id ?? $reservation), null))
                ->with('portal_notice', 'Payment already completed. Your booking is available in the customer portal.');
        }
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
    $requiresCustomerAuth = true;
    $customerAuthenticated = (bool) $request->session()->get('portal_customer_authenticated', false);
    $continueUrl = '/booking/checkout' . ($reservation !== null ? ('/' . $reservation) : '');
    $query = trim((string) parse_url((string) $request->fullUrl(), PHP_URL_QUERY));
    if ($query !== '') {
        $continueUrl .= '?' . $query;
    }

    $dateLabels = ['start' => 'Check-in', 'end' => 'Check-out'];
    if ($categoryKey !== '' && array_key_exists($categoryKey, $categoryLabelMap)) {
        $dateLabels = $categoryLabelMap[$categoryKey];
    }

    $cancellationPolicy = trim((string) $request->query('cancellation_policy', ''));
    if ($cancellationPolicy === '') {
        $cancellationPolicy = trim((string) ($reservationNotes['cancellation_policy'] ?? 'Cancellation terms are set by the property/service provider and shown in your booking details.'));
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
        $backQuery = [
            'checkin' => trim((string) ($reservationNotes['service_start_date'] ?? '')),
            'checkout' => trim((string) ($reservationNotes['service_end_date'] ?? '')),
            'adults' => max(1, (int) ($reservationNotes['adults'] ?? 1)),
            'children' => max(0, (int) ($reservationNotes['children'] ?? 0)),
        ];

        $backUrl = '/room/' . (int) ($roomRow->id ?? 0);
        $backUrl .= '?' . http_build_query($backQuery, '', '&', PHP_QUERY_RFC3986);
    } elseif ($propertyRow && !empty($reservationNotes['category_key'])) {
        $backQuery = [
            'checkin' => trim((string) ($reservationNotes['service_start_date'] ?? '')),
            'checkout' => trim((string) ($reservationNotes['service_end_date'] ?? '')),
            'adults' => max(1, (int) ($reservationNotes['adults'] ?? 1)),
            'children' => max(0, (int) ($reservationNotes['children'] ?? 0)),
            'infants' => max(0, (int) ($reservationNotes['infants'] ?? 0)),
        ];

        $backUrl = '/category-booking/' . urlencode((string) $reservationNotes['category_key']) . '/' . (int) ($propertyRow->id ?? 0);
        $backUrl .= '?' . http_build_query($backQuery, '', '&', PHP_QUERY_RFC3986);
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
            ? trim((string) ($reservationRow->payment_gateway ?? ''))
            : trim((string) $request->query('payment_gateway', '')),
        'reservation_currency' => strtoupper(trim((string) ($reservationRow->currency ?? $roomRow->currency ?? $propertyRow->currency ?? 'MVR'))),
        'amount' => (float) ($reservationNotes['invoice_total_amount'] ?? $request->query('total', (float) ($reservationRow->total_amount ?? 0))),
    ];
    try {
        $paymentPolicy = CheckoutPaymentRouter::buildPaymentPolicy(
            $paymentContext,
            $reservationRow
                ? trim((string) ($reservationRow->payment_currency ?? ''))
                : trim((string) $request->query('payment_currency', ''))
        );
    } catch (\InvalidArgumentException $exception) {
        $paymentPolicy = [
            'segment' => CheckoutPaymentRouter::resolveCustomerSegment(
                (string) ($paymentContext['primary_nationality'] ?? ''),
                (string) ($paymentContext['guest_residency'] ?? '')
            ),
            'currency' => strtoupper(trim((string) ($paymentContext['reservation_currency'] ?? 'MVR'))),
            'gateway' => '',
            'gateway_label' => 'Gateway unavailable',
            'provider' => '',
            'provider_label' => 'Gateway unavailable',
            'gateway_mode' => 'internal',
            'available_options' => [],
            'customer_notice' => $exception->getMessage(),
        ];
    }

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
        'requiresCustomerAuth' => $requiresCustomerAuth,
        'customerAuthenticated' => $customerAuthenticated,
        'customerLoginContinueUrl' => $continueUrl,
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

Route::get('/booking/checkout/{reservation}/transfer', function (Request $request, int $reservation) {
    if (!Schema::hasTable('vendor_reservations')) {
        abort(404);
    }

    $reservationRow = DB::table('vendor_reservations')->where('id', $reservation)->first();
    if (!$reservationRow) {
        abort(404);
    }

    $notes = workationReservationPaymentNotes($reservationRow);

    $primaryFirstName = trim((string) ($notes['primary_first_name'] ?? ''));
    $primaryLastName = trim((string) ($notes['primary_last_name'] ?? ''));
    $primaryNationality = trim((string) ($notes['primary_nationality'] ?? ''));
    $primaryEmail = trim((string) ($notes['primary_email'] ?? ''));
    $primaryMobile = trim((string) ($notes['primary_mobile'] ?? ''));
    $guestDetailsComplete = $primaryFirstName !== ''
        && $primaryLastName !== ''
        && $primaryEmail !== ''
        && $primaryMobile !== ''
        && $primaryNationality !== ''
        && strcasecmp($primaryNationality, 'Not specified') !== 0;

    if (!$guestDetailsComplete) {
        return redirect('/booking/checkout/' . $reservation)
            ->withErrors(['guest_details' => 'Please complete guest details first. Transfer selection depends on the selected guest nationality.']);
    }

    $reservationCategoryKey = strtolower(trim((string) ($notes['category_key'] ?? '')));

    // Sea transport and water sports bypass transfer selection entirely.
    if (in_array($reservationCategoryKey, ['sea_transport', 'water_sports'], true)) {
        return redirect('/booking/checkout/' . $reservation)
            ->with('status', 'Transfer selection is not required for this category.');
    }

    $propertyId = (int) ($reservationRow->vendor_property_id ?? 0);
    $propertyRow = $propertyId > 0 ? VendorPropertyCompatibilityReader::loadPropertyById($propertyId) : null;

    $roomId = (int) ($notes['room_id'] ?? 0);
    $roomRow = Schema::hasTable('vendor_property_room_categories') && $roomId > 0
        ? DB::table('vendor_property_room_categories')->where('id', $roomId)->first()
        : null;

    $transferOptions = collect($notes['property_transfer_options'] ?? [])->filter(static fn ($option) => is_array($option))->values();
    if ($reservationCategoryKey === 'liveaboard') {
        $liveaboardTransferEligible = (bool) ($notes['liveaboard_transfer_eligible'] ?? false);
        if (!$liveaboardTransferEligible || $transferOptions->isEmpty()) {
            return redirect('/booking/checkout/' . $reservation)
                ->with('status', 'Transfer add-on is not required for this liveaboard package.');
        }
    }
    $availableCodes = $transferOptions
        ->map(static fn ($option) => strtolower(trim((string) ($option['code'] ?? ''))))
        ->filter(static fn ($code) => $code !== '')
        ->values();

    $primaryNationality = trim((string) ($notes['primary_nationality'] ?? ''));
    $guestResidency = strtolower(trim((string) ($notes['guest_residency'] ?? '')));
    if (!in_array($guestResidency, ['local_resident', 'foreign_national'], true)) {
        $guestResidency = ReservationPricingPolicy::isForeigner($primaryNationality, null)
            ? 'foreign_national'
            : 'local_resident';
    }

    $savedTransferOption = strtolower(trim((string) ($notes['transfer_option'] ?? 'none')));
    if ($savedTransferOption === '' || $savedTransferOption === 'none' || !$availableCodes->contains($savedTransferOption)) {
        $savedTransferOption = $availableCodes->first() ?: 'none';
    }

    $normalizedTransferOption = strtolower(trim((string) ($notes['transfer_option'] ?? 'none')));
    $includeTransfer = !in_array($normalizedTransferOption, ['', 'none', 'no_transfer', 'decline', 'declined'], true)
        && $availableCodes->contains($savedTransferOption);

    $backUrl = '/booking/checkout/' . $reservation . '?edit_guest=1';

    $isExcursion = strcasecmp($reservationCategoryKey, 'excursion') === 0;
    $startDate = trim((string) ($notes['service_start_date'] ?? ''));
    $endDate = trim((string) ($notes['service_end_date'] ?? ''));

    // Calculate base amount without transfer
    $invoiceTotal = (float) ($notes['invoice_total_amount'] ?? ($reservationRow->total_amount ?? 0));
    $currentTransferCharge = (float) ($notes['transfer_charge_total'] ?? ($notes['transfer_charge'] ?? 0));
    $baseAmountWithoutTransfer = max(0, $invoiceTotal - $currentTransferCharge);

    return view('booking-transfer-selection', [
        'reservation' => $reservationRow,
        'property' => $propertyRow,
        'room' => $roomRow,
        'summary' => [
            'category_key' => $reservationCategoryKey,
            'checkin' => $startDate,
            'checkout' => $endDate,
            'adults' => max(1, (int) ($notes['adults'] ?? 1)),
            'children' => max(0, (int) ($notes['children'] ?? 0)),
            'primary_nationality' => $primaryNationality,
            'guest_residency' => $guestResidency,
            'invoice_total_amount' => $invoiceTotal,
            'base_amount_without_transfer' => $baseAmountWithoutTransfer,
            'current_transfer_charge' => $currentTransferCharge,
            'discounted_subtotal' => (float) ($notes['discounted_subtotal'] ?? 0),
            'subtotal_amount' => (float) ($notes['subtotal_amount'] ?? 0),
            'transfer_charge_total' => $currentTransferCharge,
        ],
        'transferOptions' => $transferOptions,
        'selectedTransferOption' => old('transfer_option', $savedTransferOption),
        'includeTransfer' => old('include_transfer', $includeTransfer ? '1' : '0') === '1',
        'backUrl' => $backUrl,
        'isExcursion' => $isExcursion,
        'hasTransferOptions' => $transferOptions->isNotEmpty(),
        'currency' => strtoupper(trim((string) ($notes['quote_payment_currency'] ?? $reservationRow->currency ?? $roomRow->currency ?? $propertyRow->currency ?? 'MVR'))),
    ]);
});

Route::post('/booking/checkout/{reservation}/transfer', function (Request $request, int $reservation) {
    if (!Schema::hasTable('vendor_reservations')) {
        abort(404);
    }

    $reservationRow = DB::table('vendor_reservations')->where('id', $reservation)->first();
    if (!$reservationRow) {
        abort(404);
    }

    if (in_array(strtolower(trim((string) ($reservationRow->status ?? 'pending'))), ['cancelled', 'canceled'], true)) {
        return back()->withErrors(['transfer' => 'Cancelled reservations cannot be updated.']);
    }

    if (strtolower(trim((string) ($reservationRow->payment_status ?? 'unpaid'))) === 'paid') {
        return back()->withErrors(['transfer' => 'Transfer option cannot be changed after payment.']);
    }

    $validated = $request->validate([
        'include_transfer' => ['nullable', 'boolean'],
        'transfer_option' => ['nullable', 'string', 'max:80'],
    ]);

    $notes = workationReservationPaymentNotes($reservationRow);
    $reservationCategoryKey = strtolower(trim((string) ($notes['category_key'] ?? '')));
    $transferOptions = collect($notes['property_transfer_options'] ?? [])->filter(static fn ($option) => is_array($option))->values();
    if ($reservationCategoryKey === 'liveaboard') {
        $liveaboardTransferEligible = (bool) ($notes['liveaboard_transfer_eligible'] ?? false);
        if (!$liveaboardTransferEligible || $transferOptions->isEmpty()) {
            return redirect('/booking/checkout/' . $reservation)
                ->with('status', 'Transfer add-on is not required for this liveaboard package.');
        }
    }
    $availableCodes = $transferOptions
        ->map(static fn ($option) => strtolower(trim((string) ($option['code'] ?? ''))))
        ->filter(static fn ($code) => $code !== '')
        ->values();

    $includeTransfer = (bool) ($validated['include_transfer'] ?? false);
    $selectedTransferOption = strtolower(trim((string) ($validated['transfer_option'] ?? 'none')));

    if (!$includeTransfer && $selectedTransferOption !== '' && $selectedTransferOption !== 'none') {
        $includeTransfer = true;
    }

    if ($includeTransfer && $transferOptions->isEmpty()) {
        return back()->withErrors(['transfer' => 'No transfer options are available for this property.']);
    }

    if ($includeTransfer && ($selectedTransferOption === '' || !$availableCodes->contains($selectedTransferOption))) {
        return back()->withErrors(['transfer' => 'Please select a valid transfer option.']);
    }

    if (!$includeTransfer) {
        $selectedTransferOption = 'none';
    }

    $primaryNationality = trim((string) ($notes['primary_nationality'] ?? ''));
    $guestResidency = strtolower(trim((string) ($notes['guest_residency'] ?? '')));
    if (!in_array($guestResidency, ['local_resident', 'foreign_national'], true)) {
        $guestResidency = ReservationPricingPolicy::isForeigner($primaryNationality, null)
            ? 'foreign_national'
            : 'local_resident';
    }

    $pricing = ReservationPricingPolicy::calculate([
        'listing_category' => trim((string) ($notes['category_key'] ?? '')) !== '' ? (string) ($notes['category_key'] ?? 'accommodation') : 'accommodation',
        'subtotal_amount' => (float) ($notes['subtotal_amount'] ?? $notes['room_subtotal'] ?? $reservationRow->total_amount ?? 0),
        'discount_percent' => (float) ($notes['discount_percent'] ?? 0),
        'adults' => max(1, (int) ($notes['adults'] ?? 1)),
        'children' => max(0, (int) ($notes['children'] ?? 0)),
        'infants' => max(0, (int) ($notes['infants'] ?? 0)),
        'nights' => max(1, (int) ($notes['nights'] ?? 1)),
        'room_count' => max(1, (int) ($notes['rooms'] ?? 1)),
        'primary_nationality' => $primaryNationality,
        'guest_residency' => $guestResidency,
        'transfer_option' => $selectedTransferOption,
        'property_transfer_options' => $transferOptions->all(),
        'vendor_tax_overrides' => is_array($notes['vendor_tax_overrides'] ?? null) ? $notes['vendor_tax_overrides'] : [],
        'property_currency' => strtoupper(trim((string) ($reservationRow->currency ?? 'MVR'))),
        'prices_include_tax' => true,
    ]);

    $totalAmount = (float) ($pricing['invoice_total_amount'] ?? $notes['invoice_total_amount'] ?? $reservationRow->total_amount ?? 0);

    try {
        $paymentQuote = CheckoutPaymentRouter::buildPaymentQuote([
            'primary_nationality' => $primaryNationality,
            'guest_residency' => $guestResidency,
            'reservation_currency' => strtoupper(trim((string) ($reservationRow->currency ?? 'MVR'))),
            'amount' => $totalAmount,
        ]);
    } catch (\InvalidArgumentException $exception) {
        return back()->withErrors(['transfer' => $exception->getMessage()]);
    }

    $notes['primary_nationality'] = $primaryNationality;
    $notes['guest_residency'] = $guestResidency;
    $notes['transfer_option'] = (string) ($pricing['transfer_option'] ?? $selectedTransferOption);
    $notes['transfer_option_label'] = (string) ($pricing['transfer_option_label'] ?? 'No transfer');
    $notes['transfer_charge'] = (float) ($pricing['transfer_charge_total'] ?? 0);
    $notes['transfer_charge_total'] = (float) ($pricing['transfer_charge_total'] ?? 0);
    $notes['transfer_local_adult_rate'] = (float) ($pricing['transfer_local_adult_rate'] ?? 0);
    $notes['transfer_local_child_rate'] = (float) ($pricing['transfer_local_child_rate'] ?? 0);
    $notes['transfer_foreign_adult_rate'] = (float) ($pricing['transfer_foreign_adult_rate'] ?? 0);
    $notes['transfer_foreign_child_rate'] = (float) ($pricing['transfer_foreign_child_rate'] ?? 0);
    $notes['transfer_applied_adult_rate'] = (float) ($pricing['transfer_applied_adult_rate'] ?? 0);
    $notes['transfer_applied_child_rate'] = (float) ($pricing['transfer_applied_child_rate'] ?? 0);
    $notes['discount_amount'] = (float) ($pricing['discount_amount'] ?? ($notes['discount_amount'] ?? 0));
    $notes['discounted_subtotal'] = (float) ($pricing['discounted_subtotal'] ?? ($notes['discounted_subtotal'] ?? 0));
    $notes['tax_amount'] = (float) ($pricing['total_tax_amount'] ?? ($notes['tax_amount'] ?? 0));
    $notes['total_tax_amount'] = (float) ($pricing['total_tax_amount'] ?? ($notes['total_tax_amount'] ?? 0));
    $notes['tax_lines'] = $pricing['tax_lines'] ?? ($notes['tax_lines'] ?? []);
    $notes['invoice_total_amount'] = $totalAmount;
    $notes['quote_source_currency'] = (string) ($paymentQuote['source_currency'] ?? '');
    $notes['quote_source_amount'] = (float) ($paymentQuote['source_amount'] ?? 0);
    $notes['quote_payment_currency'] = (string) ($paymentQuote['currency'] ?? '');
    $notes['quote_payment_amount'] = (float) ($paymentQuote['amount'] ?? 0);
    $notes['quote_gateway'] = (string) ($paymentQuote['gateway'] ?? '');
    $notes['quote_provider'] = (string) ($paymentQuote['provider'] ?? '');
    $notes['quote_gateway_label'] = (string) ($paymentQuote['gateway_label'] ?? '');
    $notes['quote_provider_label'] = (string) ($paymentQuote['provider_label'] ?? '');
    $notes['quote_fx_rate'] = (float) ($paymentQuote['fx_rate'] ?? 1);
    $notes['quote_fx_base_currency'] = (string) ($paymentQuote['fx_base_currency'] ?? 'MVR');
    $notes['quote_quoted_at'] = (string) ($paymentQuote['quoted_at'] ?? now()->toIso8601String());

    DB::table('vendor_reservations')
        ->where('id', $reservation)
        ->update([
            'total_amount' => $totalAmount,
            'notes' => json_encode($notes),
            'updated_at' => now(),
        ]);

    $checkoutQuery = [];
    $checkin = trim((string) ($notes['service_start_date'] ?? ''));
    $checkout = trim((string) ($notes['service_end_date'] ?? ''));
    if ($checkin !== '') {
        $checkoutQuery['checkin'] = $checkin;
    }
    if ($checkout !== '') {
        $checkoutQuery['checkout'] = $checkout;
    }

    $redirectUrl = '/booking/checkout/' . $reservation;
    if ($checkoutQuery !== []) {
        $redirectUrl .= '?' . http_build_query($checkoutQuery, '', '&', PHP_QUERY_RFC3986);
    }

    return redirect($redirectUrl);
});

Route::post('/booking/checkout/{reservation}/guest-details', function (Request $request, int $reservation) {
    if (!Schema::hasTable('vendor_reservations')) {
        abort(404);
    }

    $reservationRow = DB::table('vendor_reservations')->where('id', $reservation)->first();
    if (!$reservationRow) {
        abort(404);
    }

    if (in_array(strtolower(trim((string) ($reservationRow->status ?? 'pending'))), ['cancelled', 'canceled'], true)) {
        return back()->withErrors(['guest_details' => 'Cancelled reservations cannot be updated.']);
    }

    if (strtolower(trim((string) ($reservationRow->payment_status ?? 'unpaid'))) === 'paid') {
        return back()->withErrors(['guest_details' => 'Guest details cannot be changed after payment.']);
    }

    $validated = $request->validate([
        'primary_first_name' => ['required', 'string', 'max:80'],
        'primary_last_name' => ['required', 'string', 'max:80'],
        'primary_nationality' => ['required', 'string', 'max:80'],
        'guest_residency' => ['nullable', Rule::in(['local_resident', 'foreign_national'])],
        'primary_email' => ['required', 'email', 'max:120'],
        'primary_mobile' => ['required', 'string', 'max:32'],
        'additional_guest_details' => ['nullable', 'string', 'max:2000'],
        'service_notes' => ['nullable', 'string', 'max:2000'],
    ]);

    $notes = workationReservationPaymentNotes($reservationRow);
    $categoryKey = strtolower(trim((string) ($notes['category_key'] ?? '')));

    $primaryNationality = trim((string) ($validated['primary_nationality'] ?? ''));
    $guestResidency = strtolower(trim((string) ($validated['guest_residency'] ?? '')));
    if (!in_array($guestResidency, ['local_resident', 'foreign_national'], true)) {
        $guestResidency = ReservationPricingPolicy::isForeigner($primaryNationality, null)
            ? 'foreign_national'
            : 'local_resident';
    }

    $transferOptions = collect($notes['property_transfer_options'] ?? [])->filter(static fn ($option) => is_array($option))->values();
    $selectedTransferOption = strtolower(trim((string) ($notes['transfer_option'] ?? 'none')));
    if ($selectedTransferOption === '') {
        $selectedTransferOption = 'none';
    }

    $pricing = ReservationPricingPolicy::calculate([
        'listing_category' => $categoryKey !== '' ? $categoryKey : 'accommodation',
        'subtotal_amount' => (float) ($notes['subtotal_amount'] ?? $notes['room_subtotal'] ?? $reservationRow->total_amount ?? 0),
        'discount_percent' => (float) ($notes['discount_percent'] ?? 0),
        'adults' => max(1, (int) ($notes['adults'] ?? 1)),
        'children' => max(0, (int) ($notes['children'] ?? 0)),
        'infants' => max(0, (int) ($notes['infants'] ?? 0)),
        'nights' => max(1, (int) ($notes['nights'] ?? 1)),
        'room_count' => max(1, (int) ($notes['rooms'] ?? 1)),
        'primary_nationality' => $primaryNationality,
        'guest_residency' => $guestResidency,
        'transfer_option' => $selectedTransferOption,
        'property_transfer_options' => $transferOptions->all(),
        'vendor_tax_overrides' => is_array($notes['vendor_tax_overrides'] ?? null) ? $notes['vendor_tax_overrides'] : [],
        'property_currency' => strtoupper(trim((string) ($reservationRow->currency ?? 'MVR'))),
        'prices_include_tax' => true,
    ]);

    $totalAmount = (float) ($pricing['invoice_total_amount'] ?? $notes['invoice_total_amount'] ?? $reservationRow->total_amount ?? 0);

    try {
        $paymentQuote = CheckoutPaymentRouter::buildPaymentQuote([
            'primary_nationality' => $primaryNationality,
            'guest_residency' => $guestResidency,
            'reservation_currency' => strtoupper(trim((string) ($reservationRow->currency ?? 'MVR'))),
            'amount' => $totalAmount,
        ]);
    } catch (\InvalidArgumentException $exception) {
        return back()->withErrors(['guest_details' => $exception->getMessage()]);
    }

    $notes['primary_first_name'] = trim((string) ($validated['primary_first_name'] ?? ''));
    $notes['primary_last_name'] = trim((string) ($validated['primary_last_name'] ?? ''));
    $notes['primary_nationality'] = $primaryNationality;
    $notes['guest_residency'] = $guestResidency;
    $notes['primary_email'] = strtolower(trim((string) ($validated['primary_email'] ?? '')));
    $notes['primary_mobile'] = trim((string) ($validated['primary_mobile'] ?? ''));
    $notes['additional_guest_details'] = trim((string) ($validated['additional_guest_details'] ?? ''));
    $notes['service_notes'] = trim((string) ($validated['service_notes'] ?? ''));
    $notes['transfer_option'] = (string) ($pricing['transfer_option'] ?? $selectedTransferOption);
    $notes['transfer_option_label'] = (string) ($pricing['transfer_option_label'] ?? ($notes['transfer_option_label'] ?? 'No transfer'));
    $notes['transfer_charge'] = (float) ($pricing['transfer_charge_total'] ?? ($notes['transfer_charge'] ?? 0));
    $notes['transfer_charge_total'] = (float) ($pricing['transfer_charge_total'] ?? ($notes['transfer_charge_total'] ?? 0));
    $notes['discount_amount'] = (float) ($pricing['discount_amount'] ?? ($notes['discount_amount'] ?? 0));
    $notes['discounted_subtotal'] = (float) ($pricing['discounted_subtotal'] ?? ($notes['discounted_subtotal'] ?? 0));
    $notes['tax_amount'] = (float) ($pricing['total_tax_amount'] ?? ($notes['tax_amount'] ?? 0));
    $notes['total_tax_amount'] = (float) ($pricing['total_tax_amount'] ?? ($notes['total_tax_amount'] ?? 0));
    $notes['tax_lines'] = $pricing['tax_lines'] ?? ($notes['tax_lines'] ?? []);
    $notes['invoice_total_amount'] = $totalAmount;
    $notes['quote_source_currency'] = (string) ($paymentQuote['source_currency'] ?? '');
    $notes['quote_source_amount'] = (float) ($paymentQuote['source_amount'] ?? 0);
    $notes['quote_payment_currency'] = (string) ($paymentQuote['currency'] ?? '');
    $notes['quote_payment_amount'] = (float) ($paymentQuote['amount'] ?? 0);
    $notes['quote_gateway'] = (string) ($paymentQuote['gateway'] ?? '');
    $notes['quote_provider'] = (string) ($paymentQuote['provider'] ?? '');
    $notes['quote_gateway_label'] = (string) ($paymentQuote['gateway_label'] ?? '');
    $notes['quote_provider_label'] = (string) ($paymentQuote['provider_label'] ?? '');
    $notes['quote_fx_rate'] = (float) ($paymentQuote['fx_rate'] ?? 1);
    $notes['quote_fx_base_currency'] = (string) ($paymentQuote['fx_base_currency'] ?? 'MVR');
    $notes['quote_quoted_at'] = (string) ($paymentQuote['quoted_at'] ?? now()->toIso8601String());

    DB::table('vendor_reservations')
        ->where('id', $reservation)
        ->update([
            'total_amount' => $totalAmount,
            'customer_email' => $notes['primary_email'],
            'notes' => json_encode($notes),
            'updated_at' => now(),
        ]);

    // Sea transport and water sports do not require a transfer selection step.
    $skipTransferCategories = ['sea_transport', 'water_sports'];
    if (in_array($categoryKey, $skipTransferCategories, true)) {
        $skipCheckoutQuery = [];
        $skipCheckin = trim((string) ($notes['service_start_date'] ?? ''));
        $skipCheckout = trim((string) ($notes['service_end_date'] ?? ''));
        if ($skipCheckin !== '') {
            $skipCheckoutQuery['checkin'] = $skipCheckin;
        }
        if ($skipCheckout !== '') {
            $skipCheckoutQuery['checkout'] = $skipCheckout;
        }
        $skipRedirectUrl = '/booking/checkout/' . $reservation;
        if ($skipCheckoutQuery !== []) {
            $skipRedirectUrl .= '?' . http_build_query($skipCheckoutQuery, '', '&', PHP_QUERY_RFC3986);
        }
        return redirect($skipRedirectUrl)
            ->with('status', 'Guest details saved. Proceed to select a payment method.');
    }

    return redirect('/booking/checkout/' . $reservation . '/transfer')
        ->with('status', 'Guest details saved. Continue with transfer selection.');
});

Route::post('/booking/checkout/{reservation}/payment-intent', function (Request $request, int $reservation) {
    if (!Schema::hasTable('vendor_reservations')) {
        abort(404);
    }

    $reservationRow = DB::table('vendor_reservations')->where('id', $reservation)->first();
    if (!$reservationRow) {
        abort(404);
    }

    $notes = workationReservationPaymentNotes($reservationRow);

    $reservationCategoryKey = strtolower(trim((string) ($notes['category_key'] ?? '')));
    $requiresCustomerAuth = true;
    if ($requiresCustomerAuth && !(bool) $request->session()->get('portal_customer_authenticated', false)) {
        return redirect('/portal/customer/login?continue=' . urlencode('/booking/checkout/' . $reservation))
            ->with('status', 'Please sign in or create a customer account to continue checkout and payment.');
    }

    $validated = $request->validate([
        'payment_currency' => ['nullable', 'string', 'min:3', 'max:8'],
        'payment_gateway' => ['nullable', 'string', 'min:2', 'max:64'],
        'payment_provider' => ['nullable', 'string', 'min:2', 'max:64'],
        'payment_selection' => ['nullable', 'string', 'max:120'],
        'primary_nationality' => ['nullable', 'string', 'max:120'],
        'guest_residency' => ['nullable', Rule::in(['local_resident', 'foreign_national'])],
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

    $primaryNationality = trim((string) ($notes['primary_nationality'] ?? $validated['primary_nationality'] ?? ''));
    $guestResidency = strtolower(trim((string) ($notes['guest_residency'] ?? $validated['guest_residency'] ?? '')));
    if (!in_array($guestResidency, ['local_resident', 'foreign_national'], true)) {
        $guestResidency = ReservationPricingPolicy::isForeigner($primaryNationality, null)
            ? 'foreign_national'
            : 'local_resident';
    }

    $notes['primary_nationality'] = $primaryNationality;
    $notes['guest_residency'] = $guestResidency;
    $notes['transfer_option'] = trim((string) ($validated['transfer_option'] ?? ($notes['transfer_option'] ?? '')));
    $notes['transfer_option_label'] = trim((string) ($validated['transfer_option_label'] ?? ($notes['transfer_option_label'] ?? '')));
    $normalizedTransferOption = strtolower(trim((string) ($notes['transfer_option'] ?? '')));
    $isNoTransfer = in_array($normalizedTransferOption, ['', 'none', 'no_transfer', 'decline', 'declined'], true);
    if ($isNoTransfer) {
        $notes['transfer_option'] = 'none';
        if (trim((string) ($notes['transfer_option_label'] ?? '')) === '') {
            $notes['transfer_option_label'] = 'No transfer';
        }
    }

    $notes['transfer_charge'] = $isNoTransfer
        ? 0.0
        : max(0, (float) ($validated['transfer_charge'] ?? ($notes['transfer_charge'] ?? 0)));
    $notes['transfer_charge_total'] = $notes['transfer_charge'];
    $notes['invoice_total_amount'] = max(0, (float) ($validated['invoice_total_amount'] ?? ($notes['invoice_total_amount'] ?? ($reservationRow->total_amount ?? 0))));

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

    // Backward compatibility: if route fields are not posted, allow router defaults
    // based on reservation context (segment, allowed currencies, and gateway availability).
    if ($requestedGateway === '') {
        $requestedGateway = null;
    }
    if ($requestedCurrency === '') {
        $requestedCurrency = null;
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

    $provider = strtolower(trim((string) ($intent['provider'] ?? '')));
    $successReturnUrl = workationPaymentSuccessReturnUrl($reservation, $provider);
    $cancelReturnUrl = url('/booking/checkout/' . $reservation);

    $stripeSession = null;
    if (strtolower(trim((string) ($intent['provider'] ?? ''))) === 'stripe') {
        $stripeSession = workationCreateStripeCheckoutSession([
            'reservation_id' => $reservation,
            'intent_id' => (string) ($intent['intent_id'] ?? ''),
            'amount' => (float) ($intent['amount'] ?? 0),
            'currency' => (string) ($intent['currency'] ?? ''),
            'return_url' => $successReturnUrl,
            'cancel_url' => $cancelReturnUrl,
            'customer_email' => (string) ($reservationRow->customer_email ?? ''),
        ]);

        if (is_array($stripeSession)) {
            $intent['stripe_session_id'] = (string) ($stripeSession['id'] ?? '');
            $intent['stripe_payment_status'] = (string) ($stripeSession['payment_status'] ?? '');
            $intent['stripe_session_status'] = (string) ($stripeSession['status'] ?? '');
            $intent['checkout_url'] = (string) ($stripeSession['url'] ?? '');
        }
    }

        // BML Connect: create a per-transaction URL via the BML Connect API.
        // The redirectUrl for BML is the browser return point after the customer pays.
        $bmlTransaction = null;
        if ($provider === 'bml' && workationIsBmlConnectConfigured((string) ($intent['gateway'] ?? ''))) {
            $bmlRedirectUrl = url('/booking/payment/webhooks/' . rawurlencode((string) ($intent['gateway'] ?? 'bml_mvr'))
                . '?reservation_id=' . $reservation
                . '&intent_id=' . rawurlencode((string) ($intent['intent_id'] ?? '')));
            $bmlTransaction = workationCreateBmlConnectTransaction([
                'gateway'        => (string) ($intent['gateway'] ?? ''),
                'reservation_id' => $reservation,
                'intent_id'      => (string) ($intent['intent_id'] ?? ''),
                'amount'         => (float) ($intent['amount'] ?? 0),
                'currency'       => (string) ($intent['currency'] ?? ''),
                'redirect_url'   => $bmlRedirectUrl,
            ]);

            if (is_array($bmlTransaction)) {
                $intent['bml_transaction_id'] = (string) ($bmlTransaction['id'] ?? '');
                $intent['bml_transaction_state'] = (string) ($bmlTransaction['state'] ?? '');
                $intent['checkout_url'] = (string) ($bmlTransaction['url'] ?? '');
            }
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
    if ($checkoutUrl !== '' && !preg_match('/^https?:\/\//i', $checkoutUrl)) {
        $checkoutUrl = 'https://' . ltrim($checkoutUrl, '/');
    }
    $gatewayMode = strtolower(trim((string) ($intent['gateway_mode'] ?? 'internal')));
    $provider = strtolower(trim((string) ($intent['provider'] ?? '')));

    logger()->info('Checkout redirect evaluation', [
        'reservation_id' => $reservation,
        'gateway' => (string) ($intent['gateway'] ?? ''),
        'provider' => $provider,
        'gateway_mode' => $gatewayMode,
        'checkout_url_present' => $checkoutUrl !== '',
        'checkout_url' => $checkoutUrl,
    ]);

    if ($provider === 'stripe' && is_array($stripeSession) && $checkoutUrl !== '') {
        return redirect()->away($checkoutUrl);
    }

    // BML Connect: URL is already fully formed by the API — redirect directly without appending query params.
    if ($provider === 'bml' && is_array($bmlTransaction) && $checkoutUrl !== '') {
        return redirect()->away($checkoutUrl);
    }

    if ($checkoutUrl !== '') {
        if (!filter_var($checkoutUrl, FILTER_VALIDATE_URL)) {
            logger()->warning('External gateway checkout URL is invalid', [
                'reservation_id' => $reservation,
                'gateway' => (string) ($intent['gateway'] ?? ''),
                'provider' => $provider,
                'checkout_url' => $checkoutUrl,
            ]);

            return back()->withErrors([
                'payment' => 'Selected payment gateway checkout URL is invalid. Please contact support to update gateway configuration.',
            ]);
        }

        $gateway = (string) ($intent['gateway'] ?? '');
        $payload = workationBuildGatewayCheckoutPayload($gateway, [
            'intent_id' => (string) ($intent['intent_id'] ?? ''),
            'reservation_id' => $reservation,
            'amount' => (float) ($intent['amount'] ?? 0),
            'currency' => (string) ($intent['currency'] ?? ''),
            'provider' => (string) ($intent['provider'] ?? ''),
            'return_url' => $successReturnUrl,
            'cancel_url' => $cancelReturnUrl,
            'webhook_url' => url('/booking/payment/webhooks/' . $gateway),
            'customer_email' => (string) ($reservationRow->customer_email ?? ''),
        ]);
        $payload = workationSignGatewayCheckoutPayload($gateway, $payload);
        $query = http_build_query($payload, '', '&', PHP_QUERY_RFC3986);
        $target = $checkoutUrl . (str_contains($checkoutUrl, '?') ? '&' : '?') . $query;

        return redirect()->away($target);
    }

    if (!app()->environment('testing') && in_array($provider, ['bml', 'mib'], true)) {
        logger()->warning('Bank gateway missing checkout URL; simulator fallback blocked', [
            'reservation_id' => $reservation,
            'gateway' => (string) ($intent['gateway'] ?? ''),
            'provider' => $provider,
            'gateway_mode' => $gatewayMode,
        ]);

        return back()->withErrors([
            'payment' => 'Selected bank gateway is not fully configured (missing checkout URL). Please contact support to complete gateway setup.',
        ]);
    }

    if ($gatewayMode === 'external') {
        logger()->warning('External gateway missing checkout URL', [
            'reservation_id' => $reservation,
            'gateway' => (string) ($intent['gateway'] ?? ''),
            'provider' => $provider,
            'gateway_mode' => $gatewayMode,
        ]);

        return back()->withErrors([
            'payment' => 'Selected payment gateway is configured as external, but its checkout URL is missing. Please contact support to complete payment setup.',
        ]);
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

    $payload = json_decode((string) ($reservationRow->payment_payload_json ?? ''), true);
    if (!is_array($payload)) {
        $payload = [];
    }
    $gatewayMode = strtolower(trim((string) ($payload['gateway_mode'] ?? 'internal')));
    $provider = strtolower(trim((string) ($payload['provider'] ?? '')));
    $checkoutUrl = trim((string) ($payload['checkout_url'] ?? ''));
    if ($checkoutUrl !== '' && !preg_match('/^https?:\/\//i', $checkoutUrl)) {
        $checkoutUrl = 'https://' . ltrim($checkoutUrl, '/');
    }
    if ($checkoutUrl !== '') {
        if (!filter_var($checkoutUrl, FILTER_VALIDATE_URL)) {
            return redirect('/booking/checkout/' . $reservation)
                ->withErrors(['payment' => 'Selected payment gateway checkout URL is invalid. Please contact support to update gateway configuration.']);
        }

        $gateway = (string) ($payload['gateway'] ?? $reservationRow->payment_gateway ?? '');
        $successReturnUrl = workationPaymentSuccessReturnUrl($reservation, $provider);
        $cancelReturnUrl = url('/booking/checkout/' . $reservation);

        $isBmlConnectIntent = $provider === 'bml' && trim((string) ($payload['bml_transaction_id'] ?? '')) !== '';
        if ($isBmlConnectIntent) {
            $target = $checkoutUrl;
        } else {
            $redirectPayload = workationBuildGatewayCheckoutPayload($gateway, [
                'intent_id' => (string) ($reservationRow->payment_intent_id ?? ''),
                'reservation_id' => $reservation,
                'amount' => (float) ($reservationRow->payment_amount ?? 0),
                'currency' => strtoupper(trim((string) ($reservationRow->payment_currency ?? 'MVR'))),
                'provider' => (string) ($payload['provider'] ?? ''),
                'return_url' => $successReturnUrl,
                'cancel_url' => $cancelReturnUrl,
                'webhook_url' => url('/booking/payment/webhooks/' . $gateway),
                'customer_email' => (string) ($reservationRow->customer_email ?? ''),
            ]);
            $redirectPayload = workationSignGatewayCheckoutPayload($gateway, $redirectPayload);
            $query = http_build_query($redirectPayload, '', '&', PHP_QUERY_RFC3986);
            $target = $checkoutUrl . (str_contains($checkoutUrl, '?') ? '&' : '?') . $query;
        }

        return redirect()->away($target);
    }

    if (!app()->environment('testing') && in_array($provider, ['bml', 'mib'], true)) {
        return redirect('/booking/checkout/' . $reservation)
            ->withErrors(['payment' => 'Selected bank gateway is not fully configured (missing checkout URL). Please contact support to complete gateway setup.']);
    }

    if ($gatewayMode === 'external') {
        return redirect('/booking/checkout/' . $reservation)
            ->withErrors(['payment' => 'Selected payment gateway is configured as external, but checkout URL is missing. Please contact support to complete payment setup.']);
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
    $provider = strtolower(trim((string) ($payload['provider'] ?? '')));
    $checkoutUrl = trim((string) ($payload['checkout_url'] ?? ''));
    if ($checkoutUrl !== '' && !preg_match('/^https?:\/\//i', $checkoutUrl)) {
        $checkoutUrl = 'https://' . ltrim($checkoutUrl, '/');
    }
    if ($checkoutUrl !== '') {
        if (!filter_var($checkoutUrl, FILTER_VALIDATE_URL)) {
            return back()->withErrors(['payment' => 'Selected payment gateway checkout URL is invalid. Please contact support to update gateway configuration.']);
        }

        $gateway = (string) ($payload['gateway'] ?? '');
        $provider = strtolower(trim((string) ($payload['provider'] ?? '')));
        $successReturnUrl = workationPaymentSuccessReturnUrl($reservation, $provider);
        $cancelReturnUrl = url('/booking/checkout/' . $reservation);

        $isBmlConnectIntent = $provider === 'bml' && trim((string) ($payload['bml_transaction_id'] ?? '')) !== '';
        if ($isBmlConnectIntent) {
            $target = $checkoutUrl;
        } else {
            $redirectPayload = workationBuildGatewayCheckoutPayload($gateway, [
                'intent_id' => (string) ($reservationRow->payment_intent_id ?? ''),
                'reservation_id' => $reservation,
                'amount' => (float) ($reservationRow->payment_amount ?? 0),
                'currency' => strtoupper(trim((string) ($reservationRow->payment_currency ?? 'MVR'))),
                'provider' => (string) ($payload['provider'] ?? ''),
                'return_url' => $successReturnUrl,
                'cancel_url' => $cancelReturnUrl,
                'webhook_url' => url('/booking/payment/webhooks/' . $gateway),
                'customer_email' => (string) ($reservationRow->customer_email ?? ''),
            ]);
            $redirectPayload = workationSignGatewayCheckoutPayload($gateway, $redirectPayload);
            $query = http_build_query($redirectPayload, '', '&', PHP_QUERY_RFC3986);
            $target = $checkoutUrl . (str_contains($checkoutUrl, '?') ? '&' : '?') . $query;
        }

        return redirect()->away($target);
    }

    if (!app()->environment('testing') && in_array($provider, ['bml', 'mib'], true)) {
        return redirect('/booking/checkout/' . $reservation)
            ->withErrors(['payment' => 'Selected bank gateway is not fully configured (missing checkout URL). Please contact support to complete gateway setup.']);
    }

    workationApplyReservationPaymentEvent($reservationRow, [
        'event_id' => 'internal_' . Str::lower(Str::random(20)),
        'intent_id' => (string) $validated['intent_id'],
        'reference' => trim((string) ($validated['payment_reference'] ?? ('INT-' . $reservation))),
        'status' => 'paid',
    ]);

    return redirect(workationPaymentSuccessReturnUrl($reservation, $provider))
        ->with('portal_notice', 'Payment recorded and reservation confirmed.');
});

Route::match(['get', 'post'], '/booking/payment/webhooks/{gateway}', function (Request $request, string $gateway) {
    if (!Schema::hasTable('vendor_reservations')) {
        abort(404);
    }

    $gateway = strtolower(trim($gateway));

    // Some gateways return customers to this URL via GET.
    // Treat GET as a browser-return handoff (not as a signed webhook event).
    if (strtoupper((string) $request->method()) === 'GET') {
        $reservationId = (int) $request->query('reservation_id', 0);
        $intentId = trim((string) $request->query('intent_id', ''));

        if ($reservationId <= 0 && $intentId !== '') {
            $reservationId = (int) (DB::table('vendor_reservations')
                ->where('payment_intent_id', $intentId)
                ->value('id') ?? 0);
        }

        if ($reservationId > 0) {
            $reservationRow = DB::table('vendor_reservations')->where('id', $reservationId)->first();

            if ($reservationRow
                && in_array($gateway, ['bml', 'bml_mvr', 'bml_usd'], true)
                && strtolower(trim((string) ($reservationRow->payment_status ?? 'unpaid'))) !== 'paid') {
                $bmlState = strtoupper(trim((string) ($request->query('state', $request->query('status', '')))));
                $bmlTransactionId = trim((string) ($request->query('transactionId', $request->query('transaction_id', ''))));

                if ($bmlTransactionId === '') {
                    $paymentPayload = json_decode((string) ($reservationRow->payment_payload_json ?? ''), true);
                    if (is_array($paymentPayload)) {
                        $bmlTransactionId = trim((string) ($paymentPayload['bml_transaction_id'] ?? ''));
                    }
                }

                if ($bmlState === '' && $bmlTransactionId !== '' && in_array($gateway, ['bml_mvr', 'bml_usd'], true)) {
                    $statusSnapshot = workationFetchBmlConnectTransactionStatus($gateway, $bmlTransactionId);
                    if (is_array($statusSnapshot)) {
                        $bmlState = strtoupper(trim((string) ($statusSnapshot['state'] ?? '')));
                        $bmlTransactionId = trim((string) ($statusSnapshot['transaction_id'] ?? $bmlTransactionId));
                    }
                }

                if ($bmlState === 'CONFIRMED') {
                    workationApplyReservationPaymentEvent($reservationRow, [
                        'event_id' => 'bml_browser_' . ($bmlTransactionId !== '' ? $bmlTransactionId : Str::lower(Str::random(16))),
                        'intent_id' => (string) ($reservationRow->payment_intent_id ?? ''),
                        'reference' => $bmlTransactionId,
                        'status' => 'paid',
                    ]);

                    return redirect(workationPaymentSuccessReturnUrl($reservationId, $gateway))
                        ->with('portal_notice', 'Payment verified successfully and your booking is now confirmed.');
                }

                if (in_array($bmlState, ['DECLINED', 'CANCELLED'], true)) {
                    $mappedFailureStatus = $bmlState === 'CANCELLED' ? 'cancelled' : 'failed';

                    workationApplyReservationPaymentEvent($reservationRow, [
                        'event_id' => 'bml_browser_' . ($bmlTransactionId !== '' ? $bmlTransactionId : Str::lower(Str::random(16))),
                        'intent_id' => (string) ($reservationRow->payment_intent_id ?? ''),
                        'reference' => $bmlTransactionId,
                        'status' => $mappedFailureStatus,
                        'error' => 'BML state: ' . $bmlState,
                    ]);

                    return redirect(workationPaymentFailureReturnUrl($reservationId, $gateway))
                        ->withErrors(['payment' => 'BML payment did not complete (' . $bmlState . '). Please retry or use another card.']);
                }
            }

            return redirect(workationPaymentSuccessReturnUrl($reservationId, $gateway))
                ->with('portal_notice', 'Payment return received. We are verifying your payment status.');
        }

        return redirect('/customer?section=bookings&payment=processing')
            ->with('portal_notice', 'Payment return received. Please refresh your bookings in a moment.');
    }

    $raw = (string) $request->getContent();

    if ($gateway === 'stripe' && trim((string) $request->header('Stripe-Signature', '')) !== '') {
        $gatewayConfig = CheckoutPaymentRouter::gatewayConfig('stripe');
        $stripeSecret = trim((string) ($gatewayConfig['webhook_secret'] ?? env('STRIPE_WEBHOOK_SECRET', env('STRIPE_WEBHOOK_SIGNING_SECRET', ''))));
        $stripeSignature = trim((string) $request->header('Stripe-Signature', ''));
        if (!workationVerifyStripeWebhookSignature($raw, $stripeSignature, $stripeSecret)) {
            Log::warning('Stripe webhook rejected: invalid signature', [
                'gateway' => $gateway,
                'has_secret' => $stripeSecret !== '',
            ]);
            return response()->json(['ok' => false, 'message' => 'Invalid signature'], 401);
        }

        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            return response()->json(['ok' => false, 'message' => 'Invalid payload'], 422);
        }

        $eventId = trim((string) ($payload['id'] ?? ''));
        $eventType = trim((string) ($payload['type'] ?? ''));
        $eventObject = $payload['data']['object'] ?? [];
        if (!is_array($eventObject)) {
            $eventObject = [];
        }

        $reservationId = (int) (
            $eventObject['metadata']['reservation_id']
            ?? $eventObject['client_reference_id']
            ?? 0
        );

        if ($reservationId <= 0) {
            return response()->json(['ok' => true, 'result' => 'ignored']);
        }

        $reservationRow = DB::table('vendor_reservations')->where('id', $reservationId)->first();
        if (!$reservationRow) {
            // Acknowledge unknown reservation IDs to prevent endless Stripe retries.
            return response()->json(['ok' => true, 'result' => 'ignored']);
        }

        $mappedStatus = 'ignored';
        if (in_array($eventType, ['checkout.session.completed', 'checkout.session.async_payment_succeeded', 'payment_intent.succeeded'], true)) {
            $mappedStatus = 'paid';
        } elseif (in_array($eventType, ['payment_intent.canceled'], true)) {
            $mappedStatus = 'canceled';
        } elseif ($eventType === 'charge.refunded') {
            $mappedStatus = 'cancelled';
        }

        if ($mappedStatus === 'ignored') {
            return response()->json(['ok' => true, 'result' => 'ignored']);
        }

        $result = workationApplyReservationPaymentEvent($reservationRow, [
            'event_id' => $eventId,
            'intent_id' => (string) ($eventObject['metadata']['intent_id'] ?? $reservationRow->payment_intent_id ?? ''),
            'reference' => (string) ($eventObject['id'] ?? $eventId),
            'status' => $mappedStatus,
            'error' => '',
        ]);

        return response()->json(['ok' => true, 'result' => $result['status'] ?? 'processed']);
    }

    $signature = trim((string) $request->header('X-Workation-Signature', ''));

        // BML Connect server-to-server payment notification.
        // BML sends a JSON POST with transactionId, localId, state, amount, currency.
        // The localId is the value we set when creating the transaction (WRK-{id}-{hash}).
        if (in_array($gateway, ['bml_mvr', 'bml_usd'], true)) {
            $payload = json_decode($raw, true);
            if (!is_array($payload)) {
                return response()->json(['ok' => false, 'message' => 'Invalid BML payload'], 422);
            }

            // Extract reservation ID from localId (format: WRK-{reservationId}-{hash})
            $localId = trim((string) ($payload['localId'] ?? ''));
            $reservationId = 0;
            if (preg_match('/^WRK-(\d+)-/', $localId, $matches)) {
                $reservationId = (int) $matches[1];
            }

            // Fallback: look up by BML transaction ID stored in payment_payload_json
            if ($reservationId <= 0) {
                $transactionId = trim((string) ($payload['transactionId'] ?? ($payload['id'] ?? '')));
                if ($transactionId !== '') {
                    $reservationId = (int) (DB::table('vendor_reservations')
                        ->where('payment_payload_json', 'like', '%' . $transactionId . '%')
                        ->value('id') ?? 0);
                }
            }

            if ($reservationId <= 0) {
                Log::warning('BML Connect webhook: cannot resolve reservation from localId', [
                    'gateway'   => $gateway,
                    'local_id'  => $localId,
                    'payload'   => $payload,
                ]);
                return response()->json(['ok' => false, 'message' => 'Reservation not found'], 404);
            }

            $reservationRow = DB::table('vendor_reservations')->where('id', $reservationId)->first();
            if (!$reservationRow) {
                return response()->json(['ok' => false, 'message' => 'Reservation not found'], 404);
            }

            // Map BML state to internal status.
            // BML Connect states: INITIATED, CONFIRMED, DECLINED, CANCELLED.
            $bmlState = strtoupper(trim((string) ($payload['state'] ?? '')));
            $mappedStatus = match ($bmlState) {
                'CONFIRMED' => 'paid',
                'DECLINED'  => 'failed',
                'CANCELLED' => 'cancelled',
                default     => 'failed',
            };

            $transactionId = trim((string) ($payload['transactionId'] ?? ($payload['id'] ?? '')));

            $result = workationApplyReservationPaymentEvent($reservationRow, [
                'event_id' => 'bml_' . ($transactionId !== '' ? $transactionId : Str::lower(Str::random(16))),
                'intent_id' => (string) ($reservationRow->payment_intent_id ?? ''),
                'reference' => $transactionId,
                'status'    => $mappedStatus,
                'error'     => $mappedStatus !== 'paid' ? ('BML state: ' . $bmlState) : '',
            ]);

            Log::info('BML Connect webhook processed', [
                'reservation_id' => $reservationId,
                'gateway'        => $gateway,
                'bml_state'      => $bmlState,
                'mapped_status'  => $mappedStatus,
                'transaction_id' => $transactionId,
            ]);

            return response()->json(['ok' => true, 'result' => $result['status'] ?? 'processed']);
        }

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

// ── Water sports multi-item cart booking ───────────────────────────────────
Route::post('/booking/water-sports-cart', function (Request $request) {
    $payload = $request->validate([
        'property_id'        => ['required', 'integer', 'min:1'],
        'visitor_residency'  => ['nullable', Rule::in(['local_resident', 'foreign_national'])],
        'service_date'       => ['required', 'date', 'after_or_equal:today'],
        'primary_first_name' => ['required', 'string', 'max:80'],
        'primary_last_name'  => ['required', 'string', 'max:80'],
        'primary_email'      => ['required', 'email', 'max:190'],
        'primary_mobile'     => ['required', 'string', 'max:40', 'regex:/^\+?[0-9][0-9\s\-()]{5,39}$/'],
        'service_notes'      => ['nullable', 'string', 'max:500'],
        'cart_items'         => ['required', 'string'],
    ], [
        'primary_mobile.regex' => 'Please enter a valid mobile number.',
    ]);

    // Decode and validate cart items JSON
    $cartRaw = json_decode((string) $payload['cart_items'], true);
    if (!is_array($cartRaw) || empty($cartRaw)) {
        return back()->withErrors(['booking' => 'Your cart is empty. Please add at least one equipment item.']);
    }

    $propertyRow = VendorPropertyCompatibilityReader::loadPropertyById((int) $payload['property_id']);
    if (!$propertyRow) {
        abort(404);
    }

    $listingStatus = strtolower(trim((string) ($propertyRow->status ?? 'inactive')));
    if ($listingStatus !== 'active') {
        abort(404);
    }

    $listingCategory = strtolower(trim((string) ($propertyRow->listing_category ?? '')));
    if ($listingCategory !== 'water_sports') {
        abort(404);
    }

    if (isset($propertyRow->listing_moderation_status)) {
        $moderationStatus = strtolower(trim((string) ($propertyRow->listing_moderation_status ?? 'draft')));
        if ($moderationStatus !== 'approved') {
            return back()->withErrors(['booking' => 'This listing is not yet available for bookings.']);
        }
    }

    // Validate + price each cart item against live DB records
    if (!Schema::hasTable('vendor_water_sports_rental_items')) {
        return back()->withErrors(['booking' => 'Equipment booking is not yet available for this property.']);
    }

    $visitorResidency = strtolower(trim((string) ($payload['visitor_residency'] ?? 'foreign_national')));
    $visitorIsLocal   = $visitorResidency === 'local_resident';
    $mvrUsdRate       = (float) env('MVR_USD_RATE', 15.42);
    $currency         = $visitorIsLocal ? 'MVR' : 'USD';

    $vendorUserId     = (int) ($propertyRow->vendor_user_id ?? $propertyRow->user_id ?? 0);
    $primaryFirstName = trim((string) $payload['primary_first_name']);
    $primaryLastName  = trim((string) $payload['primary_last_name']);
    $primaryEmail     = trim((string) $payload['primary_email']);
    $primaryMobile    = trim((string) $payload['primary_mobile']);
    $serviceNotes     = trim((string) ($payload['service_notes'] ?? ''));
    $serviceDate      = \Carbon\Carbon::parse((string) $payload['service_date'])->startOfDay();
    $customerName     = trim($primaryFirstName . ' ' . $primaryLastName);

    // Collect unique item IDs from cart
    $requestedItemIds = collect($cartRaw)
        ->map(static fn ($entry) => (int) ($entry['itemId'] ?? $entry['id'] ?? 0))
        ->filter(static fn (int $id) => $id > 0)
        ->unique()
        ->values()
        ->all();

    $dbItems = DB::table('vendor_water_sports_rental_items')
        ->whereIn('id', $requestedItemIds)
        ->where('vendor_property_id', (int) $propertyRow->id)
        ->where('status', 'active')
        ->get()
        ->keyBy('id');

    $lineItems  = [];
    $grandTotal = 0.0;
    $errors     = [];

    foreach ($cartRaw as $idx => $entry) {
        $itemId      = (int) ($entry['itemId'] ?? $entry['id'] ?? 0);
        $pricingType = in_array((string) ($entry['pricingType'] ?? $entry['pricing_type'] ?? 'hourly'), ['per_seat', 'hourly']) ? (string) ($entry['pricingType'] ?? $entry['pricing_type'] ?? 'hourly') : 'hourly';

        $dbItem = $dbItems->get($itemId);
        if (!$dbItem) {
            $errors[] = 'Item #' . ($idx + 1) . ' is no longer available.';
            continue;
        }

        $qtyAvail = (int) ($dbItem->quantity_available ?? 1);

        if ($pricingType === 'per_seat') {
            $adultQty = max(0, (int) ($entry['adultQty'] ?? 0));
            $childQty = max(0, (int) ($entry['childQty'] ?? 0));
            $qty      = $adultQty + $childQty;
            if ($qty === 0) continue;

            if ($visitorIsLocal) {
                $adultPrice = (float) ($dbItem->price_per_seat_adult_local ?? 0);
                $childPrice = (float) ($dbItem->price_per_seat_child_local ?? 0);
            } else {
                $adultPrice = (float) ($dbItem->price_per_seat_adult_usd ?? 0);
                $childPrice = (float) ($dbItem->price_per_seat_child_usd ?? 0);
            }

            $lineTotal  = ($adultQty * $adultPrice) + ($childQty * $childPrice);
            $unitPrice  = $qty > 0 ? round($lineTotal / $qty, 4) : 0;
            $durationMins = 0;
            $guestType  = 'mixed';
        } else {
            $qty          = max(1, (int) ($entry['qty'] ?? 1));
            $durationMins = max(1, (int) ($entry['durationMins'] ?? 30));
            $guestType    = in_array((string) ($entry['guestType'] ?? 'adult'), ['adult', 'child']) ? (string) $entry['guestType'] : 'adult';

            if ($qty > $qtyAvail) {
                $errors[] = 'Only ' . $qtyAvail . ' unit(s) of "' . $dbItem->name . '" available.';
                $qty = $qtyAvail;
            }

            if ($visitorIsLocal) {
                $unitPrice = $guestType === 'child'
                    ? (float) ($dbItem->price_per_hour_child_local ?? 0)
                    : (float) ($dbItem->price_per_hour_local ?? 0);
            } else {
                $unitPrice = $guestType === 'child'
                    ? (float) ($dbItem->price_per_hour_child_usd ?? 0)
                    : (float) ($dbItem->price_per_hour_usd ?? 0);
            }

            $lineTotal = $unitPrice * $qty * ($durationMins / 60);
        }

        $grandTotal += $lineTotal;

        $lineItems[] = [
            'item_id'       => $itemId,
            'item_name'     => (string) ($dbItem->name ?? ''),
            'equipment_type'=> (string) ($dbItem->equipment_type ?? 'other'),
            'category'      => (string) ($dbItem->equipment_category ?? 'other'),
            'guest_type'    => $guestType,
            'qty'           => $qty,
            'duration_mins' => $durationMins,
            'unit_price'    => $unitPrice,
            'line_total'    => $lineTotal,
            'currency'      => $currency,
        ];
    }

    if (!empty($errors)) {
        return back()->withErrors(['booking' => implode(' ', $errors)]);
    }

    if (empty($lineItems)) {
        return back()->withErrors(['booking' => 'No valid items in cart.']);
    }

    $reservationId = 0;

    if (Schema::hasTable('vendor_reservations')) {
        $reservationId = (int) DB::table('vendor_reservations')->insertGetId([
            'vendor_user_id'      => $vendorUserId,
            'vendor_property_id'  => (int) $propertyRow->id,
            'vendor_service_id'   => null,
            'customer_name'       => $customerName !== '' ? $customerName : 'Guest Customer',
            'customer_email'      => $primaryEmail !== '' ? $primaryEmail : 'guest@workation.local',
            'start_at'            => $serviceDate,
            'end_at'              => $serviceDate,
            'guests'              => max(1, array_sum(array_column($lineItems, 'qty'))),
            'total_amount'        => $grandTotal,
            'currency'            => $currency,
            'status'              => 'pending',
            'payment_status'      => 'unpaid',
            'notes'               => json_encode([
                'category_key'        => 'water_sports',
                'category_label'      => 'Water Sports',
                'service_start_date'  => $serviceDate->toDateString(),
                'service_end_date'    => $serviceDate->toDateString(),
                'primary_first_name'  => $primaryFirstName,
                'primary_last_name'   => $primaryLastName,
                'primary_email'       => $primaryEmail,
                'primary_mobile'      => $primaryMobile,
                'guest_residency'     => $visitorResidency,
                'service_notes'       => $serviceNotes,
                'booking_type'        => 'water_sports_multi_item',
                'line_items'          => $lineItems,
                'subtotal_amount'     => $grandTotal,
                'total_tax_amount'    => 0,
                'invoice_total_amount'=> $grandTotal,
                'currency'            => $currency,
                'mvr_usd_rate'        => $mvrUsdRate,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (Schema::hasColumn('vendor_reservations', 'listing_category')) {
            DB::table('vendor_reservations')
                ->where('id', $reservationId)
                ->update(['listing_category' => 'water_sports']);
        }
    }

    $checkoutUrl = '/booking/checkout'
        . ($reservationId ? ('/' . $reservationId) : '')
        . '?property_id=' . (int) $propertyRow->id
        . '&category_key=water_sports'
        . '&checkin=' . urlencode($serviceDate->toDateString())
        . '&checkout=' . urlencode($serviceDate->toDateString())
        . '&adults=' . max(1, array_sum(array_column($lineItems, 'qty')))
        . '&children=0'
        . '&primary_first_name=' . urlencode($primaryFirstName)
        . '&primary_last_name=' . urlencode($primaryLastName)
        . '&primary_email=' . urlencode($primaryEmail)
        . '&primary_mobile=' . urlencode($primaryMobile)
        . '&guest_residency=' . urlencode($visitorResidency)
        . '&service_notes=' . urlencode($serviceNotes)
        . '&total=' . urlencode((string) $grandTotal);

    return redirect($checkoutUrl);
});