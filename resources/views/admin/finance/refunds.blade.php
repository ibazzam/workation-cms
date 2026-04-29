{{--
  resources/views/admin/finance/refunds.blade.php
  ADMIN ONLY — refund case list and lifecycle management.
  source_medium is INTERNAL — refunds must flow back through the original payment medium.
--}}
@php
    $mediumColors = ['mib'=>'chip-blue','bml'=>'chip-ok','stripe'=>'chip-purple'];
    $bandColors   = ['local_mvr'=>'chip-teal','foreign_usd'=>'chip-warn'];
    $statusColors = [
        'requested'    =>'chip-grey',
        'under_review' =>'chip-warn',
        'approved'     =>'chip-blue',
        'processing'   =>'chip-warn',
        'completed'    =>'chip-ok',
        'rejected'     =>'chip-err',
    ];
@endphp
@include('admin.finance._layout', [
    'pageTitle'    => 'Refund Cases',
    'pageSubtitle' => 'Refunds are routed back through the original payment medium. Source is internal only.',
    'activeNav'    => 'refunds',
])

{{-- Open new refund case --}}
<div class="section">
  <p class="section-title">Open a Refund Case</p>
  <form method="POST" action="/portal/admin/finance/refunds" id="open-refund-form" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;align-items:end;">
    @csrf
    <div>
      <label style="font-size:.73rem;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);display:block;margin-bottom:4px;">Reservation ID</label>
      <input type="number" name="reservation_id" required style="width:100%;border:1px solid #c8d3df;border-radius:8px;padding:7px 9px;font-size:.84rem;font-family:inherit;background:#fff;">
    </div>
    <div>
      <label style="font-size:.73rem;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);display:block;margin-bottom:4px;">Refund Amount</label>
      <input type="number" step="0.01" name="refund_amount" required style="width:100%;border:1px solid #c8d3df;border-radius:8px;padding:7px 9px;font-size:.84rem;font-family:inherit;background:#fff;">
    </div>
    <div>
      <label style="font-size:.73rem;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);display:block;margin-bottom:4px;">Refund Type</label>
      <select name="refund_type" style="width:100%;border:1px solid #c8d3df;border-radius:8px;padding:7px 9px;font-size:.84rem;font-family:inherit;background:#fff;">
        <option value="full">Full</option>
        <option value="partial">Partial</option>
      </select>
    </div>
    <div style="grid-column:1 / -1;">
      <label style="font-size:.73rem;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);display:block;margin-bottom:4px;">Reason / Notes</label>
      <textarea name="reason_notes" rows="2" style="width:100%;border:1px solid #c8d3df;border-radius:8px;padding:7px 9px;font-size:.84rem;font-family:inherit;background:#fff;resize:vertical;"></textarea>
    </div>
    <div>
      <button type="submit" class="btn-primary" style="width:100%;">Open Case</button>
    </div>
  </form>
</div>

{{-- Filter + Cases table --}}
<div class="section">
  <p class="section-title">Refund Cases</p>

  <form method="GET" action="/portal/admin/finance/refunds" class="filter-bar">
    <select name="status">
      <option value="">All statuses</option>
      @foreach(['requested','under_review','approved','processing','completed','rejected'] as $st)
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
    <a href="/portal/admin/finance/refunds" style="font-size:.8rem;color:var(--muted);align-self:center;">Reset</a>
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
          <th>Type</th>
          <th>Status</th>
          <th>Timeline / SLA</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($cases ?? [] as $case)
        @php
          $isTerminal = in_array((string) ($case->status ?? ''), ['completed','rejected'], true);
          $isEscalated = !$isTerminal && ((string) ($case->sla_escalated_at ?? '') !== '' || ((string) ($case->sla_due_at ?? '') !== '' && now()->greaterThan($case->sla_due_at)));
        @endphp
        <tr>
          <td style="font-weight:700;font-size:.78rem;">{{ $case->case_ref }}</td>
          <td>{{ $case->reservation_id }}</td>
          <td style="font-size:.78rem;">
            {{ $case->vendor_name ?? '—' }}<br>
            <span style="color:var(--muted);">{{ $case->vendor_email ?? '' }}</span>
          </td>
          <td><span class="chip {{ $mediumColors[$case->source_medium] ?? 'chip-grey' }}">{{ strtoupper($case->source_medium) }}</span></td>
          <td><span class="chip {{ $bandColors[$case->currency_band] ?? 'chip-grey' }}">{{ $case->currency_band }}</span></td>
          <td style="font-weight:700;font-family:monospace;">{{ number_format($case->refund_amount,2) }} {{ $case->refund_currency ?? '' }}</td>
          <td><span class="chip chip-grey">{{ $case->refund_type }}</span></td>
          <td><span class="chip {{ $statusColors[$case->status] ?? 'chip-grey' }}">{{ str_replace('_',' ',$case->status) }}</span></td>
          <td style="font-size:.74rem;white-space:nowrap;">
            Requested: {{ $case->created_at ?? '—' }}<br>
            Review: {{ $case->review_started_at ?? '—' }}<br>
            Approved: {{ $case->approved_at ?? '—' }}<br>
            Completed: {{ $case->completed_at ?? '—' }}<br>
            Rejected: {{ $case->rejected_at ?? '—' }}
            @if(($case->sla_due_at ?? null) !== null)
              <br>SLA Due: {{ $case->sla_due_at }}
            @endif
            @if($isEscalated)
              <br><span class="chip chip-err" style="font-size:.68rem;">ESCALATED</span>
            @endif
          </td>
          <td style="font-size:.74rem;white-space:nowrap;">{{ $case->created_at }}</td>
          <td>
            <div style="display:flex;gap:5px;flex-wrap:wrap;">
              @if($case->status === 'requested')
              <form method="POST" action="/portal/admin/finance/refunds/{{ $case->case_ref }}/review" style="display:inline;" onsubmit="return confirm('Move refund case {{ $case->case_ref }} to under review?')">
                @csrf
                <button type="submit" class="btn-warn" style="padding:4px 8px;font-size:.74rem;">Start Review</button>
              </form>
              @endif
              @if($case->status === 'requested' || $case->status === 'under_review')
              <form method="POST" action="/portal/admin/finance/refunds/{{ $case->case_ref }}/approve" style="display:inline;" onsubmit="return confirm('Approve refund case {{ $case->case_ref }}?')">
                @csrf
                <button type="submit" class="btn-ok" style="padding:4px 8px;font-size:.74rem;">Approve</button>
              </form>
              <form method="POST" action="/portal/admin/finance/refunds/{{ $case->case_ref }}/reject" style="display:inline;">
                @csrf
                <input type="hidden" name="resolution_notes" class="reject-notes">
                <button type="button" class="btn-danger" style="padding:4px 8px;font-size:.74rem;" onclick="promptReject(this)">Reject</button>
              </form>
              @elseif($case->status === 'approved')
              <form method="POST" action="/portal/admin/finance/refunds/{{ $case->case_ref }}/complete" style="display:inline;">
                @csrf
                <input type="hidden" name="gateway_refund_reference" class="complete-ref">
                <button type="button" class="btn-primary" style="padding:4px 8px;font-size:.74rem;background:#155f83;" onclick="promptComplete(this)">Mark Complete</button>
              </form>
              @endif
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="11" style="color:var(--muted);padding:16px;">No refund cases found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

  </div>
</div>
<script>
function promptReject(btn){
  var notes = prompt('Rejection notes (required):');
  if(!notes || !notes.trim()){ return; }
  var form = btn.closest('form');
  form.querySelector('.reject-notes').value = notes.trim();
  if(confirm('Reject this refund case?')){ form.submit(); }
}
function promptComplete(btn){
  var ref = prompt('Gateway refund reference (required):');
  if(!ref || !ref.trim()){ return; }
  var form = btn.closest('form');
  form.querySelector('.complete-ref').value = ref.trim();
  if(confirm('Mark this refund as completed?')){ form.submit(); }
}
</script>
</body>
</html>
