## WB-201 Authority Cutover Runbook

Status: In progress (created 2026-03-07)
Owner: Backend Lead

Purpose
- Establish `infra/backend` as the single business API authority.
- Decommission Laravel business routes after parity and smoke checks.
- Keep a controlled rollback path for one release window.

Current state (repo evidence)
- Business routes are served from Laravel in `routes/web.php`.
- Current Laravel endpoints:
  - `GET /api/workations`
  - `GET /api/workations/{workation}`
  - `POST /api/workations`
  - `PUT /api/workations/{workation}`
  - `DELETE /api/workations/{workation}`
  - `POST /api/transport/holds`
  - `POST /api/transport/holds/{hold}/confirm`
  - `POST /api/transport/holds/{hold}/release`
- New authority backend currently exposes only health in `infra/backend/src/health.controller.ts`:
  - `GET /health`

Definition of done (WB-201)
- Endpoint parity checklist completed for all in-scope business endpoints.
- Staging smoke journey green against `infra/backend`.
- Laravel business routes disabled (or guarded behind emergency flag).
- Rollback tested and documented.

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
- Remove or hard-disable Laravel business routes from `routes/web.php` after cutover stability window.
- Keep only non-business or maintenance endpoints if needed.

## Endpoint Parity Checklist

Use this table before cutover. Mark each item `done` only after tests pass.

| Endpoint | Source (Laravel) | Target (`infra/backend`) | Contract test | Staging smoke | Notes |
|---|---|---|---|---|---|
| `GET /api/workations` | yes | implemented | implemented | pending | Prisma-backed; covered by contract script |
| `GET /api/workations/{workation}` | yes | implemented | implemented | pending | Prisma-backed; covered by contract script |
| `POST /api/workations` | yes | implemented | implemented | pending | Prisma-backed; covered by contract script |
| `PUT /api/workations/{workation}` | yes | implemented | implemented | pending | Prisma-backed; covered by contract script |
| `DELETE /api/workations/{workation}` | yes | implemented | implemented | pending | Prisma-backed; covered by contract script |
| `POST /api/transport/holds` | yes | implemented | implemented | pending | Prisma-backed with idempotency key support |
| `POST /api/transport/holds/{hold}/confirm` | yes | implemented | implemented | pending | Prisma-backed; 200 status parity |
| `POST /api/transport/holds/{hold}/release` | yes | implemented | implemented | pending | Prisma-backed; 200 status parity |

Contract test command
- `cd infra/backend && npm.cmd run contract:test`

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
