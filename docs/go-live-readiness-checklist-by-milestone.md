# Go-Live Readiness Checklist by Milestone

Use this checklist to gate milestone completion and launch progression.

## Usage
- Complete the checklist for each milestone before promoting release scope.
- Any unchecked `Blocker` item prevents milestone signoff.
- Record evidence links for every checked item.

## Milestone M1: Foundation Freeze
### Blockers
- [ ] Single backend authority confirmed for production traffic.
- [ ] PostgreSQL configuration parity validated across environments.
- [ ] Migration rollback playbook tested on staging.
### Readiness
- [ ] Feature flag defaults reviewed and documented.
- [ ] Core auth/rbac smoke checks pass.
- [ ] Deployment/rollback owner assigned.
### Evidence
- Runbook(s): ____________________
- CI/validation run(s): ____________________
- Signoff owner/date: ____________________

## Milestone M2: Travel Core v1
### Blockers
- [ ] Islands/atolls APIs pass contract and smoke checks.
- [ ] Accommodation and transport CRUD/read flows pass staging validation.
- [ ] Search/list/detail critical-path errors below release threshold.
### Readiness
- [ ] Core catalog completeness baseline validated.
- [ ] Known gaps triaged with owner and ETA.
- [ ] Preflight gate pass recorded.
### Evidence
- Dashboard snapshot/report: ____________________
- Preflight run: ____________________
- Signoff owner/date: ____________________

## Milestone M3: Booking Engine v1
### Blockers
- [ ] Multi-leg itinerary dependency checks pass.
- [ ] Booking state machine transitions validated (draft/hold/confirmed/cancelled/refunded).
- [ ] Checkout failure handling and rollback behavior validated.
### Readiness
- [ ] End-to-end search-to-book flow green in staging.
- [ ] Route completion baseline captured.
- [ ] On-call escalation path confirmed for booking incidents.
### Evidence
- Test suite/report: ____________________
- KPI snapshot: ____________________
- Signoff owner/date: ____________________

## Milestone M4: Payments Productionization
### Blockers
- [ ] Webhook processing and reconciliation jobs pass reliability checks.
- [ ] Refund/dispute pathways validated with ownership and approval controls.
- [ ] Settlement reporting generated and reviewed by finance owner.
### Readiness
- [ ] Payment alert routes tested (pager/slack/email as configured).
- [ ] Reconciliation exception workflow rehearsed.
- [ ] Finance and support escalation contacts confirmed.
### Evidence
- Reconciliation run/report: ____________________
- Alert test record: ____________________
- Signoff owner/date: ____________________

## Milestone M5: Marketplace Expansion A
### Blockers
- [ ] Vehicle rental and excursion core booking flows are operational.
- [ ] Domain-specific availability and quote endpoints pass preflight checks.
- [ ] New vertical authorization and scope rules validated.
### Readiness
- [ ] Integration smoke includes both new verticals.
- [ ] Content quality baseline complete for launchable listings.
- [ ] Support macro/playbook updates published for new vertical incidents.
### Evidence
- Preflight output: ____________________
- Content ops QA set: ____________________
- Signoff owner/date: ____________________

## Milestone M6: Marketplace Expansion B
### Blockers
- [ ] Restaurant reservation and resort day-visit flows are operational.
- [ ] Availability/quote windows validated for both verticals.
- [ ] Unified checkout supports four-plus vertical combinations.
### Readiness
- [ ] Booking dependency conflicts tested and resolved.
- [ ] Vendor onboarding artifacts complete for participating partners.
- [ ] Customer policy disclosures verified for each vertical.
### Evidence
- Integration report: ____________________
- Vendor signoff packet: ____________________
- Signoff owner/date: ____________________

## Milestone M7: Digital Nomad Layer
### Blockers
- [ ] Remote-work space inventory/pass flows validated.
- [ ] Connectivity and amenity metadata quality checks pass.
- [ ] Remote-work bookings complete with payment and confirmation.
### Readiness
- [ ] Nomad-specific support runbooks published.
- [ ] Listing recertification schedule configured.
- [ ] KPI segmentation includes remote-work flows.
### Evidence
- Validation run(s): ____________________
- KPI evidence: ____________________
- Signoff owner/date: ____________________

## Milestone M8: Launch Readiness
### Blockers
- [ ] External observability stack operational with dashboards and alert routes.
- [ ] Security and data-governance controls validated in latest release window.
- [ ] Load/performance and live preflight gates pass for launch target.
- [ ] Support tooling and compensation governance in active operations.
- [ ] Go/no-go decision recorded with rollback owner and procedure.
### Readiness
- [ ] Incident runbook links validated and accessible.
- [ ] Communications plan prepared for launch-day incidents.
- [ ] Hypercare schedule and daily review cadence finalized.
### Evidence
- Final go/no-go record: ____________________
- Rollback rehearsal evidence: ____________________
- Signoff owner/date: ____________________

## Cross-Milestone Signoff Rules
- At least one engineering owner and one operations owner must sign off each milestone.
- Any `Blocker` regression reopens milestone status to `NOT READY`.
- Launch to public traffic requires all M8 blocker checks complete and documented.
