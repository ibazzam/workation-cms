<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root {
            --bg: #edf4f2;
            --ink: #16212e;
            --muted: #5b6778;
            --card: #fffefb;
            --line: #d7e0e6;
            --hero-1: #183d64;
            --hero-2: #116b86;
            --hero-3: #1a9a7f;
            --ok: #0b5c2a;
            --ok-bg: #d8f7e2;
            --warn: #7a4606;
            --warn-bg: #ffeccd;
            --err: #6d1111;
            --err-bg: #ffe0de;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 8% 10%, #d4ebff 0, #d4ebff00 32%),
                radial-gradient(circle at 90% 10%, #dff5e8 0, #dff5e800 35%),
                var(--bg);
        }

        .page {
            max-width: 1120px;
            margin: 0 auto;
            padding: 24px 18px 34px;
        }

        .hero {
            background: linear-gradient(130deg, var(--hero-1) 0%, var(--hero-2) 48%, var(--hero-3) 100%);
            border-radius: 18px;
            color: #fff;
            padding: 24px;
            box-shadow: 0 22px 44px rgba(18, 38, 58, 0.2);
        }

        .eyebrow {
            display: inline-block;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            font-size: 0.75rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #d7f2f5;
            margin-bottom: 10px;
        }

        .hero h1 {
            margin: 0 0 8px;
            font-size: clamp(1.45rem, 2.8vw, 2.3rem);
            line-height: 1.15;
        }

        .hero p {
            margin: 0;
            color: #dcf4f3;
            max-width: 780px;
        }

        .hero-links {
            margin-top: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .auth-bar {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .auth-user {
            font-size: 0.82rem;
            border: 1px solid #b8dfe4;
            border-radius: 999px;
            padding: 6px 10px;
            background: rgba(11, 49, 75, 0.32);
            color: #dff4fb;
        }

        .logout {
            border: 1px solid #b8dfe4;
            border-radius: 9px;
            padding: 7px 10px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #f0fbff;
            background: rgba(11, 49, 75, 0.45);
            cursor: pointer;
        }

        .hero-link {
            color: #ecfbff;
            text-decoration: none;
            border: 1px solid #b8dfe4;
            border-radius: 9px;
            padding: 8px 10px;
            font-size: 0.82rem;
            background: rgba(11, 49, 75, 0.32);
        }

        .layout {
            margin-top: 14px;
            display: grid;
            grid-template-columns: 1.2fr 1.8fr;
            gap: 12px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 14px;
        }

        .label {
            margin: 0 0 8px;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            font-size: 0.75rem;
            letter-spacing: 0.11em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .token-input {
            width: 100%;
            border: 1px solid #c8d3df;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 0.95rem;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
        }

        .btn {
            margin-top: 10px;
            border: 0;
            border-radius: 10px;
            padding: 9px 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-primary {
            background: #0f5f8d;
            color: #fff;
        }

        .btn-secondary {
            background: #edf2f8;
            color: #183452;
            margin-left: 8px;
        }

        .endpoint {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px;
            align-items: center;
            border: 1px solid #d7dee6;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 8px;
            background: #fff;
        }

        .endpoint code {
            font-size: 0.83rem;
            color: #233247;
            word-break: break-all;
        }

        .endpoint button {
            border: 0;
            border-radius: 8px;
            background: #0e6b81;
            color: #fff;
            font-weight: 700;
            padding: 7px 10px;
            cursor: pointer;
        }

        .endpoint button[disabled] {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .endpoint button.is-loading::after {
            content: " ...";
        }

        .state {
            margin-top: 12px;
            display: inline-block;
            border-radius: 999px;
            padding: 4px 8px;
            font-weight: 700;
            font-size: 0.78rem;
        }

        .state.ok { color: var(--ok); background: var(--ok-bg); }
        .state.warn { color: var(--warn); background: var(--warn-bg); }
        .state.err { color: var(--err); background: var(--err-bg); }

        pre {
            margin: 10px 0 0;
            border-radius: 10px;
            border: 1px solid #d8e1ea;
            background: #f8fbff;
            padding: 12px;
            max-height: 360px;
            overflow: auto;
            font-size: 0.82rem;
            line-height: 1.4;
        }

        .manage {
            margin-top: 14px;
        }

        .notice {
            margin-top: 12px;
            border-radius: 10px;
            border: 1px solid #b7e2c3;
            background: #eaf9ef;
            color: #135028;
            padding: 10px 12px;
            font-size: 0.88rem;
        }

        .error-box {
            margin-top: 12px;
            border-radius: 10px;
            border: 1px solid #f0b7b3;
            background: #fff0ef;
            color: #731e1a;
            padding: 10px 12px;
            font-size: 0.88rem;
        }

        .role-pill {
            display: inline-block;
            border-radius: 999px;
            padding: 4px 8px;
            font-size: 0.72rem;
            font-weight: 700;
            border: 1px solid #c8d4df;
            background: #f2f7fb;
            color: #1b3856;
        }

        .user-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 18px;
        }

        .group-title {
            margin: 14px 0 8px;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
            font-size: 0.8rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .group-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 10px;
        }

        .group-search {
            flex: 1 1 280px;
            border: 1px solid #c8d3df;
            border-radius: 8px;
            padding: 8px 9px;
            font-size: 0.88rem;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            background: #fff;
        }

        .group-select-wrap {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.82rem;
            color: var(--muted);
        }

        .bulk-delete-btn {
            border: 0;
            border-radius: 8px;
            background: #8a1f1f;
            color: #fff;
            padding: 8px 10px;
            font-weight: 700;
            cursor: pointer;
        }

        .bulk-delete-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .user-select {
            width: 16px;
            height: 16px;
            margin: 0 2px 0 0;
        }

        .user-row {
            /* Remove box-shadow and margin-bottom to prevent nesting */
            border: 1px solid #d7dee6;
            border-radius: 10px;
            padding: 14px 12px;
            background: #fff;
            margin-bottom: 0;
            box-shadow: none;
            overflow: hidden;
            transition: border-color 0.2s;
        }
        .user-row:not(:last-child) {
            margin-bottom: 0;
        }

        .user-head {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 8px;
        }

        .user-name {
            font-weight: 700;
        }

        .small {
            color: var(--muted);
            font-size: 0.82rem;
        }

        .manage-form {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            align-items: end;
        }

        .manage-form label {
            font-size: 0.75rem;
            color: var(--muted);
            display: block;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .manage-form input,
        .manage-form select {
            width: 100%;
            border: 1px solid #c8d3df;
            border-radius: 8px;
            padding: 8px 9px;
            font-size: 0.88rem;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            background: #fff;
        }

        .manage-form button {
            border: 0;
            border-radius: 8px;
            background: #155f83;
            color: #fff;
            padding: 8px 10px;
            font-weight: 700;
            cursor: pointer;
        }

        @media (max-width: 980px) {
            .manage-form {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 900px) {
            .layout {
                grid-template-columns: 1fr;
            }
        }
        .prominent {
            font-size: 1.05rem;
            font-weight: 700;
            box-shadow: 0 2px 12px rgba(220, 38, 38, 0.08);
            border-width: 2px;
            animation: fade-in 0.4s;
        }
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <main class="page" data-api-base="{{ $apiBase }}">
        <section class="hero">
            <span class="eyebrow">Internal Access</span>
            <h1>Admin Portal</h1>
            <p>Use a valid admin bearer token to test operational APIs from this page. Token is stored in browser session storage only.</p>
            <div class="hero-links">
                <a class="hero-link" href="/">Back to Home</a>
                <a class="hero-link" href="/vendor">Go to Vendor Portal</a>
                <a class="hero-link" href="{{ $apiBase }}/api/v1/ops/metrics" target="_blank" rel="noopener">Open Public Metrics</a>
            </div>
            <div class="auth-bar">
                <span class="auth-user">Signed in as {{ $portalUser }}</span>
                <span class="role-pill">Role: {{ $portalRole }}</span>
                <form method="POST" action="/portal/admin/logout">
                    @csrf
                    <button class="logout" type="submit">Log Out</button>
                </form>
            </div>
        </section>

        @if (session('portal_notice'))
            <div class="notice prominent" id="successBox">{{ session('portal_notice') }}</div>
        @endif
        <section class="card">
            <p class="label">Session Debug</p>
            <pre style="background:#f7f7f7;border-radius:8px;padding:8px;font-size:0.9rem;">portal_admin_authenticated: {{ session('portal_admin_authenticated') ? 'true' : 'false' }}
        portal_admin_role: {{ session('portal_admin_role') ?? 'null' }}
        portal_admin_user: {{ session('portal_admin_user') ?? 'null' }}
        portal_admin_user_id: {{ session('portal_admin_user_id') ?? 'null' }}

        canManageUsers: {{ var_export($canManageUsers, true) }}
        </pre>
        </section>
        @if ($errors->any())
            <div class="error-box prominent" id="errorBox">{{ $errors->first() }}</div>
        @endif

        <section class="layout">
            <article class="card">
                <p class="label">Auth</p>
                <input id="tokenInput" class="token-input" type="password" placeholder="Paste admin JWT bearer token">
                <div>
                    <button id="saveToken" class="btn btn-primary" type="button">Save Token</button>
                    <button id="clearToken" class="btn btn-secondary" type="button">Clear</button>
                </div>
                <div id="tokenState" class="state warn">TOKEN NOT SET</div>
            </article>

            <article class="card">
                <p class="label">Admin API Actions</p>
                <div class="endpoint">
                    <code>GET /api/v1/auth/admin/ping</code>
                    <button type="button" data-path="/api/v1/auth/admin/ping">Run</button>
                </div>
                <div class="endpoint">
                    <code>GET /api/v1/ops/alerts</code>
                    <button type="button" data-path="/api/v1/ops/alerts">Run</button>
                </div>
                <div class="endpoint">
                    <code>GET /api/v1/ops/runbooks</code>
                    <button type="button" data-path="/api/v1/ops/runbooks">Run</button>
                </div>
                <div class="endpoint">
                    <code>GET /api/v1/payments/admin/jobs/health</code>
                    <button type="button" data-path="/api/v1/payments/admin/jobs/health">Run</button>
                </div>
                <div class="endpoint">
                    <code>GET /api/v1/payments/admin/reconcile/status</code>
                    <button type="button" data-path="/api/v1/payments/admin/reconcile/status">Run</button>
                </div>
                <pre id="output">Ready. Save token, then run an endpoint.</pre>
            </article>
        </section>

        <button id="toggleModerationBtn" class="btn btn-primary" type="button" style="margin-bottom:18px;">Show Moderation Panel</button>
        <section class="card manage" id="moderationPanel" style="display:none;">
            <p class="label">Portal User Moderation</p>
            @if (!$canManageUsers)
                <p class="small">Super Admin role required to modify users, roles, and suspension status.</p>
            @else
                <p class="small">Change role permissions and suspend/reactivate accounts directly in the application.</p>
                <!-- User Creation Form (AJAX, with role/status) -->
                <form class="manage-form" id="userCreateForm" autocomplete="off" style="margin-bottom:18px;background:#f7f7f7;padding:14px;border-radius:10px;">
                    @csrf
                    <div style="margin-bottom:8px;">
                        <label>Name</label>
                        <input name="name" required placeholder="Full Name">
                    </div>
                    <div style="margin-bottom:8px;">
                        <label>Email</label>
                        <input name="email" type="email" required placeholder="Email">
                    </div>
                    <div style="margin-bottom:8px;">
                        <label>Role</label>
                        <select name="portal_role" required>
                            <option value="ADMIN">ADMIN</option>
                            <option value="ADMIN_SUPER">ADMIN_SUPER</option>
                            <option value="ADMIN_CARE">ADMIN_CARE</option>
                            <option value="ADMIN_FINANCE">ADMIN_FINANCE</option>
                            <option value="VENDOR">VENDOR</option>
                        </select>
                    </div>
                    <div style="margin-bottom:8px;">
                        <label>Status</label>
                        <select name="portal_enabled" required>
                            <option value="1">ACTIVE</option>
                            <option value="0">SUSPENDED</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit">Create User</button>
                    </div>
                </form>
                <div id="userCreateNotice" style="display:none;margin-bottom:12px;"></div>
                <!-- Existing Users Moderation -->
                <p class="group-title">Admin Users ({{ $adminPortalUsers->count() }})</p>
                <div class="group-toolbar" data-group="admin">
                    <input class="group-search" type="search" id="adminUserSearch" placeholder="Search admin users by username, name, email, or role" data-target-list="adminUserList">
                    <label class="group-select-wrap" for="adminSelectAll">
                        <input class="group-select-all" type="checkbox" id="adminSelectAll" data-group="admin">
                        Select all visible
                    </label>
                    <form method="POST" action="/portal/admin/users/bulk-delete" class="bulk-delete-form" data-group="admin">
                        @csrf
                        <div class="bulk-user-ids" data-group="admin"></div>
                        <button class="bulk-delete-btn" type="submit" data-group="admin" disabled>Delete Selected (0)</button>
                    </form>
                </div>
                <div class="user-list" id="adminUserList">
                    @forelse ($adminPortalUsers as $managedUser)
                        <div class="user-row" data-user-id="{{ $managedUser->id }}">
                            <div class="user-head">
                                <input class="user-select" type="checkbox" data-group="admin" value="{{ $managedUser->id }}" aria-label="Select user {{ $managedUser->username ?: $managedUser->email }}">
                                <span class="user-name">{{ $managedUser->username ?: 'no-username' }}</span>
                                <span class="role-pill">{{ $managedUser->portal_role ?: 'NONE' }}</span>
                                <span class="small">{{ $managedUser->name }} | {{ $managedUser->email }}</span>
                                @if (!$managedUser->portal_enabled)
                                    <span class="state err">SUSPENDED</span>
                                @else
                                    <span class="state ok">ACTIVE</span>
                                @endif
                                <span style="flex:1 1 auto;"></span>
                                <button type="button" class="btn btn-secondary edit-user-btn" data-user-id="{{ $managedUser->id }}" style="margin-left:auto;">Edit</button>
                                <form method="POST" action="/portal/admin/users/{{ $managedUser->id }}/delete" style="display:inline; margin-left:8px;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary delete-user-btn" onclick="return confirm('Are you sure you want to delete this user?');">Delete</button>
                                </form>
                            </div>
                        </div>
                        <!-- Only one edit-user-form per user, outside user-row, to avoid merge conflicts and ensure flat structure -->
                        <div class="edit-user-form" id="edit-user-form-{{ $managedUser->id }}" style="display:none;">
                            <form class="manage-form" method="POST" action="/portal/admin/users/{{ $managedUser->id }}/manage">
                                @csrf
                                <div>
                                    <label>Role</label>
                                    <select name="portal_role">
                                        <option value="ADMIN" @selected($managedUser->portal_role === 'ADMIN')>ADMIN</option>
                                        <option value="ADMIN_SUPER" @selected($managedUser->portal_role === 'ADMIN_SUPER')>ADMIN_SUPER</option>
                                        <option value="ADMIN_CARE" @selected($managedUser->portal_role === 'ADMIN_CARE')>ADMIN_CARE</option>
                                        <option value="ADMIN_FINANCE" @selected(in_array($managedUser->portal_role, ['ADMIN_FINANCE', 'ADMIN_FINACE'], true))>ADMIN_FINANCE</option>
                                        <option value="VENDOR" @selected($managedUser->portal_role === 'VENDOR')>VENDOR</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Status</label>
                                    <select name="portal_enabled">
                                        <option value="1" @selected($managedUser->portal_enabled)>ACTIVE</option>
                                        <option value="0" @selected(!$managedUser->portal_enabled)>SUSPENDED</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Vendor ID</label>
                                    <input name="portal_vendor_id" value="{{ $managedUser->portal_vendor_id ?? '' }}" placeholder="Required for VENDOR">
                                </div>
                                <div>
                                    <button type="submit">Save</button>
                                    <button type="button" class="btn btn-secondary cancel-edit-btn" data-user-id="{{ $managedUser->id }}">Cancel</button>
                                </div>
                            </form>
                        </div>
                    @empty
                        <div class="user-row">
                            <div class="small">No admin users found.</div>
                        </div>
                    @endforelse
                </div>

                <p class="group-title">Vendor Users ({{ $vendorPortalUsers->count() }})</p>
                <div class="group-toolbar" data-group="vendor">
                    <input class="group-search" type="search" id="vendorUserSearch" placeholder="Search vendor users by username, name, email, or role" data-target-list="vendorUserList">
                    <label class="group-select-wrap" for="vendorSelectAll">
                        <input class="group-select-all" type="checkbox" id="vendorSelectAll" data-group="vendor">
                        Select all visible
                    </label>
                    <form method="POST" action="/portal/admin/users/bulk-delete" class="bulk-delete-form" data-group="vendor">
                        @csrf
                        <div class="bulk-user-ids" data-group="vendor"></div>
                        <button class="bulk-delete-btn" type="submit" data-group="vendor" disabled>Delete Selected (0)</button>
                    </form>
                </div>
                <div class="user-list" id="vendorUserList">
                    @forelse ($vendorPortalUsers as $managedUser)
                        <div class="user-row" data-user-id="{{ $managedUser->id }}">
                            <div class="user-head">
                                <input class="user-select" type="checkbox" data-group="vendor" value="{{ $managedUser->id }}" aria-label="Select user {{ $managedUser->username ?: $managedUser->email }}">
                                <span class="user-name">{{ $managedUser->username ?: 'no-username' }}</span>
                                <span class="role-pill">{{ $managedUser->portal_role ?: 'NONE' }}</span>
                                <span class="small">{{ $managedUser->name }} | {{ $managedUser->email }}</span>
                                @if (!$managedUser->portal_enabled)
                                    <span class="state err">SUSPENDED</span>
                                @else
                                    <span class="state ok">ACTIVE</span>
                                @endif
                                <span style="flex:1 1 auto;"></span>
                                <button type="button" class="btn btn-secondary edit-user-btn" data-user-id="{{ $managedUser->id }}" style="margin-left:auto;">Edit</button>
                                <form method="POST" action="/portal/admin/users/{{ $managedUser->id }}/delete" style="display:inline; margin-left:8px;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary delete-user-btn" onclick="return confirm('Are you sure you want to delete this user?');">Delete</button>
                                </form>
                            </div>
                        </div>
                        <div class="edit-user-form" id="edit-user-form-{{ $managedUser->id }}" style="display:none;">
                            <form class="manage-form" method="POST" action="/portal/admin/users/{{ $managedUser->id }}/manage">
                                @csrf
                                <div>
                                    <label>Role</label>
                                    <select name="portal_role">
                                        <option value="ADMIN" @selected($managedUser->portal_role === 'ADMIN')>ADMIN</option>
                                        <option value="ADMIN_SUPER" @selected($managedUser->portal_role === 'ADMIN_SUPER')>ADMIN_SUPER</option>
                                        <option value="ADMIN_CARE" @selected($managedUser->portal_role === 'ADMIN_CARE')>ADMIN_CARE</option>
                                        <option value="ADMIN_FINANCE" @selected(in_array($managedUser->portal_role, ['ADMIN_FINANCE', 'ADMIN_FINACE'], true))>ADMIN_FINANCE</option>
                                        <option value="VENDOR" @selected($managedUser->portal_role === 'VENDOR')>VENDOR</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Status</label>
                                    <select name="portal_enabled">
                                        <option value="1" @selected($managedUser->portal_enabled)>ACTIVE</option>
                                        <option value="0" @selected(!$managedUser->portal_enabled)>SUSPENDED</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Vendor ID</label>
                                    <input name="portal_vendor_id" value="{{ $managedUser->portal_vendor_id ?? '' }}" placeholder="Required for VENDOR">
                                </div>
                                <div>
                                    <button type="submit">Save</button>
                                    <button type="button" class="btn btn-secondary cancel-edit-btn" data-user-id="{{ $managedUser->id }}">Cancel</button>
                                </div>
                            </form>
                        </div>
                    @empty
                        <div class="user-row">
                            <div class="small">No vendor users found.</div>
                        </div>
                    @endforelse
                </div>
            @endif
        </section>
    </main>

    <script>
    // User creation AJAX logic (with role/status)
    document.addEventListener('DOMContentLoaded', function () {
        var userCreateForm = document.getElementById('userCreateForm');
        var userCreateNotice = document.getElementById('userCreateNotice');
        if (userCreateForm) {
            userCreateForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                userCreateNotice.style.display = 'none';
                userCreateNotice.textContent = '';
                const name = userCreateForm.elements['name'].value.trim();
                const email = userCreateForm.elements['email'].value.trim();
                const portal_role = userCreateForm.elements['portal_role'].value;
                const portal_enabled = userCreateForm.elements['portal_enabled'].value;
                if (!name || !email || !portal_role || !portal_enabled) {
                    userCreateNotice.style.display = 'block';
                    userCreateNotice.style.color = '#b91c1c';
                    userCreateNotice.textContent = 'All fields are required.';
                    return;
                }
                try {
                    const csrfToken = userCreateForm.querySelector('input[name="_token"]')?.value || '';
                    const res = await fetch('/portal/admin/users/create', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ name, email, portal_role, portal_enabled })
                    });
                    if (res.ok) {
                        const data = await res.json().catch(() => ({}));
                        userCreateNotice.style.display = 'block';
                        userCreateNotice.style.color = '#166534';
                        userCreateNotice.textContent = data.message || 'User created successfully.';
                        userCreateForm.reset();
                    } else {
                        const data = await res.json().catch(() => ({}));
                        const errors = data.errors ? Object.values(data.errors).flat() : [];
                        userCreateNotice.style.display = 'block';
                        userCreateNotice.style.color = '#b91c1c';
                        userCreateNotice.textContent = errors[0] || data.message || 'Failed to create user.';
                    }
                } catch (err) {
                    userCreateNotice.style.display = 'block';
                    userCreateNotice.style.color = '#b91c1c';
                    userCreateNotice.textContent = 'Network error.';
                }
            });
        }
    });
                // User list edit/cancel logic
                document.addEventListener('DOMContentLoaded', function () {
                    var editButtons = document.querySelectorAll('.edit-user-btn');
                    var cancelButtons = document.querySelectorAll('.cancel-edit-btn');
                    var forms = document.querySelectorAll('.edit-user-form');
                    editButtons.forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var userId = btn.getAttribute('data-user-id');
                            // Hide all forms first
                            forms.forEach(function(f) { f.style.display = 'none'; });
                            // Show only this user's form
                            var form = document.getElementById('edit-user-form-' + userId);
                            if (form) form.style.display = 'block';
                        });
                    });
                    cancelButtons.forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var userId = btn.getAttribute('data-user-id');
                            var form = document.getElementById('edit-user-form-' + userId);
                            if (form) form.style.display = 'none';
                        });
                    });
                });
        // Toggle moderation panel on button click
        document.addEventListener('DOMContentLoaded', function () {
            var btn = document.getElementById('toggleModerationBtn');
            var panel = document.getElementById('moderationPanel');
            if (btn && panel) {
                btn.addEventListener('click', function () {
                    if (panel.style.display === 'none') {
                        panel.style.display = 'block';
                        btn.textContent = 'Hide Moderation Panel';
                    } else {
                        panel.style.display = 'none';
                        btn.textContent = 'Show Moderation Panel';
                    }
                });
            }
        });
                // Ensure feedback messages are always visible and scroll into view
                window.addEventListener('DOMContentLoaded', function () {
                    var successBox = document.getElementById('successBox');
                    var errorBox = document.getElementById('errorBox');
                    if (successBox) {
                        successBox.style.display = 'block';
                        successBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    if (errorBox) {
                        errorBox.style.display = 'block';
                        errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });

                // Client-side validation for moderation form
                document.querySelectorAll('.manage-form').forEach(function(form) {
                    form.addEventListener('submit', function(e) {
                        var role = form.querySelector('[name="portal_role"]') ? form.querySelector('[name="portal_role"]').value : null;
                        var enabled = form.querySelector('[name="portal_enabled"]') ? form.querySelector('[name="portal_enabled"]').value : null;
                        var vendorId = form.querySelector('[name="portal_vendor_id"]') ? form.querySelector('[name="portal_vendor_id"]').value : null;
                        if (!role || !enabled) {
                            e.preventDefault();
                            alert('Role and status are required.');
                            return false;
                        }
                        if (role === 'VENDOR' && !vendorId.trim()) {
                            e.preventDefault();
                            alert('Vendor ID is required for VENDOR role.');
                            return false;
                        }
                    });
                });

                // Search + bulk selection/delete controls for grouped user lists.
                document.addEventListener('DOMContentLoaded', function () {
                    var groups = ['admin', 'vendor'];

                    function getVisibleCheckboxes(group) {
                        return Array.from(document.querySelectorAll('.user-select[data-group="' + group + '"]')).filter(function (checkbox) {
                            var row = checkbox.closest('.user-row');
                            return row && row.style.display !== 'none';
                        });
                    }

                    function updateBulkState(group) {
                        var selected = getVisibleCheckboxes(group).filter(function (checkbox) {
                            return checkbox.checked;
                        });
                        var button = document.querySelector('.bulk-delete-btn[data-group="' + group + '"]');
                        var selectAll = document.querySelector('.group-select-all[data-group="' + group + '"]');
                        var visible = getVisibleCheckboxes(group);
                        if (button) {
                            button.disabled = selected.length === 0;
                            button.textContent = 'Delete Selected (' + selected.length + ')';
                        }
                        if (selectAll) {
                            selectAll.checked = visible.length > 0 && selected.length === visible.length;
                            selectAll.indeterminate = selected.length > 0 && selected.length < visible.length;
                        }
                    }

                    function renderBulkUserIds(group) {
                        var holder = document.querySelector('.bulk-user-ids[data-group="' + group + '"]');
                        if (!holder) return;
                        var selectedIds = getVisibleCheckboxes(group)
                            .filter(function (checkbox) { return checkbox.checked; })
                            .map(function (checkbox) { return checkbox.value; });
                        holder.innerHTML = selectedIds.map(function (id) {
                            return '<input type="hidden" name="user_ids[]" value="' + id + '">';
                        }).join('');
                    }

                    function applyFilter(group, term) {
                        var q = (term || '').toLowerCase();
                        var rows = document.querySelectorAll('#' + group + 'UserList .user-row[data-user-id]');
                        rows.forEach(function (row) {
                            var text = (row.textContent || '').toLowerCase();
                            var visible = !q || text.indexOf(q) !== -1;
                            row.style.display = visible ? '' : 'none';
                            if (!visible) {
                                var checkbox = row.querySelector('.user-select[data-group="' + group + '"]');
                                if (checkbox) checkbox.checked = false;
                            }
                        });
                        renderBulkUserIds(group);
                        updateBulkState(group);
                    }

                    groups.forEach(function (group) {
                        var searchInput = document.querySelector('.group-search[data-target-list="' + group + 'UserList"]');
                        var selectAll = document.querySelector('.group-select-all[data-group="' + group + '"]');
                        var form = document.querySelector('.bulk-delete-form[data-group="' + group + '"]');

                        if (searchInput) {
                            searchInput.addEventListener('input', function () {
                                applyFilter(group, searchInput.value);
                            });
                        }

                        document.querySelectorAll('.user-select[data-group="' + group + '"]').forEach(function (checkbox) {
                            checkbox.addEventListener('change', function () {
                                renderBulkUserIds(group);
                                updateBulkState(group);
                            });
                        });

                        if (selectAll) {
                            selectAll.addEventListener('change', function () {
                                var shouldCheck = !!selectAll.checked;
                                getVisibleCheckboxes(group).forEach(function (checkbox) {
                                    checkbox.checked = shouldCheck;
                                });
                                renderBulkUserIds(group);
                                updateBulkState(group);
                            });
                        }

                        if (form) {
                            form.addEventListener('submit', function (e) {
                                renderBulkUserIds(group);
                                var selectedCount = getVisibleCheckboxes(group).filter(function (checkbox) {
                                    return checkbox.checked;
                                }).length;
                                if (selectedCount === 0) {
                                    e.preventDefault();
                                    return;
                                }
                                if (!window.confirm('Are you sure you want to delete ' + selectedCount + ' selected user(s)?')) {
                                    e.preventDefault();
                                }
                            });
                        }

                        updateBulkState(group);
                    });
                });

        (function () {
            const root = document.querySelector(".page");
            const apiBase = root ? root.getAttribute("data-api-base") : "";
            const tokenInput = document.getElementById("tokenInput");
            const tokenState = document.getElementById("tokenState");
            const output = document.getElementById("output");

            const SESSION_KEY = "workation_admin_token";

            function setState(type, text) {
                tokenState.className = "state " + type;
                tokenState.textContent = text;
            }

            function getToken() {
                return sessionStorage.getItem(SESSION_KEY) || "";
            }

            function saveToken() {
                const value = (tokenInput.value || "").trim();
                if (!value) {
                    setState("warn", "TOKEN NOT SET");
                    return;
                }
                sessionStorage.setItem(SESSION_KEY, value);
                tokenInput.value = "";
                setState("ok", "TOKEN SAVED");
            }

            function clearToken() {
                sessionStorage.removeItem(SESSION_KEY);
                tokenInput.value = "";
                setState("warn", "TOKEN CLEARED");
            }

            async function run(path, triggerButton) {
                const token = getToken();
                if (!token) {
                    setState("warn", "TOKEN REQUIRED");
                    output.textContent = "Save an admin token first.";
                    return;
                }

                const button = triggerButton || null;
                if (button) {
                    button.disabled = true;
                    button.classList.add("is-loading");
                    if (!button.dataset.label) {
                        button.dataset.label = button.textContent || "Run";
                    }
                    button.textContent = "Running";
                }

                output.textContent = "Loading " + path + " ...";
                try {
                    const res = await fetch(apiBase + path, {
                        method: "GET",
                        headers: {
                            "Authorization": "Bearer " + token,
                            "Accept": "application/json"
                        },
                        cache: "no-store"
                    });
                    const text = await res.text();
                    let parsed = text;
                    try {
                        parsed = JSON.stringify(JSON.parse(text), null, 2);
                    } catch (error) {
                        // Keep plain text if response is not JSON.
                    }
                    output.textContent = "Status: " + res.status + "\n\n" + parsed;
                    if (res.ok) {
                        setState("ok", "TOKEN VALID");
                    } else if (res.status === 401 || res.status === 403) {
                        setState("err", "TOKEN INVALID FOR ADMIN");
                    } else {
                        setState("warn", "REQUEST COMPLETED WITH WARNINGS");
                    }
                } catch (error) {
                    setState("err", "REQUEST FAILED");
                    output.textContent = "Network/CORS error. Ensure API allows origin https://www.workation.mv\n\n" + String(error);
                } finally {
                    if (button) {
                        button.disabled = false;
                        button.classList.remove("is-loading");
                        button.textContent = button.dataset.label || "Run";
                    }
                }
            }

            document.getElementById("saveToken").addEventListener("click", saveToken);
            document.getElementById("clearToken").addEventListener("click", clearToken);
            document.querySelectorAll("button[data-path]").forEach((button) => {
                button.addEventListener("click", function () {
                    run(button.getAttribute("data-path"), button);
                });
            });

            if (getToken()) {
                setState("ok", "TOKEN READY");
            }
        })();
    </script>
</body>
</html>

