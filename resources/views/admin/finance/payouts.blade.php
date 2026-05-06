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

@if(($readyToSendCount ?? 0) > 0)
<div class="alert-banner ok">
  <strong>{{ $readyToSendCount }} batch{{ ($readyToSendCount ?? 0) === 1 ? '' : 'es' }}</strong> are ready to send.
  Settlement maturity and 4-eyes approvals are complete.
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

{{-- Payout account verification queue --}}
@if(isset($payoutAccountQueue) && $payoutAccountQueue->isNotEmpty())
<div class="section">
  <p class="section-title">Payout Account Verification Queue</p>
  <p style="font-size:.84rem;color:var(--muted);margin:0 0 10px;">
    Finance Admin review queue for payout account approvals. Cross-check vendor business profile, service verification, and ID proof before approving settlement accounts.
    Sole-proprietor vendors can be approved with a personal-name beneficiary account when documented below.
  </p>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>Vendor</th>
          <th>Business &amp; Compliance</th>
          <th>Account</th>
          <th>Current Status</th>
          <th>Review Action</th>
        </tr>
      </thead>
      <tbody>
        @foreach($payoutAccountQueue as $account)
        <tr>
          <td>
            <strong style="font-size:.82rem;">{{ (string) ($account->vendor_name ?? 'Vendor') }}</strong><br>
            <span style="font-size:.73rem;color:var(--muted);">{{ (string) ($account->vendor_email ?? '') }}</span><br>
            <span style="font-size:.72rem;color:var(--muted);">Vendor ID: {{ (int) ($account->vendor_user_id ?? 0) }}</span>
          </td>
          <td style="font-size:.76rem;line-height:1.4;">
            <div>Business: <strong>{{ (string) ($account->business_name ?? 'N/A') }}</strong></div>
            <div>Responsible: {{ (string) ($account->responsible_person_name ?? 'N/A') }}</div>
            <div>Vendor Verification: {{ strtoupper((string) ($account->vendor_verification_status ?? 'pending')) }}</div>
            <div>BRN: {{ (string) ($account->vendor_business_registration_number ?? 'N/A') }}</div>
            <div>License: {{ (string) ($account->vendor_business_license_number ?? 'N/A') }}</div>
            <div>Documents: {{ trim((string) ($account->vendor_verification_documents ?? '')) !== '' ? 'Uploaded' : 'Not uploaded' }}</div>
          </td>
          <td style="font-size:.76rem;line-height:1.4;">
            <div>Label: <strong>{{ (string) ($account->account_label ?? ('Account #' . (int) ($account->id ?? 0))) }}</strong></div>
            <div>Beneficiary: {{ (string) ($account->beneficiary_name ?? 'N/A') }}</div>
            <div>Bank: {{ (string) ($account->bank_name ?? 'N/A') }}</div>
            <div>Account: {{ !empty($account->bank_account_last4) ? '****' . (string) $account->bank_account_last4 : 'Hidden' }}</div>
            <div>Currency: {{ (string) ($account->currency ?? 'MVR') }}</div>
            <div>SWIFT: {{ (string) ($account->swift_code ?? 'N/A') }}</div>
          </td>
          <td style="font-size:.76rem;line-height:1.4;">
            <span class="chip {{ in_array(strtolower((string) ($account->verification_status ?? '')), ['approved', 'verified'], true) ? 'chip-ok' : (strtolower((string) ($account->verification_status ?? '')) === 'rejected' ? 'chip-err' : 'chip-warn') }}">
              {{ strtoupper(str_replace('_', ' ', (string) ($account->verification_status ?? 'pending_review'))) }}
            </span>
            @if (!empty($account->verification_notes))
              <div style="margin-top:6px;color:var(--muted);">{{ (string) $account->verification_notes }}</div>
            @endif
          </td>
          <td>
            <form method="POST" action="/portal/admin/finance/payout-accounts/{{ (int) ($account->id ?? 0) }}/verify" style="display:grid;gap:6px;min-width:250px;">
              @csrf
              <select name="verification_status" style="border:1px solid #c8d3df;border-radius:6px;padding:5px 7px;font-size:.75rem;">
                <option value="pending_review">PENDING REVIEW</option>
                <option value="approved">APPROVE</option>
                <option value="rejected">REJECT</option>
              </select>
              <label style="font-size:.72rem;color:var(--muted);display:flex;gap:6px;align-items:center;">
                <input type="checkbox" name="crosscheck_business_profile" value="1"> Cross-check business profile
              </label>
              <label style="font-size:.72rem;color:var(--muted);display:flex;gap:6px;align-items:center;">
                <input type="checkbox" name="crosscheck_service_profile" value="1"> Cross-check service verification
              </label>
              <label style="font-size:.72rem;color:var(--muted);display:flex;gap:6px;align-items:center;">
                <input type="checkbox" name="crosscheck_id_proof" value="1"> Cross-check ID proof
              </label>
              <label style="font-size:.72rem;color:var(--muted);display:flex;gap:6px;align-items:center;">
                <input type="checkbox" name="sole_proprietor_personal_name_allowed" value="1"> Sole proprietor: personal-name beneficiary allowed
              </label>
              <textarea name="review_notes" rows="3" placeholder="Review notes (required)" style="border:1px solid #c8d3df;border-radius:6px;padding:6px 8px;font-size:.75rem;" required></textarea>
              <button type="submit" class="btn-primary" style="padding:5px 9px;font-size:.75rem;">Save Review</button>
            </form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
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
          <th>Maturity / Gate</th>
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
          <td style="font-size:.74rem;line-height:1.35;">
            @php
              $maturityAt = $batch->maturity_at ?? null;
              $blockedCount = (int) ($batch->maturity_blocked_count ?? 0);
              $readyToSend = (bool) ($batch->is_ready_to_send ?? false);
              $sampleReasons = (array) ($batch->maturity_sample_reasons ?? []);
            @endphp
            @if($readyToSend)
              <span class="chip chip-ok">READY TO SEND</span>
            @else
              <span class="chip chip-warn">NOT READY</span>
            @endif
            <div style="margin-top:4px;color:var(--muted);">
              @if($maturityAt)
                Maturity: {{ \Illuminate\Support\Carbon::parse((string) $maturityAt)->toDateString() }}
              @else
                Maturity: pending
              @endif
            </div>
            @if($blockedCount > 0)
              <div style="margin-top:2px;color:#7a4606;" title="{{ implode(' | ', $sampleReasons) }}">{{ $blockedCount }} blocker{{ $blockedCount === 1 ? '' : 's' }}</div>
            @endif
          </td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              @if($batch->status === 'queued')
                @if((bool) ($batch->is_ready_to_send ?? false))
                <form method="POST" action="/portal/admin/finance/payouts/{{ $batch->id }}/send" style="display:inline;" onsubmit="return confirmSend(this)">
                  @csrf
                  <input name="bank_reference" placeholder="Bank ref" required style="border:1px solid #c8d3df;border-radius:6px;padding:4px 7px;font-size:.76rem;width:110px;">
                  <input type="date" name="expected_payout_date" title="Expected payout date" style="border:1px solid #c8d3df;border-radius:6px;padding:4px 7px;font-size:.76rem;">
                  <button type="submit" class="btn-warn" style="padding:5px 9px;font-size:.76rem;">Mark Sent</button>
                </form>
                @else
                <span style="font-size:.74rem;color:var(--muted);align-self:center;">Awaiting maturity/proof/approvals</span>
                @endif
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
        <tr><td colspan="10" style="color:var(--muted);padding:16px;">No batches found.</td></tr>
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