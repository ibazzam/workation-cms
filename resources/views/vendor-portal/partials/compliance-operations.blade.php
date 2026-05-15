@php
    $vendorOperationalHealth = is_array($vendorOperationalHealth ?? null) ? $vendorOperationalHealth : [];
    $operationalSummary = is_array($vendorOperationalHealth['summary'] ?? null) ? $vendorOperationalHealth['summary'] : [];
    $operationalIssues = collect($vendorOperationalHealth['issues'] ?? []);
    $topFailedAccounts = collect($vendorOperationalHealth['top_failed_accounts'] ?? []);
    $vendorAuditTrail = is_array($vendorAuditTrail ?? null) ? $vendorAuditTrail : [];
    $auditLogs = collect($vendorAuditTrail['logs'] ?? []);
    $auditRecentCount = (int) ($vendorAuditTrail['recent_count'] ?? 0);
    $auditWarnCount = (int) ($vendorAuditTrail['warn_severity_count'] ?? 0);
    $auditHighSeverityCount = (int) ($vendorAuditTrail['high_severity_count'] ?? 0);
    $auditTableReady = (bool) ($vendorAuditTrail['table_ready'] ?? false);
    $operationalStatus = strtolower(trim((string) ($vendorOperationalHealth['status'] ?? 'unavailable')));
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
@endphp

<section class="card ops-section" aria-label="Compliance and operations" data-panel-group="compliance">
    <div class="ops-header">
        <p class="ops-title">Compliance & Operations Command Center</p>
        <span class="ops-chip">Enterprise readiness</span>
    </div>

    <p class="small" style="margin:0 0 12px;">
        This workspace brings together control signals, operating discipline, and go-live expectations required for a serious multi-vendor booking and distribution platform.
    </p>

    <div class="summary-grid summary-grid-compact" style="margin-bottom:12px;">
        <article class="summary-card">
            <p class="summary-label">Control Status</p>
            <p class="summary-value"><span class="status-pill {{ $operationalStatusClass }}">{{ $operationalStatusLabel }}</span></p>
            <p class="summary-meta">Current channel operations health</p>
        </article>
        <article class="summary-card">
            <p class="summary-label">Accounts Requiring Action</p>
            <p class="summary-value">{{ (int) ($operationalSummary['action_required_accounts'] ?? 0) }}</p>
            <p class="summary-meta">Connected partners with issues</p>
        </article>
        <article class="summary-card">
            <p class="summary-label">Inbound Failures</p>
            <p class="summary-value">{{ (int) ($operationalSummary['inbound_failed'] ?? 0) }}</p>
            <p class="summary-meta">Webhook and ingest exceptions</p>
        </article>
        <article class="summary-card">
            <p class="summary-label">Dead-Letter Events</p>
            <p class="summary-value">{{ (int) ($operationalSummary['dead_letter_events'] ?? 0) }}</p>
            <p class="summary-meta">Needs operator review</p>
        </article>
        <article class="summary-card">
            <p class="summary-label">Audit Events 24h</p>
            <p class="summary-value">{{ $auditTableReady ? $auditRecentCount : 0 }}</p>
            <p class="summary-meta">Recent operator actions captured</p>
        </article>
        <article class="summary-card">
            <p class="summary-label">High-Severity Audit Events</p>
            <p class="summary-value">{{ $auditTableReady ? $auditHighSeverityCount : 0 }}</p>
            <p class="summary-meta">Error and critical operator events</p>
        </article>
    </div>

    <div class="ops-form-grid" style="margin-bottom:12px;">
        <article class="card" style="padding:10px; border-radius:10px; border:1px solid #d7e0e6;">
            <p class="label" style="margin:0 0 6px;">Operational checklist</p>
            <p class="small" style="margin:0 0 6px;">1. All mapped channels active and healthy.</p>
            <p class="small" style="margin:0 0 6px;">2. No dead-letter or unowned retrying events.</p>
            <p class="small" style="margin:0 0 6px;">3. Reconciliation between bookings, commissions, and payouts verified.</p>
            <p class="small" style="margin:0;">4. Escalation and recovery procedures documented for operators.</p>
        </article>

        <article class="card" style="padding:10px; border-radius:10px; border:1px solid #d7e0e6;">
            <p class="label" style="margin:0 0 6px;">Platform commands</p>
            <p class="small" style="margin:0 0 6px;">Use <strong>php artisan channel:health</strong> to check operational risk.</p>
            <p class="small" style="margin:0 0 6px;">Use <strong>php artisan channel:dispatch-outbound</strong> to process outbound sync queue.</p>
            <p class="small" style="margin:0 0 6px;">Use <strong>php artisan channel:health-alert</strong> to evaluate threshold breaches and emit alerts.</p>
            <p class="small" style="margin:0;">These commands should be scheduled and monitored in production.</p>
        </article>

        <article class="card" style="padding:10px; border-radius:10px; border:1px solid #d7e0e6;">
            <p class="label" style="margin:0 0 6px;">Audit pressure</p>
            <p class="small" style="margin:0 0 6px;">Warning events in 24h: <strong>{{ $auditTableReady ? $auditWarnCount : 0 }}</strong></p>
            <p class="small" style="margin:0 0 6px;">High-severity events in 24h: <strong>{{ $auditTableReady ? $auditHighSeverityCount : 0 }}</strong></p>
            <p class="small" style="margin:0;">Use this as an operator stress signal during launches and incident windows.</p>
        </article>

        <article class="card" style="padding:10px; border-radius:10px; border:1px solid #d7e0e6;">
            <p class="label" style="margin:0 0 6px;">Compliance reality</p>
            <p class="small" style="margin:0 0 6px;">Enterprise and ISO-grade claims require controls, evidence, operating policies, and audit programs.</p>
            <p class="small" style="margin:0 0 6px;">This portal now exposes the right operational surfaces, but certification depends on more than application screens.</p>
            <p class="small" style="margin:0;">See the readiness assessment in the project docs for the current gap list.</p>
        </article>
    </div>

    @if ($operationalIssues->isNotEmpty())
        <div class="policy-box" style="margin:0 0 12px;border:1px solid #f0d080;border-radius:12px;background:#fff9ea;padding:10px 12px;">
            <p class="small" style="margin:0 0 6px;"><strong>Active issues</strong></p>
            @foreach ($operationalIssues as $issue)
                <p class="small" style="margin:0 0 4px;">{{ (string) $issue }}</p>
            @endforeach
        </div>
    @endif

    <div class="ops-header" style="margin-top:2px;">
        <p class="ops-title">At-Risk Channel Accounts</p>
        <span class="ops-chip">{{ $topFailedAccounts->count() }} tracked</span>
    </div>

    @if ($topFailedAccounts->isEmpty())
        <p class="ops-empty">No at-risk channel accounts are currently flagged.</p>
    @else
        <div class="payout-table-wrap" style="margin-bottom:12px;">
            <table class="payout-table" aria-label="At-risk channel accounts table">
                <thead>
                    <tr>
                        <th>Channel</th>
                        <th>Account</th>
                        <th>Failures</th>
                        <th>Status</th>
                        <th>Last Sync</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($topFailedAccounts->take(10) as $row)
                        <tr>
                            <td>{{ strtoupper((string) ($row['channel_code'] ?? 'unknown')) }}</td>
                            <td>{{ (string) ($row['account_reference'] ?? 'Not set') }}</td>
                            <td>{{ (int) ($row['failure_count'] ?? 0) }}</td>
                            <td>{{ strtoupper((string) ($row['status'] ?? 'unknown')) }}</td>
                            <td>{{ trim((string) ($row['last_sync_at'] ?? '')) !== '' ? \Illuminate\Support\Carbon::parse((string) $row['last_sync_at'])->diffForHumans() : 'Never' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="ops-header" style="margin-top:2px;">
        <p class="ops-title">Executive Notes</p>
        <span class="ops-chip">Market readiness</span>
    </div>

    <div class="policy-box" style="margin:0;border:1px solid #d3e2ec;border-radius:12px;background:#f8fcff;padding:10px 12px;">
        <p class="small" style="margin:0 0 6px;"><strong>For industry-scale adoption</strong></p>
        <p class="small" style="margin:0 0 6px;">To become the default operating platform for hotels, owners, and service providers, the product must combine superior workflow design, channel reliability, billing trust, onboarding simplicity, and strong commercial execution.</p>
        <p class="small" style="margin:0;">This workspace positions the portal more like an enterprise platform, but market takeover depends on rollout strategy, data migration support, and enforceable business network effects in addition to engineering.</p>
    </div>

    <div class="ops-header" style="margin-top:12px;">
        <p class="ops-title">Recent Operator Audit Activity</p>
        <span class="ops-chip">{{ $auditRecentCount }} in 24h</span>
    </div>

    @if (!$auditTableReady)
        <p class="ops-empty">Audit table is not available yet. Run migrations to enable vendor activity history.</p>
    @elseif ($auditLogs->isEmpty())
        <p class="ops-empty">No vendor audit activity has been recorded yet.</p>
    @else
        <div class="payout-table-wrap">
            <table class="payout-table" aria-label="Vendor audit activity table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Action</th>
                        <th>Severity</th>
                        <th>Target</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($auditLogs as $auditLog)
                        <tr>
                            <td>{{ !empty($auditLog->created_at) ? \Illuminate\Support\Carbon::parse((string) $auditLog->created_at)->diffForHumans() : 'N/A' }}</td>
                            <td>{{ trim((string) ($auditLog->action ?? 'event')) }}</td>
                            <td>{{ strtoupper(trim((string) ($auditLog->severity ?? 'info'))) }}</td>
                            <td>{{ trim((string) ($auditLog->target_identifier ?? 'Not set')) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
