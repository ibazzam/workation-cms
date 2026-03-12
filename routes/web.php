<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
                'session_key' => 'portal_admin_authenticated',
                'name' => 'Admin',
                'allowed_roles' => ['ADMIN', 'ADMIN_SUPER'],
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
        return $portal === 'admin' ? '/admin' : '/vendor';
    }
}

if (!function_exists('canManagePortalUsers')) {
    function canManagePortalUsers(): bool
    {
        return session('portal_admin_authenticated', false) && session('portal_admin_role') === 'ADMIN_SUPER';
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

    $canManageUsers = canManagePortalUsers();
    $portalUsers = User::query()
        ->whereNotNull('portal_role')
        ->orderBy('portal_role')
        ->orderBy('username')
        ->get(['id', 'name', 'username', 'email', 'portal_role', 'portal_enabled', 'portal_vendor_id']);

    return view('admin-portal', [
        'apiBase' => workationApiBase(),
        'portalUser' => session('portal_admin_user', $config['name']),
        'portalRole' => session('portal_admin_role', 'ADMIN'),
        'canManageUsers' => $canManageUsers,
        'portalUsers' => $portalUsers,
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
    $portalUser = User::query()
        ->where('username', (string) $validated['username'])
        ->where('portal_enabled', true)
        ->whereIn('portal_role', $config['allowed_roles'])
        ->first();

    if (!$portalUser || !Hash::check((string) $validated['password'], (string) $portalUser->password)) {
        return back()->withErrors([
            'username' => 'Invalid username or password.',
        ])->withInput();
    }

    $request->session()->regenerate();
    session([
        $config['session_key'] => true,
        'portal_' . $portal . '_user' => $portalUser->name,
        'portal_' . $portal . '_user_id' => $portalUser->id,
        'portal_' . $portal . '_role' => $portalUser->portal_role,
    ]);

    return redirect(portalRoutePath($portal));
});

Route::post('/portal/{portal}/logout', function (Request $request, string $portal) {
    if (!in_array($portal, ['admin', 'vendor'], true)) {
        abort(404);
    }

    $config = portalConfig($portal);
    session()->forget([$config['session_key'], 'portal_' . $portal . '_user', 'portal_' . $portal . '_user_id', 'portal_' . $portal . '_role']);
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/portal/' . $portal . '/login');
});

Route::post('/portal/admin/users/{user}/manage', function (Request $request, User $user) {
    if (!canManagePortalUsers()) {
        abort(403);
    }

    $validated = $request->validate([
        'portal_role' => ['required', 'in:ADMIN,ADMIN_SUPER,VENDOR'],
        'portal_enabled' => ['required', 'in:1,0'],
        'portal_vendor_id' => ['nullable', 'string', 'max:255'],
    ]);

    $isSelf = (int) session('portal_admin_user_id') === (int) $user->id;
    $nextEnabled = $validated['portal_enabled'] === '1';
    if ($isSelf && !$nextEnabled) {
        return back()->withErrors([
            'portal_enabled' => 'You cannot suspend your own active session.',
        ]);
    }

    $nextRole = (string) $validated['portal_role'];
    if ($isSelf && $nextRole !== 'ADMIN_SUPER') {
        return back()->withErrors([
            'portal_role' => 'You cannot remove your own Super Admin role from this screen.',
        ]);
    }

    $user->portal_role = $nextRole;
    $user->portal_enabled = $nextEnabled;
    $user->portal_vendor_id = $nextRole === 'VENDOR'
        ? (trim((string) ($validated['portal_vendor_id'] ?? '')) ?: null)
        : null;
    $user->save();

    return back()->with('portal_notice', 'Portal user updated: ' . ($user->username ?: ('#' . $user->id)));
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
