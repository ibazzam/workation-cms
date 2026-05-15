@php
    $distributionSummary = is_array($distributionSummary ?? null) ? $distributionSummary : [];
    $distributionAccounts = collect($distributionAccounts ?? []);
    $distributionRoomMappings = collect($distributionRoomMappings ?? []);
    $distributionEvents = collect($distributionEvents ?? []);

    $connectedChannels = (int) ($distributionSummary['connected_channels'] ?? 0);
    $actionRequiredChannels = (int) ($distributionSummary['action_required_channels'] ?? 0);
    $mappedRooms = (int) ($distributionSummary['mapped_rooms'] ?? 0);
    $failedEvents = (int) ($distributionSummary['failed_events'] ?? 0);
    $pendingEvents = (int) ($distributionSummary['pending_events'] ?? 0);
    $setupProgress = max(0, min(100, (int) ($distributionSummary['setup_progress'] ?? 0)));
    $nextStep = trim((string) ($distributionSummary['next_step'] ?? 'Connect your first OTA channel to start receiving bookings.'));
    $lastSyncAt = trim((string) ($distributionSummary['last_sync_at'] ?? ''));

    $stepConnectDone = $connectedChannels > 0;
    $stepMapDone = $mappedRooms > 0;
    $stepHealthDone = $connectedChannels > 0 && $pendingEvents === 0 && $failedEvents === 0;
@endphp

<section class="card ops-section" aria-label="Distribution setup" data-panel-group="distribution">
    <div class="ops-header">
        <p class="ops-title">Channel Manager (Easy Setup)</p>
        <span class="ops-chip">{{ $setupProgress }}% ready</span>
    </div>

    <p class="small" style="margin:0 0 10px;">
        Manage Booking.com, Agoda, Airbnb, and direct bookings in one place. Keep this simple flow: connect channels, map rooms, and go live.
    </p>

    @if (session('status'))
        <div class="policy-box" style="margin:0 0 10px;border:1px solid #a0ddb5;border-radius:12px;background:#edf9f3;padding:10px 12px;color:#0b5c2a;">
            <p class="small" style="margin:0;">{{ (string) session('status') }}</p>
        </div>
    @endif

    @if ($errors->has('distribution'))
        <div class="policy-box" style="margin:0 0 10px;border:1px solid #f0b7b3;border-radius:12px;background:#fff1f0;padding:10px 12px;color:#6d1111;">
            <p class="small" style="margin:0;">{{ (string) $errors->first('distribution') }}</p>
        </div>
    @endif

    <div style="height:10px;border:1px solid #d7e0e6;border-radius:999px;background:#f4f8fb;overflow:hidden;margin-bottom:12px;">
        <div style="height:100%;width:{{ $setupProgress }}%;background:linear-gradient(90deg,#2fa58a,#1e7b67);"></div>
    </div>

    <div class="summary-grid summary-grid-compact" style="margin-bottom:12px;">
        <article class="summary-card">
            <p class="summary-label">Connected Channels</p>
            <p class="summary-value">{{ $connectedChannels }}</p>
            <p class="summary-meta">{{ $actionRequiredChannels }} need attention</p>
        </article>
        <article class="summary-card">
            <p class="summary-label">Mapped Rooms</p>
            <p class="summary-value">{{ $mappedRooms }}</p>
            <p class="summary-meta">Ready for inventory sync</p>
        </article>
        <article class="summary-card">
            <p class="summary-label">Sync Health</p>
            <p class="summary-value"><span class="status-pill {{ $failedEvents > 0 ? 'err' : ($pendingEvents > 0 ? 'warn' : 'ok') }}">{{ $failedEvents > 0 ? 'ACTION REQUIRED' : ($pendingEvents > 0 ? 'SYNCING' : 'HEALTHY') }}</span></p>
            <p class="summary-meta">Failed: {{ $failedEvents }} | Pending: {{ $pendingEvents }}</p>
        </article>
        <article class="summary-card">
            <p class="summary-label">Last Sync</p>
            <p class="summary-value" style="font-size:0.92rem;">{{ $lastSyncAt !== '' ? \Illuminate\Support\Carbon::parse($lastSyncAt)->diffForHumans() : 'Not yet' }}</p>
            <p class="summary-meta">{{ $lastSyncAt !== '' ? \Illuminate\Support\Carbon::parse($lastSyncAt)->format('Y-m-d H:i') : 'No sync record' }}</p>
        </article>
    </div>

    <div class="policy-box" style="margin:0 0 12px;border:1px solid #d3e2ec;border-radius:12px;background:#f8fcff;padding:10px 12px;">
        <p class="small" style="margin:0 0 6px;"><strong>What to do next</strong></p>
        <p class="small" style="margin:0;">{{ $nextStep }}</p>
    </div>

    <div class="ops-form-grid" style="margin-bottom:12px;">
        <article class="card" style="padding:10px; border-radius:10px; border:1px solid #d7e0e6;">
            <p class="label" style="margin:0 0 6px;">International OTA readiness</p>
            <p class="small" style="margin:0 0 8px;">A sellable OTA needs secure channel intake, atomic inventory control, payout reconciliation, auditability, and documented operating procedures.</p>
            <p class="small" style="margin:0;color:#516071;">Current focus: channel security, room mapping, event retry, and outbound sync orchestration.</p>
        </article>

        <article class="card" style="padding:10px; border-radius:10px; border:1px solid #d7e0e6;">
            <p class="label" style="margin:0 0 6px;">Operational controls</p>
            <p class="small" style="margin:0 0 8px;">Run the health check and outbound dispatcher as part of daily operations and before major channel launches.</p>
            <p class="small" style="margin:0;color:#516071;">Commands: <strong>php artisan channel:health</strong> and <strong>php artisan channel:dispatch-outbound</strong></p>
        </article>

        <article class="card" style="padding:10px; border-radius:10px; border:1px solid #d7e0e6;">
            <p class="label" style="margin:0 0 6px;">Compliance reality</p>
            <p class="small" style="margin:0 0 8px;">ISO readiness is a program, not a visual theme. It requires documented controls, monitoring, incident handling, audit evidence, and external certification work.</p>
            <p class="small" style="margin:0;color:#516071;">This workspace is being hardened toward that standard, but certification evidence still has to be built.</p>
        </article>
    </div>

    <div class="ops-form-grid" style="margin-bottom:12px;">
        <article class="card" style="padding:10px; border-radius:10px; border:1px solid #d7e0e6;">
            <p class="label" style="margin:0 0 6px;">Step 1: Connect OTA accounts</p>
            <p class="small" style="margin:0 0 8px;">Link Booking.com, Agoda, Airbnb, or your channel provider.</p>
            <span class="ops-chip" style="background:{{ $stepConnectDone ? '#e6f9ef' : '#fff8e5' }};border-color:{{ $stepConnectDone ? '#a0ddb5' : '#f0d080' }};color:{{ $stepConnectDone ? '#0b5c2a' : '#6b4a00' }};">{{ $stepConnectDone ? 'Done' : 'Pending' }}</span>
        </article>

        <article class="card" style="padding:10px; border-radius:10px; border:1px solid #d7e0e6;">
            <p class="label" style="margin:0 0 6px;">Step 2: Map rooms</p>
            <p class="small" style="margin:0 0 8px;">Match each OTA room to your room types to avoid overbooking.</p>
            <span class="ops-chip" style="background:{{ $stepMapDone ? '#e6f9ef' : '#fff8e5' }};border-color:{{ $stepMapDone ? '#a0ddb5' : '#f0d080' }};color:{{ $stepMapDone ? '#0b5c2a' : '#6b4a00' }};">{{ $stepMapDone ? 'Done' : 'Pending' }}</span>
        </article>

        <article class="card" style="padding:10px; border-radius:10px; border:1px solid #d7e0e6;">
            <p class="label" style="margin:0 0 6px;">Step 3: Confirm sync health</p>
            <p class="small" style="margin:0 0 8px;">Ensure no failed events, then switch to full auto-sync.</p>
            <span class="ops-chip" style="background:{{ $stepHealthDone ? '#e6f9ef' : '#fff8e5' }};border-color:{{ $stepHealthDone ? '#a0ddb5' : '#f0d080' }};color:{{ $stepHealthDone ? '#0b5c2a' : '#6b4a00' }};">{{ $stepHealthDone ? 'Done' : 'Pending' }}</span>
        </article>
    </div>

    <div class="ops-header" style="margin-top:2px;">
        <p class="ops-title">Connected Channels</p>
        <span class="ops-chip">{{ $distributionAccounts->count() }} total</span>
    </div>

    @if ($distributionAccounts->isEmpty())
        <p class="ops-empty">No channels connected yet. Start with one channel and test one room mapping before full go-live.</p>
    @else
        <div class="payout-table-wrap" style="margin-bottom:12px;">
            <table class="payout-table" aria-label="Connected channels table">
                <thead>
                    <tr>
                        <th>Channel</th>
                        <th>Account</th>
                        <th>Status</th>
                        <th>Last Sync</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($distributionAccounts->take(12) as $account)
                        @php
                            $status = strtolower(trim((string) ($account->status ?? 'disconnected')));
                            $statusLabel = ucwords(str_replace('_', ' ', $status));
                            $statusStyle = match (true) {
                                in_array($status, ['connected', 'active'], true) => 'background:#e6f9ef;border:1px solid #a0ddb5;color:#0b5c2a;',
                                in_array($status, ['action_required', 'error', 'token_expired'], true) => 'background:#fff0ef;border:1px solid #f0b7b3;color:#6d1111;',
                                default => 'background:#fff8e5;border:1px solid #f0d080;color:#6b4a00;',
                            };
                        @endphp
                        <tr>
                            <td>{{ strtoupper((string) ($account->channel_code ?? 'unknown')) }}</td>
                            <td>{{ trim((string) ($account->account_reference ?? 'Not set')) }}</td>
                            <td><span style="display:inline-block;border-radius:999px;padding:2px 8px;font-size:.72rem;font-weight:700;{{ $statusStyle }}">{{ $statusLabel }}</span></td>
                            <td>{{ !empty($account->last_sync_at) ? \Illuminate\Support\Carbon::parse((string) $account->last_sync_at)->diffForHumans() : 'Not synced yet' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="ops-header" style="margin-top:2px;">
        <p class="ops-title">Recent Sync Events</p>
        <span class="ops-chip">{{ $distributionEvents->count() }} events</span>
    </div>

    @if ($distributionEvents->isEmpty())
        <p class="ops-empty">No sync events yet. Events appear here after channel traffic starts.</p>
    @else
        <div class="payout-table-wrap">
            <table class="payout-table" aria-label="Recent sync events table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Direction</th>
                        <th>Status</th>
                        <th>Action</th>
                        <th>When</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($distributionEvents->take(15) as $event)
                        @php
                            $eventStatus = strtolower(trim((string) ($event->status ?? 'received')));
                            $eventStyle = match (true) {
                                in_array($eventStatus, ['processed', 'success', 'completed'], true) => 'background:#e6f9ef;border:1px solid #a0ddb5;color:#0b5c2a;',
                                in_array($eventStatus, ['failed', 'error', 'dead_letter'], true) => 'background:#fff0ef;border:1px solid #f0b7b3;color:#6d1111;',
                                default => 'background:#eef5ff;border:1px solid #c6d9f5;color:#1a3f6b;',
                            };
                        @endphp
                        <tr>
                            <td>{{ trim((string) ($event->event_type ?? 'event')) }}</td>
                            <td>{{ strtoupper(trim((string) ($event->direction ?? 'inbound'))) }}</td>
                            <td><span style="display:inline-block;border-radius:999px;padding:2px 8px;font-size:.72rem;font-weight:700;{{ $eventStyle }}">{{ ucwords(str_replace('_', ' ', $eventStatus)) }}</span></td>
                            <td>
                                @if (strtolower(trim((string) ($event->direction ?? 'inbound'))) === 'inbound' && in_array($eventStatus, ['failed', 'error', 'dead_letter'], true))
                                    <form method="post" action="{{ url('/vendor/distribution/events/' . ((int) ($event->id ?? 0)) . '/retry') }}" style="margin:0;">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary" style="padding:4px 10px;font-size:.75rem;">Retry</button>
                                    </form>
                                @else
                                    <span class="small" style="color:#567;">—</span>
                                @endif
                            </td>
                            <td>{{ !empty($event->created_at) ? \Illuminate\Support\Carbon::parse((string) $event->created_at)->diffForHumans() : 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
