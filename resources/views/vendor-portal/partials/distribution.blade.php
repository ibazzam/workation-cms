@php
    $portalMode = in_array(($portalMode ?? 'simple'), ['simple', 'advanced'], true) ? $portalMode : 'simple';
    $distributionTab = in_array(($distributionTab ?? 'overview'), ['overview', 'connections', 'mapping', 'issues', 'logs'], true)
        ? $distributionTab
        : 'overview';

    $distributionSummary = is_array($distributionSummary ?? null) ? $distributionSummary : [];
    $distributionAccounts = collect($distributionAccounts ?? []);
    $distributionRoomMappings = collect($distributionRoomMappings ?? []);
    $distributionEvents = collect($distributionEvents ?? []);
    $vendorRoomCategories = collect($vendorRoomCategories ?? []);
    $vendorProperties = collect($vendorProperties ?? []);

    $connectedChannels = (int) ($distributionSummary['connected_channels'] ?? 0);
    $actionRequiredChannels = (int) ($distributionSummary['action_required_channels'] ?? 0);
    $mappedRooms = (int) ($distributionSummary['mapped_rooms'] ?? 0);
    $failedEvents = (int) ($distributionSummary['failed_events'] ?? 0);
    $pendingEvents = (int) ($distributionSummary['pending_events'] ?? 0);
    $setupProgress = max(0, min(100, (int) ($distributionSummary['setup_progress'] ?? 0)));
    $goLiveReady = (bool) ($distributionSummary['go_live_ready'] ?? false);
    $readinessChecks = collect($distributionSummary['readiness_checks'] ?? []);

    $prefillAccountId = (int) request()->query('channel_account', 0);
    $prefillAccount = $prefillAccountId > 0
        ? $distributionAccounts->first(static fn ($row): bool => (int) ($row->id ?? 0) === $prefillAccountId)
        : null;
    $prefillMeta = [];
    if ($prefillAccount && is_string($prefillAccount->connection_meta ?? null) && trim((string) $prefillAccount->connection_meta) !== '') {
        $decodedMeta = json_decode((string) $prefillAccount->connection_meta, true);
        if (is_array($decodedMeta)) {
            $prefillMeta = $decodedMeta;
        }
    }

    $selectedChannel = old('channel_code', $prefillAccount ? strtolower(trim((string) ($prefillAccount->channel_code ?? 'booking'))) : 'booking');
    $selectedAccountReference = old('account_reference', $prefillAccount ? trim((string) ($prefillAccount->account_reference ?? '')) : '');
    $selectedPropertyId = old('vendor_property_id', $prefillAccount ? (string) ((int) ($prefillAccount->vendor_property_id ?? 0)) : '');
    if ($selectedPropertyId === '0') {
        $selectedPropertyId = '';
    }
    $selectedWebhookSecret = old('webhook_secret', trim((string) ($prefillMeta['webhook_secret'] ?? '')));
    $selectedInventorySyncUrl = old('inventory_sync_url', trim((string) ($prefillMeta['inventory_sync_url'] ?? '')));
    $selectedApiBase = old('api_base', trim((string) ($prefillMeta['api_base'] ?? '')));

    $roomCategoryNames = $vendorRoomCategories
        ->mapWithKeys(static fn ($room) => [
            (int) ($room->id ?? 0) => trim((string) ($room->name ?? ('Room #' . ((int) ($room->id ?? 0))))),
        ]);

    $issuesEvents = $distributionEvents
        ->filter(static function ($event): bool {
            $status = strtolower(trim((string) ($event->status ?? '')));
            return in_array($status, ['failed', 'error', 'dead_letter', 'retrying'], true);
        })
        ->values();

    $distributionModeBase = '/vendor?page=distribution&mode=' . $portalMode;
@endphp

<section class="card ops-section" aria-label="Distribution center" data-panel-group="distribution">
    @if (session('status'))
        <div class="notice" style="margin-top:0;">{{ (string) session('status') }}</div>
    @endif

    @if ($errors->has('distribution'))
        <div class="error" style="margin-top:10px;">{{ (string) $errors->first('distribution') }}</div>
    @endif

    @if ($portalMode === 'simple')
        <div class="ops-header" style="margin-top:10px;">
            <p class="ops-title">Distribution setup</p>
            <span class="ops-chip">Simple mode</span>
        </div>

        <p class="small" style="margin:0 0 10px;color:#516071;">Use Setup page for guided onboarding. Advanced mode shows full controls and logs.</p>

        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
            <a class="btn btn-secondary" href="/vendor?page=setup&mode=simple">Open setup wizard</a>
            <a class="btn" href="/vendor?page=distribution&mode=advanced&dist_tab=overview">Switch to advanced</a>
        </div>

        <article class="card" style="padding:10px;border:1px solid #d8e2eb;border-radius:12px;">
            <p class="label" style="margin:0 0 6px;">{{ $prefillAccount ? 'Reconnect / Update OTA Account' : 'Connect OTA Account' }}</p>
            <form method="post" action="{{ url('/vendor/distribution/accounts/connect') }}" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;">
                @csrf
                <label style="display:flex;flex-direction:column;gap:4px;">
                    <span class="small">Channel</span>
                    <select name="channel_code" required>
                        <option value="booking" {{ $selectedChannel === 'booking' ? 'selected' : '' }}>Booking.com</option>
                        <option value="agoda" {{ $selectedChannel === 'agoda' ? 'selected' : '' }}>Agoda</option>
                        <option value="airbnb" {{ $selectedChannel === 'airbnb' ? 'selected' : '' }}>Airbnb</option>
                    </select>
                </label>
                <label style="display:flex;flex-direction:column;gap:4px;">
                    <span class="small">Account Reference</span>
                    <input type="text" name="account_reference" value="{{ $selectedAccountReference }}" maxlength="160">
                </label>
                <label style="display:flex;flex-direction:column;gap:4px;">
                    <span class="small">Webhook Secret</span>
                    <input type="password" name="webhook_secret" value="{{ $selectedWebhookSecret }}" required>
                </label>
                <label style="display:flex;flex-direction:column;gap:4px;">
                    <span class="small">Inventory Sync URL</span>
                    <input type="url" name="inventory_sync_url" value="{{ $selectedInventorySyncUrl }}">
                </label>
                <label style="display:flex;flex-direction:column;gap:4px;">
                    <span class="small">API Base URL</span>
                    <input type="url" name="api_base" value="{{ $selectedApiBase }}">
                </label>
                <label style="display:flex;flex-direction:column;gap:4px;">
                    <span class="small">Listing / Property Scope (optional)</span>
                    <select name="vendor_property_id">
                        <option value="">All listings</option>
                        @foreach ($vendorProperties->take(300) as $property)
                            @php $propId = (int) ($property->vendor_property_id ?? 0); @endphp
                            @if ($propId > 0)
                                <option value="{{ $propId }}" {{ (string) $selectedPropertyId === (string) $propId ? 'selected' : '' }}>
                                    {{ trim((string) ($property->name ?? ('Listing #' . $propId))) }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </label>
                <div style="grid-column:1 / -1;display:flex;justify-content:flex-end;">
                    <button type="submit" class="btn btn-secondary">{{ $prefillAccount ? 'Save OTA Account' : 'Save and continue' }}</button>
                </div>
            </form>
        </article>

        <article class="card" style="margin-top:12px;padding:10px;border:1px solid #d8e2eb;border-radius:12px;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
                <div>
                    <p class="label" style="margin:0 0 4px;">Go-Live Readiness</p>
                    <p class="small" style="margin:0;color:#516071;">Ready means channels are connected, rooms are mapped, and issues are cleared.</p>
                </div>
                <span class="status-pill {{ $goLiveReady ? 'ok' : 'warn' }}">{{ $goLiveReady ? 'READY' : 'NOT READY' }}</span>
            </div>
            <div style="display:grid;gap:8px;margin-top:10px;">
                @foreach ($readinessChecks as $check)
                    @php $passed = (bool) ($check['passed'] ?? false); @endphp
                    <div style="border:1px solid {{ $passed ? '#a0ddb5' : '#f0d080' }};border-radius:10px;padding:8px;background:#fff;">
                        <div class="small" style="font-weight:700;color:{{ $passed ? '#0b5c2a' : '#6b4a00' }};">{{ $passed ? 'PASS' : 'BLOCKED' }}</div>
                        <div class="small" style="margin-top:3px;color:#243746;">{{ trim((string) ($check['label'] ?? 'Readiness check')) }}</div>
                        <div class="small" style="margin-top:2px;color:#516071;">{{ trim((string) ($check['detail'] ?? '')) }}</div>
                    </div>
                @endforeach
            </div>
        </article>

        @if ($issuesEvents->isNotEmpty())
            <article class="card" style="margin-top:12px;padding:10px;border:1px solid #d8e2eb;border-radius:12px;">
                <p class="label" style="margin:0 0 8px;">Top issues</p>
                <div style="display:grid;gap:8px;">
                    @foreach ($issuesEvents->take(3) as $event)
                        @php
                            $eventStatus = strtolower(trim((string) ($event->status ?? 'received')));
                            $eventDirection = strtolower(trim((string) ($event->direction ?? 'inbound')));
                            $eventId = (int) ($event->id ?? 0);
                        @endphp
                        <div style="border:1px solid #e1e8ee;border-radius:10px;padding:8px;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px;align-items:center;">
                            <div>
                                <div class="small" style="font-weight:700;color:#243746;">{{ trim((string) ($event->event_type ?? 'event')) }} ({{ strtoupper($eventDirection) }})</div>
                                <div class="small" style="color:#5a6d7f;">Attempts: {{ max(0, (int) ($event->retry_count ?? 0)) }}</div>
                                @if (!empty($event->error_message))
                                    <div class="small" style="color:#6d1111;">Last error: {{ trim((string) $event->error_message) }}</div>
                                @endif
                            </div>
                            <div>
                                @if ($eventId > 0)
                                    <form method="post" action="{{ url('/vendor/distribution/events/' . $eventId . '/retry') }}" style="margin:0;">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary" style="padding:5px 9px;">{{ $eventDirection === 'outbound' ? 'Requeue' : 'Retry' }}</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>
        @endif
    @else
        <div class="ops-header" style="margin-top:10px;">
            <p class="ops-title">Distribution Control Center</p>
            <span class="ops-chip">Advanced mode</span>
        </div>

        <div class="workspace-category-tabs" style="margin:10px 0 12px;">
            <a class="workspace-category-tab {{ $distributionTab === 'overview' ? 'is-active' : '' }}" href="{{ $distributionModeBase . '&dist_tab=overview' }}">Overview</a>
            <a class="workspace-category-tab {{ $distributionTab === 'connections' ? 'is-active' : '' }}" href="{{ $distributionModeBase . '&dist_tab=connections' }}">Connections</a>
            <a class="workspace-category-tab {{ $distributionTab === 'mapping' ? 'is-active' : '' }}" href="{{ $distributionModeBase . '&dist_tab=mapping' }}">Mapping</a>
            <a class="workspace-category-tab {{ $distributionTab === 'issues' ? 'is-active' : '' }}" href="{{ $distributionModeBase . '&dist_tab=issues' }}">Issues</a>
            <a class="workspace-category-tab {{ $distributionTab === 'logs' ? 'is-active' : '' }}" href="{{ $distributionModeBase . '&dist_tab=logs' }}">Logs</a>
        </div>

        @if ($distributionTab === 'overview')
            <div class="summary-grid summary-grid-compact" style="margin-top:0;">
                <article class="summary-card">
                    <p class="summary-label">Setup progress</p>
                    <p class="summary-value">{{ $setupProgress }}%</p>
                </article>
                <article class="summary-card">
                    <p class="summary-label">Go-live readiness</p>
                    <p class="summary-value"><span class="status-pill {{ $goLiveReady ? 'ok' : 'warn' }}">{{ $goLiveReady ? 'READY' : 'BLOCKED' }}</span></p>
                </article>
                <article class="summary-card">
                    <p class="summary-label">Channels connected</p>
                    <p class="summary-value">{{ $connectedChannels }}</p>
                </article>
                <article class="summary-card">
                    <p class="summary-label">Events</p>
                    <p class="summary-value">{{ $failedEvents }} failed / {{ $pendingEvents }} pending</p>
                </article>
            </div>
            <article class="card" style="margin-top:12px;padding:10px;border:1px solid #d8e2eb;border-radius:12px;">
                <p class="label" style="margin:0 0 6px;">Go-Live Readiness</p>
                <div style="display:grid;gap:8px;">
                    @foreach ($readinessChecks as $check)
                        @php $passed = (bool) ($check['passed'] ?? false); @endphp
                        <div class="small" style="padding:8px;border:1px solid {{ $passed ? '#a0ddb5' : '#f0d080' }};border-radius:10px;">
                            <strong>{{ $passed ? 'PASS' : 'BLOCKED' }}:</strong> {{ trim((string) ($check['label'] ?? 'Readiness check')) }}
                        </div>
                    @endforeach
                </div>
            </article>
        @endif

        @if ($distributionTab === 'connections')
            <article class="card" style="padding:10px;border:1px solid #d8e2eb;border-radius:12px;">
                <p class="label" style="margin:0 0 8px;">{{ $prefillAccount ? 'Reconnect / Update OTA Account' : 'Connect OTA account' }}</p>
                <form method="post" action="{{ url('/vendor/distribution/accounts/connect') }}" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;">
                    @csrf
                    <label style="display:flex;flex-direction:column;gap:4px;"><span class="small">Channel selector</span><select name="channel_code" required><option value="booking" {{ $selectedChannel === 'booking' ? 'selected' : '' }}>Booking.com</option><option value="agoda" {{ $selectedChannel === 'agoda' ? 'selected' : '' }}>Agoda</option><option value="airbnb" {{ $selectedChannel === 'airbnb' ? 'selected' : '' }}>Airbnb</option></select></label>
                    <label style="display:flex;flex-direction:column;gap:4px;"><span class="small">Account reference</span><input type="text" name="account_reference" value="{{ $selectedAccountReference }}"></label>
                    <label style="display:flex;flex-direction:column;gap:4px;"><span class="small">Webhook secret</span><input type="password" name="webhook_secret" value="{{ $selectedWebhookSecret }}" required></label>
                    <label style="display:flex;flex-direction:column;gap:4px;"><span class="small">Inventory sync URL</span><input type="url" name="inventory_sync_url" value="{{ $selectedInventorySyncUrl }}"></label>
                    <label style="display:flex;flex-direction:column;gap:4px;"><span class="small">API base URL</span><input type="url" name="api_base" value="{{ $selectedApiBase }}"></label>
                    <label style="display:flex;flex-direction:column;gap:4px;"><span class="small">Access token (optional)</span><input type="password" name="access_token" value="{{ old('access_token') }}"></label>
                    <div style="grid-column:1 / -1;display:flex;justify-content:flex-end;"><button type="submit" class="btn btn-secondary">{{ $prefillAccount ? 'Save OTA Account' : 'Connect OTA account' }}</button></div>
                </form>
            </article>

            <div class="payout-table-wrap" style="margin-top:12px;">
                <table class="payout-table" aria-label="Connected channels">
                    <thead><tr><th>Channel</th><th>Account</th><th>Status</th><th>Token freshness</th><th>Action</th></tr></thead>
                    <tbody>
                        @forelse ($distributionAccounts->take(60) as $account)
                            @php
                                $status = strtolower(trim((string) ($account->status ?? 'disconnected')));
                                $accountMeta = [];
                                if (is_string($account->connection_meta ?? null) && trim((string) $account->connection_meta) !== '') {
                                    $decodedMeta = json_decode((string) $account->connection_meta, true);
                                    if (is_array($decodedMeta)) {
                                        $accountMeta = $decodedMeta;
                                    }
                                }
                                $tokenUpdatedAt = trim((string) ($accountMeta['access_token_updated_at'] ?? ''));
                            @endphp
                            <tr>
                                <td>{{ strtoupper((string) ($account->channel_code ?? 'unknown')) }}</td>
                                <td>{{ trim((string) ($account->account_reference ?? 'Not set')) }}</td>
                                <td>{{ strtoupper($status) }}</td>
                                <td>{{ $tokenUpdatedAt !== '' ? \Illuminate\Support\Carbon::parse($tokenUpdatedAt)->diffForHumans() : 'Unknown' }}</td>
                                <td>
                                    @if (in_array($status, ['connected', 'active', 'action_required', 'token_expired', 'error'], true))
                                        <form method="post" action="{{ url('/vendor/distribution/accounts/' . ((int) ($account->id ?? 0)) . '/disconnect') }}" style="margin:0;">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary" style="padding:5px 9px;">Disconnect</button>
                                        </form>
                                    @else
                                        <a class="btn" style="padding:5px 9px;" href="{{ url('/vendor?page=distribution&mode=advanced&dist_tab=connections&channel_account=' . ((int) ($account->id ?? 0))) }}">Reconnect</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">No connected channels yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        @if ($distributionTab === 'mapping')
            <article class="card" style="padding:10px;border:1px solid #d8e2eb;border-radius:12px;">
                <p class="label" style="margin:0 0 8px;">Create or update mappings</p>
                <form method="post" action="{{ url('/vendor/distribution/room-mappings/save') }}" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;">
                    @csrf
                    <label style="display:flex;flex-direction:column;gap:4px;"><span class="small">OTA account</span><select name="vendor_channel_account_id" required><option value="">Select account</option>@foreach ($distributionAccounts->take(100) as $account) @php $accountId = (int) ($account->id ?? 0); @endphp @if ($accountId > 0)<option value="{{ $accountId }}" {{ (string) old('vendor_channel_account_id') === (string) $accountId ? 'selected' : '' }}>{{ strtoupper(trim((string) ($account->channel_code ?? 'ota'))) }} - {{ trim((string) ($account->account_reference ?? ('Account #' . $accountId))) }}</option>@endif @endforeach</select></label>
                    <label style="display:flex;flex-direction:column;gap:4px;"><span class="small">External room id</span><input type="text" name="external_room_id" value="{{ old('external_room_id') }}" required></label>
                    <label style="display:flex;flex-direction:column;gap:4px;"><span class="small">Internal room selector</span><select name="internal_room_category_id" required><option value="">Select room</option>@foreach ($vendorRoomCategories->take(200) as $room) @php $roomId = (int) ($room->id ?? 0); @endphp @if ($roomId > 0)<option value="{{ $roomId }}" {{ (string) old('internal_room_category_id') === (string) $roomId ? 'selected' : '' }}>{{ trim((string) ($room->name ?? ('Room #' . $roomId))) }}</option>@endif @endforeach</select></label>
                    <label style="display:flex;flex-direction:column;gap:4px;"><span class="small">External room name</span><input type="text" name="external_room_name" value="{{ old('external_room_name') }}"></label>
                    <div style="grid-column:1 / -1;display:flex;justify-content:flex-end;"><button type="submit" class="btn btn-secondary">Save mapping</button></div>
                </form>
            </article>

            <div style="margin-top:10px;display:flex;justify-content:flex-end;"><input id="mappingSearchInput" type="search" placeholder="Search mappings" style="min-width:220px;"></div>
            <div class="payout-table-wrap" style="margin-top:10px;">
                <table class="payout-table" aria-label="Mapping table" id="mappingTable">
                    <thead><tr><th>External room</th><th>Internal room</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                        @forelse ($distributionRoomMappings->sortByDesc(static fn ($mapping) => strtolower(trim((string) ($mapping->mapping_status ?? 'inactive'))) === 'active')->take(250) as $mapping)
                            @php $mappingStatus = strtolower(trim((string) ($mapping->mapping_status ?? 'active'))); @endphp
                            <tr>
                                <td>{{ trim((string) ($mapping->external_room_id ?? 'Unknown external room')) }}</td>
                                <td>{{ $roomCategoryNames->get((int) ($mapping->internal_room_category_id ?? 0), 'Unknown room') }}</td>
                                <td>{{ strtoupper($mappingStatus) }}</td>
                                <td>
                                    @if ($mappingStatus === 'active')
                                        <form method="post" action="{{ url('/vendor/distribution/room-mappings/' . ((int) ($mapping->id ?? 0)) . '/deactivate') }}" style="margin:0;">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary" style="padding:5px 9px;">Deactivate</button>
                                        </form>
                                    @else
                                        <span class="small">Use Save mapping to reactivate</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4">No mappings yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        @if ($distributionTab === 'issues')
            <div class="payout-table-wrap">
                <table class="payout-table" aria-label="Failed and retrying events">
                    <thead><tr><th>Type</th><th>Direction</th><th>Status</th><th>Diagnostics</th><th>Action</th></tr></thead>
                    <tbody>
                        @forelse ($issuesEvents->take(120) as $event)
                            @php
                                $eventStatus = strtolower(trim((string) ($event->status ?? 'failed')));
                                $eventDirection = strtolower(trim((string) ($event->direction ?? 'inbound')));
                                $eventId = (int) ($event->id ?? 0);
                            @endphp
                            <tr>
                                <td>{{ trim((string) ($event->event_type ?? 'event')) }}</td>
                                <td>{{ strtoupper($eventDirection) }}</td>
                                <td>{{ strtoupper($eventStatus) }}</td>
                                <td>
                                    <div class="small">Attempts: {{ max(0, (int) ($event->retry_count ?? 0)) }}</div>
                                    @if (!empty($event->error_message))
                                        <div class="small" style="color:#6d1111;">Last error: {{ trim((string) $event->error_message) }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                        @if ($eventId > 0 && in_array($eventStatus, ['failed', 'error', 'dead_letter'], true))
                                            <form method="post" action="{{ url('/vendor/distribution/events/' . $eventId . '/retry') }}" style="margin:0;">
                                                @csrf
                                                <button type="submit" class="btn btn-secondary" style="padding:5px 9px;">{{ $eventDirection === 'outbound' ? 'Requeue' : 'Retry' }}</button>
                                            </form>
                                        @endif
                                        @if ($eventId > 0 && $eventDirection === 'outbound')
                                            <form method="post" action="{{ url('/vendor/distribution/events/' . $eventId . '/dispatch-now') }}" style="margin:0;">
                                                @csrf
                                                <button type="submit" class="btn" style="padding:5px 9px;">Dispatch now</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">No failed or retrying events.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        @if ($distributionTab === 'logs')
            @php
                $logDirection = strtolower(trim((string) request()->query('direction', 'all')));
                $logStatus = strtolower(trim((string) request()->query('status', 'all')));
                $logChannel = strtolower(trim((string) request()->query('channel', 'all')));

                $logsFiltered = $distributionEvents->filter(static function ($event) use ($logDirection, $logStatus, $logChannel): bool {
                    $direction = strtolower(trim((string) ($event->direction ?? '')));
                    $status = strtolower(trim((string) ($event->status ?? '')));
                    $channel = strtolower(trim((string) ($event->channel_code ?? '')));
                    if ($logDirection !== 'all' && $direction !== $logDirection) {
                        return false;
                    }
                    if ($logStatus !== 'all' && $status !== $logStatus) {
                        return false;
                    }
                    if ($logChannel !== 'all' && $channel !== $logChannel) {
                        return false;
                    }
                    return true;
                })->values();

                $logStatuses = $distributionEvents->map(static fn ($event) => strtolower(trim((string) ($event->status ?? ''))))->filter()->unique()->values();
                $logChannels = $distributionEvents->map(static fn ($event) => strtolower(trim((string) ($event->channel_code ?? ''))))->filter()->unique()->values();
            @endphp

            <form method="get" action="/vendor" class="card" style="padding:10px;border:1px solid #d8e2eb;border-radius:12px;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;">
                <input type="hidden" name="page" value="distribution">
                <input type="hidden" name="mode" value="advanced">
                <input type="hidden" name="dist_tab" value="logs">
                <label style="display:flex;flex-direction:column;gap:4px;"><span class="small">Direction</span><select name="direction"><option value="all" {{ $logDirection === 'all' ? 'selected' : '' }}>All</option><option value="inbound" {{ $logDirection === 'inbound' ? 'selected' : '' }}>Inbound</option><option value="outbound" {{ $logDirection === 'outbound' ? 'selected' : '' }}>Outbound</option></select></label>
                <label style="display:flex;flex-direction:column;gap:4px;"><span class="small">Status</span><select name="status"><option value="all" {{ $logStatus === 'all' ? 'selected' : '' }}>All</option>@foreach ($logStatuses as $statusOption)<option value="{{ $statusOption }}" {{ $logStatus === $statusOption ? 'selected' : '' }}>{{ strtoupper($statusOption) }}</option>@endforeach</select></label>
                <label style="display:flex;flex-direction:column;gap:4px;"><span class="small">Channel</span><select name="channel"><option value="all" {{ $logChannel === 'all' ? 'selected' : '' }}>All</option>@foreach ($logChannels as $channelOption)<option value="{{ $channelOption }}" {{ $logChannel === $channelOption ? 'selected' : '' }}>{{ strtoupper($channelOption) }}</option>@endforeach</select></label>
                <div style="display:flex;align-items:flex-end;gap:8px;"><button type="submit" class="btn btn-secondary">Apply filters</button><a class="btn" href="{{ $distributionModeBase . '&dist_tab=logs' }}">Reset</a></div>
            </form>

            <div class="payout-table-wrap" style="margin-top:10px;">
                <table class="payout-table" aria-label="Recent events logs">
                    <thead><tr><th>Created</th><th>Type</th><th>Direction</th><th>Status</th><th>Channel</th><th>Error</th></tr></thead>
                    <tbody>
                        @forelse ($logsFiltered->take(200) as $event)
                            <tr>
                                <td>{{ !empty($event->created_at) ? \Illuminate\Support\Carbon::parse((string) $event->created_at)->format('Y-m-d H:i') : 'N/A' }}</td>
                                <td>{{ trim((string) ($event->event_type ?? 'event')) }}</td>
                                <td>{{ strtoupper(trim((string) ($event->direction ?? 'inbound'))) }}</td>
                                <td>{{ strtoupper(trim((string) ($event->status ?? 'received'))) }}</td>
                                <td>{{ strtoupper(trim((string) ($event->channel_code ?? 'unknown'))) }}</td>
                                <td>{{ \Illuminate\Support\Str::limit(trim((string) ($event->error_message ?? '')), 80) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No events found for selected filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</section>

@if ($portalMode === 'advanced' && $distributionTab === 'mapping')
<script>
    (function () {
        var input = document.getElementById('mappingSearchInput');
        var table = document.getElementById('mappingTable');
        if (!input || !table) {
            return;
        }
        input.addEventListener('input', function () {
            var query = String(input.value || '').toLowerCase().trim();
            var rows = table.querySelectorAll('tbody tr');
            rows.forEach(function (row) {
                var text = String(row.textContent || '').toLowerCase();
                row.style.display = query === '' || text.indexOf(query) !== -1 ? '' : 'none';
            });
        });
    })();
</script>
@endif
