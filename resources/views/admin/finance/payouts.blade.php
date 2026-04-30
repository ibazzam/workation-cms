{{--
  resources/views/admin/finance/payouts.blade.php
  ADMIN ONLY — payout batch list and batch-build trigger.
  source_medium is INTERNAL — color-coded for admin clarity; never exposed to vendors.
  MIB = blue (local MVR, fast), BML = green (local MVR, fast), Stripe = purple (foreign USD, delayed 2-7 days).
--}}
@php
    $mediumColors = ['mib'=>'chip-blue','bml'=>'chip-ok','stripe'=>'chip-purple'];
    $bandColors   = ['local_mvr'=>'chip-teal','foreign_usd'=>'chip-warn'];
    $statusColors = [
        'queued'     =>'chip-grey',
        'processing' =>'chip-warn',
        'sent'       =>'chip-blue',
        'confirmed'  =>'chip-ok',
        'failed'     =>'chip-err',
        'cancelled'  =>'chip-err',
    ];
@endphp
@include('admin.finance._layout', [
    'pageTitle'    => 'Payout Batches',
    'pageSubtitle' => 'Build, track and confirm vendor payout batches — strictly separated by medium and currency band.',
    'activeNav'    => 'payouts',
])

{{-- Eligible alert --}}
@if(($eligibleCount ?? 0) > 0)
<div class="alert-banner warn">
  <strong>{{ $eligibleCount }} reservations</strong> are eligible for payout but not yet batched.
  Use the "Build Batches" button below to queue them.
</div>
@endif

{{-- Pending summary (INTERNAL) --}}
@if(isset($pendingSummary) && $pendingSummary->isNotEmpty())
<div class="section">
  <p class="section-title">Pending by Medium &amp; Band <span class="internal-label">Internal</span></p>
  <div class="stat-grid">
    @foreach($pendingSummary as $row)
    <div class="stat-card">
      <p class="stat-label">
        <span class="chip {{ $mediumColors[$row->source_medium] ?? 'chip-grey' }}">{{ strtoupper($row->source_medium) }}</span>
        &nbsp;<span class="chip {{ $bandColors[$row->currency_band] ?? 'chip-grey' }}">{{ $row->currency_band }}</span>
      </p>
      <p class="stat-value">{{ number_format($row->total_net, 2) }}</p>
      <p style="margin:4px 0 0;font-size:.76rem;color:var(--muted);">{{ $row->batch_count }} batches · {{ $row->currency }} · {{ $row->status }}</p>
    </div>
    @endforeach
  </div>
</div>
@endif

{{-- Build batches action --}}
<div class="section">
  <p class="section-title">Build Batches</p>
  <p style="font-size:.84rem;color:var(--muted);margin:0 0 10px;">
    Groups all eligible reservations (paid, confirmed/completed, not yet payout-queued) by medium and currency band.
    Stripe batches will settle later due to foreign bank processing times.
  </p>
  <form method="POST" action="/portal/admin/finance/payouts/build" onsubmit="return confirm('Build payout batches for today? This cannot be undone.')">
    @csrf
    <label style="display:flex;gap:8px;align-items:flex-start;margin:0 0 10px;font-size:.82rem;color:var(--muted);">
      <input type="checkbox" name="combine_by_vendor_currency" value="1" style="margin-top:2px;">
      <span>
        Combine same-vendor payouts by currency across gateways/customers.
        <strong style="color:#274155;">Internal mode:</strong> source medium will be marked as <strong>MIXED</strong>.
      </span>
    </label>
    <button type="submit" class="btn-primary">Build Batches for Today</button>
  </form>
</div>

{{-- Filter + Batch list --}}
<div class="section">
  <p class="section-title">Batches</p>

  <form method="GET" action="/portal/admin/finance/payouts" class="filter-bar">
    <select name="status">
      <option value="">All statuses</option>
      @foreach(['queued','processing','sent','confirmed','failed','cancelled'] as $st)
      <option value="{{ $st }}" @selected(($filters['status']??'') === $st)>{{ ucfirst($st) }}</option>
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
    <input type="date" name="batch_date" value="{{ $filters['batch_date']??'' }}">
    <button type="submit">Filter</button>
    <a href="/portal/admin/finance/payouts" style="font-size:.8rem;color:var(--muted);align-self:center;">Reset</a>
  </form>

  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>Batch Ref</th>
          <th>Date</th>
          <th>Medium <span class="internal-label">Internal</span></th>
          <th>Band <span class="internal-label">Internal</span></th>
          <th>Currency</th>
          <th>Vendors</th>
          <th>Net Payout</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($batches ?? [] as $batch)
        <tr>
          <td><a href="/portal/admin/finance/payouts/{{ $batch->id }}" style="color:#155f83;font-weight:700;font-size:.78rem;">{{ $batch->batch_ref }}</a></td>
          <td style="font-size:.78rem;">{{ $batch->batch_date }}</td>
          <td><span class="chip {{ $mediumColors[$batch->source_medium] ?? 'chip-grey' }}">{{ strtoupper($batch->source_medium) }}</span></td>
          <td><span class="chip {{ $bandColors[$batch->currency_band] ?? 'chip-grey' }}">{{ $batch->currency_band }}</span></td>
          <td>{{ $batch->currency }}</td>
          <td>{{ $batch->item_count }}</td>
          <td style="font-weight:700;font-family:monospace;">{{ number_format($batch->net_payout_amount, 2) }}</td>
          <td><span class="chip {{ $statusColors[$batch->status] ?? 'chip-grey' }}">{{ $batch->status }}</span></td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              @if($batch->status === 'queued')
              <form method="POST" action="/portal/admin/finance/payouts/{{ $batch->id }}/send" style="display:inline;" onsubmit="return confirmSend(this)">
                @csrf
                <input name="bank_reference" placeholder="Bank ref" required style="border:1px solid #c8d3df;border-radius:6px;padding:4px 7px;font-size:.76rem;width:110px;">
                <input type="date" name="expected_payout_date" title="Expected payout date" style="border:1px solid #c8d3df;border-radius:6px;padding:4px 7px;font-size:.76rem;">
                <button type="submit" class="btn-warn" style="padding:5px 9px;font-size:.76rem;">Mark Sent</button>
              </form>
              @elseif($batch->status === 'processing')
              <form method="POST" action="/portal/admin/finance/payouts/{{ $batch->id }}/confirm" style="display:inline;" onsubmit="return confirm('Confirm batch {{ $batch->batch_ref }} as settled?')">
                @csrf
                <button type="submit" class="btn-ok" style="padding:5px 9px;font-size:.76rem;">Confirm Settled</button>
              </form>
              @endif
              <a href="/portal/admin/finance/payouts/{{ $batch->id }}" style="font-size:.76rem;color:#155f83;align-self:center;">View</a>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="9" style="color:var(--muted);padding:16px;">No batches found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

  </div>
</div>
<script>
function confirmSend(form){
  var ref = form.querySelector('[name=bank_reference]').value.trim();
  if(!ref){ alert('Bank reference is required.'); return false; }
  return confirm('Mark this batch as sent with bank reference: ' + ref + '?');
}
</script>
</body>
</html>