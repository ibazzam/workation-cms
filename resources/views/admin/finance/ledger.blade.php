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
        'vendor_payout_on_hold'  => 'chip-err',
        'refund_initiated'       => 'chip-err',
        'refund_completed'       => 'chip-err',
        'dispute_opened'         => 'chip-purple',
        'dispute_resolved'       => 'chip-teal',
        'dispute_lost'           => 'chip-err',
        'website_maintenance_expense' => 'chip-warn',
        'domain_expense'         => 'chip-warn',
        'subscription_expense'   => 'chip-warn',
        'salary_expense'         => 'chip-warn',
        'operations_expense'     => 'chip-warn',
    ];
    $mediumColors = [
      'mib' => 'chip-blue',
      'bml' => 'chip-ok',
      'stripe' => 'chip-purple',
      'internal' => 'chip-grey',
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

@if(session('success'))
<div class="alert-banner ok">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert-banner err">{{ session('error') }}</div>
@endif

{{-- Revenue/expense transparency snapshot --}}
@if(isset($financialSnapshot) && $financialSnapshot->isNotEmpty())
<div class="section">
  <p class="section-title">Revenue, Payout, Fee & Expense Snapshot</p>
  <div class="stat-grid">
    @foreach($financialSnapshot as $row)
    <div class="stat-card">
      <p class="stat-label">Currency: {{ $row->currency }}</p>
      <p style="margin:0;font-size:.78rem;color:var(--muted);">Revenue Collected: <strong style="color:#0b5c2a;">{{ number_format((float) ($row->revenue_collected ?? 0), 2) }}</strong></p>
      <p style="margin:4px 0 0;font-size:.78rem;color:var(--muted);">Commission Deductions: <strong>{{ number_format((float) ($row->commission_deducted ?? 0), 2) }}</strong></p>
      <p style="margin:4px 0 0;font-size:.78rem;color:var(--muted);">Gateway Fee Deductions: <strong>{{ number_format((float) ($row->gateway_fee_deducted ?? 0), 2) }}</strong></p>
      <p style="margin:4px 0 0;font-size:.78rem;color:var(--muted);">Payouts Queued: <strong>{{ number_format((float) ($row->vendor_payout_queued ?? 0), 2) }}</strong></p>
      <p style="margin:4px 0 0;font-size:.78rem;color:var(--muted);">Refunds: <strong>{{ number_format((float) ($row->refunds ?? 0), 2) }}</strong></p>
      <p style="margin:4px 0 0;font-size:.78rem;color:var(--muted);">Operating Expenses: <strong>{{ number_format((float) ($row->operating_expenses ?? 0), 2) }}</strong></p>
      <p style="margin:8px 0 0;font-size:.82rem;">Net Position: <strong style="color:{{ ((float) ($row->net_after_payout_and_expenses ?? 0)) >= 0 ? '#0b5c2a' : '#6d1111' }};">{{ number_format((float) ($row->net_after_payout_and_expenses ?? 0), 2) }}</strong></p>
    </div>
    @endforeach
  </div>
</div>
@endif

{{-- Manual expense update form --}}
<div class="section">
  <p class="section-title">Record Finance Expense (Transparent Update)</p>
  <p style="font-size:.83rem;color:var(--muted);margin:0 0 10px;">
    Record website maintenance fees, domain fees, monthly subscriptions, staff salaries, and other operating costs.
    Each entry is appended to the immutable finance ledger for audit transparency.
  </p>
  <form method="POST" action="/portal/admin/finance/ledger/expenses" class="filter-bar">
    @csrf
    <select name="event_type" required>
      @foreach(($expenseEventTypes ?? []) as $expenseType)
      <option value="{{ $expenseType }}">{{ str_replace('_',' ', $expenseType) }}</option>
      @endforeach
    </select>
    <input type="number" step="0.01" min="0.01" name="amount" placeholder="Amount" required style="width:130px;">
    <select name="currency" required>
      <option value="MVR">MVR</option>
      <option value="USD">USD</option>
    </select>
    <input type="text" name="reference_id" placeholder="Invoice/Bank Txn Ref" style="min-width:180px;">
    <input type="date" name="occurred_at">
    <input type="text" name="notes" placeholder="Description / notes" required style="min-width:260px;">
    <button type="submit" class="btn-primary">Record Expense</button>
  </form>
</div>

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
    <input type="month" name="report_month" value="{{ $filters['report_month']??'' }}" title="Report month">
    <select name="event_type">
      <option value="">All event types</option>
      @foreach(($availableEventTypes ?? []) as $et)
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
    <a href="/portal/admin/finance/ledger/export/csv?{{ http_build_query([
      'report_month' => $filters['report_month'] ?? '',
      'event_type' => $filters['event_type'] ?? '',
      'medium' => $filters['medium'] ?? '',
      'band' => $filters['band'] ?? '',
      'vendor_id' => $filters['vendor_id'] ?? '',
      'date_from' => $filters['date_from'] ?? '',
      'date_to' => $filters['date_to'] ?? '',
    ]) }}" class="btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;">Export CSV</a>
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
          <th>Reference</th>
          <th>Notes</th>
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
          <td style="font-size:.75rem;">{{ (string) ($ev->gateway_reference ?? '') !== '' ? (string) ($ev->gateway_reference ?? '') : '—' }}</td>
          <td style="font-size:.75rem;max-width:250px;">{{ (string) ($ev->notes ?? '') !== '' ? (string) ($ev->notes ?? '') : '—' }}</td>
          <td style="font-size:.76rem;">{{ $ev->actor_role ?? '—' }}</td>
          <td style="font-size:.76rem;white-space:nowrap;">{{ $ev->occurred_at }}</td>
        </tr>
        @empty
        <tr><td colspan="13" style="color:var(--muted);padding:16px;">No events found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

  </div>{{-- .shell --}}
</div>{{-- .page --}}
</body>
</html>
