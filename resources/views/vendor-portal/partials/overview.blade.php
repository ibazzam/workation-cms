        @php
            $vendorOperationalHealth = is_array($vendorOperationalHealth ?? null) ? $vendorOperationalHealth : [];
            $operationalSummary = is_array($vendorOperationalHealth['summary'] ?? null) ? $vendorOperationalHealth['summary'] : [];
            $operationalIssues = collect($vendorOperationalHealth['issues'] ?? []);
            $operationalStatus = strtolower(trim((string) ($vendorOperationalHealth['status'] ?? 'unavailable')));
            $vendorAuditTrail = is_array($vendorAuditTrail ?? null) ? $vendorAuditTrail : [];
            $auditRecentCount = (int) ($vendorAuditTrail['recent_count'] ?? 0);
            $auditTableReady = (bool) ($vendorAuditTrail['table_ready'] ?? false);
            $auditHighSeverityLogs = collect($vendorAuditTrail['high_severity_logs'] ?? []);
            $operationalStatusLabel = match ($operationalStatus) {
                'healthy' => 'HEALTHY',
                'degraded' => 'DEGRADED',
                'action_required' => 'ACTION REQUIRED',
                default => 'UNAVAILABLE',
            };
            $operationalStatusClass = match ($operationalStatus) {
                'healthy' => 'ok',
                'degraded' => 'warn',
                'action_required' => 'err',
                default => 'warn',
            };
            $topFailedAccounts = collect($vendorOperationalHealth['top_failed_accounts'] ?? []);
        @endphp

        <section id="vendorSummary" class="summary-grid summary-grid-compact" aria-label="Vendor dashboard summary" data-panel-group="overview">
            <article class="summary-card">
                <p class="summary-label">Listings</p>
                <p id="summaryBookings" class="summary-value">{{ $vendorListingCount }}</p>
                <p class="summary-meta">Active inventory</p>
            </article>

            <article class="summary-card">
                <p class="summary-label">Bookings</p>
                <p id="summarySettlements" class="summary-value">{{ $vendorReservations->count() }}</p>
                <p class="summary-meta">Received reservations</p>
            </article>

            <article class="summary-card">
                <p class="summary-label">Attention</p>
                <p class="summary-value"><span id="summaryConnectivity" class="status-pill {{ $vendorUnresolvedCareCount > 0 ? 'warn' : 'ok' }}">{{ $vendorUnresolvedCareCount > 0 ? 'OPEN' : 'CLEAR' }}</span></p>
                <p id="summaryLastSync" class="summary-meta">{{ $vendorUnresolvedCareCount }} cases, {{ $vendorPendingReviewResponses }} replies</p>
            </article>

            <article class="summary-card">
                <p class="summary-label">Avg Booking</p>
                <p id="summaryToken" class="summary-value">MVR {{ number_format($vendorAverageBookingValue, 2) }}</p>
                <p id="summaryTokenMeta" class="summary-meta">Per reservation</p>
            </article>
        </section>

        <section class="card overview-actions-card" aria-label="Operations control tower" data-panel-group="overview" style="margin-top:12px;">
            <div class="overview-actions-head">
                <p class="label">Operations Control Tower</p>
                <p class="small">High-level readiness signals for channel connectivity, event flow, and operational action items.</p>
            </div>

            <div class="summary-grid summary-grid-compact" style="margin-bottom:12px;">
                <article class="summary-card">
                    <p class="summary-label">Operational Status</p>
                    <p class="summary-value"><span class="status-pill {{ $operationalStatusClass }}">{{ $operationalStatusLabel }}</span></p>
                    <p class="summary-meta">Vendor channel health snapshot</p>
                </article>
                <article class="summary-card">
                    <p class="summary-label">Connected Accounts</p>
                    <p class="summary-value">{{ (int) ($operationalSummary['active_accounts'] ?? 0) }}</p>
                    <p class="summary-meta">of {{ (int) ($operationalSummary['accounts_total'] ?? 0) }} configured</p>
                </article>
                <article class="summary-card">
                    <p class="summary-label">Sync Queue</p>
                    <p class="summary-value">{{ (int) ($operationalSummary['outbound_queued'] ?? 0) }}</p>
                    <p class="summary-meta">retrying {{ (int) ($operationalSummary['outbound_retrying'] ?? 0) }}</p>
                </article>
                <article class="summary-card">
                    <p class="summary-label">Critical Exceptions</p>
                    <p class="summary-value">{{ (int) ($operationalSummary['dead_letter_events'] ?? 0) }}</p>
                    <p class="summary-meta">dead-letter events</p>
                </article>
                <article class="summary-card">
                    <p class="summary-label">Audit Activity</p>
                    <p class="summary-value">{{ $auditTableReady ? $auditRecentCount : 0 }}</p>
                    <p class="summary-meta">{{ $auditTableReady ? 'operator events in last 24h' : 'audit table not migrated yet' }}</p>
                </article>
            </div>

            @if ($operationalIssues->isNotEmpty())
                <div class="policy-box" style="margin:0 0 12px;border:1px solid #f0d080;border-radius:12px;background:#fff9ea;padding:10px 12px;">
                    <p class="small" style="margin:0 0 6px;"><strong>Immediate actions</strong></p>
                    @foreach ($operationalIssues->take(4) as $issue)
                        <p class="small" style="margin:0 0 4px;">{{ (string) $issue }}</p>
                    @endforeach
                </div>
            @endif

            @if ($topFailedAccounts->isNotEmpty())
                <div class="payout-table-wrap">
                    <table class="payout-table" aria-label="Top failed accounts table">
                        <thead>
                            <tr>
                                <th>Channel</th>
                                <th>Account</th>
                                <th>Failures</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topFailedAccounts->take(5) as $row)
                                <tr>
                                    <td>{{ strtoupper((string) ($row['channel_code'] ?? 'unknown')) }}</td>
                                    <td>{{ (string) ($row['account_reference'] ?? 'Not set') }}</td>
                                    <td>{{ (int) ($row['failure_count'] ?? 0) }}</td>
                                    <td>{{ strtoupper((string) ($row['status'] ?? 'unknown')) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="ops-header" style="margin-top:12px;">
                <p class="ops-title">Recent High-Severity Audit Events</p>
                <span class="ops-chip">last 5</span>
            </div>

            @if (!$auditTableReady)
                <p class="ops-empty">Audit table is not available yet. Run migrations to enable high-severity event visibility.</p>
            @elseif ($auditHighSeverityLogs->isEmpty())
                <p class="ops-empty">No high-severity audit events recorded recently.</p>
            @else
                <div class="payout-table-wrap">
                    <table class="payout-table" aria-label="Recent high-severity audit events table">
                        <thead>
                            <tr>
                                <th>When</th>
                                <th>Action</th>
                                <th>Severity</th>
                                <th>Target</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($auditHighSeverityLogs as $auditLog)
                                <tr>
                                    <td>{{ !empty($auditLog->created_at) ? \Illuminate\Support\Carbon::parse((string) $auditLog->created_at)->diffForHumans() : 'N/A' }}</td>
                                    <td>{{ trim((string) ($auditLog->action ?? 'event')) }}</td>
                                    <td>{{ strtoupper(trim((string) ($auditLog->severity ?? 'error'))) }}</td>
                                    <td>{{ trim((string) ($auditLog->target_identifier ?? 'Not set')) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="card overview-actions-card" aria-label="Quick actions" data-panel-group="overview">
            <div class="overview-actions-head">
                <p class="label">Quick Actions</p>
                <p class="small">Open the next task directly.</p>
            </div>
            <div class="overview-actions-grid">
                <a class="overview-action" href="/vendor/listings">
                    <span class="overview-action-title">Manage Listings</span>
                    <span class="overview-action-copy">Create, edit, or clean up inventory.</span>
                </a>
                <a class="overview-action" href="/vendor/reservations">
                    <span class="overview-action-title">Review Reservations</span>
                    <span class="overview-action-copy">Check bookings and follow-ups.</span>
                </a>
                <a class="overview-action" href="/vendor/billing">
                    <span class="overview-action-title">View Billing</span>
                    <span class="overview-action-copy">Track settlements and payouts.</span>
                </a>
                <a class="overview-action" href="/vendor/distribution">
                    <span class="overview-action-title">Open Channel Manager</span>
                    <span class="overview-action-copy">Monitor connectivity, retries, and OTA sync events.</span>
                </a>
                <a class="overview-action" href="/vendor/messages">
                    <span class="overview-action-title">Guest Messaging</span>
                    <span class="overview-action-copy">Handle pre-arrival questions and service recovery faster.</span>
                </a>
                <a class="overview-action" href="/vendor/reports">
                    <span class="overview-action-title">View Reports</span>
                    <span class="overview-action-copy">Review performance and operational readiness in one place.</span>
                </a>
                <a class="overview-action" href="/vendor/compliance">
                    <span class="overview-action-title">Compliance &amp; Operations</span>
                    <span class="overview-action-copy">Review command-center signals, active risks, and enterprise readiness controls.</span>
                </a>
                <a class="overview-action" href="/vendor/profile">
                    <span class="overview-action-title">Update Profile</span>
                    <span class="overview-action-copy">Keep business details current.</span>
                </a>
            </div>
        </section>