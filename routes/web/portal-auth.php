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

Route::get('/portal/{portal}/login', function (Request $request, string $portal) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    if (!in_array($portal, ['admin', 'vendor', 'customer'], true)) {
        abort(404);
    }

    if ($portal === 'customer') {
        rememberCustomerPostAuthRedirect($request);
    }

    $config = portalConfig($portal);
    if (session()->get($config['session_key'], false)) {
        if ($portal === 'customer') {
            $postAuthRedirect = consumeCustomerPostAuthRedirect($request, '/');
            return redirect($postAuthRedirect);
        }

        return $portal === 'customer'
            ? redirect('/')
            : redirect(portalRoutePath($portal));
    }

    $socialProviders = [];
    if ($portal === 'customer') {
        $socialProviders = collect(supportedCustomerSocialProviders())
            ->mapWithKeys(static fn (string $provider) => [
                $provider => [
                    'configured' => isCustomerSocialProviderConfigured($provider),
                    'redirect' => '/portal/customer/oauth/' . $provider . '/redirect',
                ],
            ])
            ->all();
    } elseif ($portal === 'vendor') {
        $socialProviders = collect(supportedVendorSocialProviders())
            ->filter(static fn (string $provider) => in_array($provider, ['google', 'facebook'], true))
            ->mapWithKeys(static fn (string $provider) => [
                $provider => [
                    'configured' => isVendorSocialProviderConfigured($provider),
                    'redirect' => '/portal/vendor/oauth/' . $provider . '/redirect',
                ],
            ])
            ->all();
    }

    return view('portal-login', [
        'portal' => $portal,
        'portalName' => $config['name'],
        'socialProviders' => $socialProviders,
    ]);
});

Route::get('/portal/vendor/register', function (Request $request) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    $mode = strtolower(trim((string) $request->query('mode', 'email')));
    if (!in_array($mode, ['email', 'otp', 'minimal'], true)) {
        $mode = 'email';
    }

    if ($mode === 'otp' && trim((string) session('otp_email', '')) === '') {
        return redirect('/portal/vendor/register?mode=email');
    }

    $minimalPayload = session('vendor_minimal_signup_payload');
    if ($mode === 'minimal' && !is_array($minimalPayload)) {
        return redirect('/portal/vendor/register?mode=email');
    }

    return view('portal-vendor-register', [
        'mode' => $mode,
        'minimalPayload' => is_array($minimalPayload) ? $minimalPayload : [],
    ]);
});

Route::get('/portal/vendor/oauth/health', function () {
    return response()->json(vendorSocialHealthSnapshot());
});

Route::post('/portal/vendor/email-otp/send', function (Request $request) {
    $validated = $request->validate([
        'identifier' => ['nullable', 'string', 'max:160'],
        'email' => ['nullable', 'string', 'max:160'],
    ]);

    $rawIdentifier = trim((string) ($validated['identifier'] ?? $validated['email'] ?? ''));
    $resolvedIdentifier = vendorResolveOtpIdentifier($rawIdentifier);
    $channel = (string) ($resolvedIdentifier['channel'] ?? 'invalid');
    $normalizedIdentifier = (string) ($resolvedIdentifier['normalized'] ?? '');

    if ($channel === 'invalid' || $normalizedIdentifier === '') {
        return back()->withErrors([
            'registration' => 'Enter a valid email address or phone number to continue.',
        ])->withInput();
    }

    $existingUser = null;
    if ($channel === 'email') {
        $existingUser = User::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedIdentifier])
            ->first();
    } elseif (Schema::hasColumn('users', 'phone')) {
        $phoneWithoutPlus = ltrim($normalizedIdentifier, '+');
        $existingUser = User::query()
            ->where('phone', $normalizedIdentifier)
            ->orWhere('phone', $phoneWithoutPlus)
            ->first();
    }

    if ($existingUser instanceof User && normalizePortalRoleValue((string) $existingUser->portal_role) !== 'VENDOR') {
        return back()->withErrors([
            'registration' => 'This identifier is already linked to a non-vendor account. Please use the correct portal login.',
        ])->withInput();
    }

    $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $cacheKey = vendorOtpCacheKeyForIdentifier($channel, $normalizedIdentifier);

    cache()->put($cacheKey, [
        'hash' => Hash::make($otpCode),
        'attempts' => 0,
        'channel' => $channel,
        'destination' => $normalizedIdentifier,
        'created_at' => now()->toIso8601String(),
    ], now()->addMinutes(10));

    try {
        vendorDeliverOtpCode($channel, $normalizedIdentifier, $otpCode);
    } catch (\Throwable $e) {
        Log::warning('Failed to send vendor OTP.', [
            'channel' => $channel,
            'destination' => $normalizedIdentifier,
            'error' => $e->getMessage(),
        ]);

        $deliveryGuidance = 'Check Twilio configuration and try again.';
        $errorMessage = strtolower($e->getMessage());
        if (str_contains($errorMessage, 'whatsapp')) {
            $deliveryGuidance = 'WhatsApp delivery failed. Confirm sandbox join from your phone, TWILIO_WHATSAPP_FROM, and template ContentSid settings.';
        } elseif (str_contains($errorMessage, 'sms')) {
            $deliveryGuidance = 'SMS delivery failed. Confirm TWILIO_FROM_NUMBER is active for your account and destination region.';
        }

        return back()->withErrors([
            'registration' => 'Unable to send verification code right now. Please try again in a moment.',
        ])->with('otp_delivery_guidance', $deliveryGuidance)->withInput();
    }

    $response = redirect('/portal/vendor/register?mode=otp')
        ->with('status', 'A 6-digit verification code has been sent to your ' . ($channel === 'phone' ? 'phone number.' : 'email.'))
        ->with('otp_identifier', $normalizedIdentifier)
        ->with('otp_channel', $channel)
        ->with('otp_sent', true);

    if ($channel === 'email') {
        $response->with('otp_email', $normalizedIdentifier);
    }

    if (app()->environment('testing')) {
        $response->with('otp_test_code', $otpCode);
    }

    return $response;
});

Route::post('/portal/vendor/email-otp/verify', function (Request $request) {
    $validated = $request->validate([
        'identifier' => ['nullable', 'string', 'max:160'],
        'email' => ['nullable', 'string', 'max:160'],
        'otp' => ['required', 'digits:6'],
    ]);

    $rawIdentifier = trim((string) ($validated['identifier'] ?? $validated['email'] ?? ''));
    $resolvedIdentifier = vendorResolveOtpIdentifier($rawIdentifier);
    $channel = (string) ($resolvedIdentifier['channel'] ?? 'invalid');
    $normalizedIdentifier = (string) ($resolvedIdentifier['normalized'] ?? '');
    if ($channel === 'invalid' || $normalizedIdentifier === '') {
        return redirect('/portal/vendor/register?mode=otp')->withErrors([
            'registration' => 'Enter the same valid email or phone number used for OTP.',
        ])->withInput();
    }

    $otp = trim((string) $validated['otp']);
    $cacheKey = vendorOtpCacheKeyForIdentifier($channel, $normalizedIdentifier);
    $cachedOtp = cache()->get($cacheKey);

    if (!is_array($cachedOtp) || empty($cachedOtp['hash'])) {
        return redirect('/portal/vendor/register?mode=otp')->withErrors([
            'registration' => 'Verification code expired. Request a new 6-digit code and try again.',
        ])->withInput()->with('otp_identifier', $normalizedIdentifier)->with('otp_channel', $channel);
    }

    $attempts = (int) ($cachedOtp['attempts'] ?? 0);
    if (!Hash::check($otp, (string) $cachedOtp['hash'])) {
        $attempts++;

        if ($attempts >= 5) {
            cache()->forget($cacheKey);
            return redirect('/portal/vendor/register?mode=otp')->withErrors([
                'registration' => 'Too many invalid code attempts. Request a new code and try again.',
            ])->withInput()->with('otp_identifier', $normalizedIdentifier)->with('otp_channel', $channel);
        }

        cache()->put($cacheKey, [
            'hash' => (string) $cachedOtp['hash'],
            'attempts' => $attempts,
            'created_at' => (string) ($cachedOtp['created_at'] ?? now()->toIso8601String()),
        ], now()->addMinutes(10));

        return redirect('/portal/vendor/register?mode=otp')->withErrors([
            'registration' => 'Invalid verification code. Please check the 6-digit OTP and try again.',
        ])->withInput()->with('otp_identifier', $normalizedIdentifier)->with('otp_channel', $channel);
    }

    cache()->forget($cacheKey);

    $portalUser = null;
    if ($channel === 'email') {
        $portalUser = User::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedIdentifier])
            ->first();
    } elseif (Schema::hasColumn('users', 'phone')) {
        $phoneWithoutPlus = ltrim($normalizedIdentifier, '+');
        $portalUser = User::query()
            ->where('phone', $normalizedIdentifier)
            ->orWhere('phone', $phoneWithoutPlus)
            ->first();
    }

    if ($portalUser instanceof User) {
        if (normalizePortalRoleValue((string) $portalUser->portal_role) !== 'VENDOR') {
            return redirect('/portal/vendor/register?mode=otp')->withErrors([
                'registration' => 'This email belongs to a non-vendor account. Please use the correct portal login.',
            ])->withInput()->with('otp_identifier', $normalizedIdentifier)->with('otp_channel', $channel);
        }

        if (!(bool) $portalUser->portal_enabled) {
            return redirect('/portal/vendor/register?mode=otp')->withErrors([
                'registration' => 'Your vendor account is currently disabled. Please contact support.',
            ])->withInput()->with('otp_identifier', $normalizedIdentifier)->with('otp_channel', $channel);
        }

        $request->session()->regenerate();
        session([
            'portal_vendor_authenticated' => true,
            'portal_vendor_user' => $portalUser->name,
            'portal_vendor_user_id' => $portalUser->id,
            'portal_vendor_role' => $portalUser->portal_role,
            'portal_vendor_oauth_provider' => $channel,
        ]);

        Auth::login($portalUser);

        return redirect('/vendor')->with('portal_notice', 'Signed in successfully.');
    }

    session([
        'vendor_minimal_signup_payload' => [
            'email' => $channel === 'email'
                ? $normalizedIdentifier
                : ('phone_' . substr(md5($normalizedIdentifier), 0, 20) . '@relay.workation.local'),
            'provider' => $channel,
            'oauth_id' => null,
            'suggested_name' => '',
            'contact_phone' => $channel === 'phone' ? $normalizedIdentifier : '',
            'email_verified' => true,
        ],
    ]);

    return redirect('/portal/vendor/register?mode=minimal')->with('status', 'Email verified. Complete minimal registration to continue.');
});

Route::post('/portal/vendor/minimal-register', function (Request $request) {
    $payload = session('vendor_minimal_signup_payload');
    if (!is_array($payload)) {
        return redirect('/portal/vendor/register?mode=email')->withErrors([
            'registration' => 'Start with email or social login to continue registration.',
        ]);
    }

    $validated = $request->validate([
        'given_name' => ['required', 'string', 'max:80'],
        'family_name' => ['required', 'string', 'max:80'],
        'contact_phone' => ['required', 'string', 'max:40'],
        'agree_terms' => ['accepted'],
    ]);

    $email = strtolower(trim((string) ($payload['email'] ?? '')));
    $provider = strtolower(trim((string) ($payload['provider'] ?? 'email')));
    $oauthId = trim((string) ($payload['oauth_id'] ?? ''));

    if ($email === '') {
        return redirect('/portal/vendor/register?mode=email')->withErrors([
            'registration' => 'Registration context expired. Please start again.',
        ]);
    }

    $portalUser = User::query()
        ->whereRaw('LOWER(email) = ?', [$email])
        ->first();

    if ($portalUser instanceof User && normalizePortalRoleValue((string) $portalUser->portal_role) !== 'VENDOR') {
        return redirect('/portal/vendor/register?mode=email')->withErrors([
            'registration' => 'This email is already linked to a non-vendor account.',
        ]);
    }

    if (!$portalUser instanceof User) {
        $portalUser = new User();
        $portalUser->email = $email;
        $portalUser->username = generatePortalUsernameFromEmail($email);
        $portalUser->password = Hash::make(Str::random(40));
    }

    $givenName = trim((string) $validated['given_name']);
    $familyName = trim((string) $validated['family_name']);
    $portalUser->name = trim($givenName . ' ' . $familyName);
    $portalUser->portal_role = 'VENDOR';
    $portalUser->portal_enabled = true;
    if (trim((string) $portalUser->portal_vendor_id) === '') {
        $prefix = $provider === 'email' ? 'EML' : strtoupper(substr($provider, 0, 3));
        $portalUser->portal_vendor_id = $prefix . '-' . strtoupper(substr(md5($email), 0, 8));
    }

    if (Schema::hasColumn('users', 'phone')) {
        $enteredPhone = vendorNormalizePhoneNumber((string) $validated['contact_phone']);
        $fallbackPhone = vendorNormalizePhoneNumber((string) ($payload['contact_phone'] ?? ''));
        $portalUser->phone = $enteredPhone !== '' ? $enteredPhone : $fallbackPhone;
    }

    if ($oauthId !== '') {
        $providerColumn = $provider . '_oauth_id';
        if (Schema::hasColumn('users', $providerColumn)) {
            $portalUser->{$providerColumn} = $oauthId;
        }
    }

    if (empty($portalUser->email_verified_at)) {
        $portalUser->email_verified_at = now();
    }

    $portalUser->save();

    session()->forget('vendor_minimal_signup_payload');

    $request->session()->regenerate();
    session([
        'portal_vendor_authenticated' => true,
        'portal_vendor_user' => $portalUser->name,
        'portal_vendor_user_id' => $portalUser->id,
        'portal_vendor_role' => $portalUser->portal_role,
    ]);

    Auth::login($portalUser);

    return redirect('/vendor')->with('portal_notice', 'Registration complete. Welcome to the vendor portal.');
});

Route::get('/portal/vendor/oauth/{provider}/redirect', function (Request $request, string $provider) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    $provider = strtolower(trim($provider));
    if (!in_array($provider, supportedVendorSocialProviders(), true)) {
        abort(404);
    }

    $request->session()->put(portalOAuthIntentSessionKey($provider), 'vendor');

    if (!isVendorSocialProviderConfigured($provider)) {
        return redirect('/portal/vendor/register')->withErrors([
            'registration' => ucfirst($provider) . ' sign-in is not configured yet. Please use email signup for now.',
        ]);
    }

    $health = vendorSocialHealthSnapshot();
    $providerHealth = $health['providers'][$provider] ?? null;
    if (is_array($providerHealth)) {
        if (!$providerHealth['redirect_uses_https']) {
            return redirect('/portal/vendor/register')->withErrors([
                'registration' => ucfirst($provider) . ' sign-in is temporarily unavailable because redirect URL must use HTTPS. Please use email signup for now.',
            ])->with('oauth_retry_guidance', 'Set ' . strtoupper($provider) . '_REDIRECT_URI to an HTTPS URL and try again.');
        }
    }

    if ($provider === 'apple') {
        $state = Str::random(40);
        $request->session()->put('vendor_oauth_state_apple', $state);

        $query = http_build_query([
            'response_type' => 'code',
            'response_mode' => 'query',
            'client_id' => (string) config('services.apple.client_id'),
            'redirect_uri' => vendorSocialRedirectUrl('apple'),
            'scope' => 'name email',
            'state' => $state,
        ]);

        return redirect()->away('https://appleid.apple.com/auth/authorize?' . $query);
    }

    if ($provider === 'facebook') {
        $facebookRedirect = Socialite::driver('facebook')
            ->redirectUrl(vendorSocialRedirectUrl('facebook'))
            ->setScopes(['public_profile'])
            ->stateless()
            ->redirect();

        $targetUrl = (string) $facebookRedirect->getTargetUrl();
        $targetUrl = preg_replace('/([?&]scope=)[^&]*/', '$1public_profile', $targetUrl) ?: $targetUrl;

        return redirect()->away($targetUrl);
    }

    $driver = Socialite::driver($provider);
    if ($provider === 'google') {
        $driver = $driver->with(['prompt' => 'login']);
    }

    return $driver->stateless()->redirect();
});

Route::get('/portal/vendor/oauth/{provider}/callback', function (Request $request, string $provider) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    $provider = strtolower(trim($provider));
    if (!in_array($provider, supportedVendorSocialProviders(), true)) {
        abort(404);
    }

    $intentKey = portalOAuthIntentSessionKey($provider);
    $oauthIntent = strtolower(trim((string) $request->session()->get($intentKey, '')));
    if ($oauthIntent === 'customer' && in_array($provider, supportedCustomerSocialProviders(), true)) {
        $request->session()->forget($intentKey);

        $queryString = (string) $request->getQueryString();
        $target = '/portal/customer/oauth/' . $provider . '/callback';
        if ($queryString !== '') {
            $target .= '?' . $queryString;
        }

        return redirect($target);
    }

    try {
        if ($provider === 'facebook' && trim((string) $request->query('error', '')) !== '') {
            $errorReason = trim((string) $request->query('error_reason', 'oauth_error'));
            $errorDescription = trim((string) $request->query('error_description', ''));
            $callbackHint = trim((string) $request->query('error_message', ''));
            $errorDetails = $errorDescription !== '' ? $errorDescription : $callbackHint;

            Log::warning('Facebook OAuth callback returned an explicit provider error.', [
                'reason' => $errorReason,
                'details' => $errorDetails,
            ]);

            return redirect('/portal/vendor/register')->withErrors([
                'registration' => 'Facebook sign-in was denied or not fully configured. Please retry once, then use Google or email while Facebook app setup is finalized.',
            ])->with('oauth_retry_guidance', 'Verify Facebook Valid OAuth Redirect URIs exactly match FACEBOOK_REDIRECT_URI, including scheme, host, and path.');
        }

        if (!isVendorSocialProviderConfigured($provider)) {
            return redirect('/portal/vendor/register')->withErrors([
                'registration' => ucfirst($provider) . ' sign-in is not configured yet. Please use email signup for now.',
            ]);
        }

        $providerColumn = $provider . '_oauth_id';
        if (!Schema::hasColumn('users', $providerColumn)) {
            return redirect('/portal/vendor/register')->withErrors([
                'registration' => 'Social sign-in database columns are missing. Please run migrations and try again.',
            ]);
        }

        $oauthId = '';
        $email = '';
        $name = '';

        if ($provider === 'apple') {
            $expectedState = (string) $request->session()->pull('vendor_oauth_state_apple', '');
            $receivedState = (string) $request->query('state', '');
            if ($expectedState === '' || !hash_equals($expectedState, $receivedState)) {
                throw new \RuntimeException('Invalid Apple sign-in state. Please try again.');
            }

            $code = trim((string) $request->query('code', ''));
            if ($code === '') {
                throw new \RuntimeException('Apple sign-in did not return an authorization code.');
            }

            $applePrivateKey = str_replace('\\n', "\n", (string) config('services.apple.private_key', ''));
            $appleClientSecret = JWT::encode([
                'iss' => (string) config('services.apple.team_id'),
                'iat' => time(),
                'exp' => time() + 300,
                'aud' => 'https://appleid.apple.com',
                'sub' => (string) config('services.apple.client_id'),
            ], $applePrivateKey, 'ES256', (string) config('services.apple.key_id'));

            $tokenResponse = Http::asForm()->post('https://appleid.apple.com/auth/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => vendorSocialRedirectUrl('apple'),
                'client_id' => (string) config('services.apple.client_id'),
                'client_secret' => $appleClientSecret,
            ]);

            if (!$tokenResponse->ok()) {
                throw new \RuntimeException('Apple token exchange failed.');
            }

            $idToken = trim((string) $tokenResponse->json('id_token', ''));
            if ($idToken === '') {
                throw new \RuntimeException('Apple sign-in did not return a valid identity token.');
            }

            $appleKeys = cache()->remember('vendor_oauth_apple_keys', 3600, function () {
                $response = Http::get('https://appleid.apple.com/auth/keys');
                if (!$response->ok()) {
                    throw new \RuntimeException('Unable to download Apple signing keys.');
                }

                return $response->json();
            });

            $decodedAppleToken = (array) JWT::decode($idToken, JWK::parseKeySet($appleKeys));
            $oauthId = trim((string) ($decodedAppleToken['sub'] ?? ''));
            $email = strtolower(trim((string) ($decodedAppleToken['email'] ?? '')));
            $name = trim((string) ($decodedAppleToken['name'] ?? ''));
        } else {
            try {
                $oauthUser = Socialite::driver($provider)
                    ->redirectUrl(vendorSocialRedirectUrl($provider))
                    ->stateless()
                    ->user();

                $oauthId = trim((string) $oauthUser->getId());
                $email = strtolower(trim((string) $oauthUser->getEmail()));
                $name = trim((string) ($oauthUser->getName() ?: ''));
            } catch (\Throwable $socialiteError) {
                if ($provider !== 'facebook') {
                    throw $socialiteError;
                }

                $authorizationCode = trim((string) $request->query('code', ''));
                if ($authorizationCode === '') {
                    throw $socialiteError;
                }

                $tokenResponse = Http::retry(2, 200)->timeout(10)->get('https://graph.facebook.com/v19.0/oauth/access_token', [
                    'client_id' => (string) config('services.facebook.client_id'),
                    'client_secret' => (string) config('services.facebook.client_secret'),
                    'redirect_uri' => vendorSocialRedirectUrl('facebook'),
                    'code' => $authorizationCode,
                ]);

                $accessToken = trim((string) $tokenResponse->json('access_token', ''));
                if (!$tokenResponse->ok() || $accessToken === '') {
                    throw $socialiteError;
                }

                $profileResponse = Http::retry(2, 200)->timeout(10)->get('https://graph.facebook.com/me', [
                    'fields' => 'id,name,email',
                    'access_token' => $accessToken,
                ]);

                if (!$profileResponse->ok()) {
                    throw $socialiteError;
                }

                $oauthId = trim((string) $profileResponse->json('id', ''));
                $email = strtolower(trim((string) $profileResponse->json('email', '')));
                $name = trim((string) $profileResponse->json('name', ''));

                Log::info('Vendor Facebook OAuth fallback exchange used after Socialite user retrieval failure.');
            }
        }

        if ($oauthId === '') {
            throw new \RuntimeException('Unable to resolve your social account identity.');
        }

        if ($email === '' && $provider === 'apple') {
            $email = 'apple_' . substr(md5($oauthId), 0, 20) . '@relay.workation.local';
        }

        if ($email === '' && $provider === 'facebook') {
            $email = 'facebook_' . substr(md5($oauthId), 0, 20) . '@relay.workation.local';
        }

        if ($email === '') {
            throw new \RuntimeException('Your social account did not provide an email address. Please use email signup.');
        }

        $portalUser = User::query()
            ->where($providerColumn, $oauthId)
            ->orWhereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($portalUser instanceof User) {
            if (normalizePortalRoleValue((string) $portalUser->portal_role) !== 'VENDOR') {
                throw new \RuntimeException('This email is already linked to a non-vendor account.');
            }
        }

        if ($name === '') {
            $name = trim((string) Str::of(Str::before($email, '@'))->replace(['.', '_', '-'], ' ')->title());
        }
        if ($name === '') {
            $name = 'Vendor Partner';
        }

        if (!$portalUser instanceof User) {
            session([
                'vendor_minimal_signup_payload' => [
                    'email' => $email,
                    'provider' => $provider,
                    'oauth_id' => $oauthId,
                    'suggested_name' => $name,
                    'email_verified' => true,
                ],
            ]);

            return redirect('/portal/vendor/register?mode=minimal')->with('status', ucfirst($provider) . ' verified. Complete minimal registration to continue.');
        }

        $portalUser->name = $name;
        $portalUser->portal_role = 'VENDOR';
        $portalUser->portal_enabled = true;
        if (trim((string) $portalUser->portal_vendor_id) === '') {
            $portalUser->portal_vendor_id = strtoupper(substr($provider, 0, 3)) . '-' . strtoupper(substr(md5($oauthId), 0, 8));
        }
        $portalUser->{$providerColumn} = $oauthId;
        if (empty($portalUser->email_verified_at)) {
            $portalUser->email_verified_at = now();
        }
        $portalUser->save();

        $request->session()->regenerate();
        session([
            'portal_vendor_authenticated' => true,
            'portal_vendor_user' => $portalUser->name,
            'portal_vendor_user_id' => $portalUser->id,
            'portal_vendor_role' => $portalUser->portal_role,
        ]);

        Auth::login($portalUser);

        return redirect('/vendor')->with('portal_notice', 'Signed in successfully with ' . ucfirst($provider) . '.');
    } catch (\Throwable $e) {
        Log::warning('Vendor social login failed.', [
            'provider' => $provider,
            'error' => $e->getMessage(),
        ]);

        $registrationMessage = 'Unable to sign in with ' . ucfirst($provider) . '. Please use email signup or try again.';
        if ($provider === 'facebook') {
            $registrationMessage = 'Unable to sign in with Facebook. Please try again, and if it still fails use Google or email while we complete Facebook app verification.';
        }

        return redirect('/portal/vendor/register')->withErrors([
            'registration' => $registrationMessage,
        ])->with('oauth_retry_guidance', 'Tip: retry once, then use Google or email signup if the provider window reports URL/redirect issues.');
    }
});

Route::match(['GET', 'POST'], '/portal/vendor/oauth/facebook/data-deletion', function (Request $request) {
    $confirmationCode = (string) Str::uuid();
    $statusUrl = url('/portal/vendor/oauth/facebook/data-deletion/status/' . $confirmationCode);

    Log::info('Facebook data deletion callback received.', [
        'has_signed_request' => filled($request->input('signed_request')),
        'ip' => $request->ip(),
    ]);

    return response()->json([
        'url' => $statusUrl,
        'confirmation_code' => $confirmationCode,
    ]);
});

Route::get('/portal/vendor/oauth/facebook/data-deletion/status/{confirmationCode}', function (string $confirmationCode) {
    return response()->json([
        'status' => 'success',
        'confirmation_code' => $confirmationCode,
        'message' => 'Facebook data deletion request has been acknowledged.',
    ]);
});

Route::get('/portal/vendor/oauth/facebook/data-deletion-instructions', function () {
    return response()->view('portal-facebook-data-deletion-instructions');
});

Route::post('/portal/vendor/register', function (Request $request) {
    if (!Schema::hasTable('vendor_registration_requests')) {
        return back()->withErrors([
            'registration' => 'Vendor self-registration is not available yet. Please contact support.',
        ])->withInput();
    }

    $validated = $request->validate([
        'contact_name' => ['required', 'string', 'max:120'],
        'email' => ['required', 'email', 'max:160'],
        'phone' => ['required', 'string', 'max:40'],
        'vendor_type' => ['required', Rule::in(['accommodation', 'transport', 'restaurant', 'major_vendor', 'vehicle_rental', 'excursions', 'small_service', 'other'])],
    ]);

    $email = strtolower(trim((string) $validated['email']));
    $existingUser = User::query()
        ->whereRaw('LOWER(email) = ?', [$email])
        ->first();

    if ($existingUser instanceof User && normalizePortalRoleValue((string) $existingUser->portal_role) === 'VENDOR') {
        return back()->withErrors([
            'email' => 'A vendor account with this email already exists. Please use vendor login or forgot password.',
        ])->withInput();
    }

    $existingPending = DB::table('vendor_registration_requests')
        ->whereRaw('LOWER(email) = ?', [$email])
        ->where('status', 'pending')
        ->exists();

    if ($existingPending) {
        return back()->withErrors([
            'email' => 'A registration request for this email is already under review.',
        ])->withInput();
    }

    $businessLicensePath = '';
    $verificationPath = null;
    $partnerName = trim((string) $validated['contact_name']);

    DB::table('vendor_registration_requests')->insert([
        'business_name' => $partnerName,
        'contact_name' => trim((string) $validated['contact_name']),
        'email' => $email,
        'phone' => trim((string) $validated['phone']),
        'vendor_type' => (string) $validated['vendor_type'],
        'business_registration_number' => '',
        'license_number' => '',
        'business_license_document_path' => $businessLicensePath,
        'verification_document_path' => $verificationPath,
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return back()->with('status', 'Registration submitted successfully. You can complete business and service verification after login by submitting your listings for review.');
});

Route::get('/portal/customer/register', function (Request $request) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    rememberCustomerPostAuthRedirect($request);

    if (session()->get('portal_customer_authenticated', false)) {
        $postAuthRedirect = consumeCustomerPostAuthRedirect($request, '/');
        return redirect($postAuthRedirect);
    }

    $socialProviders = collect(supportedCustomerSocialProviders())
        ->mapWithKeys(static fn (string $provider) => [
            $provider => [
                'configured' => isCustomerSocialProviderConfigured($provider),
                'redirect' => '/portal/customer/oauth/' . $provider . '/redirect',
            ],
        ])
        ->all();

    return view('portal-customer-register', [
        'socialProviders' => $socialProviders,
    ]);
});

Route::get('/portal/customer/oauth/{provider}/redirect', function (Request $request, string $provider) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    $provider = strtolower(trim($provider));
    if (!in_array($provider, supportedCustomerSocialProviders(), true)) {
        abort(404);
    }

    rememberCustomerPostAuthRedirect($request);

    $request->session()->put(portalOAuthIntentSessionKey($provider), 'customer');

    if (!isCustomerSocialProviderConfigured($provider)) {
        return redirect('/portal/customer/register')->withErrors([
            'registration' => ucfirst($provider) . ' sign-in is not configured yet. Please use email registration for now.',
        ]);
    }

    if ($provider === 'facebook') {
        $facebookRedirect = Socialite::driver('facebook')
            ->redirectUrl(customerSocialRedirectUrl('facebook'))
            ->setScopes(['public_profile'])
            ->stateless()
            ->redirect();

        $targetUrl = (string) $facebookRedirect->getTargetUrl();
        $targetUrl = preg_replace('/([?&]scope=)[^&]*/', '$1public_profile', $targetUrl) ?: $targetUrl;

        return redirect()->away($targetUrl);
    }

    $driver = Socialite::driver($provider)
        ->redirectUrl(customerSocialRedirectUrl($provider));

    if ($provider === 'google') {
        $driver = $driver->with(['prompt' => 'login']);
    }

    return $driver
        ->stateless()
        ->redirect();
});

Route::get('/portal/customer/oauth/{provider}/callback', function (Request $request, string $provider) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    $provider = strtolower(trim($provider));
    if (!in_array($provider, supportedCustomerSocialProviders(), true)) {
        abort(404);
    }

    $intentKey = portalOAuthIntentSessionKey($provider);

    try {
        if ($provider === 'facebook' && trim((string) $request->query('error', '')) !== '') {
            return redirect('/portal/customer/register')->withErrors([
                'registration' => 'Facebook sign-in was denied. Please retry or use email registration.',
            ]);
        }

        if (!isCustomerSocialProviderConfigured($provider)) {
            return redirect('/portal/customer/register')->withErrors([
                'registration' => ucfirst($provider) . ' sign-in is not configured yet. Please use email registration for now.',
            ]);
        }

        $providerColumn = customerSocialProviderColumn($provider);
        $supportsProviderColumn = $providerColumn !== '' && customerSchemaHasColumn($providerColumn);

        $oauthId = '';
        $email = '';
        $name = '';

        try {
            $oauthUser = Socialite::driver($provider)
                ->redirectUrl(customerSocialRedirectUrl($provider))
                ->stateless()
                ->user();

            $oauthId = trim((string) $oauthUser->getId());
            $email = strtolower(trim((string) $oauthUser->getEmail()));
            $name = trim((string) ($oauthUser->getName() ?: ''));
        } catch (\Throwable $socialiteError) {
            if ($provider !== 'facebook') {
                throw $socialiteError;
            }

            $authorizationCode = trim((string) $request->query('code', ''));
            if ($authorizationCode === '') {
                throw $socialiteError;
            }

            $tokenResponse = Http::retry(2, 200)->timeout(10)->get('https://graph.facebook.com/v19.0/oauth/access_token', [
                'client_id' => (string) config('services.facebook.client_id'),
                'client_secret' => (string) config('services.facebook.client_secret'),
                'redirect_uri' => customerSocialRedirectUrl('facebook'),
                'code' => $authorizationCode,
            ]);

            $accessToken = trim((string) $tokenResponse->json('access_token', ''));
            if (!$tokenResponse->ok() || $accessToken === '') {
                throw $socialiteError;
            }

            $profileResponse = Http::retry(2, 200)->timeout(10)->get('https://graph.facebook.com/me', [
                'fields' => 'id,name,email',
                'access_token' => $accessToken,
            ]);

            if (!$profileResponse->ok()) {
                throw $socialiteError;
            }

            $oauthId = trim((string) $profileResponse->json('id', ''));
            $email = strtolower(trim((string) $profileResponse->json('email', '')));
            $name = trim((string) $profileResponse->json('name', ''));
        }

        if ($oauthId === '') {
            throw new \RuntimeException('Unable to resolve social account identity.');
        }

        if ($email === '') {
            $email = $provider . '_' . substr(md5($oauthId), 0, 20) . '@relay.workation.local';
        }

        if ($name === '') {
            $name = trim((string) Str::of(Str::before($email, '@'))->replace(['.', '_', '-'], ' ')->title());
        }
        if ($name === '') {
            $name = 'Customer';
        }

        $customerUser = \App\Models\Customer::query()
            ->where(function ($query) use ($supportsProviderColumn, $providerColumn, $oauthId, $email) {
                if ($supportsProviderColumn) {
                    $query->where($providerColumn, $oauthId);
                }

                $query->orWhereRaw('LOWER(email) = ?', [$email]);
            })
            ->first();

        $createdCustomer = false;

        if (!$customerUser) {
            $now = now();
            $payload = [
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
            ];

            if ($supportsProviderColumn) {
                $payload[$providerColumn] = $oauthId;
            }

            if (customerSchemaHasColumn('id')) {
                $payload['id'] = (string) Str::uuid();
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
            $createdCustomer = true;

            $customerUser = \App\Models\Customer::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();
        }

        if (!$customerUser) {
            throw new \RuntimeException('Unable to initialize member account from social identity.');
        }

        $needsSave = false;
        if ($supportsProviderColumn && trim((string) ($customerUser->{$providerColumn} ?? '')) !== $oauthId) {
            $customerUser->{$providerColumn} = $oauthId;
            $needsSave = true;
        }
        if (trim((string) ($customerUser->name ?? '')) === '' && $name !== '') {
            $customerUser->name = $name;
            $needsSave = true;
        }
        if ($needsSave) {
            $customerUser->save();
        }

        if ($createdCustomer) {
            sendCustomerPortalRegistrationNotification($email, $name);
        }

        $request->session()->regenerate();
        session([
            'portal_customer_authenticated' => true,
            'portal_customer_user' => (string) ($customerUser->name ?? 'Customer'),
            'portal_customer_user_id' => (string) ($customerUser->id ?? ''),
            'portal_customer_role' => 'CUSTOMER',
            'portal_customer_email' => strtolower(trim((string) ($customerUser->email ?? ''))),
            'portal_customer_oauth_provider' => $provider,
        ]);

        Auth::guard('customer')->login($customerUser);

        $request->session()->forget($intentKey);

        $postAuthRedirect = consumeCustomerPostAuthRedirect($request, '/');
        return redirect($postAuthRedirect)->with('status', 'Signed in successfully with ' . ucfirst($provider) . '. You can continue browsing and book normally.');
    } catch (\Throwable $e) {
        $request->session()->forget($intentKey);

        Log::warning('Customer social login failed.', [
            'provider' => $provider,
            'error' => $e->getMessage(),
        ]);

        return redirect('/portal/customer/register')->withErrors([
            'registration' => 'Unable to sign in with ' . ucfirst($provider) . '. Please use email registration or try again.',
        ]);
    }
});

Route::post('/portal/customer/profile/update', function (Request $request) {
    if (!(bool) session('portal_customer_authenticated', false)) {
        return redirect('/portal/customer/login')->withErrors([
            'username' => 'Please sign in to update your profile.',
        ]);
    }

    $customerUserId = (string) session('portal_customer_user_id', '');
    if ($customerUserId === '') {
        return back()->withErrors([
            'profile' => 'Unable to resolve your profile. Please sign in again.',
        ]);
    }

    $validated = $request->validate([
        'first_name' => ['nullable', 'string', 'max:80'],
        'last_name' => ['nullable', 'string', 'max:80'],
        'email' => ['nullable', 'email', 'max:160'],
        'phone' => ['nullable', 'string', 'max:60'],
        'dob' => ['nullable', 'date'],
        'nationality' => ['nullable', 'string', 'max:120'],
        'gender' => ['nullable', 'string', 'max:32'],
        'preferred_language' => ['nullable', 'string', 'max:16'],
        'address_line' => ['nullable', 'string', 'max:220'],
        'address_atoll_id' => ['nullable', 'string', 'max:64'],
        'address_island_id' => ['nullable', 'string', 'max:64'],
    ]);

    $customer = \App\Models\Customer::query()->where('id', $customerUserId)->first();
    if (!$customer) {
        return back()->withErrors([
            'profile' => 'Profile not found. Please sign in again.',
        ]);
    }

    $firstName = trim((string) ($validated['first_name'] ?? ''));
    $lastName = trim((string) ($validated['last_name'] ?? ''));
    $fullName = trim($firstName . ' ' . $lastName);
    if ($fullName === '') {
        $fullName = trim((string) ($customer->name ?? 'Customer'));
    }

    $email = strtolower(trim((string) ($validated['email'] ?? (string) ($customer->email ?? ''))));
    if ($email !== '') {
        $existingWithEmail = \App\Models\Customer::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('id', '!=', $customerUserId)
            ->first();

        if ($existingWithEmail) {
            return back()->withErrors([
                'email' => 'This email is already used by another account.',
            ])->withInput();
        }
    }

    $customer->name = $fullName;
    if ($email !== '') {
        $customer->email = $email;
    }
    if (customerSchemaHasColumn('updatedAt')) {
        $customer->updatedAt = now();
    }
    if (customerSchemaHasColumn('updated_at')) {
        $customer->updated_at = now();
    }
    $customer->save();

    cache()->forever(customerProfileMetaCacheKey($customerUserId), [
        'phone' => trim((string) ($validated['phone'] ?? '')),
        'dob' => trim((string) ($validated['dob'] ?? '')),
        'nationality' => trim((string) ($validated['nationality'] ?? '')),
        'gender' => trim((string) ($validated['gender'] ?? '')),
        'preferred_language' => trim((string) ($validated['preferred_language'] ?? 'en')),
        'address_line' => trim((string) ($validated['address_line'] ?? '')),
        'address_atoll_id' => trim((string) ($validated['address_atoll_id'] ?? '')),
        'address_island_id' => trim((string) ($validated['address_island_id'] ?? '')),
    ]);

    session([
        'portal_customer_user' => $fullName,
        'portal_customer_email' => $email,
    ]);

    return redirect('/customer#profile')->with('status', 'Profile updated successfully.');
});

Route::post('/portal/customer/register', function (Request $request) {
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:120'],
        'email' => ['required', 'email', 'max:160'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    $email = strtolower(trim((string) $validated['email']));

    $existingCustomer = \App\Models\Customer::query()
        ->whereRaw('LOWER(email) = ?', [$email])
        ->first();

    if ($existingCustomer) {
        return back()->withErrors([
            'email' => 'A member account with this email already exists. Please log in or reset password.',
        ])->withInput();
    }

    $now = now();
    $payload = [
        'name' => trim((string) $validated['name']),
        'email' => $email,
        'password' => Hash::make((string) $validated['password']),
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

    $verificationToken = sendCustomerPortalRegistrationNotification($email, (string) $payload['name'], true);

    $response = redirect('/portal/customer/login')->with('status', 'Member registration successful. Please verify your email before signing in.');

    if (app()->environment('testing') && is_string($verificationToken) && $verificationToken !== '') {
        $response->with('customer_verification_test_token', $verificationToken)
            ->with('customer_verification_test_email', $email);
    }

    return $response;
});

Route::get('/portal/customer/verify-email', function (Request $request) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    $email = strtolower(trim((string) $request->query('email', '')));
    $token = trim((string) $request->query('token', ''));

    if ($email === '' || $token === '') {
        return redirect('/portal/customer/login')->withErrors([
            'username' => 'Email verification link is invalid. Request a new verification email.',
        ]);
    }

    $cachedToken = cache()->get(customerVerificationTokenCacheKey($email));
    if (!is_array($cachedToken) || empty($cachedToken['hash']) || !Hash::check($token, (string) $cachedToken['hash'])) {
        return redirect('/portal/customer/login')->withErrors([
            'username' => 'Email verification link is invalid or expired. Request a new verification email.',
        ])->with('pending_verification_email', $email);
    }

    $customerUser = \App\Models\Customer::query()
        ->whereRaw('LOWER(email) = ?', [$email])
        ->first();

    if (!$customerUser) {
        return redirect('/portal/customer/register')->withErrors([
            'registration' => 'Member account was not found for this verification link. Please register again.',
        ]);
    }

    customerMarkEmailVerified($customerUser);

    return redirect('/portal/customer/login')->with('status', 'Email verified successfully. You can now sign in.');
});

Route::post('/portal/customer/verify-email/resend', function (Request $request) {
    $validated = $request->validate([
        'email' => ['required', 'email', 'max:160'],
    ]);

    $email = strtolower(trim((string) $validated['email']));
    $customerUser = \App\Models\Customer::query()
        ->whereRaw('LOWER(email) = ?', [$email])
        ->first();

    if (!$customerUser) {
        return back()->withErrors([
            'username' => 'No member account was found for this email address.',
        ]);
    }

    if (customerEmailIsVerified($customerUser)) {
        return back()->with('status', 'This customer email is already verified. You can sign in now.');
    }

    $verificationToken = sendCustomerPortalRegistrationNotification($email, (string) ($customerUser->name ?? 'Customer'), true);

    $response = redirect('/portal/customer/login')->with('status', 'Member registration successful. Please verify your email before signing in.');

    if (app()->environment('testing') && is_string($verificationToken) && $verificationToken !== '') {
        $response->with('customer_verification_test_token', $verificationToken)
            ->with('customer_verification_test_email', $email);
    }

    return $response;
});

Route::get('/portal/customer/verify-email', function (Request $request) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    $email = strtolower(trim((string) $request->query('email', '')));
    $token = trim((string) $request->query('token', ''));

    if ($email === '' || $token === '') {
        return redirect('/portal/customer/login')->withErrors([
            'username' => 'Email verification link is invalid. Request a new verification email.',
        ]);
    }

    $cachedToken = cache()->get(customerVerificationTokenCacheKey($email));
    if (!is_array($cachedToken) || empty($cachedToken['hash']) || !Hash::check($token, (string) $cachedToken['hash'])) {
        return redirect('/portal/customer/login')->withErrors([
            'username' => 'Email verification link is invalid or expired. Request a new verification email.',
        ])->with('pending_verification_email', $email);
    }

    $customerUser = \App\Models\Customer::query()
        ->whereRaw('LOWER(email) = ?', [$email])
        ->first();

    if (!$customerUser) {
        return redirect('/portal/customer/register')->withErrors([
            'registration' => 'Member account was not found for this verification link. Please register again.',
        ]);
    }

    customerMarkEmailVerified($customerUser);

    return redirect('/portal/customer/login')->with('status', 'Email verified successfully. You can now sign in.');
});

Route::post('/portal/customer/verify-email/resend', function (Request $request) {
    $validated = $request->validate([
        'email' => ['required', 'email', 'max:160'],
    ]);

    $email = strtolower(trim((string) $validated['email']));
    $customerUser = \App\Models\Customer::query()
        ->whereRaw('LOWER(email) = ?', [$email])
        ->first();

    if (!$customerUser) {
        return back()->withErrors([
            'username' => 'No member account was found for this email address.',
        ]);
    }

    if (customerEmailIsVerified($customerUser)) {
        return back()->with('status', 'This customer email is already verified. You can sign in now.');
    }

    $verificationToken = sendCustomerPortalRegistrationNotification($email, (string) ($customerUser->name ?? 'Customer'), true);

    $response = back()->with('status', 'A new verification email has been sent. Please check your inbox.');
    if (app()->environment('testing') && is_string($verificationToken) && $verificationToken !== '') {
        $response->with('customer_verification_test_token', $verificationToken)
            ->with('customer_verification_test_email', $email);
    }

    return $response;
});

Route::get('/portal/{portal}/forgot-password', function (Request $request, string $portal) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    if (!in_array($portal, ['admin', 'vendor', 'customer'], true)) {
        abort(404);
    }

    $config = portalConfig($portal);

    return view('portal-forgot-password', [
        'portal' => $portal,
        'portalName' => $config['name'],
    ]);
});

Route::post('/portal/{portal}/forgot-password', function (Request $request, string $portal) {
    if (!in_array($portal, ['admin', 'vendor', 'customer'], true)) {
        abort(404);
    }

    $validated = $request->validate([
        'email' => ['required', 'string', 'max:190'],
    ]);

    $identifier = trim((string) $validated['email']);
    $identifierLower = strtolower($identifier);
    $config = portalConfig($portal);
    $allowedRoles = collect($config['allowed_roles'])
        ->map(function ($role) {
            return normalizePortalRoleValue((string) $role);
        })
        ->unique()
        ->values();

    $portalUser = null;
    $email = '';
    if ($allowedRoles->isNotEmpty()) {
        $portalUser = \App\Models\User::query()
            ->where(function ($query) use ($identifierLower) {
                $query->whereRaw('LOWER(TRIM(email)) = ?', [$identifierLower]);

                if (Schema::hasColumn('users', 'username')) {
                    $query->orWhereRaw('LOWER(TRIM(username)) = ?', [$identifierLower]);
                }
            })
            ->first();

        if ($portalUser instanceof \App\Models\User) {
            $resolvedRole = normalizePortalRoleValue((string) $portalUser->portal_role);
            $isAllowedRole = $allowedRoles->contains($resolvedRole);
            if (!$isAllowedRole && $portal === 'admin' && Str::startsWith($resolvedRole, 'ADMIN')) {
                $isAllowedRole = true;
            }

            if (!$isAllowedRole || !$portalUser->portal_enabled) {
                Log::info('Portal forgot-password user filtered out.', [
                    'portal' => $portal,
                    'email' => strtolower(trim((string) ($portalUser->email ?? ''))),
                    'resolved_role' => $resolvedRole,
                    'portal_enabled' => (bool) $portalUser->portal_enabled,
                ]);
                $portalUser = null;
            } else {
                $email = strtolower(trim((string) ($portalUser->email ?? '')));
            }
        }
    } else {
        $portalUser = findCustomerByEmail($identifierLower);
        if ($portalUser instanceof \App\Models\Customer) {
            $email = strtolower(trim((string) ($portalUser->email ?? '')));
        }
    }


    $response = back()->with('status', 'If the email is registered for a ' . strtolower($config['name']) . ' account, a reset link has been sent.');

    if ($portalUser && $email !== '') {
        $brokerName = ($portalUser instanceof \App\Models\User) ? 'backend_users' : 'customer_users';
        $broker = Password::broker($brokerName);
        $mailSent = false;

        try {
            $token = $broker->createToken($portalUser);
            $resetUrl = url('/portal/' . $portal . '/reset-password/' . $token . '?email=' . rawurlencode($email));

            // Use Laravel's reset notification pipeline so all portal users share one reliable delivery path.
            $portalUser->sendPasswordResetNotification($token);
            $mailSent = true;

            // Debug link available only in testing.
            if (app()->environment('testing')) {
                $response->with('password_reset_debug_link', $resetUrl);
            }
        } catch (\Throwable $e) {
            try {
                if (isset($resetUrl) && $resetUrl !== '') {
                    sendPortalPasswordResetFallbackMail($email, $portal, $resetUrl, (string) ($portalUser->name ?? ''));
                    $mailSent = true;
                }
            } catch (\Throwable $mailFallbackError) {
                Log::warning('Portal forgot-password mail failed.', [
                    'portal' => $portal,
                    'broker' => $brokerName,
                    'email' => $email,
                    'mail_sent' => $mailSent,
                    'error' => $e->getMessage(),
                    'fallback_error' => $mailFallbackError->getMessage(),
                ]);
            }
        }
    }

    return $response;
});

Route::get('/portal/{portal}/reset-password/{token}', function (Request $request, string $portal, string $token) {
    $canonicalRedirect = portalCanonicalHostRedirect($request);
    if ($canonicalRedirect) {
        return $canonicalRedirect;
    }

    if (!in_array($portal, ['admin', 'vendor', 'customer'], true)) {
        abort(404);
    }

    $config = portalConfig($portal);

    return view('portal-reset-password', [
        'portal' => $portal,
        'portalName' => $config['name'],
        'token' => $token,
        'email' => (string) $request->query('email', ''),
    ]);
});

Route::post('/portal/{portal}/reset-password', function (Request $request, string $portal) {
    if (!in_array($portal, ['admin', 'vendor', 'customer'], true)) {
        abort(404);
    }

    $validated = $request->validate([
        'token' => ['required', 'string'],
        'email' => ['required', 'email'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    $email = strtolower(trim((string) $validated['email']));
    $config = portalConfig($portal);
    $allowedRoles = collect($config['allowed_roles'])
        ->map(function ($role) {
            return normalizePortalRoleValue((string) $role);
        })
        ->unique()
        ->values();

    $portalUser = null;
    if ($allowedRoles->isNotEmpty()) {
        $portalUser = \App\Models\User::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->first();

        if ($portalUser instanceof \App\Models\User) {
            $resolvedRole = normalizePortalRoleValue((string) $portalUser->portal_role);
            $isAllowedRole = $allowedRoles->contains($resolvedRole);
            if (!$isAllowedRole && $portal === 'admin' && Str::startsWith($resolvedRole, 'ADMIN')) {
                $isAllowedRole = true;
            }

            if (!$isAllowedRole) {
                $portalUser = null;
            }
        }
    } else {
        $portalUser = \App\Models\Customer::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();
    }

    if (!$portalUser) {
        Log::warning('Portal password reset user resolution failed.', [
            'portal' => $portal,
            'email' => $email,
            'allowed_roles' => $allowedRoles->all(),
        ]);
        return back()->withErrors([
            'email' => 'Unable to reset password for this ' . strtolower($config['name']) . ' account.',
        ])->withInput($request->only('email'));
    }

    try {
        $broker = ($portalUser instanceof \App\Models\User)
            ? 'backend_users'
            : 'customer_users';
        $tokenTable = (string) config("auth.passwords.$broker.table", 'password_reset_tokens');
        // Password brokers store reset tokens in their configured token table/connection.
        // Do not force user-model connection here; it can differ in split-db setups.
        $tokenQuery = DB::table($tokenTable);

        $resetRow = $tokenQuery
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (!$resetRow) {
            return back()->withErrors([
                'email' => __('passwords.token'),
            ])->withInput($request->only('email'));
        }

        $providedToken = (string) $validated['token'];
        $storedToken = (string) $resetRow->token;
        $tokenMatches = Hash::check($providedToken, $storedToken) || hash_equals($storedToken, $providedToken);
        if (!$tokenMatches) {
            return back()->withErrors([
                'email' => __('passwords.token'),
            ])->withInput($request->only('email'));
        }

        $expireMinutes = (int) config("auth.passwords.$broker.expire", 60);
        $createdAt = Carbon::parse((string) $resetRow->created_at);
        if ($createdAt->addMinutes($expireMinutes)->isPast()) {
            return back()->withErrors([
                'email' => __('passwords.token'),
            ])->withInput($request->only('email'));
        }

        $updates = [
            'password' => $portalUser instanceof \App\Models\User
                ? (string) $validated['password']
                : Hash::make((string) $validated['password']),
        ];

        $rememberTokenTable = $portalUser->getTable();
        $rememberTokenConnection = $portalUser->getConnectionName();
        $hasRememberTokenColumn = $rememberTokenConnection
            ? Schema::connection($rememberTokenConnection)->hasColumn($rememberTokenTable, 'remember_token')
            : Schema::hasColumn($rememberTokenTable, 'remember_token');
        if ($hasRememberTokenColumn) {
            $updates['remember_token'] = Str::random(60);
        }

        $portalUser->forceFill($updates)->save();
        DB::table($tokenTable)->whereRaw('LOWER(email) = ?', [$email])->delete();

        return redirect('/portal/' . $portal . '/login')->with('status', __('passwords.reset'));
    } catch (\Throwable $e) {
        Log::error('Portal password reset failed', [
            'portal' => $portal,
            'email' => $email,
            'error' => $e->getMessage(),
        ]);

        return back()->withErrors([
            'email' => 'Unable to reset password at the moment. Please request a new reset link and try again.',
        ])->withInput($request->only('email'));
    }
});

Route::post('/portal/{portal}/login', function (Request $request, string $portal) {
    if (!in_array($portal, ['admin', 'vendor', 'customer'], true)) {
        abort(404);
    }

    if ($portal === 'customer') {
        rememberCustomerPostAuthRedirect($request);
    }

    $validated = $request->validate([
        'username' => ['required', 'string'],
        'password' => ['required', 'string'],
    ]);

    $throttleKey = 'portal-login:' . $portal . '|' . strtolower(trim((string) $validated['username'])) . '|' . $request->ip();
    $maxAttempts = $portal === 'vendor' ? 5 : 7;
    $decaySeconds = $portal === 'vendor' ? 300 : 180;

    if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
        $seconds = RateLimiter::availableIn($throttleKey);
        $portalLabel = $portal === 'vendor' ? 'Vendor' : ($portal === 'customer' ? 'Customer' : 'Admin');
        return back()->withErrors([
            'username' => $portalLabel . ' login temporarily locked due to repeated attempts. Try again in ' . $seconds . ' seconds.',
        ])->withInput($request->only('username'));
    }

    try {
        $config = portalConfig($portal);
        $username = trim((string) $validated['username']);
        $password = (string) $validated['password'];
        $usernameLower = strtolower($username);
        $normalizedAllowedRoles = collect($config['allowed_roles'])
            ->map(function ($role) {
                return normalizePortalRoleValue((string) $role);
            })
            ->unique()
            ->values()
            ->all();

        $portalUser = null;
        $linkedVendorForEmail = null;

        // Admin/vendor login: users table; customer login: User table with vendor bridge.
        if (in_array('ADMIN', $config['allowed_roles'], true) || in_array('VENDOR', $config['allowed_roles'], true)) {
            if (Schema::hasColumns('users', ['username', 'portal_enabled', 'portal_role'])) {
                $portalCandidates = \App\Models\User::query()
                    ->where(function ($query) use ($username, $usernameLower) {
                        $query->where('username', $username)
                            ->orWhere('email', $username)
                            ->orWhere('username', $usernameLower)
                            ->orWhere('email', $usernameLower);
                    })
                    ->where('portal_enabled', true)
                    ->get();

                $portalUser = $portalCandidates->first(function (\App\Models\User $candidate) use ($portal, $normalizedAllowedRoles) {
                    $resolvedRole = normalizePortalRoleValue((string) $candidate->portal_role);

                    if ($portal === 'admin' && Str::startsWith($resolvedRole, 'ADMIN')) {
                        return true;
                    }

                    return in_array($resolvedRole, $normalizedAllowedRoles, true);
                });

                if (
                    $portal === 'vendor'
                    && !$portalUser
                    && str_contains($usernameLower, '@')
                ) {
                    // Allow vendors to sign in with customer password if both identities share email.
                    $candidateVendor = findActiveVendorByEmail($usernameLower);
                    $candidateCustomer = findCustomerByEmail($usernameLower);
                    $linkedVendorForEmail = $candidateVendor;

                    if (
                        $candidateVendor instanceof \App\Models\User
                        && $candidateCustomer instanceof \App\Models\Customer
                        && Hash::check($password, (string) $candidateCustomer->password)
                    ) {
                        syncVendorPasswordFromCustomer($candidateVendor, $password);
                        $portalUser = $candidateVendor;
                    }
                }
            }
        } else {
            $directCustomer = findCustomerByEmail($usernameLower);
            $linkedVendorForEmail = findActiveVendorByEmail($usernameLower);

            if ($directCustomer instanceof \App\Models\Customer && Hash::check($password, (string) $directCustomer->password)) {
                $portalUser = $directCustomer;

                // If this customer is also an active vendor, keep vendor password aligned for single credential use.
                if ($linkedVendorForEmail instanceof \App\Models\User) {
                    syncVendorPasswordFromCustomer($linkedVendorForEmail, $password);
                }
            } else {
                // Active vendors can always access customer portal with the same credentials.
                if ($linkedVendorForEmail instanceof \App\Models\User && Hash::check($password, (string) $linkedVendorForEmail->password)) {
                    $portalUser = upsertCustomerFromVendorIdentity($linkedVendorForEmail, $password);
                } else {
                    $portalUser = $directCustomer;
                }
            }
        }

        $isBootstrapAdmin = false;
        if ($portal === 'admin') {
            $bootstrapUsername = firstNonEmptyEnv([
                'PORTAL_ADMIN_USERNAME',
                'WORKATION_ADMIN_PORTAL_USERNAME',
                'ADMIN_PORTAL_USERNAME',
                'WORKATION_ADMIN_USERNAME',
                'ADMIN_USERNAME',
                'ADMIN_USER',
            ]);
            $bootstrapPassword = firstNonEmptyEnv([
                'PORTAL_ADMIN_PASSWORD',
                'WORKATION_ADMIN_PORTAL_PASSWORD',
                'ADMIN_PORTAL_PASSWORD',
                'WORKATION_ADMIN_PASSWORD',
                'ADMIN_PASSWORD',
                'ADMIN_PASS',
            ]);

            if ($bootstrapUsername !== '' && $bootstrapPassword !== '') {
                $isBootstrapAdmin = strtolower($bootstrapUsername) === $usernameLower
                    && bootstrapPasswordMatches($bootstrapPassword, $password);
            }
        }

        $isValidDbUser = $portalUser && Hash::check($password, (string) $portalUser->password);
        if (!$isValidDbUser && !$isBootstrapAdmin) {
            RateLimiter::hit($throttleKey, $decaySeconds);
            Log::warning('Portal login failed.', [
                'portal' => $portal,
                'username' => $usernameLower,
                'ip' => $request->ip(),
            ]);

            $portalMessage = $portal === 'vendor'
                ? 'Invalid vendor username/password, or account access is not enabled.'
                : ($portal === 'customer' ? 'Invalid customer email or password.' : 'Invalid username or password.');

            return back()->withErrors([
                'username' => $portalMessage,
            ])->withInput($request->only('username'));
        }

        if (
            $portal === 'customer'
            && $portalUser instanceof \App\Models\Customer
            && !customerEmailIsVerified($portalUser)
            && !($linkedVendorForEmail instanceof \App\Models\User)
        ) {
            RateLimiter::hit($throttleKey, $decaySeconds);

            return back()->withErrors([
                'username' => 'Please verify your customer email before signing in.',
            ])->withInput($request->only('username'))
                ->with('pending_verification_email', strtolower(trim((string) ($portalUser->email ?? ''))));
        }

        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();
        $sessionUserName = $portalUser ? $portalUser->name : 'Bootstrap Admin';
        $sessionUserId = $portalUser ? $portalUser->id : null;
        $sessionRole = $portalUser ? $portalUser->portal_role : 'ADMIN_SUPER';

        session([
            $config['session_key'] => true,
            'portal_' . $portal . '_user' => $sessionUserName,
            'portal_' . $portal . '_user_id' => $sessionUserId,
            'portal_' . $portal . '_role' => $sessionRole,
        ]);

        if ($portal === 'customer' && $portalUser) {
            session([
                'portal_customer_email' => strtolower(trim((string) ($portalUser->email ?? ''))),
                'portal_customer_oauth_provider' => '',
            ]);
        }

        if ($portal === 'vendor') {
            session([
                'portal_vendor_oauth_provider' => '',
            ]);
        }

        // Log in with the guard that matches the current portal.
        if ($portalUser) {
            if ($portal === 'customer') {
                Auth::guard('customer')->login($portalUser);
            } else {
                Auth::guard('backend')->login($portalUser);
            }
        }

        if ($portal === 'customer') {
            $postAuthRedirect = consumeCustomerPostAuthRedirect($request, '/');
            return redirect($postAuthRedirect)->with('status', 'Signed in successfully. You can keep browsing and book as a customer.');
        }

        return redirect(portalRoutePath($portal));
    } catch (\Throwable $e) {
        RateLimiter::hit($throttleKey, $decaySeconds);
        Log::error('Portal login failed with exception.', [
            'portal' => $portal,
            'username' => strtolower(trim((string) $validated['username'])),
            'ip' => $request->ip(),
            'error' => $e->getMessage(),
        ]);

        return back()->withErrors([
            'username' => 'Unable to sign in right now. Please try again in a moment.',
        ])->withInput($request->only('username'));
    }
});

$handlePortalLogout = function (Request $request, string $portal) {
    if (!in_array($portal, ['admin', 'vendor', 'customer'], true)) {
        abort(404);
    }

    $config = portalConfig($portal);
    $oauthProviderKey = 'portal_' . $portal . '_oauth_provider';
    $oauthProvider = strtolower(trim((string) $request->session()->get($oauthProviderKey, '')));

    $nextUrl = match ($portal) {
        'vendor' => '/portal/vendor/register?mode=email',
        'customer' => '/',
        default => '/portal/' . $portal . '/login',
    };

    if ($portal === 'customer') {
        Auth::guard('customer')->logout();
    } else {
        Auth::guard('backend')->logout();
    }
    session()->forget([$config['session_key'], 'portal_' . $portal . '_user', 'portal_' . $portal . '_user_id', 'portal_' . $portal . '_role', $oauthProviderKey]);
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    if (in_array($portal, ['vendor', 'customer'], true) && $oauthProvider === 'google') {
        $continueUrl = url($nextUrl);
        $googleLogoutUrl = 'https://accounts.google.com/Logout?continue=' . urlencode($continueUrl);

        return redirect()->away($googleLogoutUrl);
    }

    return redirect($nextUrl);
};

Route::match(['GET', 'POST'], '/portal/admin/logout', function (Request $request) use ($handlePortalLogout) {
    return $handlePortalLogout($request, 'admin');
});

Route::match(['GET', 'POST'], '/portal/vendor/logout', function (Request $request) use ($handlePortalLogout) {
    return $handlePortalLogout($request, 'vendor');
});

Route::match(['GET', 'POST'], '/portal/customer/logout', function (Request $request) use ($handlePortalLogout) {
    return $handlePortalLogout($request, 'customer');
});

Route::post('/portal/{portal}/logout', function (Request $request, string $portal) use ($handlePortalLogout) {
    return $handlePortalLogout($request, $portal);
});

Route::post('/portal/admin/users/{user}/manage', function (Request $request, User $user) {
    $canManageUsers = Gate::allows('manage-portal-users');
    $canManageVendorUsers = canManageVendorUsers();
    $currentRole = normalizePortalRoleValue((string) $user->portal_role);
    if (!$canManageUsers && !($canManageVendorUsers && $currentRole === 'VENDOR')) {
        abort(403);
    }

    $request->merge([
        'portal_role' => normalizePortalRoleValue((string) $request->input('portal_role', '')),
    ]);

    $validated = $request->validate([
        'portal_role' => ['required', 'in:ADMIN,ADMIN_SUPER,ADMIN_CARE,ADMIN_FINANCE,ADMIN_MEDIA,VENDOR'],
        'portal_enabled' => ['required', 'in:1,0'],
        'portal_vendor_id' => ['nullable', 'string', 'max:255'],
        'vendor_verification_status' => ['nullable', 'in:pending,under_review,approved,rejected,suspended'],
        'vendor_verification_notes' => ['nullable', 'string', 'max:2000'],
        'vendor_approved_service_categories' => ['nullable', 'array'],
        'vendor_approved_service_categories.*' => ['required', 'string', 'max:80'],
        'vendor_contact_verified' => ['nullable', 'in:1,0'],
        'crosscheck_business_profile' => ['nullable', 'in:1,0'],
        'crosscheck_service_profile' => ['nullable', 'in:1,0'],
        'crosscheck_id_proof' => ['nullable', 'in:1,0'],
        'sole_proprietor_name_override' => ['nullable', 'in:1,0'],
        'vendor_rejection_reason' => ['nullable', 'string', 'max:2000'],
        'vendor_missing_documents' => ['nullable', 'string', 'max:2000'],
    ]);

    $isSelf = (int) session('portal_admin_user_id') === (int) $user->id;
    $nextEnabled = $validated['portal_enabled'] === '1';
    if ($isSelf && !$nextEnabled) {
        return back()->withErrors([
            'portal_enabled' => 'You cannot suspend your own active session.',
        ]);
    }

    $nextRole = normalizePortalRoleValue((string) $validated['portal_role']);

    if (!$canManageUsers && $nextRole !== 'VENDOR') {
        return back()->withErrors([
            'portal_role' => 'Admin role can only manage VENDOR accounts.',
        ]);
    }

    if ($isSelf && $nextRole !== 'ADMIN_SUPER') {
        return back()->withErrors([
            'portal_role' => 'You cannot remove your own Super Admin role from this screen.',
        ]);
    }

    $vendorId = trim((string) ($validated['portal_vendor_id'] ?? ''));
    if ($nextRole === 'VENDOR' && $vendorId === '') {
        return back()->withErrors([
            'portal_vendor_id' => 'Vendor ID is required for VENDOR users.',
        ]);
    }

    $before = [
        'portal_role' => (string) $user->portal_role,
        'portal_enabled' => (bool) $user->portal_enabled,
        'portal_vendor_id' => $user->portal_vendor_id,
        'vendor_verification_status' => Schema::hasColumn('users', 'vendor_verification_status') ? (string) ($user->vendor_verification_status ?? 'pending') : null,
        'vendor_approved_service_categories' => Schema::hasColumn('users', 'vendor_approved_service_categories') ? (string) ($user->vendor_approved_service_categories ?? '[]') : null,
    ];

    $user->portal_role = $nextRole;
    $user->portal_enabled = $nextEnabled;
    $user->portal_vendor_id = ($nextRole === 'VENDOR' && $vendorId !== '') ? $vendorId : null;

    $reviewedByUserId = (int) session('portal_admin_user_id', 0) ?: null;
    $reviewerRole = currentPortalAdminRole();
    $crosscheckBusinessProfile = (string) ($validated['crosscheck_business_profile'] ?? '0') === '1';
    $crosscheckServiceProfile = (string) ($validated['crosscheck_service_profile'] ?? '0') === '1';
    $crosscheckIdProof = (string) ($validated['crosscheck_id_proof'] ?? '0') === '1';
    $soleProprietorNameOverride = (string) ($validated['sole_proprietor_name_override'] ?? '0') === '1';
    $resolvedRejectionReason = trim((string) ($validated['vendor_rejection_reason'] ?? ''));
    $missingDocumentsInput = trim((string) ($validated['vendor_missing_documents'] ?? ''));
    $resolvedMissingDocuments = collect(preg_split('/[\r\n,]+/', $missingDocumentsInput) ?: [])
        ->map(static fn ($item) => trim((string) $item))
        ->filter(static fn ($item) => $item !== '')
        ->unique()
        ->values()
        ->all();
    $reviewLoggedStatus = null;

    if ($nextRole === 'VENDOR') {
        $requestedApprovedCategories = collect($validated['vendor_approved_service_categories'] ?? [])
            ->map(static fn ($item) => strtolower(trim((string) $item)))
            ->filter(static fn ($item) => $item !== '')
            ->values()
            ->all();

        $allowedCategories = array_keys(vendorPortalCategoryMap());
        $approvedCategories = array_values(array_unique(array_intersect($allowedCategories, $requestedApprovedCategories)));
        $verificationStatus = strtolower(trim((string) ($validated['vendor_verification_status'] ?? 'pending')));

        if ($verificationStatus === 'approved' && $approvedCategories === []) {
            return back()->withErrors([
                'vendor_approved_service_categories' => 'At least one approved service category is required when verification status is approved.',
            ])->withInput();
        }

        if ($verificationStatus === 'rejected' && $resolvedRejectionReason === '') {
            return back()->withErrors([
                'vendor_rejection_reason' => 'Rejection reason is required when verification status is rejected.',
            ])->withInput();
        }

        if ($verificationStatus !== 'rejected') {
            $resolvedRejectionReason = '';
            $resolvedMissingDocuments = [];
        }

        $verificationNotes = trim((string) ($validated['vendor_verification_notes'] ?? ''));
        if ($verificationStatus === 'rejected') {
            $rejectionContext = [];
            if ($verificationNotes !== '') {
                $rejectionContext[] = $verificationNotes;
            }
            if ($resolvedRejectionReason !== '') {
                $rejectionContext[] = 'Rejection reason: ' . $resolvedRejectionReason;
            }
            if ($resolvedMissingDocuments !== []) {
                $rejectionContext[] = 'Missing documents: ' . implode(', ', $resolvedMissingDocuments);
            }
            $verificationNotes = implode("\n", $rejectionContext);
        }

        $reviewLoggedStatus = $verificationStatus;

        if (Schema::hasColumn('users', 'vendor_verification_status')) {
            $user->vendor_verification_status = $verificationStatus;
        }
        if (Schema::hasColumn('users', 'vendor_verification_notes')) {
            $user->vendor_verification_notes = $verificationNotes;
        }
        if (Schema::hasColumn('users', 'vendor_verification_rejection_reason')) {
            $user->vendor_verification_rejection_reason = $verificationStatus === 'rejected'
                ? $resolvedRejectionReason
                : null;
        }
        if (Schema::hasColumn('users', 'vendor_verification_missing_documents')) {
            $user->vendor_verification_missing_documents = $verificationStatus === 'rejected' && $resolvedMissingDocuments !== []
                ? json_encode($resolvedMissingDocuments)
                : null;
        }
        if (Schema::hasColumn('users', 'vendor_verification_last_reviewed_at')) {
            $user->vendor_verification_last_reviewed_at = now();
        }
        if (Schema::hasColumn('users', 'vendor_verification_last_reviewed_by_user_id')) {
            $user->vendor_verification_last_reviewed_by_user_id = $reviewedByUserId;
        }
        if (Schema::hasColumn('users', 'vendor_approved_service_categories')) {
            $user->vendor_approved_service_categories = json_encode($approvedCategories);
        }
        if (Schema::hasColumn('users', 'vendor_verified_at')) {
            if ($verificationStatus === 'approved') {
                $user->vendor_verified_at = now();
            } elseif (in_array($verificationStatus, ['rejected', 'pending', 'under_review', 'suspended'], true)) {
                $user->vendor_verified_at = null;
            }
        }
        if (Schema::hasColumn('users', 'vendor_verified_by_user_id')) {
            if ($verificationStatus === 'approved') {
                $user->vendor_verified_by_user_id = (int) session('portal_admin_user_id', 0) ?: null;
            } elseif (in_array($verificationStatus, ['rejected', 'pending', 'under_review', 'suspended'], true)) {
                $user->vendor_verified_by_user_id = null;
            }
        }
        if (Schema::hasColumn('users', 'vendor_contact_verified_at')) {
            $contactVerified = (string) ($validated['vendor_contact_verified'] ?? '0') === '1';
            $user->vendor_contact_verified_at = $contactVerified ? now() : null;
        }
        if (Schema::hasColumn('users', 'vendor_contact_verified_by_user_id')) {
            $contactVerified = (string) ($validated['vendor_contact_verified'] ?? '0') === '1';
            $user->vendor_contact_verified_by_user_id = $contactVerified
                ? ((int) session('portal_admin_user_id', 0) ?: null)
                : null;
        }
    }

    $user->save();

    $notificationSentAt = null;
    if ($nextRole === 'VENDOR') {
        $vendorEmail = strtolower(trim((string) ($user->email ?? '')));
        if ($vendorEmail !== '' && filter_var($vendorEmail, FILTER_VALIDATE_EMAIL)) {
            $statusLabel = strtoupper(str_replace('_', ' ', (string) ($user->vendor_verification_status ?? 'pending')));
            $bodyLines = ['Your business/service verification review has been updated.'];

            if ((string) ($user->vendor_verification_status ?? '') === 'rejected') {
                if ($resolvedRejectionReason !== '') {
                    $bodyLines[] = 'Reason: ' . $resolvedRejectionReason;
                }
                if ($resolvedMissingDocuments !== []) {
                    $bodyLines[] = 'Missing documents: ' . implode(', ', $resolvedMissingDocuments);
                }
            }

            if ($soleProprietorNameOverride) {
                $bodyLines[] = 'Reviewer note: Sole proprietor name rule override has been applied.';
            }

            try {
                workationSendBrandedMail($vendorEmail, 'Vendor verification update', [
                    'preheader' => 'Your verification review status has changed.',
                    'headline' => 'Vendor verification update',
                    'intro' => 'Your business/service verification review has been updated.',
                    'statusLabel' => $statusLabel,
                    'statusTone' => ((string) ($user->vendor_verification_status ?? '') === 'approved') ? 'success' : (((string) ($user->vendor_verification_status ?? '') === 'rejected') ? 'danger' : 'info'),
                    'bodyLines' => $bodyLines,
                    'metaRows' => [
                        'Status' => $statusLabel,
                    ],
                    'ctaUrl' => url('/portal/vendor/login'),
                    'ctaLabel' => 'Open Vendor Portal',
                ]);
                $notificationSentAt = now();
            } catch (\Throwable $exception) {
                Log::warning('Unable to send vendor verification update email.', [
                    'user_id' => (int) $user->id,
                    'email' => $vendorEmail,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if ($reviewLoggedStatus !== null && Schema::hasTable('portal_vendor_verification_reviews')) {
            DB::table('portal_vendor_verification_reviews')->insert([
                'vendor_user_id' => (int) $user->id,
                'reviewed_by_user_id' => $reviewedByUserId,
                'reviewer_role' => $reviewerRole !== '' ? $reviewerRole : null,
                'from_status' => strtolower(trim((string) ($before['vendor_verification_status'] ?? 'pending'))),
                'to_status' => $reviewLoggedStatus,
                'crosscheck_business_profile' => $crosscheckBusinessProfile,
                'crosscheck_service_profile' => $crosscheckServiceProfile,
                'crosscheck_id_proof' => $crosscheckIdProof,
                'sole_proprietor_name_override' => $soleProprietorNameOverride,
                'missing_documents' => $resolvedMissingDocuments !== [] ? json_encode($resolvedMissingDocuments) : null,
                'rejection_reason' => $resolvedRejectionReason !== '' ? $resolvedRejectionReason : null,
                'review_notes' => trim((string) ($validated['vendor_verification_notes'] ?? '')),
                'vendor_notified_at' => $notificationSentAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    portalAdminAuditLog('user.updated', [
        'target_user_id' => (int) $user->id,
        'target_identifier' => (string) ($user->username ?: $user->email),
        'target_role' => (string) $user->portal_role,
        'before' => $before,
        'after' => [
            'portal_role' => (string) $user->portal_role,
            'portal_enabled' => (bool) $user->portal_enabled,
            'portal_vendor_id' => $user->portal_vendor_id,
            'vendor_verification_status' => Schema::hasColumn('users', 'vendor_verification_status') ? (string) ($user->vendor_verification_status ?? 'pending') : null,
            'vendor_approved_service_categories' => Schema::hasColumn('users', 'vendor_approved_service_categories') ? (string) ($user->vendor_approved_service_categories ?? '[]') : null,
            'vendor_verification_rejection_reason' => Schema::hasColumn('users', 'vendor_verification_rejection_reason') ? (string) ($user->vendor_verification_rejection_reason ?? '') : null,
            'vendor_verification_missing_documents' => Schema::hasColumn('users', 'vendor_verification_missing_documents') ? (string) ($user->vendor_verification_missing_documents ?? '') : null,
        ],
    ]);

    return back()->with('portal_notice', 'Portal user updated: ' . ($user->username ?: ('#' . $user->id)));
});

Route::get('/portal/admin/vendor-registrations/{registration}/document/{documentType}', function (int $registration, string $documentType) {
    if (!canReviewVendorRegistrations()) {
        abort(403);
    }

    if (!Schema::hasTable('vendor_registration_requests')) {
        abort(404);
    }

    if (!in_array($documentType, ['business_license', 'verification'], true)) {
        abort(404);
    }

    $registrationRow = DB::table('vendor_registration_requests')
        ->where('id', $registration)
        ->first();

    if (!$registrationRow) {
        abort(404);
    }

    $path = $documentType === 'business_license'
        ? (string) ($registrationRow->business_license_document_path ?? '')
        : (string) ($registrationRow->verification_document_path ?? '');

    if ($path === '' || !Storage::disk('local')->exists($path)) {
        abort(404);
    }

    return Storage::disk('local')->download($path);
});

Route::post('/portal/admin/vendor-registrations/{registration}/approve', function (Request $request, int $registration) {
    if (!canReviewVendorRegistrations()) {
        abort(403);
    }

    if (!Schema::hasTable('vendor_registration_requests')) {
        return back()->withErrors([
            'registration' => 'Vendor registration table is missing. Run migrations first.',
        ]);
    }

    $validated = $request->validate([
        'portal_vendor_id' => ['required', 'string', 'max:255'],
        'approval_notes' => ['nullable', 'string', 'max:2000'],
    ]);

    $registrationRow = DB::table('vendor_registration_requests')
        ->where('id', $registration)
        ->first();

    if (!$registrationRow) {
        return back()->withErrors([
            'registration' => 'Vendor registration request not found.',
        ]);
    }

    if ((string) $registrationRow->status !== 'pending') {
        return back()->withErrors([
            'registration' => 'Only pending registration requests can be approved.',
        ]);
    }

    $email = strtolower(trim((string) $registrationRow->email));
    $vendorId = trim((string) $validated['portal_vendor_id']);
    $approvalNotes = trim((string) ($validated['approval_notes'] ?? ''));

    // ADMIN_CARE can review and request approval, but final approval is ADMIN/ADMIN_SUPER only.
    if (!canApproveVendorRegistrationRequest()) {
        if (!portalActionRequestsEnabled()) {
            return back()->withErrors([
                'registration' => 'Approval request workflow is not available until migrations are applied.',
            ]);
        }

        $existingPending = DB::table('portal_admin_action_requests')
            ->where('status', 'pending')
            ->where('action_type', 'vendor_registration_approve')
            ->where('target_registration_id', $registration)
            ->exists();

        if ($existingPending) {
            return back()->withErrors([
                'registration' => 'An approval request is already pending for this vendor registration.',
            ]);
        }

        $requestId = createPortalActionRequest(
            'vendor_registration_approve',
            null,
            $registration,
            (string) $registrationRow->email,
            $approvalNotes !== '' ? $approvalNotes : 'Submitted by ADMIN_CARE for ADMIN/ADMIN_SUPER approval.',
            [
                'portal_vendor_id' => $vendorId,
                'approval_notes' => $approvalNotes,
            ]
        );

        portalAdminAuditLog('vendor_registration.approval_requested', [
            'target_identifier' => (string) $registrationRow->email,
            'target_role' => 'VENDOR',
            'registration_id' => $registration,
            'action_request_id' => $requestId,
            'portal_vendor_id' => $vendorId,
        ]);

        return back()->with('portal_notice', 'Vendor approval request submitted for ADMIN/ADMIN_SUPER approval.');
    }

    $resetEmailSent = false;
    $resetEmailError = null;
    $approvedUserId = null;
    $approvedUserIdentifier = null;

    try {
        DB::transaction(function () use (
            $registration,
            $email,
            $vendorId,
            $approvalNotes,
            &$resetEmailSent,
            &$resetEmailError,
            &$approvedUserId,
            &$approvedUserIdentifier
        ) {
            $requestRow = DB::table('vendor_registration_requests')
                ->where('id', $registration)
                ->lockForUpdate()
                ->first();

            if (!$requestRow || (string) $requestRow->status !== 'pending') {
                throw new \RuntimeException('This registration request is no longer pending.');
            }

            $existingUser = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if ($existingUser instanceof User && normalizePortalRoleValue((string) $existingUser->portal_role) !== 'VENDOR') {
                throw new \RuntimeException('An existing non-vendor account already uses this email.');
            }

            $portalUser = $existingUser;
            if (!$portalUser instanceof User) {
                $portalUser = new User();
                $portalUser->username = generatePortalUsernameFromEmail($email);
                $portalUser->email = $email;
                $portalUser->password = Hash::make(Str::random(24));
            }

            $portalUser->name = (string) $requestRow->contact_name;
            $portalUser->portal_role = 'VENDOR';
            $portalUser->portal_enabled = true;
            $portalUser->portal_vendor_id = $vendorId;
            $portalUser->save();

            $approvedUserId = (int) $portalUser->id;
            $approvedUserIdentifier = (string) ($portalUser->username ?: $portalUser->email);

            try {
                $token = Password::broker('backend_users')->createToken($portalUser);
                $portalUser->sendPasswordResetNotification($token);
                $resetEmailSent = true;
            } catch (\Throwable $mailError) {
                $resetEmailSent = false;
                $resetEmailError = $mailError->getMessage();
                Log::error('Failed to send vendor portal reset email after registration approval.', [
                    'registration_id' => (int) $requestRow->id,
                    'user_id' => (int) $portalUser->id,
                    'email' => $email,
                    'error' => $mailError->getMessage(),
                ]);
            }

            DB::table('vendor_registration_requests')
                ->where('id', $registration)
                ->update([
                    'status' => 'approved',
                    'review_notes' => $approvalNotes !== '' ? $approvalNotes : null,
                    'reviewed_by_user_id' => session('portal_admin_user_id'),
                    'reviewed_at' => now(),
                    'approved_user_id' => $portalUser->id,
                    'updated_at' => now(),
                ]);
        });
    } catch (\Throwable $e) {
        return back()->withErrors([
            'registration' => $e->getMessage(),
        ]);
    }

    portalAdminAuditLog('vendor_registration.approved', [
        'target_user_id' => $approvedUserId,
        'target_identifier' => $approvedUserIdentifier,
        'target_role' => 'VENDOR',
        'registration_id' => $registration,
        'registration_email' => $email,
        'portal_vendor_id' => $vendorId,
        'reset_email_sent' => $resetEmailSent,
    ]);

    if ($resetEmailSent) {
        return back()->with('portal_notice', 'Vendor registration approved and reset email sent.');
    }

    return back()->withErrors([
        'registration' => 'Vendor registration approved, but password setup email failed to send. Please verify mail settings.',
    ]);
});

Route::post('/portal/admin/action-requests/{requestId}/approve', function (int $requestId) {
    if (!portalActionRequestsEnabled()) {
        return back()->withErrors(['request' => 'Action request workflow table is missing. Run migrations first.']);
    }

    $requestRow = DB::table('portal_admin_action_requests')
        ->where('id', $requestId)
        ->first();

    if (!$requestRow || (string) $requestRow->status !== 'pending') {
        return back()->withErrors(['request' => 'Pending action request not found.']);
    }

    if ((string) $requestRow->action_type === 'vendor_delete') {
        if (!canApproveVendorDeleteRequest()) {
            abort(403);
        }

        $targetUserId = (int) ($requestRow->target_user_id ?? 0);
        $targetUser = $targetUserId > 0 ? User::query()->find($targetUserId) : null;
        if ($targetUser instanceof User) {
            if (normalizePortalRoleValue((string) $targetUser->portal_role) !== 'VENDOR') {
                return back()->withErrors(['request' => 'Target user is no longer a vendor account.']);
            }
            $targetUser->delete();
        }

        DB::table('portal_admin_action_requests')
            ->where('id', $requestId)
            ->update([
                'status' => 'approved',
                'approved_by_user_id' => session('portal_admin_user_id'),
                'approved_at' => now(),
                'updated_at' => now(),
            ]);

        portalAdminAuditLog('vendor_delete.approved', [
            // The vendor may have been deleted above; keep identifier/role and avoid stale FK value.
            'target_user_id' => null,
            'target_identifier' => (string) ($requestRow->target_identifier ?? 'unknown-vendor'),
            'target_role' => 'VENDOR',
            'action_request_id' => $requestId,
        ]);

        return back()->with('portal_notice', 'Vendor delete request approved and processed.');
    }

    if ((string) $requestRow->action_type === 'vendor_registration_approve') {
        if (!canApproveVendorRegistrationRequest()) {
            abort(403);
        }

        $registrationId = (int) ($requestRow->target_registration_id ?? 0);
        if ($registrationId <= 0) {
            return back()->withErrors(['request' => 'Vendor registration target is missing.']);
        }

        $registrationRow = DB::table('vendor_registration_requests')
            ->where('id', $registrationId)
            ->first();

        if (!$registrationRow || (string) $registrationRow->status !== 'pending') {
            return back()->withErrors(['request' => 'Vendor registration is no longer pending.']);
        }

        $payload = [];
        if (!empty($requestRow->payload)) {
            $decoded = json_decode((string) $requestRow->payload, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        $vendorId = trim((string) ($payload['portal_vendor_id'] ?? ''));
        if ($vendorId === '') {
            return back()->withErrors(['request' => 'Approval request payload is missing vendor ID.']);
        }
        $approvalNotes = trim((string) ($payload['approval_notes'] ?? ''));

        $email = strtolower(trim((string) $registrationRow->email));
        $resetEmailSent = false;

        DB::transaction(function () use ($registrationId, $email, $vendorId, $approvalNotes, $requestId, &$resetEmailSent): void {
            $requestRegistrationRow = DB::table('vendor_registration_requests')
                ->where('id', $registrationId)
                ->lockForUpdate()
                ->first();

            if (!$requestRegistrationRow || (string) $requestRegistrationRow->status !== 'pending') {
                throw new \RuntimeException('This registration request is no longer pending.');
            }

            $existingUser = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if ($existingUser instanceof User && normalizePortalRoleValue((string) $existingUser->portal_role) !== 'VENDOR') {
                throw new \RuntimeException('An existing non-vendor account already uses this email.');
            }

            $portalUser = $existingUser;
            if (!$portalUser instanceof User) {
                $portalUser = new User();
                $portalUser->username = generatePortalUsernameFromEmail($email);
                $portalUser->email = $email;
                $portalUser->password = Hash::make(Str::random(24));
            }

            $portalUser->name = (string) $requestRegistrationRow->contact_name;
            $portalUser->portal_role = 'VENDOR';
            $portalUser->portal_enabled = true;
            $portalUser->portal_vendor_id = $vendorId;
            $portalUser->save();

            $token = Password::broker('backend_users')->createToken($portalUser);
            $portalUser->sendPasswordResetNotification($token);
            $resetEmailSent = true;

            DB::table('vendor_registration_requests')
                ->where('id', $registrationId)
                ->update([
                    'status' => 'approved',
                    'review_notes' => $approvalNotes !== '' ? $approvalNotes : null,
                    'reviewed_by_user_id' => session('portal_admin_user_id'),
                    'reviewed_at' => now(),
                    'approved_user_id' => $portalUser->id,
                    'updated_at' => now(),
                ]);

            DB::table('portal_admin_action_requests')
                ->where('id', $requestId)
                ->update([
                    'status' => 'approved',
                    'approved_by_user_id' => session('portal_admin_user_id'),
                    'approved_at' => now(),
                    'updated_at' => now(),
                ]);
        });

        portalAdminAuditLog('vendor_registration.approval_request_approved', [
            'target_identifier' => (string) $registrationRow->email,
            'target_role' => 'VENDOR',
            'registration_id' => $registrationId,
            'action_request_id' => $requestId,
            'reset_email_sent' => $resetEmailSent,
        ]);

        return back()->with('portal_notice', 'Vendor registration approval request processed successfully.');
    }

    if ((string) $requestRow->action_type === 'vendor.category_request') {
        if (!canApproveVendorRegistrationRequest()) {
            abort(403);
        }

        $targetUserId = (int) ($requestRow->target_user_id ?? 0);
        if ($targetUserId <= 0) {
            return back()->withErrors(['request' => 'Category request target vendor is missing.']);
        }

        $targetUser = User::query()->find($targetUserId);
        if (!$targetUser instanceof User || normalizePortalRoleValue((string) $targetUser->portal_role) !== 'VENDOR') {
            return back()->withErrors(['request' => 'Target user is not a valid vendor account anymore.']);
        }

        $payload = [];
        if (!empty($requestRow->payload)) {
            $decoded = json_decode((string) $requestRow->payload, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        $requestAction = strtolower(trim((string) ($payload['request_action'] ?? 'subscribe')));
        if (!in_array($requestAction, ['subscribe', 'open', 'release'], true)) {
            $requestAction = 'subscribe';
        }

        $allowedCategoryKeys = array_keys(vendorPortalCategoryMap());
        $requestedCategories = collect($payload['categories'] ?? [])
            ->map(static function ($value) use ($allowedCategoryKeys): ?string {
                $canonical = vendorPortalCanonicalCategory((string) $value);
                if ($canonical === null || !in_array($canonical, $allowedCategoryKeys, true)) {
                    return null;
                }

                return $canonical;
            })
            ->filter(static fn ($value) => is_string($value) && $value !== '')
            ->unique()
            ->values()
            ->all();

        if ($requestedCategories === []) {
            return back()->withErrors(['request' => 'Category request payload has no valid categories.']);
        }

        $registeredCategories = [];
        if (Schema::hasColumn('users', 'portal_service_categories')) {
            $registeredDecoded = json_decode((string) ($targetUser->portal_service_categories ?? '[]'), true);
            if (is_array($registeredDecoded)) {
                $registeredCategories = collect($registeredDecoded)
                    ->map(static fn ($value) => vendorPortalCanonicalCategory((string) $value))
                    ->filter(static fn ($value) => is_string($value) && $value !== '')
                    ->unique()
                    ->values()
                    ->all();
            }
        }

        $approvedCategories = [];
        if (Schema::hasColumn('users', 'vendor_approved_service_categories')) {
            $approvedDecoded = json_decode((string) ($targetUser->vendor_approved_service_categories ?? '[]'), true);
            if (is_array($approvedDecoded)) {
                $approvedCategories = collect($approvedDecoded)
                    ->map(static fn ($value) => vendorPortalCanonicalCategory((string) $value))
                    ->filter(static fn ($value) => is_string($value) && $value !== '')
                    ->unique()
                    ->values()
                    ->all();
            }
        }

        if ($requestAction === 'release') {
            $registeredCategories = array_values(array_diff($registeredCategories, $requestedCategories));
            $approvedCategories = array_values(array_diff($approvedCategories, $requestedCategories));
        } else {
            $registeredCategories = array_values(array_unique(array_merge($registeredCategories, $requestedCategories)));
            $approvedCategories = array_values(array_unique(array_merge($approvedCategories, $requestedCategories)));
        }

        if (Schema::hasColumn('users', 'portal_service_categories')) {
            $targetUser->portal_service_categories = json_encode($registeredCategories);
        }
        if (Schema::hasColumn('users', 'vendor_approved_service_categories')) {
            $targetUser->vendor_approved_service_categories = json_encode($approvedCategories);
        }
        if (Schema::hasColumn('users', 'vendor_verification_status')) {
            $targetUser->vendor_verification_status = 'approved';
        }
        if (Schema::hasColumn('users', 'vendor_verification_last_reviewed_at')) {
            $targetUser->vendor_verification_last_reviewed_at = now();
        }
        if (Schema::hasColumn('users', 'vendor_verified_at')) {
            $targetUser->vendor_verified_at = now();
        }
        $targetUser->save();

        DB::table('portal_admin_action_requests')
            ->where('id', $requestId)
            ->update([
                'status' => 'approved',
                'approved_by_user_id' => session('portal_admin_user_id'),
                'approved_at' => now(),
                'updated_at' => now(),
            ]);

        portalAdminAuditLog('vendor_category_request.approved', [
            'target_user_id' => (int) $targetUser->id,
            'target_identifier' => (string) ($targetUser->username ?: $targetUser->email),
            'target_role' => 'VENDOR',
            'action_request_id' => $requestId,
            'request_action' => $requestAction,
            'requested_categories' => $requestedCategories,
            'approved_categories_after' => $approvedCategories,
        ]);

        return back()->with('portal_notice', 'Vendor category request approved and applied.');
    }

    return back()->withErrors(['request' => 'Unsupported action request type.']);
});

Route::post('/portal/admin/action-requests/{requestId}/reject', function (Request $request, int $requestId) {
    if (!portalActionRequestsEnabled()) {
        return back()->withErrors(['request' => 'Action request workflow table is missing. Run migrations first.']);
    }

    $validated = $request->validate([
        'reason' => ['required', 'string', 'max:2000'],
    ]);

    $requestRow = DB::table('portal_admin_action_requests')
        ->where('id', $requestId)
        ->first();

    if (!$requestRow || (string) $requestRow->status !== 'pending') {
        return back()->withErrors(['request' => 'Pending action request not found.']);
    }

    $actionType = (string) $requestRow->action_type;
    if ($actionType === 'vendor_delete' && !canApproveVendorDeleteRequest()) {
        abort(403);
    }
    if ($actionType === 'vendor_registration_approve' && !canApproveVendorRegistrationRequest()) {
        abort(403);
    }
    if ($actionType === 'vendor.category_request' && !canApproveVendorRegistrationRequest()) {
        abort(403);
    }

    DB::table('portal_admin_action_requests')
        ->where('id', $requestId)
        ->update([
            'status' => 'rejected',
            'approved_by_user_id' => session('portal_admin_user_id'),
            'approved_at' => now(),
            'rejection_reason' => trim((string) $validated['reason']),
            'updated_at' => now(),
        ]);

    portalAdminAuditLog('action_request.rejected', [
        'target_identifier' => (string) ($requestRow->target_identifier ?? 'unknown-target'),
        'action_type' => $actionType,
        'action_request_id' => $requestId,
    ]);

    return back()->with('portal_notice', 'Action request rejected.');
});

Route::post('/portal/admin/vendor-registrations/{registration}/reject', function (Request $request, int $registration) {
    if (!canReviewVendorRegistrations()) {
        abort(403);
    }

    if (!Schema::hasTable('vendor_registration_requests')) {
        return back()->withErrors([
            'registration' => 'Vendor registration table is missing. Run migrations first.',
        ]);
    }

    $validated = $request->validate([
        'review_notes' => ['required', 'string', 'max:2000'],
    ]);

    $registrationRow = DB::table('vendor_registration_requests')
        ->where('id', $registration)
        ->first();

    if (!$registrationRow) {
        return back()->withErrors([
            'registration' => 'Vendor registration request not found.',
        ]);
    }

    if ((string) $registrationRow->status !== 'pending') {
        return back()->withErrors([
            'registration' => 'Only pending registration requests can be rejected.',
        ]);
    }

    DB::table('vendor_registration_requests')
        ->where('id', $registration)
        ->update([
            'status' => 'rejected',
            'review_notes' => trim((string) $validated['review_notes']),
            'reviewed_by_user_id' => session('portal_admin_user_id'),
            'reviewed_at' => now(),
            'updated_at' => now(),
        ]);

    portalAdminAuditLog('vendor_registration.rejected', [
        'target_identifier' => (string) $registrationRow->email,
        'target_role' => 'VENDOR',
        'registration_id' => $registration,
        'registration_email' => (string) $registrationRow->email,
    ]);

    return back()->with('portal_notice', 'Vendor registration rejected.');
});

// Legacy Laravel business routes are decommissioned in runtime.
Route::post('/portal/admin/listings/{listing}/approve', function (Request $request, int $listing) {
    if (!canModerateListings()) {
        abort(403);
    }

    $categoryHint = vendorPortalCanonicalCategory((string) $request->input('listing_category', ''));
    if ($categoryHint === null) {
        return back()->withErrors(['listing' => 'Missing listing category context. Refresh the admin page and try again.']);
    }
    $dedicatedRowId = max(0, (int) $request->input('listing_dedicated_row_id', 0));
    $listingRow = $dedicatedRowId > 0
        ? \App\Support\VendorPropertyCompatibilityReader::loadPropertyByDedicatedRowId($dedicatedRowId, $categoryHint)
        : \App\Support\VendorPropertyCompatibilityReader::loadPropertyById($listing, $categoryHint);
    if (!$listingRow) {
        return back()->withErrors(['listing' => 'Listing not found.']);
    }

    $currentStatus = strtolower(trim((string) ($listingRow->listing_moderation_status ?? '')));
    if ($currentStatus !== 'pending_review') {
        return back()->withErrors(['listing' => 'Only listings in pending_review status can be approved.']);
    }

    $adminNotes = trim((string) ($request->input('admin_notes') ?? ''));
    $adminUserId = (int) session('portal_admin_user_id');
    $categoryHint = vendorPortalCanonicalCategory((string) ($listingRow->listing_category ?? '')) ?? $categoryHint;

    \App\Support\VendorPropertyCompatibilityReader::updateModerationStatus(
        (int) ($listingRow->id ?? $listing),
        'approved',
        $adminNotes ?: null,
        $adminUserId,
        $categoryHint,
        $dedicatedRowId > 0 ? $dedicatedRowId : (int) ($listingRow->dedicated_row_id ?? 0)
    );

    portalAdminAuditLog('listing.approved', [
        'target_identifier' => (string) ($listingRow->listing_name ?? $listingRow->name ?? ('listing_id:' . $listing)),
        'target_role' => 'VENDOR',
        'listing_id' => $listing,
        'vendor_id' => (int) ($listingRow->vendor_user_id ?? 0),
    ]);

    $vendorUserId = (int) ($listingRow->vendor_user_id ?? 0);
    if ($vendorUserId > 0) {
        $vendorUser = User::query()->find($vendorUserId);
        $vendorEmail = strtolower(trim((string) ($vendorUser?->email ?? '')));
        if ($vendorEmail !== '' && filter_var($vendorEmail, FILTER_VALIDATE_EMAIL)) {
            $approvalNotes = trim((string) ($adminNotes ?? ''));
            try {
                workationSendBrandedMail($vendorEmail, 'Service listing approved', [
                    'preheader' => 'Your service listing is now approved.',
                    'headline' => 'Service listing approved',
                    'intro' => 'Your service listing has been approved and is now open for bookings.',
                    'statusLabel' => 'Approved',
                    'statusTone' => 'success',
                    'bodyLines' => $approvalNotes !== ''
                        ? ['Your service listing has been approved.', 'Reviewer notes: ' . $approvalNotes]
                        : ['Your service listing has been approved.'],
                    'metaRows' => [
                        'Listing' => (string) ($listingRow->listing_name ?? ('Listing #' . $listing)),
                    ],
                    'ctaUrl' => url('/vendor'),
                    'ctaLabel' => 'Open Vendor Dashboard',
                ]);
            } catch (\Throwable $exception) {
                Log::warning('Unable to send listing approval notification.', [
                    'listing_id' => $listing,
                    'vendor_user_id' => $vendorUserId,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    return back()->with('portal_notice', 'Listing approved and is now open for bookings.');
});

Route::post('/portal/admin/listings/{listing}/unapprove', function (Request $request, int $listing) {
    if (!canModerateListings()) {
        abort(403);
    }

    $categoryHint = vendorPortalCanonicalCategory((string) $request->input('listing_category', ''));
    if ($categoryHint === null) {
        return back()->withErrors(['listing' => 'Missing listing category context. Refresh the admin page and try again.']);
    }

    $dedicatedRowId = max(0, (int) $request->input('listing_dedicated_row_id', 0));
    $listingRow = $dedicatedRowId > 0
        ? \App\Support\VendorPropertyCompatibilityReader::loadPropertyByDedicatedRowId($dedicatedRowId, $categoryHint)
        : \App\Support\VendorPropertyCompatibilityReader::loadPropertyById($listing, $categoryHint);
    if (!$listingRow) {
        return back()->withErrors(['listing' => 'Listing not found.']);
    }

    $currentStatus = strtolower(trim((string) ($listingRow->listing_moderation_status ?? '')));
    if ($currentStatus !== 'approved') {
        return back()->withErrors(['listing' => 'Only approved listings can be moved back to pending review.']);
    }

    $resolvedCategoryHint = vendorPortalCanonicalCategory((string) ($listingRow->listing_category ?? '')) ?? $categoryHint;
    $unapproveNotes = trim((string) ($request->input('admin_notes') ?? ''));
    if ($unapproveNotes === '') {
        $unapproveNotes = 'Listing moved back to pending review by admin.';
    }

    \App\Support\VendorPropertyCompatibilityReader::reopenForReview(
        (int) ($listingRow->id ?? $listing),
        $unapproveNotes,
        $resolvedCategoryHint,
        $dedicatedRowId > 0 ? $dedicatedRowId : (int) ($listingRow->dedicated_row_id ?? 0)
    );

    portalAdminAuditLog('listing.unapproved', [
        'target_identifier' => (string) ($listingRow->listing_name ?? $listingRow->name ?? ('listing_id:' . $listing)),
        'target_role' => 'VENDOR',
        'listing_id' => (int) ($listingRow->id ?? $listing),
        'vendor_id' => (int) ($listingRow->vendor_user_id ?? 0),
        'listing_category' => $resolvedCategoryHint,
    ]);

    return back()->with('portal_notice', 'Listing moved back to pending review.');
});

Route::get('/portal/admin/listings/{listing}/preview', function (Request $request, int $listing) {
    if (!canModerateListings()) {
        abort(403);
    }

    $categoryHint = vendorPortalCanonicalCategory((string) $request->query('category', ''));
    $dedicatedRowId = max(0, (int) $request->query('row_id', 0));
    $listingRow = ($dedicatedRowId > 0 && $categoryHint !== null)
        ? \App\Support\VendorPropertyCompatibilityReader::loadPropertyByDedicatedRowId($dedicatedRowId, $categoryHint)
        : \App\Support\VendorPropertyCompatibilityReader::loadPropertyById($listing, $categoryHint);
    if (!$listingRow) {
        return back()->withErrors(['listing' => 'Listing not found.']);
    }

    $resolvedCategory = vendorPortalCanonicalCategory((string) ($listingRow->listing_category ?? '')) ?? $categoryHint;
    $listingDetails = json_decode((string) ($listingRow->listing_details ?? $listingRow->details ?? ''), true);
    if (!is_array($listingDetails)) {
        $listingDetails = [];
    }
    $isRetreatFlagEnabled = static function ($value): bool {
        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    };
    if ($resolvedCategory === 'excursion') {
        $detailsMode = strtolower(trim(str_replace('-', '_', (string) ($listingDetails['program_customization_mode'] ?? ''))));
        $detailsListingCategory = strtolower(trim(str_replace('-', '_', (string) ($listingDetails['listing_category'] ?? ''))));
        $hasRetreatPackageShape = array_key_exists('total_package_price', $listingDetails)
            || array_key_exists('package_included_pax', $listingDetails)
            || array_key_exists('included_services', $listingDetails);
        $rowRetreatFlag = $isRetreatFlagEnabled($listingRow->is_corporate_retreat ?? '0')
            || $isRetreatFlagEnabled($listingRow->is_retreat_package ?? '0');
        $detailsRetreatFlag = $isRetreatFlagEnabled($listingDetails['is_corporate_retreat'] ?? '0')
            || $isRetreatFlagEnabled($listingDetails['is_retreat_package'] ?? '0')
            || $detailsListingCategory === 'corporate_retreat'
            || $detailsMode === 'corporate_retreat'
            || $hasRetreatPackageShape;
        if ($rowRetreatFlag || $detailsRetreatFlag) {
            $resolvedCategory = 'corporate_retreat';
        }
    }
    $listingId = (int) ($listingRow->id ?? $listing);

    if ($resolvedCategory === 'excursion' && $listingId > 0) {
        $canonicalRow = \App\Support\VendorPropertyCompatibilityReader::loadPropertyById($listingId);
        if ($canonicalRow) {
            $canonicalDetails = json_decode((string) ($canonicalRow->listing_details ?? $canonicalRow->details ?? ''), true);
            if (!is_array($canonicalDetails)) {
                $canonicalDetails = [];
            }
            $canonicalMode = strtolower(trim(str_replace('-', '_', (string) ($canonicalDetails['program_customization_mode'] ?? ''))));
            $canonicalListingCategory = strtolower(trim(str_replace('-', '_', (string) ($canonicalDetails['listing_category'] ?? ($canonicalRow->listing_category ?? '')))));
            $canonicalRetreatShape = array_key_exists('total_package_price', $canonicalDetails)
                || array_key_exists('package_included_pax', $canonicalDetails)
                || array_key_exists('included_services', $canonicalDetails);
            $canonicalRetreatFlag = $isRetreatFlagEnabled($canonicalRow->is_corporate_retreat ?? '0')
                || $isRetreatFlagEnabled($canonicalRow->is_retreat_package ?? '0')
                || $isRetreatFlagEnabled($canonicalDetails['is_corporate_retreat'] ?? '0')
                || $isRetreatFlagEnabled($canonicalDetails['is_retreat_package'] ?? '0')
                || $canonicalListingCategory === 'corporate_retreat'
                || $canonicalMode === 'corporate_retreat'
                || $canonicalRetreatShape;

            if ($canonicalRetreatFlag) {
                $resolvedCategory = 'corporate_retreat';
            }
        }
    }

    if ($resolvedCategory === 'accommodation') {
        return redirect('/property/' . $listingId . '?preview=admin');
    }

    if ($resolvedCategory === 'sea_transport') {
        return redirect('/sea-transport/' . $listingId . '?preview=admin');
    }

    $bookingSlugMap = [
        'land_transport' => 'land-transport',
        'corporate_retreat' => 'corporate-retreats',
    ];
    $bookingCategory = $bookingSlugMap[$resolvedCategory] ?? $resolvedCategory;
    $query = ['preview' => 'admin'];
    if ($resolvedCategory === 'corporate_retreat') {
        $query['retreat_mode'] = '1';
    }

    return redirect('/category-booking/' . rawurlencode($bookingCategory) . '/' . $listingId . '?' . http_build_query($query));
});

Route::post('/portal/admin/listings/{listing}/reject', function (Request $request, int $listing) {
    if (!canModerateListings()) {
        abort(403);
    }

    $validated = $request->validate([
        'admin_notes' => ['required', 'string', 'max:2000'],
        'missing_documents' => ['nullable', 'string', 'max:1000'],
    ]);

    $categoryHint = vendorPortalCanonicalCategory((string) $request->input('listing_category', ''));
    if ($categoryHint === null) {
        return back()->withErrors(['listing' => 'Missing listing category context. Refresh the admin page and try again.']);
    }
    $dedicatedRowId = max(0, (int) $request->input('listing_dedicated_row_id', 0));
    $listingRow = $dedicatedRowId > 0
        ? \App\Support\VendorPropertyCompatibilityReader::loadPropertyByDedicatedRowId($dedicatedRowId, $categoryHint)
        : \App\Support\VendorPropertyCompatibilityReader::loadPropertyById($listing, $categoryHint);
    if (!$listingRow) {
        return back()->withErrors(['listing' => 'Listing not found.']);
    }

    $currentStatus = strtolower(trim((string) ($listingRow->listing_moderation_status ?? '')));
    if (!in_array($currentStatus, ['pending_review', 'approved'], true)) {
        return back()->withErrors(['listing' => 'Only pending_review or approved listings can be rejected.']);
    }

    $adminUserId = (int) session('portal_admin_user_id');
    $categoryHint = vendorPortalCanonicalCategory((string) ($listingRow->listing_category ?? '')) ?? $categoryHint;

    $rejectionNotes = trim((string) $validated['admin_notes']);
    $missingDocuments = trim((string) ($validated['missing_documents'] ?? ''));
    if ($missingDocuments !== '') {
        $rejectionNotes .= "\nMissing documents: " . $missingDocuments;
    }

    \App\Support\VendorPropertyCompatibilityReader::updateModerationStatus(
        (int) ($listingRow->id ?? $listing),
        'rejected',
        $rejectionNotes,
        $adminUserId,
        $categoryHint,
        $dedicatedRowId > 0 ? $dedicatedRowId : (int) ($listingRow->dedicated_row_id ?? 0)
    );

    portalAdminAuditLog('listing.rejected', [
        'target_identifier' => (string) ($listingRow->listing_name ?? $listingRow->name ?? ('listing_id:' . $listing)),
        'target_role' => 'VENDOR',
        'listing_id' => $listing,
        'vendor_id' => (int) ($listingRow->vendor_user_id ?? 0),
    ]);

    $vendorUserId = (int) ($listingRow->vendor_user_id ?? 0);
    if ($vendorUserId > 0) {
        $vendorUser = User::query()->find($vendorUserId);
        $vendorEmail = strtolower(trim((string) ($vendorUser?->email ?? '')));
        if ($vendorEmail !== '' && filter_var($vendorEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                workationSendBrandedMail($vendorEmail, 'Service listing review update', [
                    'preheader' => 'Your service listing review has been updated.',
                    'headline' => 'Service listing review update',
                    'intro' => 'Your service listing has been reviewed and requires changes before it can be approved.',
                    'statusLabel' => 'Rejected',
                    'statusTone' => 'danger',
                    'bodyLines' => array_values(array_filter([
                        'Your service listing has been rejected after review.',
                        'Reason: ' . trim((string) $validated['admin_notes']),
                        $missingDocuments !== '' ? 'Missing documents: ' . $missingDocuments : null,
                    ])),
                    'metaRows' => [
                        'Listing' => (string) ($listingRow->listing_name ?? ('Listing #' . $listing)),
                    ],
                    'ctaUrl' => url('/vendor'),
                    'ctaLabel' => 'Open Vendor Dashboard',
                ]);
            } catch (\Throwable $exception) {
                Log::warning('Unable to send listing rejection notification.', [
                    'listing_id' => $listing,
                    'vendor_user_id' => $vendorUserId,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    return back()->with('portal_notice', 'Listing rejected. The vendor will be notified to make corrections.');
});

// Atoll & Island shared data API endpoints