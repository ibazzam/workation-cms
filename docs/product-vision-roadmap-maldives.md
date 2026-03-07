

## 1) End-Product Scope (Vision to Capability Map)

Core customer outcomes:
- Book accommodation across local islands, guesthouses, and boutique hotels.
- Book inter-island transport via speedboat ferries and domestic flights.
- Add on-island mobility (car/bike rental where available).
- Discover and book activities, excursions, and leisure.
- Reserve restaurants and resort day visits.
- Access remote work / digital nomad services.
- Plan by atoll and island with realistic transport dependencies.
- Read and post customer reviews/ratings for properties and service providers.
- Discover social proof through property/service social media touchpoints.

## 2) Complete Task List (Master To-Do)

## A. Foundation and Architecture
- [ ] Finalize single-backend authority and decommission legacy Laravel API paths.
- [x] Lock environment configs to PostgreSQL everywhere (dev/staging/prod + docs + templates).
- [ ] Enforce API contract/version policy for all new domains.
- [x] Implement schema governance (migration checklist, rollback playbook, seed strategy).
- [x] Set up feature flags for gradual rollouts by domain.

## B. Identity, Auth, and Access
- [x] JWT auth + role-based guards in backend.
- [x] Add vendor-scoped authorization rules (tenant ownership boundaries).
- [x] Add customer identity lifecycle (profile completeness, preferences).
- [x] Add audit logging for admin actions across all write endpoints.
- [ ] Add permission matrix docs + automated tests for all role/resource combinations.

## C. Geographic and Catalog Core
- [x] Islands/atolls read APIs.
- [x] Countries + service categories APIs.
- [ ] Add island metadata completeness (facilities, connectivity, emergency services).
- [ ] Add geospatial search support (by atoll + nearby + route relevance).
- [ ] Build canonical taxonomy for accommodation/transport/activity categories.

## D. Accommodation Domain
- [x] Base accommodation CRUD/read endpoints.
- [x] Availability model (room inventory, blackout dates, min-stay).
- [x] Pricing model (seasonality, occupancy tiers, promotions, currency conversion).
- [ ] Policies (cancellation, no-show, children, taxes/fees).
- [ ] Media/content quality rules and moderation pipeline.

## E. Transport Domain (Maldives Reality-Critical)
- [x] Base transport CRUD/read endpoints with route fields.
- [x] Speedboat schedule model (operator timetables, frequency, seat inventory).
- [x] Domestic flight schedule model (airline, class, baggage, fare families).
- [x] Real-time/disruption model (delays, weather cancellations, re-accommodation rules).
- [ ] Transport quoting + hold expiry + fare lock strategy.

## F. Booking Orchestration
- [x] Basic booking creation and dependency checks.
- [x] Multi-leg itinerary planner (arrival + transfers + accommodation alignment).
- [x] End-to-end booking state machine (draft/hold/confirmed/cancelled/refunded).
- [x] Unified cart/checkout for multi-service bundles.
- [x] Rebooking and change management flows.

## G. Payments and Financial Ops
- [x] Payment intent creation + webhook processing + reconciliation background jobs.
- [x] Baseline loyalty program engine (tiered wallet + booking accrual + redemption + vendor offers).
- [ ] Provider hardening to production-grade SLA behavior (timeouts, circuit breaking, dead-letter escalation).
- [ ] Refunds/partial refunds + charge dispute handling.
- [ ] Settlement and payout reporting for vendors.
- [ ] Finance-ledger integration and tax invoice generation.

## H. New Marketplace Verticals (Vision Gaps)
- [ ] Car & bike rental domain (inventory, pickup/drop rules, age/license checks).
- [ ] Excursions/leisure booking domain (slots, capacity, equipment constraints).
- [ ] Restaurant reservation domain (tables, seating windows, deposit policies).
- [ ] Resort day visit domain (quota windows, transfer bundles, pass restrictions).
- [ ] Remote work spaces/digital nomad services domain (desk inventory, passes, connectivity quality).
- [x] Baseline customer reviews APIs for accommodations/transports (create + public listing + rating summary + verified-stay flag).
- [x] Baseline social media links APIs for accommodations/transports (public listing + admin/vendor CRUD with scope rules).
- [x] Moderation baseline for customer reviews (flagging + admin queue + hide/publish for accommodation/transport reviews).
- [x] Moderation baseline for social links (public approved-only visibility + flagging + admin queue + approve/hide).
- [ ] Expanded customer reviews/ratings domain (activity/service reviews, moderation reasons, and deeper trust/safety workflows).
- [ ] Expanded social media integration layer (embed policies, richer UGC trust/safety controls, and content quality tooling).

## I. Frontend Product UX
- [x] React Query + typed client migration started for admin and key public pages.
- [ ] Complete migration of all pages to shared typed hooks and query keys.
- [ ] End-user search/discovery UX by atoll/island with dependency-aware suggestions.
- [ ] Checkout UX for bundled transport + stay + activities.
- [ ] Customer booking management UX (changes, cancellations, credits/refunds).

## J. Ops, Reliability, and Observability
- [x] Background job runners and operational alert endpoints implemented.
- [x] Cloudflare integration baseline runbook + preflight verification script added.
- [ ] Cloudflare staging cutover executed (DNS/SSL/cache/WAF) and preflight checks passed on hosted domains.
- [ ] External observability stack (metrics, tracing, structured logs, SLO dashboards).
- [ ] Incident runbooks for weather/service disruptions and provider outages.
- [ ] Queue SLOs and automated alert routing (pager/Slack/email).
- [ ] Load/performance testing for peak season scenarios.

## K. Quality, Security, and Compliance
- [x] Broad backend contract tests exist across current domains.
- [ ] Expand contract + integration coverage for all new verticals.
- [x] Add E2E user journey tests (search → book → pay → manage).
- [ ] Security hardening (rate limits, abuse prevention, secret rotation, dependency scanning).
- [ ] Data governance (PII retention, backups/restore drills, GDPR-like controls as required).

## L. Business and Launch Readiness
- [ ] Vendor onboarding workflows and SLA contracts.
- [ ] Content ops workflows for island/service quality curation.
- [ ] Customer support tooling (case management, compensation policies).
- [ ] KPI instrumentation (conversion funnel by atoll, route completion, failed checkout reasons).
- [ ] Go-live readiness checklist per milestone.

## 3) Milestone Chart

| Milestone | Goal | Key Deliverables | Exit Criteria | Target Window |
|---|---|---|---|---|
| M1 Foundation Freeze | Single architecture + DB consistency | Backend authority decision executed, PostgreSQL standardized, migration playbooks | Legacy API no longer on critical path; env parity confirmed | Weeks 1-2 |
| M2 Travel Core v1 | Geography + accommodation + transport base | Islands/atolls, accommodations, transport CRUD/read, contract coverage | Search/list/detail stable in staging | Weeks 3-5 |
| M3 Booking Engine v1 | Real itinerary-capable booking | Multi-leg orchestration, booking state machine, dependency validation | End-to-end book flow for sample atoll routes | Weeks 6-8 |
| M4 Payments Productionization | Reliable payment lifecycle | Webhooks/reconcile hardening, refunds, failure policies, finance reports | Payment SLO + reconciliation reliability targets met | Weeks 9-10 |
| M5 Marketplace Expansion A | Add mobility + activities | Car/bike rental + excursions domains + checkout integration | Two new verticals bookable in staging | Weeks 11-13 |
| M6 Marketplace Expansion B | Add dining + resort day visits | Restaurant reservation + resort day-pass modules | Four+ verticals unified in one checkout experience | Weeks 14-16 |
| M7 Digital Nomad Layer | Remote work services | Coworking desk inventory, passes, nomad utilities | Remote-work vertical live with booking + payment | Weeks 17-18 |
| M8 Launch Readiness | Operational excellence + launch controls | Full observability stack, support tooling, load/security sign-off | Go-live checklist complete, rollback tested | Weeks 19-20 |

## 4) Delivery Review — What Is Done vs What Is Pending

## Completed / In Progress (Good Momentum)
- Backend modular architecture implemented for key domains and admin operations.
- JWT + RBAC enforcement in place.
- Current core APIs implemented: islands, accommodations, transports, bookings, payments, vendors, countries, service categories.
- Payment operational foundation exists: webhook processing, reconciliation runner, background jobs, alerts endpoints.
- Frontend modernization underway: React Query + typed API client/hooks added and applied across admin surfaces and key pages.
- Extensive contract testing exists for currently implemented backend domains.

## Still Pending (Critical to Vision Completion)
- Full product verticals missing: car/bike rentals, excursions, restaurant reservations, resort day visits, remote work services.
- Reviews/social/social-loyalty baselines now include first-pass moderation, but deeper trust & safety and advanced campaign tooling are still pending.
- Transport and booking models need production-grade schedule/inventory/disruption handling for Maldives sea/air realities.
- Rebooking/change management and customer self-service post-booking flows are not complete.
- External observability stack and SLO-driven operations are not complete yet.
- Final backend authority cleanup and legacy Laravel route removal still pending.
- End-to-end customer journey tests and launch readiness controls still pending.

## 5) Suggested Priority Order (Execution)

1. Complete M1: backend authority + PostgreSQL lock-in everywhere.
2. Finish M3 booking orchestration depth (itinerary + state machine + bundled checkout).
3. Productionize M4 payments reliability and refunds.
4. Ship M5/M6 new vertical domains in two waves (mobility/activities then dining/day visits).
5. Add M7 digital nomad service layer.
6. Close M8 with observability, security, load, support, and launch controls.

## 6) KPI Targets to Track Per Milestone

- Search-to-book conversion by atoll/island.
- Payment success rate and reconciliation lag.
- Booking completion time for multi-leg itineraries.
- Queue dead-letter rate and retry recovery rate.
- Cancellation/refund cycle time.
- API p95 latency and error rate by domain.

## 7) Actionable Task Lists (Current)

### A) Immediate Go-Live Task List (Staging/Preview)
- [x] Run full backend contract matrix and confirm green status (134/134 pass, 0 fail).
- [x] Run local pre-host smoke journey (search → book → pay → manage) and confirm pass.
- [x] Run local pre-host moderation verification (reviews/social moderation flows) and confirm pass.
- [x] Run local pre-host ops verification (payments admin jobs/alerts/reconcile endpoints) and confirm pass.
- [x] Create and verify `api.workation.mv` DNS + origin mapping.
- [x] Deploy backend to Render on `main` (authority backend live).
- [x] Execute live preflight in hosting environment (`Live preflight passed` on 2026-03-07).
- [ ] Verify admin moderation paths in hosting (review flag/hide/publish and social flag/approve/hide).
- [ ] Verify scheduler health in hosting (reconciliation runner + cleanup task logs).
- [x] Confirm rollback path and environment variable parity before production promotion (legacy Laravel routes now env-guarded).

### B) Production Readiness Task List (Before Public Launch)
- [ ] Add moderation reason codes and reviewer notes for reviews/social trust & safety workflows.
- [ ] Add rate-limiting and abuse controls on review/social write endpoints.
- [ ] Complete observability baseline (metrics, structured logs, alert routes, on-call runbook links).
- [ ] Run load/performance test for booking + payments critical paths and record SLO baselines.
- [ ] Run security pass (secrets audit, dependency scan, auth hardening checks).

### C) Next Sprint Build List (Product Expansion)
- [ ] Implement activity/service review targets and include moderation queue support.
- [ ] Extend social integration with embed policy controls and UGC safety validation.
- [ ] Complete frontend typed hook migration for remaining customer flows.
- [ ] Add deployment gate in CI requiring full contract matrix pass before promote.

## 8) Live Finish Now (Render + Neon)

Current state
- Authority backend is live on Render at `https://api.workation.mv` (branch: `main`).
- Hosted health check is passing at `GET /api/v1/health`.
- Hosted live preflight is passing with bearer-token auth.

Immediate execution steps
1. Keep Render pinned to `main` and verify env parity on each deploy (`DATABASE_URL`, `AUTH_JWT_SECRET`, auth flags).
2. Run hosted preflight with bearer token:
	- `BASE_URL=https://api.workation.mv SCHEDULE_ID=<known_schedule_id> AUTH_BEARER_TOKEN=<jwt> npm.cmd run live:preflight`
3. Track auth secret rotation and token issuance in ops runbook (no secrets in terminal history).
4. Begin staged decommission of Laravel legacy business routes using rollback guard from `docs/wb-201-authority-cutover-runbook.md`.

Success criteria for this step
- Hosted health check passes.
- Hosted workation CRUD passes in preflight.
- Hosted transport domain smoke passes in preflight (legacy holds if available, otherwise transports schedule/list fallback).
- No critical errors in Render logs during preflight window.

## 9) Repository Reality Scan (2026-03-07)

This section validates current implementation against go-live priorities using code currently in this workspace.

### A) Confirmed Implemented (Code Evidence)
- Transport hold lifecycle API exists in Laravel routes and controller:
	- `routes/web.php`
	- `app/Http/Controllers/TransportHoldController.php`
- Hold inventory reservation/release with DB transaction and idempotency:
	- `app/Models/TransportHold.php`
	- `app/Models/TransportInventory.php`
- Provider outbound job queue + retry processing command:
	- `app/Models/TransportProviderJob.php`
	- `app/Console/Commands/ProcessTransportProviderJobs.php`
- Expired hold reconciliation command:
	- `app/Console/Commands/ReconcileTransportHolds.php`
- Basic Workation CRUD API:
	- `routes/web.php`
	- `app/Http/Controllers/WorkationController.php`
- Baseline tests for current Laravel transport/workation scope:
	- `tests/Feature/TransportHoldTest.php`
	- `tests/Feature/TransportProviderJobsTest.php`
	- `tests/Feature/WorkationApiTest.php`

### B) Confirmed Gaps / Mismatch With Vision Claims
- Backend authority cutover not complete:
	- Laravel API is still active and currently serves functional domain endpoints (`routes/web.php`).
	- New backend under `infra/backend/src` exposes only health endpoint (`health.controller.ts`).
- Full domain surface not present in runnable API routes:
	- No route/controller evidence in this workspace for islands/accommodations/transports CRUD, bookings, payments, vendors, reviews/social APIs beyond hold/workation endpoints.
- PostgreSQL lock-in not complete across root Laravel env template:
	- Root `.env.example` defaults to SQLite (`DB_CONNECTION=sqlite`).
	- `config/database.php` default connection is env-driven with sqlite fallback.
- Frontend migration is early-stage in `infra/frontend`:
	- Pages are placeholders and rely on a simple reachability fetch (`components/RemoteStatus.tsx`).
	- No typed client or React Query implementation found in `infra/frontend`.
- E2E readiness is limited:
	- Current E2E script assumes static `schedule_id=1` in target env (`tests/e2e/transport-hold.mjs`).
	- Staging smoke does not yet represent full search->book->pay->manage flow in this codebase.
- Observability/SLO stack remains planning-level in docs:
	- No dedicated tracing/metrics exporters or SLO dashboard configuration found in app/infra code.

### C) Immediate Endpoints Missing For M1-M4 Exit
- New authority backend missing business endpoints (only `/health` exists in `infra/backend/src`).
- Missing explicit API endpoints for:
	- Transport schedules search/list and inventory quoting.
	- Disruption ingestion and re-accommodation orchestration.
	- Booking state transitions and customer booking management.
	- Payment refunds/partial refunds/dispute lifecycle.
	- Vendor settlement and payout reporting.

## 10) Prioritized 2-Week Sprint Board (Top 6)

Owners are role-based so this can be applied immediately even if personnel shift.

| Ticket | Priority | Owner | Scope | Dependencies | Done Criteria |
|---|---|---|---|---|---|
| WB-201 M1 Authority Cutover Plan | P0 | Backend Lead | Define and execute cutover from Laravel business routes to `infra/backend`; keep Laravel as fallback only during transition window | Architecture sign-off | Approved cutover ADR, endpoint parity checklist, rollback steps tested |
| WB-202 PostgreSQL Env Parity | P0 | DevOps Lead | Standardize PostgreSQL defaults across root env templates, CI, local compose, and staging/prod docs | WB-201 | All env templates point to PostgreSQL, bootstrap scripts and docs updated, smoke migration passes in dev+staging |
| WB-203 Transport Production API v1 | P0 | Transport Backend Engineer | Add schedule inventory query + hold TTL/expiry semantics + disruption event ingestion + rebooking policy MVP on authority backend | WB-201, WB-202 | Contract tests for schedule/hold/disruption pass, no double-booking under concurrency test, disruption event creates actionable rebooking candidates |
| WB-204 Booking/Checkout Reliability | P1 | Checkout Engineer | Implement itinerary hold coherence, fare-lock expiry behavior, and deterministic checkout failure handling | WB-203 | Search->hold->checkout journey passes with hold expiry edge cases; failed checkout rate baseline captured |
| WB-205 Payments Refunds and Settlement Core | P1 | Payments Engineer | Add refund/partial-refund workflow, dispute state tracking, and settlement reporting export for vendors | WB-201, WB-202 | Refund and partial-refund happy/failure paths covered by integration tests; settlement report generated and reconciled |
| WB-206 Observability + Staging Smoke Gate | P1 | SRE/Platform Engineer | Establish p95/p99 + error-rate dashboards for booking/payments, alert routes, and a staging smoke gate for deploys | WB-203, WB-204, WB-205 | Dashboard live, alert routing tested, staging deploy blocked unless smoke + key SLO checks pass |

### Sprint Cadence (2 Weeks)
- Days 1-2: WB-201, WB-202 design and environment parity.
- Days 3-6: WB-203 implementation and transport contract tests.
- Days 5-8: WB-204 checkout reliability in parallel with WB-203 stabilization.
- Days 7-10: WB-205 refunds/settlement workflows.
- Days 9-10: WB-206 dashboards, alerts, and staging smoke gate activation.

### Suggested Owner Matrix
- Backend Lead: authority cutover, endpoint parity, legacy route retirement.
- Transport Backend Engineer: schedules/inventory/disruption/rebooking.
- Checkout Engineer: bundled checkout reliability and booking management states.
- Payments Engineer: refunds, disputes, settlement/payout exports.
- DevOps Lead: PostgreSQL parity, staging/prod config consistency.
- SRE/Platform Engineer: metrics/tracing/SLOs, alerting, load/smoke gates.

## 11) Execution Log

- 2026-03-07: `WB-201` started.
	- Added authority cutover runbook with phased plan, endpoint parity checklist, and rollback playbook:
		- `docs/wb-201-authority-cutover-runbook.md`
- 2026-03-07: `WB-202` started.
	- Switched root non-test DB defaults to PostgreSQL:
		- `.env.example`
		- `config/database.php`
	- Added local development doc with explicit Postgres baseline and cutover note:
		- `docs/development.md`
	- Completed PostgreSQL parity for queue/failure defaults and CI integration smoke workflow:
		- `config/queue.php` (`batching.database` and `failed.database` default to `pgsql`)
		- `.github/workflows/integration-smoke.yml` migrated from SQLite to PostgreSQL service
- 2026-03-07: `WB-203` started.
	- Added authority backend transport API scaffold in `infra/backend`:
		- schedules list + inventory endpoints
		- hold create/confirm/release endpoints (`/api/v1` and legacy-parity `/api` paths)
		- disruption ingestion endpoint with re-accommodation queue status response
	- Added authority backend workations CRUD parity endpoint scaffold.
	- Verified `infra/backend` compiles successfully with `npm.cmd run build`.
	- Added transport quote endpoint for inventory/fare-class pricing behavior:
		- `GET /api/v1/transports/{id}/quote?guests=<n>&fareClassCode=<code>`
	- Extended hosted preflight transport smoke to exercise disruption ingestion + re-accommodation + resolve flow when fixture transports are available.
- 2026-03-07: `WB-204` started.
	- Added booking/checkout reliability scaffold endpoints in `infra/backend`:
		- `POST /api/v1/bookings/itinerary-hold`
		- `POST /api/v1/checkout/confirm`
		- `GET /api/v1/bookings/{id}`
	- Added in-memory draft-to-confirm booking flow with hold coherence checks.
- 2026-03-07: `WB-205` started.
	- Added payments reliability scaffold endpoints in `infra/backend`:
		- `POST /api/v1/payments/refunds`
		- `POST /api/v1/payments/disputes`
		- `GET /api/v1/payments/settlements/report`
	- Added in-memory refund/dispute records and settlement summary generation.
- 2026-03-07: `WB-206` started.
	- Added observability baseline scaffold in `infra/backend`:
		- Request timing/error capture middleware
		- `GET /api/v1/ops/slo-summary` for p95/p99 and error-rate snapshot
		- `GET /api/v1/ops/metrics` Prometheus-style metrics output
- 2026-03-07: Authority backend persistence migration completed for current scaffold.
	- Replaced in-memory controllers with Prisma-backed persistence (`infra/prisma/schema.prisma`).
	- Added Prisma service wiring and response mappers in `infra/backend/src`.
	- Added WB-201 contract parity script:
		- `infra/backend/scripts/contract-parity.cjs`
		- `npm.cmd run contract:test`
	- Verified Prisma client regeneration and backend TypeScript build.
- 2026-03-07: Live deployment stabilized on Render + Neon.
	- Promoted backend runtime parity from `chore/backend-deploy-fixes` to `main`:
		- `9f52a3bd` (`Sync main backend runtime with deployed deploy-fixes branch`)
	- Updated hosted preflight to support current transports route shape and bearer-only auth:
		- `8c370957` (`Make live preflight compatible with transports schedule endpoints`)
	- Verified live API and preflight against `https://api.workation.mv`:
		- `GET /api/v1/health` = 200
		- `node tests/e2e/live-preflight.mjs` = `Live preflight passed`
- 2026-03-07: `WB-201` guarded decommission completed.
	- Legacy Laravel business routes moved behind emergency rollback flag:
		- `routes/web.php` now requires `LEGACY_LARAVEL_BUSINESS_ROUTES_ENABLED=true` to re-enable legacy routes outside testing.
	- Added rollback flag documentation in root env template:
		- `.env.example`
