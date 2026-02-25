# Workation Maldives — GitHub Issues Pack

Date: 2026-02-21
Source: `docs/sprint-board-maldives.md`

How to use:
1. Copy each issue block into a new GitHub issue.
2. Replace `@ibazzam` with the actual assignee.
3. Keep acceptance criteria and DoD checklists as merge gates.

---

## ISSUE 01 — Decommission Laravel API Product Path
Labels: `backend` `architecture` `migration` `priority:high` `milestone:M1`
Assignee: `@ibazzam-platform`

### Objective
Remove Laravel API endpoints from the active product path and enforce NestJS as the single runtime API source.

### Scope
- Disable/deprecate legacy Laravel API routes.
- Confirm frontend/backoffice traffic points only to NestJS.
- Document rollback and cutover verification steps.

### Acceptance Criteria
- No production/staging traffic depends on Laravel API endpoints.
- Smoke tests for core journeys pass against NestJS only.
- Cutover notes and rollback procedure are documented.

### Definition of Done
- [ ] Routing/config updated
- [ ] Validation commands executed and attached
- [ ] Rollback procedure documented
- [ ] PR approved by Platform + Backend

---

## ISSUE 02 — PostgreSQL Standardization Across Environments
Labels: `backend` `database` `devops` `priority:high` `milestone:M1`
Assignee: `@owner-data`

### Objective
Ensure all environment templates/configs use PostgreSQL consistently.

### Scope
- Update root and infra env templates.
- Remove/retire SQLite defaults.
- Verify Prisma + app configs align.

### Acceptance Criteria
- Env examples no longer default to SQLite.
- PostgreSQL DSN format validated in docs and templates.
- New developer setup runs with PostgreSQL without manual patching.

### Definition of Done
- [ ] `.env.example` files updated
- [ ] README/setup docs updated
- [ ] Local setup dry-run completed
- [ ] PR approved by Data + Platform

---

## ISSUE 03 — Migration Governance and Rollback Playbook
Labels: `database` `reliability` `priority:high` `milestone:M1`
Assignee: `@owner-data`

### Objective
Establish a repeatable schema migration and rollback process.

### Scope
- Add migration checklist and rollback runbook.
- Add pre/post migration verification steps.
- Define backup/restore checkpoints.

### Acceptance Criteria
- Runbook exists and is reviewed.
- Rollback rehearsal performed for latest migration set.
- CI references governance checklist.

### Definition of Done
- [ ] Runbook added in docs
- [ ] Rehearsal evidence attached
- [ ] Checklist referenced in PR template

---

## ISSUE 04 — API Contract Compatibility Gate
Labels: `backend` `quality` `priority:high` `milestone:M1`
Assignee: `@owner-backend`

### Objective
Prevent breaking API changes without explicit versioning.

### Scope
- Add contract compatibility checklist to PR process.
- Define additive vs breaking change policy.
- Wire contract tests to CI gate.

### Acceptance Criteria
- Contract test failures block merge.
- Version policy documented and linked in contributing docs.
- At least one PR validated through new gate.

### Definition of Done
- [ ] CI gate enabled
- [ ] Policy doc merged
- [ ] PR template updated

---

## ISSUE 05 — Accommodation Availability Engine
Labels: `backend` `accommodations` `priority:high` `milestone:M2`
Assignee: `@owner-backend`

### Objective
Support room inventory and blackout/min-stay constraints in accommodation booking.

### Scope
- Availability schema and APIs.
- Validation for blackout/min-stay.
- Contract tests for edge cases.

### Acceptance Criteria
- Availability endpoint returns correct inventory by date range.
- Booking rejects invalid blackout/min-stay requests.
- Tests cover standard and edge scenarios.

### Definition of Done
- [ ] Schema + migrations complete
- [ ] API implemented
- [ ] Contract tests green

---

## ISSUE 06 — Seasonal/Dynamic Pricing for Accommodation
Labels: `backend` `pricing` `accommodations` `priority:high` `milestone:M2`
Assignee: `@owner-backend`

### Objective
Implement seasonal and occupancy-sensitive pricing outputs.

### Scope
- Pricing rules model.
- Quote endpoint integration.
- Promotion compatibility.

### Acceptance Criteria
- Quote output reflects configured season/occupancy rules.
- Pricing tests validate deterministic results.
- Admin-configurable rule paths documented.

### Definition of Done
- [ ] Pricing rules persisted
- [ ] Quote logic integrated
- [ ] Test matrix attached

---

## ISSUE 07 — Speedboat Schedule and Seat Inventory
Labels: `backend` `transport` `priority:high` `milestone:M2`
Assignee: `@owner-backend`

### Objective
Model speedboat timetable and seat capacity for inter-island transport.

### Scope
- Route schedule entities.
- Capacity decrement and hold rules.
- Read/search APIs by atoll/island and date.

### Acceptance Criteria
- Schedules query by route/date returns expected results.
- Capacity updates safely under concurrent bookings.
- Contract tests include overbooking prevention.

### Definition of Done
- [ ] Schedule model merged
- [ ] Capacity checks in booking flow
- [ ] Concurrency test evidence

---

## ISSUE 08 — Domestic Flight Schedules and Fare Classes
Labels: `backend` `transport` `flights` `priority:high` `milestone:M2`
Assignee: `@owner-backend`

### Objective
Support domestic flight scheduling and fare family modeling.

### Scope
- Flight schedule schema and APIs.
- Fare class fields and rules.
- Compatibility with itinerary/booking flow.

### Acceptance Criteria
- Flight search by origin/destination/date works.
- Fare classes returned with booking constraints.
- Tests cover seat/class availability behavior.

### Definition of Done
- [ ] Schema + API done
- [ ] Booking integration path validated
- [ ] Contract tests green

---

## ISSUE 09 — Booking State Machine (v1)
Labels: `backend` `bookings` `priority:high` `milestone:M3`
Assignee: `@owner-backend`

### Objective
Introduce robust booking lifecycle states and transition guards.

### Scope
- States: draft/hold/confirmed/cancelled/refunded.
- Transition validation and audit fields.
- API exposure for state-safe actions.

### Acceptance Criteria
- Illegal transitions are rejected.
- Legal transitions update state with audit metadata.
- Automated tests validate transition graph.

### Definition of Done
- [ ] State machine implemented
- [ ] Transition APIs documented
- [ ] Test coverage complete

---

## ISSUE 10 — Itinerary Dependency Engine
Labels: `backend` `bookings` `transport` `priority:high` `milestone:M3`
Assignee: `@owner-backend`

### Objective
Enforce Maldives-specific dependency checks between arrival, transfer, and stay.

### Scope
- Time-window compatibility checks.
- Island/atoll consistency validation.
- Failure reason taxonomy.

### Acceptance Criteria
- Incompatible itineraries are rejected with actionable reasons.
- Compatible multi-leg itineraries pass and persist correctly.
- Tests include sea/air combinations and edge windows.

### Definition of Done
- [ ] Rule engine merged
- [ ] Error taxonomy documented
- [ ] Integration tests green

---

## ISSUE 11 — Unified Cart and Bundled Checkout
Labels: `backend` `frontend` `checkout` `priority:high` `milestone:M3`
Assignee: `@owner-frontend`

### Objective
Enable single checkout for combined transport + accommodation (+ future verticals).

### Scope
- Cart model and APIs.
- Checkout payload and validation.
- Frontend cart/checkout views.

### Acceptance Criteria
- User can add multiple service types and checkout once.
- Price/availability revalidation occurs at checkout.
- E2E flow passes for core bundle.

### Definition of Done
- [ ] Cart API + UI merged
- [ ] Checkout flow implemented
- [ ] E2E test evidence attached

---

## ISSUE 12 — Payments Resilience Hardening
Labels: `backend` `payments` `reliability` `priority:high` `milestone:M4`
Assignee: `@owner-backend`

### Objective
Increase payment provider robustness for production reliability.

### Scope
- Retry/backoff/timeouts/circuit behavior.
- Dead-letter escalation and alerting thresholds.
- Idempotency strengthening.

### Acceptance Criteria
- Provider failures degrade gracefully and recover.
- Failed events routed to dead-letter with actionable metadata.
- Reconciliation catches delayed or out-of-order updates.

### Definition of Done
- [ ] Resilience layer merged
- [ ] Alert thresholds documented
- [ ] Failure-mode test report attached

---

## ISSUE 13 — Refunds and Partial Refunds
Labels: `backend` `payments` `finance` `priority:high` `milestone:M4`
Assignee: `@owner-backend`

### Objective
Support full/partial refunds and proper booking/payment state updates.

### Scope
- Refund APIs and internal state transitions.
- Finance/admin visibility.
- Contract tests for refund edge cases.

### Acceptance Criteria
- Full and partial refunds update state correctly.
- Audit records and reconciliation fields are stored.
- Finance admin can view refund lifecycle.

### Definition of Done
- [ ] APIs merged
- [ ] State updates validated
- [ ] Contract tests green

---

## ISSUE 14 — Car/Bike Rental Domain MVP
Labels: `backend` `frontend` `marketplace` `priority:high` `milestone:M5`
Assignee: `@owner-backend`

### Objective
Launch rental vertical with inventory and booking constraints.

### Scope
- Rental inventory schema/API.
- Age/license/policy checks.
- Basic listing and booking UI.

### Acceptance Criteria
- Rentals searchable and bookable for eligible users.
- Policy constraints enforced in booking.
- Contract + E2E tests pass for rental flow.

### Definition of Done
- [ ] Domain model complete
- [ ] APIs + UI merged
- [ ] Tests and docs completed

---

## ISSUE 15 — Excursions & Activities Domain MVP
Labels: `backend` `frontend` `marketplace` `priority:high` `milestone:M5`
Assignee: `@owner-backend`

### Objective
Launch excursions with slot capacity and booking support.

### Scope
- Activity catalog model.
- Slot inventory/capacity controls.
- Booking and cancellation policies.

### Acceptance Criteria
- Activities discoverable by island/atoll/date.
- Capacity and slot limits enforced.
- Booking flow integrated with unified checkout.

### Definition of Done
- [ ] API + data model merged
- [ ] UI listing + booking path merged
- [ ] Test evidence attached

---

## ISSUE 16 — Restaurant Reservation Domain MVP
Labels: `backend` `frontend` `marketplace` `priority:medium` `milestone:M6`
Assignee: `@owner-backend`

### Objective
Support table/time-slot reservations with optional deposits.

### Scope
- Table inventory and timeslot model.
- Reservation create/modify/cancel APIs.
- Reservation UI for discovery and booking.

### Acceptance Criteria
- Reservations enforce table/time capacity.
- Optional deposit policy applied where configured.
- Admin/vendor can manage availability windows.

### Definition of Done
- [ ] Schema + APIs complete
- [ ] UI flow merged
- [ ] Contract tests green

---

## ISSUE 17 — Resort Day Visit Domain MVP
Labels: `backend` `frontend` `marketplace` `priority:medium` `milestone:M6`
Assignee: `@owner-backend`

### Objective
Enable day-pass bookings with quota and transfer bundle constraints.

### Scope
- Day pass quotas by date/window.
- Optional transport bundle integration.
- Customer and admin management flows.

### Acceptance Criteria
- Quota limits and blackout windows enforced.
- Day visits can be bundled in checkout where eligible.
- Tests validate quota/dependency edge cases.

### Definition of Done
- [ ] Domain APIs merged
- [ ] UI booking flow merged
- [ ] Tests + docs completed

---

## ISSUE 18 — Remote Work & Digital Nomad Services MVP
Labels: `backend` `frontend` `marketplace` `priority:medium` `milestone:M7`
Assignee: `@owner-backend`

### Objective
Ship remote work spaces and nomad service booking capabilities.

### Scope
- Desk/pass inventory model.
- Service catalog and booking APIs.
- Frontend discovery + purchase UX.

### Acceptance Criteria
- Remote work services searchable and bookable.
- Inventory constraints enforced.
- KPI events emitted for nomad funnel.

### Definition of Done
- [ ] APIs + model complete
- [ ] Frontend flow complete
- [ ] KPI instrumentation verified

---

## ISSUE 19 — Full Observability Stack and SLO Dashboards
Labels: `sre` `observability` `priority:high` `milestone:M8`
Assignee: `@owner-sre`

### Objective
Implement production-grade observability and SLO monitoring.

### Scope
- Metrics + traces + structured logs.
- Service and queue dashboards.
- Alert routing policies.

### Acceptance Criteria
- Core services have p95 latency/error dashboards.
- Queue health and payment reconciliation dashboards exist.
- Alerts route to on-call channels.

### Definition of Done
- [ ] Instrumentation merged
- [ ] Dashboards published
- [ ] Alert rules verified

---

## ISSUE 20 — Launch Readiness and Rollback Drill
Labels: `release` `ops` `qa` `priority:high` `milestone:M8`
Assignee: `@owner-product-ops`

### Objective
Finalize go-live readiness with rehearsed rollback.

### Scope
- Cross-team launch checklist.
- Incident/rollback rehearsal.
- Stakeholder sign-off workflow.

### Acceptance Criteria
- Checklist complete with evidence.
- Rollback drill executed successfully.
- Final go/no-go review documented.

### Definition of Done
- [ ] Checklist signed
- [ ] Drill report attached
- [ ] Launch approval recorded
