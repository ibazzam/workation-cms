<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

    $canManageUsers = Gate::allows('manage-portal-users');
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

Route::get('/portal/admin/forgot-password', function () {
    return view('portal-forgot-password');
});

Route::post('/portal/admin/forgot-password', function (Request $request) {
    $validated = $request->validate([
        'email' => ['required', 'email'],
    ]);

    $email = strtolower(trim((string) $validated['email']));
    $adminConfig = portalConfig('admin');
    $portalUser = User::query()
        ->whereRaw('LOWER(email) = ?', [$email])
        ->where('portal_enabled', true)
        ->whereIn('portal_role', $adminConfig['allowed_roles'])
        ->first();

    if ($portalUser) {
        $token = Password::broker()->createToken($portalUser);
        $portalUser->sendPasswordResetNotification($token);
    }

    return back()->with('status', 'If the email is registered for an admin account, a reset link has been sent.');
})->name('password.email');

Route::get('/portal/admin/reset-password/{token}', function (Request $request, string $token) {
    return view('portal-reset-password', [
        'token' => $token,
        'email' => (string) $request->query('email', ''),
    ]);
})->name('password.reset');

Route::post('/portal/admin/reset-password', function (Request $request) {
    $validated = $request->validate([
        'token' => ['required', 'string'],
        'email' => ['required', 'email'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    $email = strtolower(trim((string) $validated['email']));
    $adminConfig = portalConfig('admin');
    $portalUser = User::query()
        ->whereRaw('LOWER(email) = ?', [$email])
        ->where('portal_enabled', true)
        ->whereIn('portal_role', $adminConfig['allowed_roles'])
        ->first();

    if (!$portalUser) {
        return back()->withErrors([
            'email' => 'Unable to reset password for this account.',
        ])->withInput($request->only('email'));
    }

    $status = Password::broker()->reset(
        [
            'email' => $email,
            'password' => (string) $validated['password'],
            'password_confirmation' => (string) $validated['password_confirmation'],
            'token' => (string) $validated['token'],
        ],
        function (User $user, string $password) {
            $updates = [
                'password' => $password,
            ];

            // Some production databases may not include remember_token on legacy users schemas.
            if (Schema::hasColumn('users', 'remember_token')) {
                $updates['remember_token'] = Str::random(60);
            }

            $user->forceFill($updates)->save();
        }
    );

    if ($status === Password::PASSWORD_RESET) {
        return redirect('/portal/admin/login')->with('status', __($status));
    }

    return back()->withErrors([
        'email' => __($status),
    ])->withInput($request->only('email'));
})->name('password.update');

Route::post('/portal/{portal}/login', function (Request $request, string $portal) {
    if (!in_array($portal, ['admin', 'vendor'], true)) {
        abort(404);
    }

    $validated = $request->validate([
        'username' => ['required', 'string'],
        'password' => ['required', 'string'],
    ]);

    $config = portalConfig($portal);
    $username = trim((string) $validated['username']);
    $password = (string) $validated['password'];
    $usernameLower = strtolower($username);

    $portalUser = null;
    if (Schema::hasColumns('users', ['username', 'portal_enabled', 'portal_role'])) {
        $portalUser = User::query()
            ->where(function ($query) use ($usernameLower) {
                $query->whereRaw('LOWER(username) = ?', [$usernameLower])
                    ->orWhereRaw('LOWER(email) = ?', [$usernameLower]);
            })
            ->where('portal_enabled', true)
            ->whereIn('portal_role', $config['allowed_roles'])
            ->first();
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
        return back()->withErrors([
            'username' => 'Invalid username or password.',
        ])->withInput();
    }

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
    if (!Gate::allows('manage-portal-users')) {
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

    $vendorId = trim((string) ($validated['portal_vendor_id'] ?? ''));

    $user->portal_role = $nextRole;
    $user->portal_enabled = $nextEnabled;
    $user->portal_vendor_id = ($nextRole === 'VENDOR' && $vendorId !== '') ? $vendorId : null;
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
