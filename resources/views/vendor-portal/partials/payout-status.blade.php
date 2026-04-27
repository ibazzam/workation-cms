{{--
  resources/views/vendor-portal/partials/payout-status.blade.php

  VENDOR-FACING PARTIAL — source-blind payout status per reservation.

  SECURITY CONTRACT — the following columns MUST NEVER appear here:
    - payout_source_medium  (internal: which bank/gateway we used)
    - source_medium         (internal: MIB / BML / Stripe)
    - currency_band         (internal: local_mvr / foreign_usd)
    - payment_gateway       (internal)
    - gateway_reference     (internal)
    - batch_ref / batch_id  (internal)
    - bank account details  (internal)
    - commission breakdown  (internal — shown as "platform fee" only)

  Variables provided by the caller:
    $payoutStatusRows — collection of objects from vendor_reservations:
        id, reservation_code (or similar), check_in, check_out,
        payout_status, payout_currency, vendor_payout_amount,
        has_open_dispute, has_refund_case
        (payout_source_medium MUST be excluded from the query)
--}}

@php
    $statusColors = [
        'queued'     => ['bg'=>'#eef5ff','border'=>'#c6d9f5','text'=>'#1a3f6b','label'=>'Queued'],
        'processing' => ['bg'=>'#fff8e5','border'=>'#f0d080','text'=>'#6b4a00','label'=>'Processing'],
        'paid'       => ['bg'=>'#e6f9ef','border'=>'#a0ddb5','text'=>'#0b5c2a','label'=>'Paid'],
        'on_hold'    => ['bg'=>'#fff0ef','border'=>'#f0b7b3','text'=>'#6d1111','label'=>'On Hold'],
        'cancelled'  => ['bg'=>'#f4f4f4','border'=>'#d0d5db','text'=>'#3d4d5a','label'=>'Cancelled'],
    ];
    $defaultStatus = ['bg'=>'#f4f4f4','border'=>'#d0d5db','text'=>'#3d4d5a','label'=>'Pending'];
@endphp

<section class="card ops-section" aria-label="Payout status per reservation" data-panel-group="billing">
    <div class="ops-header">
        <p class="ops-title">Payout Status</p>
        <span class="ops-chip">Per reservation</span>
    </div>

    <p class="small" style="margin:0 0 12px;">
        Each reservation shows the payout status of your share after the platform fee is deducted.
        Payouts are processed by Workation and credited to your registered bank account.
        You will be notified once your payout has been sent.
    </p>

    @if(isset($payoutStatusRows) && $payoutStatusRows->isNotEmpty())

    <div class="ops-table-wrap">
        <table class="ops-table" aria-label="Payout status table">
            <thead>
                <tr>
                    <th>Reservation</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Your Payout</th>
                    <th>Currency</th>
                    <th>Payout Status</th>
                    <th>Flags</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payoutStatusRows as $row)
                @php
                    $st     = $statusColors[$row->payout_status ?? ''] ?? $defaultStatus;
                    $label  = $st['label'];
                @endphp
                <tr>
                    <td style="font-weight:700;font-size:.82rem;">
                        {{ $row->reservation_code ?? '#' . $row->id }}
                    </td>
                    <td style="font-size:.8rem;">{{ $row->check_in ?? '—' }}</td>
                    <td style="font-size:.8rem;">{{ $row->check_out ?? '—' }}</td>
                    <td style="font-weight:700;font-family:monospace;">
                        @if($row->vendor_payout_amount)
                            {{ number_format($row->vendor_payout_amount, 2) }}
                        @else
                            <span style="color:#5b6778;">—</span>
                        @endif
                    </td>
                    <td style="font-size:.8rem;">{{ $row->payout_currency ?? '—' }}</td>
                    <td>
                        <span style="
                            display:inline-block;
                            border-radius:999px;
                            padding:3px 10px;
                            font-size:.73rem;
                            font-weight:700;
                            background:{{ $st['bg'] }};
                            border:1px solid {{ $st['border'] }};
                            color:{{ $st['text'] }};
                        ">{{ $label }}</span>
                    </td>
                    <td>
                        {{-- Dispute flag — shown as boolean only, no medium detail --}}
                        @if($row->has_open_dispute ?? false)
                        <span style="display:inline-block;border-radius:6px;padding:2px 7px;font-size:.7rem;font-weight:700;background:#fff0ef;border:1px solid #f0b7b3;color:#6d1111;margin-bottom:3px;">
                            ⚠ Dispute in progress
                        </span>
                        @endif

                        {{-- Refund flag — boolean only --}}
                        @if($row->has_refund_case ?? false)
                        <span style="display:inline-block;border-radius:6px;padding:2px 7px;font-size:.7rem;font-weight:700;background:#fff8e5;border:1px solid #f0d080;color:#6b4a00;">
                            Refund case open
                        </span>
                        @endif

                        @if(!($row->has_open_dispute ?? false) && !($row->has_refund_case ?? false))
                            <span style="color:#9aabb8;font-size:.76rem;">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @else
    <p class="ops-empty">No payout records yet. Reservations will appear here once payment has been collected.</p>
    @endif

    <p class="small" style="margin:12px 0 0;color:#7a8ea0;">
        <strong>Platform fee</strong> covers transaction handling and is deducted from the gross booking amount before payout.
        Contact support for any questions about a specific payout.
    </p>
</section>
