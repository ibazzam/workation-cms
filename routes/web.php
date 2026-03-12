<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
                'username' => (string) env('WORKATION_ADMIN_PORTAL_USERNAME', ''),
                'password' => (string) env('WORKATION_ADMIN_PORTAL_PASSWORD', ''),
                'session_key' => 'portal_admin_authenticated',
                'name' => 'Admin',
            ];
        }

        return [
            'username' => (string) env('WORKATION_VENDOR_PORTAL_USERNAME', ''),
            'password' => (string) env('WORKATION_VENDOR_PORTAL_PASSWORD', ''),
            'session_key' => 'portal_vendor_authenticated',
            'name' => 'Vendor',
        ];
    }
}

if (!function_exists('portalRoutePath')) {
    function portalRoutePath(string $portal): string
    {
        return $portal === 'admin' ? '/admin' : '/vendor';
    }
}

Route::get('/', function () {
    $apiBase = workationApiBase();

    return view('welcome', [
        'apiBase' => $apiBase,
        'activityLinks' => [
            [
                'label' => 'Strict Live Preflight PASS - Run 22991556615',
                'url' => 'https://github.com/ibazzam/workation-cms/actions/runs/22991556615',
            ],
            [
                'label' => 'Strict Live Preflight PASS - Run 22992285238',
                'url' => 'https://github.com/ibazzam/workation-cms/actions/runs/22992285238',
            ],
            [
                'label' => 'Promotion Evidence - Run 22991538950',
                'url' => 'https://github.com/ibazzam/workation-cms/actions/runs/22991538950',
            ],
        ],
        'artifactLinks' => [
            [
                'label' => 'Launch Approval Record (2026-03-18)',
                'url' => 'https://github.com/ibazzam/workation-cms/blob/main/docs/launch-final-approval-record-2026-03-18.md',
            ],
            [
                'label' => 'Production Verification Report (2026-03-18)',
                'url' => 'https://github.com/ibazzam/workation-cms/blob/main/docs/production-verification-report-2026-03-18.md',
            ],
            [
                'label' => 'Alert Routing Verification (2026-03-18)',
                'url' => 'https://github.com/ibazzam/workation-cms/blob/main/docs/alert-routing-verification-2026-03-18.md',
            ],
        ],
    ]);
});

Route::get('/admin', function () {
    $portal = 'admin';
    $config = portalConfig($portal);
    if (!session()->get($config['session_key'], false)) {
        return redirect('/portal/' . $portal . '/login');
    }

    return view('admin-portal', [
        'apiBase' => workationApiBase(),
        'portalUser' => session('portal_admin_user', $config['name']),
    ]);
});

Route::get('/vendor', function () {
    $portal = 'vendor';
    $config = portalConfig($portal);
    if (!session()->get($config['session_key'], false)) {
        return redirect('/portal/' . $portal . '/login');
    }

    return view('vendor-portal', [
        'apiBase' => workationApiBase(),
        'portalUser' => session('portal_vendor_user', $config['name']),
    ]);
});

Route::get('/portal/{portal}/login', function (string $portal) {
    if (!in_array($portal, ['admin', 'vendor'], true)) {
        abort(404);
    }

    $config = portalConfig($portal);
    if (session()->get($config['session_key'], false)) {
        return redirect(portalRoutePath($portal));
    }

    return view('portal-login', [
        'portal' => $portal,
        'portalName' => $config['name'],
    ]);
});

Route::post('/portal/{portal}/login', function (Request $request, string $portal) {
    if (!in_array($portal, ['admin', 'vendor'], true)) {
        abort(404);
    }

    $validated = $request->validate([
        'username' => ['required', 'string'],
        'password' => ['required', 'string'],
    ]);

    $config = portalConfig($portal);
    $expectedUsername = $config['username'];
    $expectedPassword = $config['password'];

    if ($expectedUsername === '' || $expectedPassword === '') {
        return back()->withErrors([
            'username' => 'Portal credentials are not configured on server environment variables.',
        ])->withInput();
    }

    $usernameMatches = hash_equals($expectedUsername, (string) $validated['username']);
    $passwordMatches = hash_equals($expectedPassword, (string) $validated['password']);
    if (!($usernameMatches && $passwordMatches)) {
        return back()->withErrors([
            'username' => 'Invalid username or password.',
        ])->withInput();
    }

    $request->session()->regenerate();
    session([
        $config['session_key'] => true,
        'portal_' . $portal . '_user' => $validated['username'],
    ]);

    return redirect(portalRoutePath($portal));
});

Route::post('/portal/{portal}/logout', function (Request $request, string $portal) {
    if (!in_array($portal, ['admin', 'vendor'], true)) {
        abort(404);
    }

    $config = portalConfig($portal);
    session()->forget([$config['session_key'], 'portal_' . $portal . '_user']);
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/portal/' . $portal . '/login');
});

// Legacy Laravel business routes are decommissioned in runtime.
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
