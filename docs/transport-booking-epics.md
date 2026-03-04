## Transport & Booking — Prioritized Epics and Tasks

Purpose: make Maldives transport and multi-leg booking production-grade by handling schedules, inventory, quoting/holds, disruption recovery, and provider hardening.

Priority order (short to long): Schedule & Inventory → Quoting/Holds/Fare-Lock → Disruption & Re-accommodation → Provider Hardening & Integrations → Testing/CI/Observability

---

### Epic 1 — Schedule & Inventory (Highest priority)
- Goal: Reliable authoritative transport schedules with seat/inventory accounting and operator timetables.
- Outcomes: accurate availability, race-free inventory, route metadata for planner.
Tasks:
  - Add normalized `transport_schedules` and `transport_inventory` models.
  - Implement seat-level hold/commit APIs with idempotency keys.
  - Create reconciliation job to re-sync provider schedule deltas nightly and on webhooks.
  - Add data validators for timetable overlaps, timezone normalization, and route leg mapping.
Acceptance criteria:
  - Schedules published to staging show identical search results for 10k synthetic queries.
  - Inventory holds succeed under 50 concurrent requests with 0 double-bookings.
Estimate: 1–2 sprints

---

### Epic 2 — Quoting, Hold Expiry & Fare Lock
- Goal: Provide accurate quotes, temporary holds, and configurable fare-lock windows for multi-leg bundles.
Tasks:
  - Implement quote API that composes transport+accommodation+extras and returns TTL and hold token.
  - Implement hold lifecycle (create, extend, confirm, expire) with background cleanup and notifications.
  - Add fare-lock policy engine (per-provider TTL, retry/backoff, soft/hard holds).
  - UI/checkout flow changes to display hold expiry and explain failure modes.
Acceptance criteria:
  - Quote->hold->confirm flow completes end-to-end in staging with correct ledger entries and no orphan holds after TTL.
Estimate: 1–2 sprints

---

### Epic 3 — Disruption & Re-accommodation
- Goal: Handle delays/cancellations (sea/air) with automatic rebooking suggestions and vendor compensation rules.
Tasks:
  - Introduce `disruption` events and mapping to affected bookings.
  - Implement automated re-accommodation engine with rule priorities (same-day alternative, next-available, vendor-assisted manual queue).
  - Build notifier integrations (email/SMS/in-app) and operator admin queue for manual resolution.
Acceptance criteria:
  - Simulated delay scenario triggers suggested rebooking options and records chosen resolution path.
Estimate: 1–2 sprints

---

### Epic 4 — Provider Hardening & Integration Patterns
- Goal: Make provider integrations resilient and observable (timeouts, retries, circuit breaker, SLAs).
Tasks:
  - Implement a provider adapter interface and common retry/circuit-breaker middleware.
  - Add provider health-checks and SLAs to registry; surface degraded providers in admin UI.
  - Create dead-letter queue for failed provider messages and a support workflow for manual reconciliation.
Acceptance criteria:
  - One failing provider does not impact unrelated routes; failures routed to DLQ and visible in UI.
Estimate: 1 sprint

---

### Epic 5 — Testing, Observability & CI Gates
- Goal: Ensure correctness via contract tests, load tests, and CI gating for transport/booking changes.
Tasks:
  - Add contract tests for schedule/inventory/hold APIs with mock provider fixtures.
  - Add load test scenarios for peak-season multi-leg booking (artillery/k6 scripts) and record baseline.
  - Add CI gate: require contract tests + basic load smoke before merging transport or booking changes to main.
Acceptance criteria:
  - Contract matrix green on PRs; load smoke passes within acceptable latencies on staging.
Estimate: 1 sprint (plus ongoing maintenance)

---

Next steps:
- Create GitHub issues from each task above and tag them `transport`, `booking`, `high-priority`.
- Kickoff: run a 1‑day design session with product, backend, and ops to finalize acceptance criteria and data contracts.

Contact: I can open the issues and suggest assignments/estimates — say `create_issues` to proceed.
