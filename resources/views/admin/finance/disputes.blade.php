{{--
  resources/views/admin/finance/disputes.blade.php
  ADMIN ONLY — dispute / chargeback case management.
  source_medium is INTERNAL. Stripe disputes have formal respond-by deadlines.
  MIB/BML disputes are manual cases.
--}}
@php
    $mediumColors = ['mib'=>'chip-blue','bml'=>'chip-ok','stripe'=>'chip-purple'];
    $bandColors   = ['local_mvr'=>'chip-teal','foreign_usd'=>'chip-warn'];
    $statusColors = [
        'opened'            =>'chip-warn',
        'evidence_submitted'=>'chip-blue',
        'under_review'      =>'chip-grey',
        'won'               =>'chip-ok',
        'lost'              =>'chip-err',
        'accepted'          =>'chip-grey',
    ];
@endphp
@include('admin.finance._layout', [
    'pageTitle'    => 'Dispute Cases',
    'pageSubtitle' => 'Chargeback and payment dispute management. Stripe disputes carry formal response deadlines.',
    'activeNav'    => 'disputes',
])

{{-- Urgent banner --}}
@if(($urgentCount ?? 0) > 0)
<div class="alert-banner err">
  <strong>{{ $urgentCount }} dispute{{ $urgentCount > 1 ? 's' : '' }}</strong> require a response within 3 days.
  Failing to respond to a Stripe dispute will result in an automatic loss.
</div>
@endif

{{-- Open new dispute --}}
<div class="section">
  <p class="section-title">Open a Dispute Case</p>
  <form method="POST" action="/portal/admin/finance/disputes" id="open-dispute-form" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;align-items:end;">
    @csrf
    <div>
      <label style="font-size:.73rem;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);display:block;margin-bottom:4px;">Reservation ID</label>
      <input type="number" name="reservation_id" required style="width:100%;border:1px solid #c8d3df;border-radius:8px;padding:7px 9px;font-size:.84rem;font-family:inherit;background:#fff;">
    </div>
    <div>
      <label style="font-size:.73rem;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);display:block;margin-bottom:4px;">Disputed Amount</label>
      <input type="number" step="0.01" name="disputed_amount" required style="width:100%;border:1px solid #c8d3df;border-radius:8px;padding:7px 9px;font-size:.84rem;font-family:inherit;background:#fff;">
    </div>
    <div>
      <label style="font-size:.73rem;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);display:block;margin-bottom:4px;">Gateway Dispute ID</label>
      <input type="text" name="gateway_dispute_id" placeholder="e.g. dp_xxx" style="width:100%;border:1px solid #c8d3df;border-radius:8px;padding:7px 9px;font-size:.84rem;font-family:inherit;background:#fff;">
    </div>
    <div>
      <label style="font-size:.73rem;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);display:block;margin-bottom:4px;">Reason</label>
      <input type="text" name="dispute_reason" placeholder="e.g. fraudulent, unrecognized" style="width:100%;border:1px solid #c8d3df;border-radius:8px;padding:7px 9px;font-size:.84rem;font-family:inherit;background:#fff;">
    </div>
    <div>
      <label style="font-size:.73rem;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);display:block;margin-bottom:4px;">Respond By</label>
      <input type="date" name="respond_by" style="width:100%;border:1px solid #c8d3df;border-radius:8px;padding:7px 9px;font-size:.84rem;font-family:inherit;background:#fff;">
    </div>
    <div style="grid-column:1 / -1;">
      <label style="font-size:.73rem;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);display:block;margin-bottom:4px;">Notes</label>
      <textarea name="notes" rows="2" style="width:100%;border:1px solid #c8d3df;border-radius:8px;padding:7px 9px;font-size:.84rem;font-family:inherit;background:#fff;resize:vertical;"></textarea>
    </div>
    <div>
      <button type="submit" class="btn-primary" style="width:100%;">Open Dispute</button>
    </div>
  </form>
</div>

{{-- Filter + Cases table --}}
<div class="section">
  <p class="section-title">Dispute Cases</p>

  <form method="GET" action="/portal/admin/finance/disputes" class="filter-bar">
    <select name="status">
      <option value="">All statuses</option>
      @foreach(['opened','evidence_submitted','under_review','won','lost','accepted'] as $st)
      <option value="{{ $st }}" @selected(($filters['status']??'') === $st)>{{ ucfirst(str_replace('_',' ',$st)) }}</option>
      @endforeach
    </select>
    <select name="medium"> {{-- INTERNAL --}}
      <option value="">All mediums</option>
      <option value="mib"    @selected(($filters['medium']??'') === 'mib')>MIB</option>
      <option value="bml"    @selected(($filters['medium']??'') === 'bml')>BML</option>
      <option value="stripe" @selected(($filters['medium']??'') === 'stripe')>Stripe</option>
    </select>
    <button type="submit">Filter</button>
    <a href="/portal/admin/finance/disputes" style="font-size:.8rem;color:var(--muted);align-self:center;">Reset</a>
  </form>

  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>Case Ref</th>
          <th>Reservation</th>
          <th>Vendor</th>
          <th>Medium <span class="internal-label">Internal</span></th>
          <th>Band <span class="internal-label">Internal</span></th>
          <th>Amount</th>
          <th>Reason</th>
          <th>Respond By</th>
          <th>Status</th>
          <th>Outcome</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($cases ?? [] as $case)
        @php
          $isUrgent = false;
          if($case->respond_by && !in_array($case->status, ['won','lost','accepted'])){
            $diff = now()->diffInDays($case->respond_by, false);
            $isUrgent = $diff >= 0 && $diff <= 3;
          }
        @endphp
        <tr style="{{ $isUrgent ? 'background:#fff8e5;' : '' }}">
          <td style="font-weight:700;font-size:.78rem;">
            {{ $case->case_ref }}
            @if($isUrgent) <span class="chip chip-err" style="font-size:.68rem;">URGENT</span> @endif
          </td>
          <td>{{ $case->reservation_id }}</td>
          <td style="font-size:.78rem;">
            {{ $case->vendor_name ?? '—' }}<br>
            <span style="color:var(--muted);">{{ $case->vendor_email ?? '' }}</span>
          </td>
          <td><span class="chip {{ $mediumColors[$case->source_medium] ?? 'chip-grey' }}">{{ strtoupper($case->source_medium) }}</span></td>
          <td><span class="chip {{ $bandColors[$case->currency_band] ?? 'chip-grey' }}">{{ $case->currency_band }}</span></td>
          <td style="font-weight:700;font-family:monospace;">{{ number_format($case->disputed_amount,2) }} {{ $case->disputed_currency ?? '' }}</td>
          <td style="font-size:.76rem;">{{ $case->dispute_reason ?? '—' }}</td>
          <td style="font-size:.76rem;white-space:nowrap;{{ $isUrgent ? 'color:#6d1111;font-weight:700;' : '' }}">
            {{ $case->respond_by ?? '—' }}
            @if($case->respond_by && !in_array($case->status,['won','lost','accepted']))
              @php $daysLeft = now()->diffInDays($case->respond_by, false); @endphp
              @if($daysLeft >= 0)
                <br><span style="font-size:.7rem;">{{ $daysLeft }}d left</span>
              @else
                <br><span style="font-size:.7rem;color:#6d1111;">overdue</span>
              @endif
            @endif
          </td>
          <td><span class="chip {{ $statusColors[$case->status] ?? 'chip-grey' }}">{{ str_replace('_',' ',$case->status) }}</span></td>
          <td style="font-size:.76rem;">
            @if($case->outcome)
              <span class="chip {{ $case->outcome === 'won' ? 'chip-ok' : ($case->outcome === 'lost' ? 'chip-err' : 'chip-grey') }}">{{ $case->outcome }}</span>
              @if($case->outcome_amount)
                <br>{{ number_format($case->outcome_amount,2) }}
              @endif
            @else
              <span style="color:var(--muted);">—</span>
            @endif
          </td>
          <td>
            <div style="display:flex;gap:5px;flex-wrap:wrap;">
              @if(in_array($case->status, ['opened','under_review']))
              <button type="button" class="btn-warn" style="padding:4px 8px;font-size:.74rem;" onclick="promptEvidence('{{ $case->case_ref }}')">Submit Evidence</button>
              @endif
              @if(!in_array($case->status, ['won','lost','accepted']))
              <button type="button" class="btn-danger" style="padding:4px 8px;font-size:.74rem;" onclick="promptResolve('{{ $case->case_ref }}')">Resolve</button>
              @endif
            </div>

            {{-- Hidden evidence form --}}
            <form id="ev-form-{{ $case->case_ref }}" method="POST" action="/portal/admin/finance/disputes/{{ $case->case_ref }}/evidence" style="display:none;">
              @csrf
              <input type="hidden" name="evidence_notes">
            </form>
            {{-- Hidden resolve form --}}
            <form id="re-form-{{ $case->case_ref }}" method="POST" action="/portal/admin/finance/disputes/{{ $case->case_ref }}/resolve" style="display:none;">
              @csrf
              <input type="hidden" name="outcome">
              <input type="hidden" name="outcome_amount">
              <input type="hidden" name="resolution_notes">
            </form>
          </td>
        </tr>
        @empty
        <tr><td colspan="11" style="color:var(--muted);padding:16px;">No dispute cases found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

  </div>
</div>
<script>
function promptEvidence(caseRef){
  var notes = prompt('Evidence notes (required):');
  if(!notes||!notes.trim()) return;
  var form = document.getElementById('ev-form-' + caseRef);
  form.querySelector('[name=evidence_notes]').value = notes.trim();
  if(confirm('Submit evidence for ' + caseRef + '?')){ form.submit(); }
}
function promptResolve(caseRef){
  var outcome = prompt('Outcome (won / lost / accepted):');
  if(!outcome||!['won','lost','accepted'].includes(outcome.trim().toLowerCase())) { alert('Must be: won, lost, or accepted'); return; }
  outcome = outcome.trim().toLowerCase();
  var amount = prompt('Outcome amount (e.g. 0 for won, full amount for lost):');
  if(amount===null) return;
  var notes = prompt('Resolution notes (optional):') || '';
  var form = document.getElementById('re-form-' + caseRef);
  form.querySelector('[name=outcome]').value         = outcome;
  form.querySelector('[name=outcome_amount]').value  = parseFloat(amount)||0;
  form.querySelector('[name=resolution_notes]').value= notes;
  if(confirm('Resolve dispute ' + caseRef + ' as "' + outcome + '"?')){ form.submit(); }
}
</script>
</body>
</html>
