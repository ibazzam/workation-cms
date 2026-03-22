<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Users and Customers | Workation</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
    <style>
        :root {
            --bg: #eef3f7;
            --ink: #16212e;
            --muted: #5b6778;
            --card: #fffefb;
            --line: #d7e0e6;
            --hero-1: #1e3f5f;
            --hero-2: #1f6b85;
            --hero-3: #2ea39a;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background: radial-gradient(circle at 8% 10%, #d7ecff 0, #d7ecff00 32%), var(--bg);
        }

        .page {
            max-width: 1160px;
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

        .hero-link {
            color: #ecfbff;
            text-decoration: none;
            border: 1px solid #b8dfe4;
            border-radius: 9px;
            padding: 8px 10px;
            font-size: 0.82rem;
            background: rgba(11, 49, 75, 0.32);
        }

        .summary {
            margin-top: 14px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .summary-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px;
        }

        .label {
            margin: 0;
            font-size: 0.74rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        .value {
            margin: 6px 0 0;
            font-size: 1.35rem;
            font-weight: 700;
            color: #1f3346;
        }

        .meta {
            margin-top: 6px;
            font-size: 0.8rem;
            color: var(--muted);
        }

        .filters {
            margin-top: 14px;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px;
            align-items: center;
        }

        .filters input {
            border: 1px solid #c8d3df;
            border-radius: 8px;
            padding: 9px 10px;
            font-size: 0.9rem;
            font-family: "Outfit", "Trebuchet MS", sans-serif;
        }

        .filters button {
            border: 0;
            border-radius: 8px;
            background: #145f84;
            color: #fff;
            font-weight: 700;
            padding: 9px 12px;
            cursor: pointer;
        }

        .grid {
            margin-top: 14px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px;
        }

        .card h2 {
            margin: 0 0 8px;
            font-size: 1.02rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.84rem;
        }

        th,
        td {
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #edf2f8;
            color: #233247;
        }

        th {
            font-size: 0.72rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #48627b;
            font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .empty {
            border: 1px dashed #c8d3df;
            border-radius: 10px;
            padding: 12px;
            color: var(--muted);
            font-size: 0.84rem;
            background: #f9fcff;
        }

        @media (max-width: 900px) {
            .summary { grid-template-columns: 1fr 1fr; }
            .filters { grid-template-columns: 1fr; }
            table { display: block; overflow-x: auto; white-space: nowrap; }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="hero">
            <span class="eyebrow">Operations</span>
            <h1>Users and Customers Console</h1>
            <p>Unified layout for portal admin users, vendor users, and customer accounts with quick filtering and empty-state handling.</p>
            <div class="hero-links">
                <a class="hero-link" href="/admin">Back to Admin Portal</a>
                <a class="hero-link" href="/customer">Open Customer Portal</a>
                <a class="hero-link" href="/vendor">Open Vendor Portal</a>
            </div>
        </section>

        <section class="summary" aria-label="Users and customers summary">
            <article class="summary-card">
                <p class="label">Admin Users</p>
                <p class="value">{{ $summary['admin_users'] }}</p>
                <p class="meta">Portal operations roles</p>
            </article>
            <article class="summary-card">
                <p class="label">Vendor Users</p>
                <p class="value">{{ $summary['vendor_users'] }}</p>
                <p class="meta">Vendor-access accounts</p>
            </article>
            <article class="summary-card">
                <p class="label">Customers</p>
                <p class="value">{{ $summary['customers'] }}</p>
                <p class="meta">Customer records in customer store</p>
            </article>
            <article class="summary-card">
                <p class="label">Suspended Accounts</p>
                <p class="value">{{ $summary['suspended_users'] }}</p>
                <p class="meta">Disabled admin/vendor users</p>
            </article>
        </section>

        <form class="filters" method="GET" action="/users">
            <input type="search" name="q" value="{{ $query }}" placeholder="Search username, name, email, role...">
            <button type="submit">Apply Filter</button>
        </form>

        <section class="grid">
            <article class="card" id="adminUsersSection">
                <h2>Admin Users ({{ $adminUsers->count() }})</h2>
                @if ($adminUsers->isEmpty())
                    <div class="empty">No admin users found for the current filter.</div>
                @else
                    <table aria-label="Admin users table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($adminUsers as $user)
                                <tr>
                                    <td>{{ $user->username ?: 'no-username' }}</td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->portal_role }}</td>
                                    <td>{{ $user->portal_enabled ? 'ACTIVE' : 'SUSPENDED' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </article>

            <article class="card" id="vendorUsersSection">
                <h2>Vendor Users ({{ $vendorUsers->count() }})</h2>
                @if ($vendorUsers->isEmpty())
                    <div class="empty">No vendor users found for the current filter.</div>
                @else
                    <table aria-label="Vendor users table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Vendor ID</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($vendorUsers as $user)
                                <tr>
                                    <td>{{ $user->username ?: 'no-username' }}</td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->portal_vendor_id ?: 'N/A' }}</td>
                                    <td>{{ $user->portal_enabled ? 'ACTIVE' : 'SUSPENDED' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </article>

            <article class="card" id="customersSection">
                <h2>Customers ({{ $customers->count() }})</h2>
                @if ($customers->isEmpty())
                    <div class="empty">No customer records found for the current filter.</div>
                @else
                    <table aria-label="Customers table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($customers as $customer)
                                <tr>
                                    <td>{{ $customer->name ?? 'N/A' }}</td>
                                    <td>{{ $customer->email ?? 'N/A' }}</td>
                                    <td>{{ $customer->createdAt ?? ($customer->created_at ?? 'N/A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </article>
        </section>
    </main>
</body>
</html>
