@php
    $distributionSummary = is_array($distributionSummary ?? null) ? $distributionSummary : [];
    $distributionEvents = collect($distributionEvents ?? []);
    $setupProgress = max(0, min(100, (int) ($distributionSummary['setup_progress'] ?? 0)));
    $goLiveReady = (bool) ($distributionSummary['go_live_ready'] ?? false);
    $connectedChannels = (int) ($distributionSummary['connected_channels'] ?? 0);
    $mappedRooms = (int) ($distributionSummary['mapped_rooms'] ?? 0);
    $failedEvents = (int) ($distributionSummary['failed_events'] ?? 0);
    $pendingEvents = (int) ($distributionSummary['pending_events'] ?? 0);

    $bookingsToday = (int) ($vendorDashboardSnapshot['bookings_today'] ?? 0);
    $revenueToday = (float) ($vendorDashboardSnapshot['revenue_today'] ?? 0);

    $syncHealthLabel = $failedEvents > 0 ? 'Needs fixing' : 'Healthy';
    $stepsAway = max(0, min(3, 3 - (int) (($connectedChannels > 0 ? 1 : 0) + ($mappedRooms > 0 ? 1 : 0) + (($pendingEvents === 0 && $failedEvents === 0) ? 1 : 0))));

    $nextActionLabel = 'Review go-live checklist';
    $nextActionHref = '/vendor?page=setup&mode=simple#step-go-live';
    if ($connectedChannels <= 0) {
        $nextActionLabel = 'Connect channel';
        $nextActionHref = '/vendor?page=setup&mode=simple#step-connect';
    } elseif ($mappedRooms <= 0) {
        $nextActionLabel = 'Map rooms';
        $nextActionHref = '/vendor?page=setup&mode=simple#step-map';
    } elseif ($failedEvents > 0) {
        $nextActionLabel = 'Fix sync issue';
        $nextActionHref = '/vendor?page=setup&mode=simple#step-sync';
    }

    $simpleAlerts = $distributionEvents
        ->filter(static function ($event): bool {
            $status = strtolower(trim((string) ($event->status ?? '')));
            return in_array($status, ['failed', 'error', 'dead_letter', 'retrying'], true);
        })
        ->take(3)
        ->values();
@endphp

<section class="card ops-section" data-panel-group="overview" aria-label="Simple mode home">
    <div class="simple-home-strip">
        <div>
            <p class="summary-label" style="margin:0 0 4px;">Setup progress</p>
            <p class="summary-value" style="margin:0;"><span class="status-pill {{ $goLiveReady ? 'ok' : 'warn' }}">Setup {{ $setupProgress }}% complete</span></p>
            <p class="small" style="margin:6px 0 0;color:#4f667b;">You are {{ max(1, $stepsAway) }} step{{ max(1, $stepsAway) === 1 ? '' : 's' }} away from going live.</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            <a class="btn btn-secondary" href="{{ $nextActionHref }}">Continue setup</a>
            <a class="btn" href="/vendor?page=distribution&mode=advanced&dist_tab=issues">View issues</a>
        </div>
    </div>

    <div class="summary-grid" style="margin-top:16px;gap:14px;">
        <article class="summary-card">
            <p class="summary-label">Bookings today</p>
            <p class="summary-value">{{ $bookingsToday }}</p>
        </article>
        <article class="summary-card">
            <p class="summary-label">Revenue today</p>
            <p class="summary-value">MVR {{ number_format($revenueToday, 2) }}</p>
        </article>
        <article class="summary-card">
            <p class="summary-label">Channels connected</p>
            <p class="summary-value">{{ $connectedChannels }}</p>
        </article>
        <article class="summary-card">
            <p class="summary-label">Sync status</p>
            <p class="summary-value"><span class="status-pill {{ $failedEvents > 0 ? 'err' : 'ok' }}">{{ $syncHealthLabel }}</span></p>
        </article>
    </div>

    <article class="card" style="margin-top:16px;padding:14px;border:1px solid #d8e2eb;border-radius:12px;">
        <p class="label" style="margin:0 0 6px;">Do this next</p>
        <p class="small" style="margin:0 0 10px;color:#516071;">{{ trim((string) ($distributionSummary['next_step'] ?? 'Finish setup to start syncing bookings and inventory.')) }}</p>
        <a class="btn btn-secondary" href="{{ $nextActionHref }}">{{ $nextActionLabel }}</a>
    </article>

    <article class="card" style="margin-top:16px;padding:14px;border:1px solid #d8e2eb;border-radius:12px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
            <p class="label" style="margin:0;">Alerts</p>
            <a class="small" href="/vendor?page=distribution&mode=advanced&dist_tab=issues" style="font-weight:700;">View all alerts</a>
        </div>

        @if ($simpleAlerts->isEmpty())
            <p class="small" style="margin:10px 0 0;color:#4f667b;">No action-required alerts right now.</p>
        @else
            <div style="display:grid;gap:10px;margin-top:10px;">
                @foreach ($simpleAlerts as $alert)
                    @php
                        $alertStatus = strtolower(trim((string) ($alert->status ?? 'failed')));
                        $alertDirection = strtolower(trim((string) ($alert->direction ?? 'inbound')));
                        $isOutbound = $alertDirection === 'outbound';
                        $alertRowId = (int) ($alert->id ?? 0);
                    @endphp
                    <div style="border:1px solid #e1e8ee;border-radius:10px;padding:10px;background:#fff;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:center;">
                        <div>
                            <p class="small" style="margin:0;font-weight:700;color:#243746;">{{ strtoupper((string) ($alert->channel_code ?? 'OTA')) }} {{ trim((string) ($alert->event_type ?? 'sync event')) }}</p>
                            <p class="small" style="margin:3px 0 0;color:#6b1111;">{{ trim((string) ($alert->error_message ?? 'Sync needs attention.')) }}</p>
                        </div>
                        <div>
                            @if ($alertRowId > 0)
                                @if ($isOutbound)
                                    <form method="post" action="{{ url('/vendor/distribution/events/' . $alertRowId . '/retry') }}" style="margin:0;">
                                        @csrf
                                        <button class="btn btn-secondary" type="submit" style="padding:6px 10px;">Requeue</button>
                                    </form>
                                @else
                                    <form method="post" action="{{ url('/vendor/distribution/events/' . $alertRowId . '/retry') }}" style="margin:0;">
                                        @csrf
                                        <button class="btn btn-secondary" type="submit" style="padding:6px 10px;">Retry</button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </article>
</section>
