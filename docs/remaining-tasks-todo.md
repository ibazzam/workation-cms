# Remaining Tasks Execution Plan

This plan converts remaining launch work into an ordered, owner-assigned schedule.

## Planning Window
- Plan start: 2026-03-11
- Target launch-readiness signoff: 2026-03-18
- Status cadence: daily update in launch command thread by 18:00 local time

## Priority Sequence
1. Roadmap consistency cleanup
2. Launch execution rehearsal and evidence pack
3. Production verification and alert/runbook validation
4. KPI operating rhythm activation
5. Hypercare operating model finalization

## Workstream Plan

### WS1) Roadmap Consistency Cleanup
- Owner: Product Ops Lead
- Start: 2026-03-11
- Target complete: 2026-03-12
- Dependencies: none
- Tasks:
  - [x] Reconcile `docs/product-vision-roadmap-maldives.md` so section 4 (`Still Pending`) matches completed checklist status.
  - [x] Remove or annotate outdated gap statements in section 9 (`Repository Reality Scan`) that no longer reflect current implementation.
  - [x] Re-run roadmap evidence pass so each completed claim has a current dated artifact reference.
- Deliverable:
  - Updated roadmap with consistent completion narrative and evidence links.
- Exit criteria:
  - [x] No contradictory "pending" statements remain for already completed section-L items.

### WS2) Launch Execution Rehearsal
- Owner: Release Manager
- Start: 2026-03-12
- Target complete: 2026-03-14
- Dependencies: WS1
- Tasks:
  - [x] Create release-candidate tag from `main` and document scope freeze rules (`rc-2026-03-11-launch`, `docs/release-candidate-scope-freeze.md`).
  - [ ] Run one full go/no-go rehearsal using:
    - `docs/go-live-readiness-checklist-by-milestone.md`
    - `docs/go-live-signoff-template.md`
    - rehearsal record initialized: `docs/go-no-go-rehearsal-2026-03-18.md`
  - [x] Capture and store signoff evidence links for all M8 blocker gates.
  - [x] Confirm rollback owner and rollback trigger matrix (metric/incident thresholds).
    - rollback matrix drafted in `docs/go-no-go-rehearsal-2026-03-18.md`
    - owner confirmed in rehearsal metadata and support escalation roster
- Deliverable:
  - Completed rehearsal packet and signed go/no-go rehearsal record.
- Exit criteria:
  - [ ] Full rehearsal completed with evidence links for each blocker check.
    - remaining blocker: deploy remediation build (currently blocked by missing `RENDER_DEPLOY_HOOK_URL`), rerun authenticated preflight, then complete channel-delivery confirmation (`docs/alert-routing-verification-2026-03-18.md`)

### WS3) Production Verification
- Owner: SRE / Platform Lead
- Start: 2026-03-13
- Target complete: 2026-03-15
- Dependencies: WS2 (can begin in parallel after RC tag exists)
- Tasks:
  - [ ] Re-run hosted live preflight against launch target environment.
    - initial strict run failed due to missing `LIVE_PREFLIGHT_BEARER_TOKEN` (`https://github.com/ibazzam/workation-cms/actions/runs/22947199645`)
    - rerun with token secret configured also failed (`https://github.com/ibazzam/workation-cms/actions/runs/22948887462`) due to `HTTP 500` in checkout reliability path
    - isolated failing endpoint: `GET /api/v1/bookings` returns `500` under bearer-authenticated flow
    - attempted promotion blocked by missing deploy-hook secret (`https://github.com/ibazzam/workation-cms/actions/runs/22951012154`)
    - use `scripts/ops/verify-alert-routing.ps1 -RunWorkflow` after `/bookings` production fix is deployed
  - [ ] Validate launch-day incident alert routing (pager/slack/email) end-to-end.
  - [x] Validate incident runbook links are reachable and current.
  - [x] Verify customer support escalation roster and compensation approval chain are active.
- Deliverable:
  - Verification report with pass/fail outcomes and remediation notes (`docs/production-verification-report-2026-03-18.md`).
- Exit criteria:
  - [ ] No unresolved critical verification failures.
    - current critical blockers: production `/api/v1/bookings` `500` during strict live preflight, and missing `RENDER_DEPLOY_HOOK_URL` deployment secret

### WS4) KPI Operations Activation
- Owner: Product Analytics Lead
- Start: 2026-03-14
- Target complete: 2026-03-16
- Dependencies: WS2, WS3
- Tasks:
  - [x] Stand up daily KPI review rhythm using `docs/kpi-instrumentation-framework.md`.
  - [x] Confirm dashboard import and ownership for `infra/observability/grafana/workation-launch-kpi-dashboard.json`.
  - [x] Define weekly action loop for funnel drop-off, route failures, and failed-checkout reason spikes.
- Deliverable:
  - KPI review runbook and owner-assigned dashboard operations rota (`docs/kpi-operations-cadence.md`).
- Exit criteria:
  - [x] Daily KPI ritual live with named owners and escalation paths.
    - daily/weekly log templates published: `docs/kpi-daily-review-log.md`, `docs/kpi-weekly-action-log.md`

### WS5) Hypercare Readiness
- Owner: Operations Lead
- Start: 2026-03-15
- Target complete: 2026-03-17
- Dependencies: WS3, WS4
- Tasks:
  - [x] Publish launch-week on-call schedule and backup contacts.
  - [x] Define severity-based communication cadence for incidents.
  - [x] Set first-week post-launch review checkpoints and decision owners.
- Deliverable:
  - Hypercare operations plan covering D0-D7 after launch (`docs/hypercare-d0-d7-plan.md`).
- Exit criteria:
  - [ ] Hypercare plan approved by Engineering, Operations, and Product owners.
    - role-based plan artifacts published: `docs/launch-week-oncall-schedule.md`, `docs/incident-communication-cadence.md`

## Final Go/No-Go Criteria (2026-03-18)
- [ ] All roadmap sections are internally consistent.
- [ ] M8 go-live checklist has no open blocker items.
- [ ] Launch-day command process and rollback path are documented and rehearsed.
- [ ] Rehearsal, verification, KPI operations, and hypercare evidence links are compiled in one signoff record.
