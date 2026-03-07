## WB-201 Authority Cutover Runbook

Status: In progress (live authority backend on main; Laravel legacy routes guarded by rollback flag)
Owner: Backend Lead

Purpose
- Establish `infra/backend` as the single business API authority.
- Decommission Laravel business routes after parity and smoke checks.
- Keep a controlled rollback path for one release window.

Current state (repo evidence)
- Legacy Laravel business routes are guarded in `routes/web.php` and disabled by default outside testing.
- Current Laravel endpoints:
  - `GET /api/workations`
  - `GET /api/workations/{workation}`
  - `POST /api/workations`
  - `PUT /api/workations/{workation}`
  - `DELETE /api/workations/{workation}`
  - `POST /api/transport/holds`
  - `POST /api/transport/holds/{hold}/confirm`
  - `POST /api/transport/holds/{hold}/release`
- New authority backend now exposes parity endpoints for workations and transport holds with Prisma-backed persistence:
  - `GET /health`
  - `GET/POST/PUT/DELETE /api/workations...`
  - `POST /api/transport/holds`, `/confirm`, `/release`

Definition of done (WB-201)
- Endpoint parity checklist completed for all in-scope business endpoints.
- Staging smoke journey green against `infra/backend`.
- Laravel business routes disabled (or guarded behind emergency flag).
- Rollback tested and documented.

## Latest Validation Snapshot (2026-03-07)

- Live authority backend URL: `https://api.workation.mv`
- Active deploy branch: `main`
- Health check: `GET /api/v1/health` => 200
- Auth mode: bearer token required (header fallback disabled)
- Hosted preflight: `Live preflight passed`
- Hosted moderation verification: `Moderation admin paths OK`
- Hosted scheduler verification: `Scheduler health endpoints OK`
- Key commits used for live stabilization:
  - `9f52a3bd` (`Sync main backend runtime with deployed deploy-fixes branch`)
  - `8c370957` (`Make live preflight compatible with transports schedule endpoints`)

Notes
- Legacy transport hold endpoints (`/api/v1/transport/holds`) are not exposed on current live runtime.
- Preflight now validates transports using legacy hold flow when present, otherwise current transports schedule/list smoke.
- Emergency rollback flag for Laravel legacy routes:
  - `LEGACY_LARAVEL_BUSINESS_ROUTES_ENABLED=true` (temporary rollback only)
  - Default should remain `false` in normal runtime.

## Phase Plan

Phase 0: Freeze and contract capture
- Freeze schema-changing and route-changing work on Laravel business endpoints.
- Capture request/response contracts for the 8 current Laravel endpoints.
- Record auth expectations, status codes, and error shapes.

Phase 1: Parity implementation in `infra/backend`
- Implement v1 business controllers/services in `infra/backend` for:
  - workations CRUD
  - transport hold create/confirm/release
- Keep response and error semantics aligned with Laravel behavior.
- Add request validation and idempotency behavior for holds.

Phase 2: Verification and shadow smoke
- Run contract tests against `infra/backend` implementation.
- Run smoke journey in staging: create hold -> confirm -> release; CRUD workation.
- Verify operational jobs still run as expected for hold reconciliation.

Phase 3: Controlled cutover
- Point staging API traffic to `infra/backend`.
- Keep Laravel business routes available behind emergency fallback for one release window.
- Monitor error rate, latency, and booking/hold failure metrics.

Phase 4: Laravel business route decommission
- Completed as guarded decommission:
  - Legacy Laravel business routes are disabled by default.
  - Emergency rollback path is env-guarded via `LEGACY_LARAVEL_BUSINESS_ROUTES_ENABLED`.
  - Keep only temporary rollback enablement during incident handling.

## Endpoint Parity Checklist

Use this table before cutover. Mark each item `done` only after tests pass.

| Endpoint | Source (Laravel) | Target (`infra/backend`) | Contract test | Staging smoke | Notes |
|---|---|---|---|---|---|
| `GET /api/workations` | yes | implemented | implemented | passed | Live preflight pass on 2026-03-07 |
| `GET /api/workations/{workation}` | yes | implemented | implemented | passed | Live preflight pass on 2026-03-07 |
| `POST /api/workations` | yes | implemented | implemented | passed | Live preflight pass on 2026-03-07 |
| `PUT /api/workations/{workation}` | yes | implemented | implemented | passed | Live preflight pass on 2026-03-07 |
| `DELETE /api/workations/{workation}` | yes | implemented | implemented | passed | Live preflight pass on 2026-03-07 |
| `POST /api/transport/holds` | yes | legacy path only | implemented | n/a | Live runtime validates transports via `/api/v1/transports*` routes |
| `POST /api/transport/holds/{hold}/confirm` | yes | legacy path only | implemented | n/a | Live runtime validates transports via `/api/v1/transports*` routes |
| `POST /api/transport/holds/{hold}/release` | yes | legacy path only | implemented | n/a | Live runtime validates transports via `/api/v1/transports*` routes |

Contract test command
- `cd infra/backend && npm.cmd run contract:test`

Staging smoke command
- `cd . && BASE_URL=https://api.workation.mv SCHEDULE_ID=1 AUTH_BEARER_TOKEN=<jwt> npm.cmd run live:preflight`

CI smoke gate workflow
- Workflow: `.github/workflows/live-preflight-gate.yml`
- Triggers:
  - automatic on `push` to `main`
  - manual `workflow_dispatch`
- Required repository secrets:
  - `LIVE_PREFLIGHT_BEARER_TOKEN`
  - `LIVE_PREFLIGHT_X_USER_ID` (optional if bearer is sufficient)
  - `LIVE_PREFLIGHT_X_USER_ROLE` (optional if bearer is sufficient)
- Optional repository variables for push-trigger defaults:
  - `LIVE_PREFLIGHT_BASE_URL` (default: `https://api.workation.mv`)
  - `LIVE_PREFLIGHT_SCHEDULE_ID` (default: `1`)
  - `LIVE_PREFLIGHT_REQUIRE_OPS_SLO` (`true`/`false`)
  - `LIVE_PREFLIGHT_REQUIRE_CHECKOUT_RELIABILITY` (`true`/`false`)
  - `LIVE_PREFLIGHT_REQUIRE_PAYMENTS_RELIABILITY` (`true`/`false`)
  - `LIVE_PREFLIGHT_REQUIRE_MODERATION_PATHS` (`true`/`false`)
  - `LIVE_PREFLIGHT_REQUIRE_SCHEDULER_HEALTH` (`true`/`false`)
- Strict gate inputs:
  - `require_ops_slo`
  - `require_checkout_reliability`
  - `require_payments_reliability`
  - `require_moderation_paths`
  - `require_scheduler_health`
- Use strict inputs as `true` only after the corresponding endpoints and fixtures are available in target runtime.

## Rollback Playbook

Trigger conditions
- Elevated 5xx rate above agreed threshold for 10 minutes.
- Hold creation/confirm failures exceed threshold.
- Critical booking or payment flow regression detected.

Rollback steps
1. Repoint API ingress back to Laravel business routes.
2. Disable `infra/backend` business route exposure at edge/gateway.
3. Re-run smoke checks on Laravel path:
   - workation CRUD
   - transport hold create/confirm/release
4. Preserve logs and request ids from failed interval for postmortem.
5. Open incident timeline and corrective ticket before re-attempt.

Post-rollback checks
- Confirm queue health and reconciliation jobs are processing.
- Confirm no orphan holds introduced during cutover window.
- Publish incident summary with root cause and next mitigation.

## Risks and Mitigations
- Risk: response-shape drift between Laravel and `infra/backend`.
  - Mitigation: contract fixtures from Laravel responses and strict parity tests.
- Risk: partial cutover with mixed writes.
  - Mitigation: single active writer policy during cutover window.
- Risk: hidden dependency on Laravel middleware.
  - Mitigation: explicit middleware parity checklist before traffic switch.
