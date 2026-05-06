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
    $adminItemStatusColors = [
      'queued' => 'chip-grey',
      'processing' => 'chip-warn',
      'sent' => 'chip-blue',
      'on_hold' => 'chip-err',
      'confirmed' => 'chip-ok',
      'paid' => 'chip-ok',
      'failed' => 'chip-err',
      'cancelled' => 'chip-err',
    ];
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
  @php
    $maturityReady = (bool) (($maturitySnapshot['ready'] ?? false));
    $maturityAt = $maturitySnapshot['maturity_at'] ?? null;
    $maturityBlockedCount = (int) (($maturitySnapshot['blocked_count'] ?? 0));
    $maturityReasons = (array) (($maturitySnapshot['sample_reasons'] ?? []));
    $firstApprovedBy = (int) ($batch->first_approved_by_user_id ?? 0);
    $secondApprovedBy = (int) ($batch->second_approved_by_user_id ?? 0);
    $currentFinanceUserId = (int) ($currentFinanceUserId ?? 0);
    $hasProof = trim((string) ($batch->settlement_reference_proof ?? '')) !== '';
    $hasReference = trim((string) ($batch->settlement_reference_id ?? '')) !== '';
  @endphp

  <div class="alert-banner {{ $maturityReady ? 'ok' : 'warn' }}" style="margin-bottom:12px;">
    <strong>Payout maturity:</strong>
    @if($maturityReady)
      ready for release.
    @else
      not ready yet.
    @endif
    @if($maturityAt)
      Expected maturity date: {{ \Illuminate\Support\Carbon::parse((string) $maturityAt)->toDateString() }}.
    @endif
    @if($maturityBlockedCount > 0)
      Blockers: {{ $maturityBlockedCount }} ({{ implode(' | ', $maturityReasons) }}).
    @endif
  </div>

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
    @if($hasReference)
    <div class="stat-card">
      <p class="stat-label">Settlement Reference ID</p>
      <p class="stat-value" style="font-size:.9rem;word-break:break-all;">{{ $batch->settlement_reference_id }}</p>
    </div>
    @endif
    <div class="stat-card">
      <p class="stat-label">Primary Approval</p>
      <p class="stat-value" style="font-size:.85rem;">{{ $firstApprovedBy > 0 ? ('User #' . $firstApprovedBy) : 'Pending' }}</p>
    </div>
    <div class="stat-card">
      <p class="stat-label">Secondary Approval</p>
      <p class="stat-value" style="font-size:.85rem;">{{ $secondApprovedBy > 0 ? ('User #' . $secondApprovedBy) : 'Pending' }}</p>
    </div>
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
      @if(!((bool) $isReadyToSend))
      <form method="POST" action="/portal/admin/finance/payouts/{{ $batch->id }}/approve-primary" style="display:grid;gap:6px;min-width:320px;">
        @csrf
        <input name="settlement_reference_id" value="{{ (string) ($batch->settlement_reference_id ?? '') }}" placeholder="Bank deposit transaction ID / settlement reference" required style="border:1px solid #c8d3df;border-radius:6px;padding:6px 9px;font-size:.84rem;">
        <textarea name="settlement_reference_proof" rows="3" required placeholder="Proof note (bank deposit statement reference, settlement evidence, reconciliation note)" style="border:1px solid #c8d3df;border-radius:6px;padding:6px 9px;font-size:.84rem;">{{ (string) ($batch->settlement_reference_proof ?? '') }}</textarea>
        <button type="submit" class="btn-primary">Save Proof + Primary Approval</button>
      </form>

      @if($firstApprovedBy > 0 && $secondApprovedBy <= 0)
        @if($currentFinanceUserId > 0 && $currentFinanceUserId !== $firstApprovedBy)
        <form method="POST" action="/portal/admin/finance/payouts/{{ $batch->id }}/approve-secondary" onsubmit="return confirm('Confirm secondary approval for this payout batch?')">
          @csrf
          <button type="submit" class="btn-ok">Secondary Approval (4-eyes)</button>
        </form>
        @else
        <span style="font-size:.82rem;color:var(--muted);align-self:center;">Waiting for a different finance admin to provide secondary approval.</span>
        @endif
      @endif
      @else
      <form method="POST" action="/portal/admin/finance/payouts/{{ $batch->id }}/send" onsubmit="return confirmSend(this)">
        @csrf
        <input name="bank_reference" placeholder="Bank reference" required style="border:1px solid #c8d3df;border-radius:6px;padding:6px 9px;font-size:.84rem;width:180px;">
        <input type="date" name="expected_payout_date" style="border:1px solid #c8d3df;border-radius:6px;padding:6px 9px;font-size:.84rem;width:160px;">
        <button type="submit" class="btn-warn" style="margin-left:6px;">Mark Sent</button>
      </form>
      @endif
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
          <th>Account Link</th>
          <th>Status</th>
          <th>Bank Ref</th>
          <th>Update</th>
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
          <td style="font-size:.76rem;">
            @php
              $verificationStatus = strtolower(trim((string) ($item->payout_account_verification_status ?? '')));
              $verificationLabel = $verificationStatus !== '' ? strtoupper(str_replace('_', ' ', $verificationStatus)) : 'UNSPECIFIED';
            @endphp
            <span style="display:block;">Account ID: {{ (int) ($item->payout_account_id ?? 0) > 0 ? (int) ($item->payout_account_id ?? 0) : 'N/A' }}</span>
            <span style="display:block;color:var(--muted);">{{ (string) ($item->payout_account_currency ?? '—') }}</span>
            <span style="display:block;color:var(--muted);">{{ $verificationLabel }}</span>
          </td>
          <td><span class="chip {{ $adminItemStatusColors[strtolower((string) $item->status)] ?? 'chip-grey' }}">{{ $item->status }}</span></td>
          <td style="font-size:.76rem;">{{ $item->bank_reference ?? '—' }}</td>
          <td>
            <form method="POST" action="/portal/admin/finance/payout-items/{{ $item->id }}/status" style="display:grid;gap:6px;min-width:230px;">
              @csrf
              <select name="status" style="border:1px solid #c8d3df;border-radius:6px;padding:5px 7px;font-size:.75rem;">
                @foreach (['queued','processing','sent','on_hold','confirmed','paid','failed','cancelled'] as $nextStatus)
                  <option value="{{ $nextStatus }}" @selected(strtolower((string) $item->status) === $nextStatus)>{{ strtoupper($nextStatus) }}</option>
                @endforeach
              </select>
              <input name="bank_reference" value="{{ (string) ($item->bank_reference ?? '') }}" placeholder="Bank reference" style="border:1px solid #c8d3df;border-radius:6px;padding:5px 7px;font-size:.75rem;">
              <input name="notes" placeholder="Status note" style="border:1px solid #c8d3df;border-radius:6px;padding:5px 7px;font-size:.75rem;">
              <button type="submit" class="btn-primary" style="padding:5px 9px;font-size:.75rem;">Update</button>
            </form>
          </td>
        </tr>
        @empty
        <tr><td colspan="12" style="color:var(--muted);padding:16px;">No items in this batch.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Reservation-level payout accounting ledger --}}
<div class="section">
  <p class="section-title">Reservation Accounting Ledger (Admin)</p>
  <p style="font-size:.83rem;color:var(--muted);margin:0 0 10px;">
    Reservation-level payout breakdown for accounting: vendor, category, room/service, collected total, deductions, payout amount,
    collected date, payout date, and current status.
  </p>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>Reservation</th>
          <th>Vendor</th>
          <th>Service Category</th>
          <th>Service / Room</th>
          <th>Collected Total</th>
          <th>Commission</th>
          <th>Gateway Fee</th>
          <th>Payout Amount</th>
          <th>Collected From Customer</th>
          <th>Collected Date</th>
          <th>Payout Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($reservationLedgerRows ?? [] as $row)
          <tr>
            <td style="font-weight:700;">#{{ (int) ($row->reservation_id ?? 0) }}<br><span style="font-size:.72rem;color:var(--muted);">{{ (string) ($row->payment_gateway ?? '') }} {{ (string) ($row->payment_currency ?? '') }}</span></td>
            <td>{{ (string) ($row->vendor_name ?? '—') }}</td>
            <td>{{ (string) ($row->service_category ?? '—') }}</td>
            <td>{{ (string) ($row->service_or_room_name ?? '—') }}</td>
            <td style="font-family:monospace;">{{ number_format((float) ($row->collected_total_amount ?? 0), 2) }}</td>
            <td style="font-family:monospace;color:#7a4606;">{{ number_format((float) ($row->commission_amount ?? 0), 2) }}</td>
            <td style="font-family:monospace;color:#7a4606;">{{ number_format((float) ($row->gateway_fee_amount ?? 0), 2) }}</td>
            <td style="font-family:monospace;color:#0b5c2a;font-weight:700;">{{ number_format((float) ($row->payout_amount ?? 0), 2) }}</td>
            <td style="font-family:monospace;">{{ number_format((float) ($row->collected_from_customer ?? 0), 2) }}</td>
            <td>{{ (string) ($row->collected_date ?? '') !== '' ? \Illuminate\Support\Carbon::parse((string) $row->collected_date)->format('Y-m-d H:i') : '—' }}</td>
            <td>{{ (string) ($row->payout_date ?? '') !== '' ? \Illuminate\Support\Carbon::parse((string) $row->payout_date)->format('Y-m-d H:i') : '—' }}</td>
            <td><span class="chip {{ $adminItemStatusColors[strtolower((string) ($row->status ?? ''))] ?? 'chip-grey' }}">{{ strtoupper((string) ($row->status ?? 'queued')) }}</span></td>
          </tr>
        @empty
          <tr><td colspan="12" style="color:var(--muted);padding:16px;">No reservation-level rows found for this batch.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Status audit trail --}}
<div class="section">
  <p class="section-title">Payout Item Status Audit Trail</p>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>When</th>
          <th>Item</th>
          <th>From</th>
          <th>To</th>
          <th>Bank Ref</th>
          <th>Note</th>
          <th>Updated By</th>
        </tr>
      </thead>
      <tbody>
        @forelse($itemStatusLogs ?? [] as $log)
          <tr>
            <td>{{ !empty($log->created_at) ? \Illuminate\Support\Carbon::parse((string) $log->created_at)->format('Y-m-d H:i') : '—' }}</td>
            <td>#{{ (int) ($log->item_id ?? 0) }}</td>
            <td><span class="chip {{ $adminItemStatusColors[strtolower((string) ($log->from_status ?? ''))] ?? 'chip-grey' }}">{{ strtoupper((string) ($log->from_status ?? '—')) }}</span></td>
            <td><span class="chip {{ $adminItemStatusColors[strtolower((string) ($log->to_status ?? ''))] ?? 'chip-grey' }}">{{ strtoupper((string) ($log->to_status ?? '—')) }}</span></td>
            <td>{{ (string) ($log->bank_reference ?? '—') !== '' ? (string) ($log->bank_reference ?? '—') : '—' }}</td>
            <td>{{ (string) ($log->notes ?? '—') !== '' ? (string) ($log->notes ?? '—') : '—' }}</td>
            <td>{{ (string) ($log->actor_name ?? ('Admin #' . (int) ($log->actor_user_id ?? 0))) }}</td>
          </tr>
        @empty
          <tr><td colspan="7" style="color:var(--muted);padding:16px;">No item status changes logged yet.</td></tr>
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