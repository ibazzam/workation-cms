# Transport & Booking — Design Doc (Schedule, Inventory, Holds)

Status: Draft — created 2026-03-04

Summary
- Purpose: define data models, APIs, concurrency/migration strategy, failure/recovery patterns, and test/observability requirements to make Maldives transport (speedboat + domestic flights) production-grade for multi-leg bookings.

Scope
- Schedule & inventory canonical models
- Seat-level holds and idempotent commit/cancel flows
- Quote + hold lifecycle and fare-lock policy
- Reconciliation and disruption handling
- Provider adapter/resilience patterns and DLQ

Data Models (examples)
- transport_operators: id, name, provider_type, contact, config
- transport_routes: id, origin_island_id, destination_island_id, distance_km, route_meta
- transport_schedules: id, route_id, operator_id, departure_at (timestamptz), arrival_at, frequency, published_version
- transport_inventory: id, schedule_id, seat_class, total_seats, available_seats, metadata
- transport_holds: id, quote_id, schedule_id, seat_class, seats_reserved, status{held,confirmed,expired,cancelled}, ttl_expires_at, idempotency_key, created_by
- disruptions: id, schedule_id, type, severity, reported_at, provider_event_id, metadata

API Design (surface)
- GET /api/v1/transport/schedules?origin=&dest=&date= -> list schedules
- GET /api/v1/transport/schedules/{id}/inventory -> seat counts
- POST /api/v1/transport/quotes -> {legs[], passenger_count, extras} -> returns quote_id, price, ttl
- POST /api/v1/transport/holds -> {quote_id, schedule_id, seats, idempotency_key} -> returns hold_id, ttl_expires_at
- POST /api/v1/transport/holds/{hold_id}/confirm -> confirms hold -> creates booking
- POST /api/v1/transport/holds/{hold_id}/cancel -> cancels hold
- Webhooks: /internal/webhooks/provider-schedule-update, provider-disruption

Concurrency & Correctness
- Use DB transactions for inventory decrements when confirming holds; hold creation writes a durable hold row but does NOT decrement available_seats until confirm (or use a reserved counter).
- Two patterns:
  - Optimistic: decrement available_seats on confirm; keep hold tokens to prevent races.
  - Pessimistic: reserve (decrement reserved_seats) on hold create, release on cancel/expire, commit on confirm.
- Recommendation: implement reserved_seats field on `transport_inventory` and update with transactional checks (SELECT ... FOR UPDATE) for safety under concurrency.
- Use idempotency keys for hold create/confirm to make retries safe.

Migration & Backfill Plan
- Add new tables with nullable columns; deploy adapters that read from both old and new sources (feature-flagged).
- Backfill script: incremental jobs to copy provider schedules to `transport_schedules` with mapping rules; run in staging first.
- Cutover window: read-only migration validation run, then switch consumers to new APIs.

Provider Integration & Resilience
- Provider adapter interface: `fetchSchedules()`, `fetchInventory()`, `reserveSeats()`, `confirmSeats()`, `cancelReservation()`.
- Wrap calls in retry/circuit-breaker middleware; surface provider health metrics.
- Failed provider interactions go to DLQ with metadata for manual reconciliation.

Disruption Handling
- Ingest provider disruption events into `disruptions` table.
- Match affected bookings via schedule_id/time-window and mark bookings as `needs_reaccommodation`.
- Automated suggestions: query next-available schedules, present options ranked by minimal user impact; keep an operator manual queue for escalations.

Testing & CI
- Contract tests: schedule, inventory, hold lifecycle with mocked provider adapters.
- Concurrency tests: run synthetic concurrent hold/create/confirm at 50–200 clients to validate no double-book.
- Load scripts: k6 scenarios for multi-leg shopping + checkout.
- CI gate: require contract tests + smoke load for transport/booking PRs.

Observability & Alerts
- Metrics: hold_success_rate, hold_ttl_expiry_rate, inventory_decrement_failures, provider_error_rate, DLQ_size
- Traces: instrument quote→hold→confirm flow with trace ids
- Alerts: DLQ growth, provider circuit open, hold failure rate spike

Rollout & Monitoring
- Feature-flag rollouts for hold behavior (pessimistic vs optimistic) to ramp.
- Run canary on limited operator set for 48–72 hours, monitor metrics and error budgets.

Acceptance Criteria (minimal)
- No double-booking under concurrency tests
- Quote→hold→confirm end-to-end succeeds in staging with TTL behavior
- Provider failures isolated; DLQ captures failures with a visible support workflow

Estimated effort & owners
- Sprint 1: schedules + inventory + seat hold primitives + contract tests (2 backend engineers)
- Sprint 2: quote/hold lifecycle, fare-locks, DLQ, basic re-accommodation, load baselines (2 backend, 1 frontend, 1 QA, 1 ops)

Open decisions
- Pessimistic vs optimistic hold default (recommended: pessimistic with reserved_seats)
- Exact TTL defaults per provider — to be configured per operator in registry

Next steps
1. Run 1‑day design workshop to finalize TTL policy, reserve vs confirm approach, and provider adapter contract.
2. Create starter branch for epic #27 with schema migration PR and sample provider adapter scaffold.
3. Implement contract tests and CI gating.

References
- Docs: `docs/transport-booking-epics.md`, `docs/transport-2-sprint-plan.md`
