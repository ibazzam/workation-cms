# Workation Maldives — 2-Week Sprint Board

Date: 2026-02-21
Cadence: 2-week sprints

Owner legend:
- Platform
- Backend
- Frontend
- Data
- QA
- SRE
- Product/Ops

Status legend:
- Planned
- In Progress
- Done
- Blocked

## Sprint 1 (Weeks 1-2) — Architecture and Environment Lock

Goal: complete backend authority and PostgreSQL standardization.

| Work Item | Owner | Status | Acceptance Criteria | Dependencies |
|---|---|---|---|---|
| Remove Laravel API from active product path | Platform + Backend | Planned | `routes/web.php` API routes deprecated/removed behind cutover plan | Release window |
| Standardize env templates to PostgreSQL | Platform + Backend | Planned | Root and infra env examples use PostgreSQL only | None |
| Migration/rollback runbook | Data + Backend | Done | Documented and rehearsed rollback for latest schema | PostgreSQL lock |
| API versioning + compatibility checklist | Backend | Planned | PR checklist enforced for contract-safe changes | None |
| CI gate for schema drift | Data + Platform | Done | Pipeline fails on drift/missing migration | Migration scripts |

## Sprint 2 (Weeks 3-4) — Transport + Accommodation Depth

Goal: move beyond CRUD to inventory/schedule reality.

| Work Item | Owner | Status | Acceptance Criteria | Dependencies |
|---|---|---|---|---|
| Accommodation availability model | Backend + Data | Done | Inventory + blackout + min-stay enforced | Existing accommodation domain |
| Seasonal/dynamic pricing support | Backend | Done | Price quote endpoint returns correct seasonal output | Availability model |
| Speedboat timetable and seat inventory | Backend + Data | Done | Schedule endpoint + capacity decrement rules | Transport domain |
| Domestic flight schedule and fare classes | Backend + Data | Done | Flight schedule/fare schema + read API | Transport domain |
| Real-time disruption lifecycle | Backend + Data | Done | Delay/cancel/resolve + re-accommodation flows pass contracts | Transport domain |
| Contract tests for above | QA + Backend | Done | New suites green in CI | Domain implementations |

## Sprint 3 (Weeks 5-6) — Booking Orchestration v1

Goal: itinerary-aware booking and booking state machine.

| Work Item | Owner | Status | Acceptance Criteria | Dependencies |
|---|---|---|---|---|
| Booking state machine | Backend | Done | Draft/Hold/Confirmed/Cancelled transitions validated | Existing booking model |
| Itinerary dependency engine | Backend | Done | Enforces arrival-transfer-stay constraints | Transport + accommodation depth |
| Unified cart for stay + transport | Backend + Frontend | Done | Single checkout payload supports multi-service cart | State machine |
| Rebooking/change management backend flow | Backend | Done | Owner-scoped rebook cancels original and creates validated replacement | State machine + cart |
| Booking management UI | Frontend | Done | User can view/change/cancel eligible bookings | Backend APIs |
| E2E journey tests | QA | Done | search->book->pay->manage passes | Backend + frontend flows |

## Sprint 4 (Weeks 7-8) — Payments Productionization

Goal: harden payment reliability and finance operations.

| Work Item | Owner | Status | Acceptance Criteria | Dependencies |
|---|---|---|---|---|
| Refund/partial refund APIs | Backend + Finance Ops | Planned | Refund lifecycle reflected in payment/booking states | Existing payments |
| Provider resilience layer | Backend + SRE | Planned | Retries/timeouts/circuit behavior tested | Adapter integrations |
| Reconciliation dashboard UX | Frontend | Planned | Finance can inspect runs/failures and actions | Existing admin payments APIs |
| Finance export/reporting | Backend + Product/Ops | Planned | Daily reconciliation and settlement report export | Reconciliation data |
| Payment incident runbook | SRE + Product/Ops | Planned | Runbook approved and tested | Observability hooks |

## Sprint 5 (Weeks 9-10) — Marketplace Vertical A

Goal: launch mobility and excursions verticals.

| Work Item | Owner | Status | Acceptance Criteria | Dependencies |
|---|---|---|---|---|
| Car/bike rental domain | Backend + Data | Planned | Inventory + booking + policy support available | Booking/cart |
| Excursions domain | Backend + Data | Planned | Slot/capacity rules and booking endpoints live | Booking/cart |
| Frontend listing/booking for both | Frontend | Planned | Users can discover and add to cart | APIs ready |
| Contract + E2E coverage | QA | Planned | New vertical tests stable in CI | Domain + UI |

## Sprint 6 (Weeks 11-12) — Marketplace Vertical B

Goal: launch restaurants and resort day visits.

| Work Item | Owner | Status | Acceptance Criteria | Dependencies |
|---|---|---|---|---|
| Restaurant reservation domain | Backend + Data | Planned | Table/time-slot/deposit logic works | Booking/cart |
| Resort day pass domain | Backend + Data | Planned | Quota + transfer bundle rules supported | Transport + booking |
| Frontend reservation/day-visit UX | Frontend | Planned | Customer can reserve and confirm payment | APIs ready |
| Ops tooling for partners | Product/Ops + Frontend | Planned | Vendor/admin can manage inventory/windows | Domain APIs |

## Sprint 7 (Weeks 13-14) — Digital Nomad Layer

Goal: ship remote work services.

| Work Item | Owner | Status | Acceptance Criteria | Dependencies |
|---|---|---|---|---|
| Coworking inventory model | Backend + Data | Planned | Desk/pass inventory and booking windows | Booking/cart |
| Nomad services catalog | Backend | Planned | Service categories and availability APIs | Catalog core |
| Frontend nomad workflow | Frontend | Planned | Search and purchase flow usable | APIs ready |
| KPI instrumentation | Platform + Product/Ops | Planned | Funnel metrics emitted per atoll/island | Observability base |

## Sprint 8 (Weeks 15-16) — Reliability, Security, Launch Gate

Goal: operational launch readiness.

| Work Item | Owner | Status | Acceptance Criteria | Dependencies |
|---|---|---|---|---|
| Full observability stack (metrics, traces, logs) | SRE + Platform | Planned | Dashboards + alerts cover core SLOs | Service instrumentation |
| Load and resilience testing | QA + SRE | Planned | Peak profile tests meet threshold | Domain completeness |
| Security hardening pass | Platform + Backend | Planned | Rate limits, secrets, dependency scans enforced | CI gates |
| Go-live checklist + rollback drill | Product/Ops + SRE | Planned | Signed-off checklist and tested rollback | All prior sprints |

## Rolling Backlog (Across all Sprints)

- [ ] Expand typed DTO sharing between frontend and backend contracts.
- [ ] Complete React Query migration for all remaining pages/components.
- [ ] Add vendor tenant isolation and policy testing matrix.
- [ ] Improve content quality workflows for island/service pages.
- [ ] Add multilingual and regional support strategy.
