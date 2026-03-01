# Workation Maldives — Product Roadmap, Task List, and Delivery Review

Date: 2026-02-22

Related planning artifact:
- Sprint-by-sprint execution board: `docs/sprint-board-maldives.md`
- GitHub issue templates pack: `docs/github-issues-maldives.md`

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
- [ ] Lock environment configs to PostgreSQL everywhere (dev/staging/prod + docs + templates).
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
- [ ] Create and verify `api.workation.mv` DNS + origin mapping (current blocker: hostname not resolving in preflight).
- [ ] Deploy backend + frontend to hosting preview/staging domain.
- [ ] Execute smoke journey in hosting environment (search → book → pay → manage).
- [ ] Verify admin moderation paths in hosting (review flag/hide/publish and social flag/approve/hide).
- [ ] Verify scheduler health in hosting (reconciliation runner + cleanup task logs).
- [ ] Confirm rollback path and environment variable parity before production promotion.

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
