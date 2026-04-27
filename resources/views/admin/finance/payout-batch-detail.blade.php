{{--
  resources/views/admin/finance/payout-batch-detail.blade.php
  ADMIN ONLY — single payout batch detail with line items.
  source_medium is INTERNAL.
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
    $itemStatusColors = ['pending'=>'chip-grey','sent'=>'chip-blue','confirmed'=>'chip-ok','failed'=>'chip-err'];
@endphp
@include('admin.finance._layout', [
    'pageTitle'    => 'Batch ' . ($batch->batch_ref ?? ''),
    'pageSubtitle' => 'Individual vendor payout items for this batch.',
    'activeNav'    => 'payouts',
])

{{-- Batch meta --}}
@if(isset($batch))
<div class="section">
  <p class="section-title">Batch Info</p>
  <div class="stat-grid">
    <div class="stat-card">
      <p class="stat-label">Reference</p>
      <p class="stat-value" style="font-size:.95rem;word-break:break-all;">{{ $batch->batch_ref }}</p>
    </div>
    <div class="stat-card">
      <p class="stat-label">Date</p>
      <p class="stat-value" style="font-size:.95rem;">{{ $batch->batch_date }}</p>
    </div>
    <div class="stat-card">
      <p class="stat-label">Medium <span class="internal-label">Internal</span></p>
      <p class="stat-value" style="font-size:.95rem;"><span class="chip {{ $mediumColors[$batch->source_medium] ?? 'chip-grey' }}">{{ strtoupper($batch->source_medium) }}</span></p>
    </div>
    <div class="stat-card">
      <p class="stat-label">Band <span class="internal-label">Internal</span></p>
      <p class="stat-value" style="font-size:.95rem;"><span class="chip {{ $bandColors[$batch->currency_band] ?? 'chip-grey' }}">{{ $batch->currency_band }}</span></p>
    </div>
    <div class="stat-card">
      <p class="stat-label">Status</p>
      <p class="stat-value" style="font-size:.95rem;"><span class="chip {{ $statusColors[$batch->status] ?? 'chip-grey' }}">{{ $batch->status }}</span></p>
    </div>
    <div class="stat-card">
      <p class="stat-label">Gross</p>
      <p class="stat-value">{{ number_format($batch->gross_amount, 2) }} <small>{{ $batch->currency }}</small></p>
    </div>
    <div class="stat-card">
      <p class="stat-label">Commission</p>
      <p class="stat-value" style="color:#7a4606;">{{ number_format($batch->commission_amount, 2) }}</p>
    </div>
    <div class="stat-card">
      <p class="stat-label">Gateway Fee</p>
      <p class="stat-value" style="color:#7a4606;">{{ number_format($batch->gateway_fee_amount, 2) }}</p>
    </div>
    <div class="stat-card">
      <p class="stat-label">Net Payout</p>
      <p class="stat-value" style="color:#0b5c2a;">{{ number_format($batch->net_payout_amount, 2) }} <small>{{ $batch->currency }}</small></p>
    </div>
    @if($batch->bank_reference)
    <div class="stat-card">
      <p class="stat-label">Bank Reference</p>
      <p class="stat-value" style="font-size:.9rem;">{{ $batch->bank_reference }}</p>
    </div>
    @endif
    @if($batch->submitted_at)
    <div class="stat-card">
      <p class="stat-label">Submitted</p>
      <p class="stat-value" style="font-size:.85rem;">{{ $batch->submitted_at }}</p>
    </div>
    @endif
    @if($batch->confirmed_at)
    <div class="stat-card">
      <p class="stat-label">Confirmed</p>
      <p class="stat-value" style="font-size:.85rem;">{{ $batch->confirmed_at }}</p>
    </div>
    @endif
  </div>

  {{-- Action buttons --}}
  <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
    @if($batch->status === 'queued')
    <form method="POST" action="/portal/admin/finance/payouts/{{ $batch->id }}/send" onsubmit="return confirmSend(this)">
      @csrf
      <input name="bank_reference" placeholder="Bank reference" required style="border:1px solid #c8d3df;border-radius:6px;padding:6px 9px;font-size:.84rem;width:180px;">
      <button type="submit" class="btn-warn" style="margin-left:6px;">Mark Sent</button>
    </form>
    @elseif($batch->status === 'processing')
    <form method="POST" action="/portal/admin/finance/payouts/{{ $batch->id }}/confirm" onsubmit="return confirm('Confirm this batch as settled?')">
      @csrf
      <button type="submit" class="btn-ok">Confirm Settled</button>
    </form>
    @endif
    <a href="/portal/admin/finance/payouts" style="align-self:center;font-size:.84rem;color:#155f83;">← All Batches</a>
  </div>
</div>
@endif

{{-- Items table --}}
<div class="section">
  <p class="section-title">Vendor Items ({{ count($items ?? []) }})</p>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>Vendor</th>
          <th>Reservations</th>
          <th>Gross</th>
          <th>Commission</th>
          <th>Gateway Fee</th>
          <th>Net Payout</th>
          <th>Currency</th>
          <th>Bank Account</th>
          <th>Status</th>
          <th>Bank Ref</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items ?? [] as $item)
        <tr>
          <td>
            <strong style="font-size:.82rem;">{{ $item->vendor_name ?? '—' }}</strong><br>
            <span style="font-size:.73rem;color:var(--muted);">{{ $item->vendor_email ?? '' }}</span>
          </td>
          <td>
            @php
              $resIds = is_string($item->reservation_ids) ? json_decode($item->reservation_ids, true) : ($item->reservation_ids ?? []);
            @endphp
            <span style="font-size:.78rem;">{{ count($resIds) }} res.</span>
            @if(count($resIds) <= 5)
              <br><span style="font-size:.7rem;color:var(--muted);">{{ implode(', ',$resIds) }}</span>
            @endif
          </td>
          <td style="font-family:monospace;">{{ number_format($item->gross_amount,2) }}</td>
          <td style="font-family:monospace;color:#7a4606;">{{ number_format($item->commission_amount,2) }}</td>
          <td style="font-family:monospace;color:#7a4606;">{{ number_format($item->gateway_fee_amount,2) }}</td>
          <td style="font-family:monospace;font-weight:700;color:#0b5c2a;">{{ number_format($item->net_payout_amount,2) }}</td>
          <td>{{ $item->currency }}</td>
          <td style="font-size:.76rem;">
            {{ $item->bank_account_name ?? '—' }}<br>
            <span style="color:var(--muted);">{{ $item->bank_account_number ?? '' }}</span>
          </td>
          <td><span class="chip {{ $itemStatusColors[$item->status] ?? 'chip-grey' }}">{{ $item->status }}</span></td>
          <td style="font-size:.76rem;">{{ $item->bank_reference ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="10" style="color:var(--muted);padding:16px;">No items in this batch.</td></tr>
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
  return confirm('Mark batch as sent with bank reference: ' + ref + '?');
}
</script>
</body>
</html>
