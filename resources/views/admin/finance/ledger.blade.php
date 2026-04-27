{{--
  resources/views/admin/finance/ledger.blade.php
  ADMIN ONLY — full detail view of the finance_ledger event log.
  source_medium and currency_band are INTERNAL fields — never shown to vendors.
--}}
@php
    $eventColors = [
        'payment_collected'      => 'chip-ok',
        'commission_deducted'    => 'chip-warn',
        'gateway_fee_deducted'   => 'chip-warn',
        'vendor_payout_queued'   => 'chip-blue',
        'vendor_payout_sent'     => 'chip-teal',
        'vendor_payout_confirmed'=> 'chip-ok',
        'refund_initiated'       => 'chip-err',
        'refund_completed'       => 'chip-err',
        'dispute_opened'         => 'chip-purple',
        'dispute_resolved'       => 'chip-teal',
        'dispute_lost'           => 'chip-err',
    ];
    $mediumColors = [
      'mib' => 'chip-blue',
      'bml' => 'chip-ok',
      'stripe' => 'chip-purple',
    ];
    $bandColors = [
        'local_mvr'   => 'chip-teal',
        'foreign_usd' => 'chip-warn',
    ];
@endphp
@include('admin.finance._layout', [
    'pageTitle'    => 'Finance Ledger',
    'pageSubtitle' => 'Append-only event log — every financial event recorded here. Admin read-only.',
    'activeNav'    => 'ledger',
])

{{-- Medium summary (INTERNAL) --}}
@if(isset($mediumSummary) && $mediumSummary->isNotEmpty())
<div class="section">
  <p class="section-title">Medium & Band Breakdown <span class="internal-label">Internal</span></p>
  <div class="stat-grid">
    @foreach($mediumSummary as $row)
    <div class="stat-card">
      <p class="stat-label">
        <span class="chip {{ $mediumColors[$row->source_medium] ?? 'chip-grey' }}">{{ strtoupper($row->source_medium) }}</span>
        &nbsp;<span class="chip {{ $bandColors[$row->currency_band] ?? 'chip-grey' }}">{{ $row->currency_band }}</span>
      </p>
      <p class="stat-value">{{ number_format($row->total_amount, 2) }} <small style="font-size:.65rem;font-weight:400;">{{ $row->currency ?? '' }}</small></p>
      <p style="margin:4px 0 0;font-size:.76rem;color:var(--muted);">{{ number_format($row->event_count) }} events</p>
    </div>
    @endforeach
  </div>
</div>
@endif

{{-- Event type summary --}}
@if(isset($eventSummary) && $eventSummary->isNotEmpty())
<div class="section">
  <p class="section-title">Event Type Summary</p>
  <div class="stat-grid">
    @foreach($eventSummary as $row)
    <div class="stat-card">
      <p class="stat-label"><span class="chip {{ $eventColors[$row->event_type] ?? 'chip-grey' }}">{{ str_replace('_',' ',$row->event_type) }}</span></p>
      <p class="stat-value">{{ number_format($row->total_amount, 2) }}</p>
      <p style="margin:4px 0 0;font-size:.76rem;color:var(--muted);">{{ number_format($row->event_count) }} events</p>
    </div>
    @endforeach
  </div>
</div>
@endif

{{-- Filter + Events table --}}
<div class="section">
  <p class="section-title">Events</p>

  <form method="GET" action="/portal/admin/finance/ledger" class="filter-bar">
    <select name="event_type">
      <option value="">All event types</option>
      @foreach(['payment_collected','commission_deducted','gateway_fee_deducted','vendor_payout_queued','vendor_payout_sent','vendor_payout_confirmed','refund_initiated','refund_completed','dispute_opened','dispute_resolved','dispute_lost'] as $et)
      <option value="{{ $et }}" @selected(($filters['event_type']??'') === $et)>{{ str_replace('_',' ',$et) }}</option>
      @endforeach
    </select>
    <select name="medium"> {{-- INTERNAL --}}
      <option value="">All mediums</option>
      <option value="mib"    @selected(($filters['medium']??'') === 'mib')>MIB</option>
      <option value="bml"    @selected(($filters['medium']??'') === 'bml')>BML</option>
      <option value="stripe" @selected(($filters['medium']??'') === 'stripe')>Stripe</option>
    </select>
    <select name="band"> {{-- INTERNAL --}}
      <option value="">All bands</option>
      <option value="local_mvr"   @selected(($filters['band']??'') === 'local_mvr')>Local MVR</option>
      <option value="foreign_usd" @selected(($filters['band']??'') === 'foreign_usd')>Foreign USD</option>
    </select>
    <input type="number" name="vendor_id" placeholder="Vendor ID" value="{{ $filters['vendor_id']??'' }}" style="width:110px;">
    <input type="date" name="date_from" value="{{ $filters['date_from']??'' }}">
    <input type="date" name="date_to"   value="{{ $filters['date_to']??'' }}">
    <button type="submit">Filter</button>
    <a href="/portal/admin/finance/ledger" style="font-size:.8rem;color:var(--muted);align-self:center;">Reset</a>
  </form>

  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>ID</th>
          <th>Event</th>
          <th>Source <span class="internal-label">Internal</span></th>
          <th>Band <span class="internal-label">Internal</span></th>
          <th>Amount</th>
          <th>Currency</th>
          <th>Reservation</th>
          <th>Vendor</th>
          <th>Batch</th>
          <th>Actor</th>
          <th>Occurred At</th>
        </tr>
      </thead>
      <tbody>
        @forelse($events ?? [] as $ev)
        <tr>
          <td style="color:var(--muted);">{{ $ev->id }}</td>
          <td><span class="chip {{ $eventColors[$ev->event_type] ?? 'chip-grey' }}">{{ str_replace('_',' ',$ev->event_type) }}</span></td>
          <td><span class="chip {{ $mediumColors[$ev->source_medium] ?? 'chip-grey' }}">{{ strtoupper($ev->source_medium) }}</span></td>
          <td><span class="chip {{ $bandColors[$ev->currency_band] ?? 'chip-grey' }}">{{ $ev->currency_band }}</span></td>
          <td style="font-weight:700;font-family:monospace;">{{ number_format($ev->amount,2) }}</td>
          <td>{{ $ev->currency }}</td>
          <td>{{ $ev->reservation_id }}</td>
          <td style="font-size:.76rem;">
            {{ $ev->vendor_name ?? $ev->vendor_user_id }}<br>
            <span style="color:var(--muted);">{{ $ev->vendor_email ?? '' }}</span>
          </td>
          <td>
            @if($ev->batch_id)
              <a href="/portal/admin/finance/payouts/{{ $ev->batch_id }}" style="font-size:.76rem;color:#155f83;">{{ $ev->batch_id }}</a>
            @else
              <span style="color:var(--muted);">—</span>
            @endif
          </td>
          <td style="font-size:.76rem;">{{ $ev->actor_role ?? '—' }}</td>
          <td style="font-size:.76rem;white-space:nowrap;">{{ $ev->occurred_at }}</td>
        </tr>
        @empty
        <tr><td colspan="11" style="color:var(--muted);padding:16px;">No events found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

  </div>{{-- .shell --}}
</div>{{-- .page --}}
</body>
</html>
