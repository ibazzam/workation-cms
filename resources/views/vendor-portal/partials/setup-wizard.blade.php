@php
    $distributionSummary = is_array($distributionSummary ?? null) ? $distributionSummary : [];
    $distributionAccounts = collect($distributionAccounts ?? []);
    $distributionRoomMappings = collect($distributionRoomMappings ?? []);
    $distributionEvents = collect($distributionEvents ?? []);
    $vendorRoomCategories = collect($vendorRoomCategories ?? []);
    $vendorProperties = collect($vendorProperties ?? []);

    $connectedChannels = (int) ($distributionSummary['connected_channels'] ?? 0);
    $mappedRooms = (int) ($distributionSummary['mapped_rooms'] ?? 0);
    $failedEvents = (int) ($distributionSummary['failed_events'] ?? 0);
    $pendingEvents = (int) ($distributionSummary['pending_events'] ?? 0);
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

    $failedOrRetryingEvents = $distributionEvents
        ->filter(static function ($event): bool {
            $status = strtolower(trim((string) ($event->status ?? '')));
            return in_array($status, ['failed', 'error', 'dead_letter', 'retrying'], true);
        })
        ->values();

    $stepState = request()->query('step', 'connect');
    $allowedSteps = ['connect', 'map', 'sync', 'go-live'];
    if (!in_array($stepState, $allowedSteps, true)) {
        $stepState = 'connect';
    }

    if ($connectedChannels > 0 && $stepState === 'connect') {
        $stepState = 'map';
    }
    if ($mappedRooms > 0 && $stepState === 'map') {
        $stepState = 'sync';
    }
    if ($connectedChannels > 0 && $mappedRooms > 0 && $pendingEvents === 0 && $failedEvents === 0 && $stepState === 'sync') {
        $stepState = 'go-live';
    }
@endphp

<section class="card ops-section" data-panel-group="distribution" aria-label="Setup wizard">
    <div class="ops-header">
        <p class="ops-title">Setup Wizard</p>
        <span class="ops-chip">{{ (int) ($distributionSummary['setup_progress'] ?? 0) }}% complete</span>
    </div>

    @if (session('status'))
        <div class="notice" style="margin-top:10px;">{{ (string) session('status') }}</div>
    @endif

    <div style="display:grid;gap:12px;margin-top:12px;">
        <article id="step-connect" class="card" style="border:1px solid #d8e2eb;border-radius:12px;padding:12px;">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;">
                <p class="label" style="margin:0;">1. Connect channels</p>
                @if ($connectedChannels > 0)
                    <span class="status-pill ok">Done</span>
                @endif
            </div>

            @if ($stepState !== 'connect')
                <p class="small" style="margin:8px 0 0;color:{{ $connectedChannels > 0 ? '#0b5c2a' : '#6b4a00' }};">{{ $connectedChannels > 0 ? ('Connected channels: ' . $connectedChannels . '. Step completed.') : 'Channel connection is still required.' }}</p>
                <div style="margin-top:8px;display:flex;justify-content:flex-end;">
                    <a class="btn" href="/vendor?page=setup&mode=simple&step=connect#step-connect">Open step</a>
                </div>
            @else
                <form method="post" action="{{ url('/vendor/distribution/accounts/connect') }}" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:10px;">
                    @csrf
                    <label style="display:flex;flex-direction:column;gap:4px;">
                        <span class="small">Channel selector</span>
                        <select name="channel_code" required>
                            <option value="booking" {{ $selectedChannel === 'booking' ? 'selected' : '' }}>Booking.com</option>
                            <option value="agoda" {{ $selectedChannel === 'agoda' ? 'selected' : '' }}>Agoda</option>
                            <option value="airbnb" {{ $selectedChannel === 'airbnb' ? 'selected' : '' }}>Airbnb</option>
                        </select>
                    </label>
                    <label style="display:flex;flex-direction:column;gap:4px;">
                        <span class="small">Account reference</span>
                        <input type="text" name="account_reference" value="{{ $selectedAccountReference }}" maxlength="160" placeholder="Main account">
                    </label>
                    <label style="display:flex;flex-direction:column;gap:4px;">
                        <span class="small">Webhook secret</span>
                        <input type="password" name="webhook_secret" value="{{ $selectedWebhookSecret }}" required>
                    </label>
                    <label style="display:flex;flex-direction:column;gap:4px;">
                        <span class="small">Inventory sync URL</span>
                        <input type="url" name="inventory_sync_url" value="{{ $selectedInventorySyncUrl }}" placeholder="https://adapter.example.com/inventory/sync">
                    </label>
                    <label style="display:flex;flex-direction:column;gap:4px;">
                        <span class="small">API base URL</span>
                        <input type="url" name="api_base" value="{{ $selectedApiBase }}" placeholder="https://adapter.example.com">
                    </label>
                    <label style="display:flex;flex-direction:column;gap:4px;">
                        <span class="small">Listing / property scope (optional)</span>
                        <select name="vendor_property_id">
                            <option value="">All listings</option>
                            @foreach ($vendorProperties->take(300) as $property)
                                @php $propId = (int) ($property->vendor_property_id ?? 0); @endphp
                                @if ($propId > 0)
                                    <option value="{{ $propId }}" {{ (string) $selectedPropertyId === (string) $propId ? 'selected' : '' }}>{{ trim((string) ($property->name ?? ('Listing #' . $propId))) }}</option>
                                @endif
                            @endforeach
                        </select>
                    </label>
                    <details style="grid-column:1 / -1;">
                        <summary class="small" style="font-weight:700;cursor:pointer;">Show technical details</summary>
                        <div class="small" style="margin-top:8px;color:#567;display:grid;gap:4px;">
                            <a href="https://developers.booking.com/" target="_blank" rel="noopener">Where do I find this? Booking.com credentials</a>
                            <a href="https://developer.agoda.com/" target="_blank" rel="noopener">Where do I find this? Agoda credentials</a>
                            <a href="https://www.airbnb.com/help/article/3418" target="_blank" rel="noopener">Where do I find this? Airbnb partner access</a>
                        </div>
                    </details>
                    <div style="grid-column:1 / -1;display:flex;justify-content:flex-end;">
                        <button type="submit" class="btn btn-secondary">Save and continue</button>
                    </div>
                </form>
            @endif
        </article>

        <article id="step-map" class="card" style="border:1px solid #d8e2eb;border-radius:12px;padding:12px;">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;">
                <p class="label" style="margin:0;">2. Map rooms</p>
                @if ($mappedRooms > 0)
                    <span class="status-pill ok">Done</span>
                @endif
            </div>

            @if ($stepState !== 'map')
                <p class="small" style="margin:8px 0 0;color:{{ $mappedRooms > 0 ? '#0b5c2a' : '#6b4a00' }};">{{ $mappedRooms > 0 ? ('Active mappings: ' . $mappedRooms . '. Step completed.') : 'Room mapping is still required.' }}</p>
                <div style="margin-top:8px;display:flex;justify-content:flex-end;">
                    <a class="btn" href="/vendor?page=setup&mode=simple&step=map#step-map">Open step</a>
                </div>
            @else
                <form method="post" action="{{ url('/vendor/distribution/room-mappings/save') }}" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:10px;">
                    @csrf
                    <label style="display:flex;flex-direction:column;gap:4px;">
                        <span class="small">OTA account</span>
                        <select name="vendor_channel_account_id" required>
                            <option value="">Select connected account</option>
                            @foreach ($distributionAccounts->take(100) as $account)
                                @php $accountId = (int) ($account->id ?? 0); @endphp
                                @if ($accountId > 0)
                                    <option value="{{ $accountId }}" {{ (string) old('vendor_channel_account_id') === (string) $accountId ? 'selected' : '' }}>{{ strtoupper(trim((string) ($account->channel_code ?? 'ota'))) }} - {{ trim((string) ($account->account_reference ?? ('Account #' . $accountId))) }}</option>
                                @endif
                            @endforeach
                        </select>
                    </label>
                    <label style="display:flex;flex-direction:column;gap:4px;">
                        <span class="small">External room id</span>
                        <input type="text" name="external_room_id" value="{{ old('external_room_id') }}" maxlength="160" required>
                    </label>
                    <label style="display:flex;flex-direction:column;gap:4px;">
                        <span class="small">Internal room selector</span>
                        <select name="internal_room_category_id" required>
                            <option value="">Select internal room</option>
                            @foreach ($vendorRoomCategories->take(200) as $room)
                                @php $roomId = (int) ($room->id ?? 0); @endphp
                                @if ($roomId > 0)
                                    <option value="{{ $roomId }}" {{ (string) old('internal_room_category_id') === (string) $roomId ? 'selected' : '' }}>{{ trim((string) ($room->name ?? ('Room #' . $roomId))) }}</option>
                                @endif
                            @endforeach
                        </select>
                    </label>
                    <label style="display:flex;flex-direction:column;gap:4px;">
                        <span class="small">External room name</span>
                        <input type="text" name="external_room_name" value="{{ old('external_room_name') }}" maxlength="190" placeholder="Optional">
                    </label>
                    <p class="small" style="grid-column:1 / -1;margin:0;color:#516071;">Plain check: OTA account, external room id, and internal room are required.</p>
                    <div style="grid-column:1 / -1;display:flex;justify-content:flex-end;">
                        <button type="submit" class="btn btn-secondary">Save mapping</button>
                    </div>
                </form>

                @if ($distributionRoomMappings->isNotEmpty())
                    <div class="payout-table-wrap" style="margin-top:10px;">
                        <table class="payout-table" aria-label="Active room mappings">
                            <thead>
                                <tr>
                                    <th>External room</th>
                                    <th>Internal room</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($distributionRoomMappings->sortByDesc(static fn ($mapping) => strtolower(trim((string) ($mapping->mapping_status ?? 'inactive'))) === 'active')->take(15) as $mapping)
                                    <tr>
                                        <td>{{ trim((string) ($mapping->external_room_id ?? 'N/A')) }}</td>
                                        <td>{{ (int) ($mapping->internal_room_category_id ?? 0) }}</td>
                                        <td>{{ strtoupper(trim((string) ($mapping->mapping_status ?? 'active'))) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </article>

        <article id="step-sync" class="card" style="border:1px solid #d8e2eb;border-radius:12px;padding:12px;">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;">
                <p class="label" style="margin:0;">3. Test sync</p>
                <span class="status-pill {{ $failedEvents > 0 ? 'err' : ($pendingEvents > 0 ? 'warn' : 'ok') }}">{{ $failedEvents > 0 ? 'Needs action' : ($pendingEvents > 0 ? 'Running' : 'Healthy') }}</span>
            </div>

            @if ($stepState === 'sync')
                @if ($failedOrRetryingEvents->isEmpty())
                    <p class="small" style="margin:10px 0 0;color:#0b5c2a;">No failed or retrying events right now.</p>
                @else
                    <div style="display:grid;gap:8px;margin-top:10px;">
                        @foreach ($failedOrRetryingEvents->take(12) as $event)
                            @php
                                $eventId = (int) ($event->id ?? 0);
                                $eventDirection = strtolower(trim((string) ($event->direction ?? 'inbound')));
                                $eventStatus = strtolower(trim((string) ($event->status ?? 'failed')));
                            @endphp
                            <div style="border:1px solid #d8e2eb;border-radius:10px;padding:10px;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:center;">
                                <div>
                                    <p class="small" style="margin:0;font-weight:700;">{{ trim((string) ($event->event_type ?? 'sync event')) }} ({{ strtoupper($eventDirection) }})</p>
                                    <p class="small" style="margin:4px 0 0;color:#5a6d7f;">Attempts: {{ max(0, (int) ($event->retry_count ?? 0)) }}</p>
                                    @if (!empty($event->error_message))
                                        <p class="small" style="margin:3px 0 0;color:#6d1111;">Last error: {{ trim((string) $event->error_message) }}</p>
                                    @endif
                                </div>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end;">
                                    @if ($eventId > 0 && in_array($eventStatus, ['failed', 'error', 'dead_letter'], true))
                                        <form method="post" action="{{ url('/vendor/distribution/events/' . $eventId . '/retry') }}" style="margin:0;">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary" style="padding:6px 10px;">{{ $eventDirection === 'outbound' ? 'Requeue (outbound)' : 'Retry (inbound)' }}</button>
                                        </form>
                                    @endif
                                    @if ($eventId > 0 && $eventDirection === 'outbound')
                                        <form method="post" action="{{ url('/vendor/distribution/events/' . $eventId . '/dispatch-now') }}" style="margin:0;">
                                            @csrf
                                            <button type="submit" class="btn" style="padding:6px 10px;">Dispatch now</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                <div style="margin-top:10px;display:flex;justify-content:flex-end;">
                    <a href="/vendor?page=distribution&mode=advanced&dist_tab=issues" class="btn btn-secondary">Run quick sync check</a>
                </div>
            @else
                <p class="small" style="margin:8px 0 0;color:#4f667b;">{{ $failedOrRetryingEvents->isNotEmpty() ? 'Issues exist. Open this step to retry or requeue.' : 'Complete previous steps first, then run quick sync check.' }}</p>
                <div style="margin-top:8px;display:flex;justify-content:flex-end;">
                    <a class="btn" href="/vendor?page=setup&mode=simple&step=sync#step-sync">Open step</a>
                </div>
            @endif
        </article>

        <article id="step-go-live" class="card" style="border:1px solid #d8e2eb;border-radius:12px;padding:12px;">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;">
                <p class="label" style="margin:0;">4. Go live check</p>
                <span class="status-pill {{ $goLiveReady ? 'ok' : 'warn' }}">{{ $goLiveReady ? 'READY' : 'NOT READY' }}</span>
            </div>

            @if ($stepState !== 'go-live')
                <p class="small" style="margin:8px 0 0;color:#4f667b;">Open this step to review PASS/BLOCKED items and complete launch checks.</p>
                <div style="margin-top:8px;display:flex;justify-content:flex-end;">
                    <a class="btn" href="/vendor?page=setup&mode=simple&step=go-live#step-go-live">Open step</a>
                </div>
            @else
                <div style="display:grid;gap:8px;margin-top:10px;">
                    @foreach ($readinessChecks as $check)
                        @php $passed = (bool) ($check['passed'] ?? false); @endphp
                        <div style="border:1px solid {{ $passed ? '#a0ddb5' : '#f0d080' }};border-radius:10px;padding:8px;background:#fff;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px;align-items:center;">
                            <div>
                                <p class="small" style="margin:0;font-weight:700;color:{{ $passed ? '#0b5c2a' : '#6b4a00' }};">{{ $passed ? 'PASS' : 'BLOCKED' }}</p>
                                <p class="small" style="margin:3px 0 0;color:#243746;">{{ trim((string) ($check['label'] ?? 'Readiness check')) }}</p>
                                <p class="small" style="margin:2px 0 0;color:#516071;">{{ trim((string) ($check['detail'] ?? '')) }}</p>
                            </div>
                            @if (!$passed)
                                <a class="btn" href="{{ $nextActionHref }}" style="padding:6px 10px;">Fix now</a>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div style="margin-top:10px;display:flex;justify-content:flex-end;">
                    @if ($goLiveReady)
                        <a href="/vendor?page=distribution&mode=advanced&dist_tab=connections" class="btn btn-secondary">Enable full auto-sync</a>
                    @else
                        <span class="small" style="color:#7d4b0a;font-weight:700;">NOT READY (amber)</span>
                    @endif
                </div>
            @endif
        </article>
    </div>
</section>
