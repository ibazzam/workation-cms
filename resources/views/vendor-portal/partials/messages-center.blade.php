@php
    $reservationScope = strtolower(trim((string) request()->query('scope', 'all')));
    if (!in_array($reservationScope, ['active', 'pending', 'history', 'all'], true)) {
        $reservationScope = 'all';
    }

    $propertyById = collect($vendorProperties ?? collect())->keyBy(static fn ($property) => (int) ($property->id ?? 0));

    $reservationRows = collect($vendorReservations ?? collect())
        ->map(static function ($reservation) use ($propertyById) {
            $notes = json_decode((string) ($reservation->notes ?? ''), true);
            if (!is_array($notes)) {
                $notes = [];
            }

            $reservationId = (int) ($reservation->id ?? 0);
            $propertyId = (int) ($reservation->vendor_property_id ?? 0);
            $property = $propertyById->get($propertyId);

            $categoryKey = vendorPortalCanonicalCategory((string) ($notes['category_key'] ?? ''));
            if (!is_string($categoryKey) || $categoryKey === '') {
                $categoryKey = vendorPortalCanonicalCategory((string) ($property->listing_category ?? ''));
            }

            $targetLabel = trim((string) (
                $notes['room_name']
                ?? $notes['service_label']
                ?? ($property->name ?? '')
            ));
            if ($targetLabel === '') {
                $targetLabel = 'Booking thread';
            }

            return [
                'id' => $reservationId,
                'reservation_code' => 'RSV-' . str_pad((string) $reservationId, 6, '0', STR_PAD_LEFT),
                'customer_name' => trim((string) ($reservation->customer_name ?? 'Guest')),
                'payment_status' => strtolower(trim((string) ($reservation->payment_status ?? 'unpaid'))),
                'status' => strtolower(trim((string) ($reservation->status ?? 'pending'))),
                'category_key' => $categoryKey,
                'target_label' => $targetLabel,
                'created_at' => (string) ($reservation->created_at ?? ''),
            ];
        })
        ->filter(static fn (array $row): bool => (int) ($row['id'] ?? 0) > 0)
        ->values();

    $forcedCategory = vendorPortalCanonicalCategory((string) ($forcedListingCategory ?? ''));
    if (is_string($forcedCategory) && $forcedCategory !== '') {
        $reservationRows = $reservationRows
            ->filter(static fn (array $row): bool => vendorPortalCanonicalCategory((string) ($row['category_key'] ?? '')) === $forcedCategory)
            ->values();
    }

    $reservationRows = $reservationRows
        ->filter(static function (array $row) use ($reservationScope): bool {
            $status = strtolower(trim((string) ($row['status'] ?? 'pending')));
            $paymentStatus = strtolower(trim((string) ($row['payment_status'] ?? 'unpaid')));

            return match ($reservationScope) {
                'active' => in_array($status, ['confirmed', 'upcoming', 'checked_in', 'checked_out'], true) && $paymentStatus === 'paid',
                'pending' => in_array($status, ['pending', 'cancel_requested'], true) || in_array($paymentStatus, ['unpaid', 'partially_paid'], true),
                'history' => in_array($status, ['cancel_requested', 'cancelled', 'completed', 'expired', 'failed', 'rejected'], true) || in_array($paymentStatus, ['refunded'], true),
                default => true,
            };
        })
        ->sortByDesc(static fn (array $row): string => (string) ($row['created_at'] ?? ''))
        ->values();

    $messageReservationIds = $reservationRows
        ->pluck('id')
        ->map(static fn ($id) => (int) $id)
        ->filter(static fn (int $id): bool => $id > 0)
        ->unique()
        ->values();

    $vendorMessagesByReservation = collect();
    if ($messageReservationIds->isNotEmpty() && \Illuminate\Support\Facades\Schema::hasTable('reservation_messages')) {
        $vendorMsgRows = \Illuminate\Support\Facades\DB::table('reservation_messages')
            ->whereIn('reservation_id', $messageReservationIds->all())
            ->orderBy('created_at')
            ->get(['id', 'reservation_id', 'sender_role', 'sender_display_name', 'message_text', 'is_flagged', 'vendor_read', 'created_at']);

        $vendorMessagesByReservation = $vendorMsgRows->groupBy(static fn ($m) => (int) ($m->reservation_id ?? 0));

        $unreadForVendor = $vendorMsgRows
            ->where('sender_role', 'customer')
            ->where('vendor_read', false)
            ->pluck('id')
            ->all();
        if (!empty($unreadForVendor)) {
            \Illuminate\Support\Facades\DB::table('reservation_messages')
                ->whereIn('id', $unreadForVendor)
                ->update(['vendor_read' => true]);
        }
    }

    $baseMessagesQuery = '/vendor/messages' . ($forcedListingCategory !== '' ? ('?category=' . urlencode((string) $forcedListingCategory) . '&scope=') : '?scope=');
@endphp

<section class="card ops-section" aria-label="Vendor messages workspace" data-panel-group="reservations">
    <div class="ops-header">
        <p class="ops-title">Guest Communication Channel</p>
        <span class="ops-chip">{{ $reservationRows->count() }} reservation threads</span>
    </div>

    <div class="panel-links" aria-label="Message scope filter">
        <a href="{{ $baseMessagesQuery . 'active' }}">Active</a>
        <a href="{{ $baseMessagesQuery . 'pending' }}">Pending</a>
        <a href="{{ $baseMessagesQuery . 'history' }}">History</a>
        <a href="{{ $baseMessagesQuery . 'all' }}">All</a>
    </div>

    @if ($reservationRows->isEmpty())
        <p class="ops-empty" style="margin-top:10px;">No reservation-linked messages found in this scope.</p>
    @else
        <section class="vendor-message-center" aria-label="Reservation message center">
            <div class="vendor-message-center-head">
                <div class="vendor-message-center-title">Messages Workspace</div>
                <div class="vendor-message-center-note"><i class="fa-solid fa-link"></i> Each thread is linked to booking reference and reservation ID</div>
            </div>

            <div class="vendor-message-center-list">
                @foreach ($reservationRows->take(60) as $reservationRow)
                    @php
                        $rsvId = (int) ($reservationRow['id'] ?? 0);
                        $rsvMessages = $vendorMessagesByReservation[$rsvId] ?? collect();
                        $subject = (string) ($reservationRow['reservation_code'] ?? ('RSV-' . str_pad((string) $rsvId, 6, '0', STR_PAD_LEFT)))
                            . ' · '
                            . trim((string) ($reservationRow['target_label'] ?? 'Booking thread'));
                    @endphp
                    <article class="vendor-message-thread" id="vendor-msg-thread-{{ $rsvId }}">
                        <header class="vendor-message-thread-head">
                            <div>
                                <div class="vendor-message-thread-subject">Subject: {{ $subject }}</div>
                                <div class="vendor-message-thread-meta">Guest: {{ (string) ($reservationRow['customer_name'] ?? 'Guest') }} · Reservation ID: {{ $rsvId }}</div>
                            </div>
                            <a class="vendor-msg-open-center" href="/vendor/reservations{{ $forcedListingCategory !== '' ? ('?category=' . urlencode((string) $forcedListingCategory)) : '' }}">Open reservations</a>
                        </header>
                        <div class="vendor-message-thread-body">
                            @if ($rsvMessages->isNotEmpty())
                                <div class="vendor-msg-list">
                                    @foreach ($rsvMessages as $vMsg)
                                        @php
                                            $vMsgRole = (string) ($vMsg->sender_role ?? '');
                                            $vMsgName = e(trim((string) ($vMsg->sender_display_name ?? ($vMsgRole === 'vendor' ? 'You' : 'Guest'))));
                                            $vMsgText = e(trim((string) ($vMsg->message_text ?? '')));
                                            $vMsgDate = trim((string) ($vMsg->created_at ?? ''));
                                            $vMsgDate = $vMsgDate !== '' ? \Carbon\Carbon::parse($vMsgDate)->format('M j, g:i A') : '';
                                            $vMsgFlagged = (bool) ($vMsg->is_flagged ?? false);
                                        @endphp
                                        <div class="vendor-msg-bubble vendor-msg-bubble--{{ $vMsgRole === 'vendor' ? 'sent' : 'received' }}{{ $vMsgFlagged ? ' vendor-msg-bubble--flagged' : '' }}">
                                            <span class="vendor-msg-meta">{{ $vMsgName }}@if ($vMsgDate !== '') · {{ $vMsgDate }}@endif</span>
                                            <span class="vendor-msg-body">{{ $vMsgText }}</span>
                                            @if ($vMsgFlagged)
                                                <span class="vendor-msg-flag-notice"><i class="fa-solid fa-flag"></i> Flagged</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="vendor-msg-none">No messages yet for this booking.</span>
                            @endif

                            <form method="POST" action="/portal/vendor/reservations/{{ $rsvId }}/messages" class="vendor-msg-reply-form">
                                @csrf
                                <textarea name="message_text" class="vendor-msg-textarea" rows="3" maxlength="2000" placeholder="Message guest about {{ $subject }} (no contact details)" required></textarea>
                                <div class="vendor-msg-reply-footer">
                                    <span class="vendor-msg-policy-note"><i class="fa-solid fa-lock"></i> Default subject: {{ $subject }}</span>
                                    <button type="submit" class="btn btn-secondary vendor-msg-send-btn">Send</button>
                                </div>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</section>
