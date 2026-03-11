# Remaining Tasks To-Do

This list consolidates remaining work after the launch-readiness checklist completion.

## 1) Roadmap Consistency Cleanup
- [ ] Reconcile `docs/product-vision-roadmap-maldives.md` so section 4 (`Still Pending`) matches completed checklist status.
- [ ] Remove or annotate outdated gap statements in section 9 (`Repository Reality Scan`) that no longer reflect current implementation.
- [ ] Re-run a full roadmap evidence pass so each completed claim has a current dated artifact reference.

## 2) Launch Execution Tasks
- [ ] Create a release-candidate tag from `main` and document scope freeze rules.
- [ ] Run one full go/no-go rehearsal using:
  - `docs/go-live-readiness-checklist-by-milestone.md`
  - `docs/go-live-signoff-template.md`
- [ ] Capture and store signoff evidence links for all M8 blocker gates.
- [ ] Confirm rollback owner and rollback trigger matrix (metric/incident thresholds).

## 3) Production Verification Tasks
- [ ] Re-run hosted live preflight against launch target environment.
- [ ] Validate launch-day incident alert routing (pager/slack/email) end-to-end.
- [ ] Validate incident runbook links are reachable and current.
- [ ] Verify customer support escalation roster and compensation approval chain are active.

## 4) KPI Operations Tasks
- [ ] Stand up daily KPI review rhythm using `docs/kpi-instrumentation-framework.md`.
- [ ] Confirm dashboard import and ownership for:
  - `infra/observability/grafana/workation-launch-kpi-dashboard.json`
- [ ] Define weekly action loop for funnel drop-off, route failures, and failed-checkout reason spikes.

## 5) Hypercare Readiness Tasks
- [ ] Publish launch-week on-call schedule and backup contacts.
- [ ] Define severity-based communication cadence for incidents.
- [ ] Set first-week post-launch review checkpoints and decision owners.

## Completion Criteria
- [ ] All roadmap sections are internally consistent.
- [ ] M8 go-live checklist has no open blocker items.
- [ ] Launch-day command process and rollback path are documented and rehearsed.
