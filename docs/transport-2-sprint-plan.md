## Transport & Booking — 2‑Sprint Plan

Purpose: Execute the highest-risk work to make Maldives transport and multi-leg booking production-ready across two focused sprints.

Sprint cadence: 2-week sprints. Each sprint has a sprint owner (PM/Tech lead), 2 backend engineers, 1 frontend engineer, 1 QA/automation engineer, and 1 DevOps/ops engineer.

Sprint 1 — Core schedule/inventory + contracts (Weeks 1-2)
- Sprint Goal: Implement authoritative schedule & inventory models, seat-level holds, and foundational contract tests so the booking engine can rely on accurate availability.
- Owners: Product/PM: @product-lead (assign); Tech Lead: @tech-lead (assign)
- Backlog (high priority):
  - #32 Add `transport_schedules` and `transport_inventory` models
  - #33 Implement seat-level hold/commit APIs with idempotency
  - #34 Reconciliation job for provider schedule deltas
  - #35 Data validators for timetables and route mapping
  - #45 Contract tests for schedule/inventory/hold APIs (add mocks/fixtures)
  - DevOps: staging infra ready for synthetic load tests
- Acceptance Criteria:
  - Staging exposes seeded schedules for at least 3 operators and search returns consistent results for sample routes.
  - Seat-level hold flow succeeds under 50 concurrent synthetic clients without double-booking.
  - Contract tests pass in CI for the affected APIs.
- CI/QA:
  - Add contract tests to PR CI; require green before merge.
  - Smoke load script (subset) to run in staging post-deploy.

Sprint 2 — Quoting, holds, fare-locks, and resilience (Weeks 3-4)
- Sprint Goal: Add quoting + hold lifecycle, fare-lock policies, re-accommodation basics, and provider hardening; finalize load baselines and CI gates.
- Owners: Product/PM: @product-lead; Tech Lead: @tech-lead
- Backlog (high priority):
  - #36 Implement quote API composing transport+accommodation+extras
  - #37 Implement hold lifecycle and cleanup
  - #38 Add fare-lock policy engine
  - #40 Automated re-accommodation engine (MVP)
  - #41 Notifier integrations and operator admin queue (basic)
  - #42 Provider adapter interface and middleware
  - #43 Provider health-checks and SLA registry
  - #44 Dead-letter queue for failed provider messages
  - #46 Load test scripts for peak-season multi-leg booking
  - #47 CI gate: require contract tests + load smoke
- Acceptance Criteria:
  - End-to-end quote→hold→confirm flow works in staging with TTL behavior and no orphan holds.
  - Provider failures are isolated (circuit-breaker), and DLQ captures failed events.
  - Load smoke completes within latencies consistent with SLO targets; CI gate enforced before merges.

Cross-sprint deliverables
- Observability: metrics for availability/hold success rate, span/tracing for booking flows, DLQ metrics.
- Testing: expand contract fixture set; automate synthetic scenarios for re-accommodation.
- UX: checkout messaging for hold TTL and failure modes (implement behind feature flag).

Risks & Mitigations
- Risk: Provider data inconsistency. Mitigation: reconciliation job + admin reconcile reports + human review path.
- Risk: Race conditions in holds. Mitigation: idempotency keys, transactions, and contract tests under concurrency.
- Risk: CI false-positives due to external providers. Mitigation: mock provider adapters in CI and run provider integration tests separately in staging.

Next operational steps (immediate)
1. Confirm sprint owners and capacity; assign issue owners on GitHub.
2. Create sprint branches and PR templates for epic #27 (Schedule & Inventory).
3. Run a 1‑day design session to finalize data contracts and API schemas (invite backend, frontend, ops).

If you want, I can (choose):
- `assign_owners` — assign owners and estimates on the created issues automatically, or
- `create_pr_starters` — open starter branches/PR templates for epic #27.
