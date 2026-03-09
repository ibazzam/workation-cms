## WB-201 Authority Cutover Runbook

Status: Completed (authority backend is single runtime business API; legacy Laravel routes are test-only)
Owner: Backend Lead

Purpose
- Establish `infra/backend` as the single business API authority.
- Decommission Laravel business routes after parity and smoke checks.

Current state (repo evidence)
- Legacy Laravel business routes are test-only in `routes/web.php` (`app()->environment('testing')`).
- Legacy Laravel endpoints kept for test coverage only:
  - `GET /api/workations`
  - `GET /api/workations/{workation}`
  - `POST /api/workations`
  - `PUT /api/workations/{workation}`
  - `DELETE /api/workations/{workation}`
  - `POST /api/transport/holds`
  - `POST /api/transport/holds/{hold}/confirm`
  - `POST /api/transport/holds/{hold}/release`
- New authority backend exposes active business endpoints under `/api/v1/*`:
  - `GET /api/v1/health`
  - `GET/POST/PUT/DELETE /api/v1/workations...`
  - `POST /api/transport/holds`, `/confirm`, `/release`

Definition of done (WB-201)
- Endpoint parity checklist completed for all in-scope business endpoints.
- Staging smoke journey green against `infra/backend`.
- Laravel business routes decommissioned from runtime and retained only for tests.

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
- Legacy transport hold endpoints are not exposed on current live runtime.
- Preflight validates transports using `/api/v1/transports*` authority routes only.
- Review and social moderation actions now accept optional moderation metadata:
  - `reasonCode` (`SPAM`, `ABUSIVE_LANGUAGE`, `HARASSMENT`, `MISLEADING_CONTENT`, `INAPPROPRIATE_CONTENT`, `POLICY_VIOLATION`, `OTHER`)
  - `reviewerNote` (optional free-text note, up to 500 chars)
- Admin moderation queue responses include the latest moderation metadata for each flagged/hidden item.
- Reviews domain now includes activity/service targets:
  - `GET /api/v1/reviews/activities/:id`
  - `GET /api/v1/reviews/services/:id`
  - review creation accepts `targetType` in `{ACCOMMODATION, TRANSPORT, ACTIVITY, SERVICE}`
  - moderation queue supports optional `targetType` filter across all four target types.
- Social integration now includes embed policy and UGC safety controls:
  - `SocialLink.embedPolicy` supports `PLATFORM_EMBED`, `LINK_ONLY`, `NO_EMBED`
  - `SocialLink.ugcSafetyStatus` supports `SAFE`, `REVIEW`, `BLOCKED`
  - URL safety validation blocks localhost/private-network targets and validates platform-host compatibility
  - public social listing returns only links with `active=true`, `verified=true`, and `ugcSafetyStatus=SAFE`
  - moderation queue includes links with `ugcSafetyStatus != SAFE` for trust/safety review
  - optional env blocklist for domains: `SOCIAL_LINK_BLOCKED_DOMAINS` (comma-separated)
- Observability baseline endpoints are available on authority backend:
  - `GET /api/v1/ops/slo-summary`
  - `GET /api/v1/ops/metrics`
  - `GET /api/v1/ops/alerts`
  - `GET /api/v1/ops/runbooks`
- Runbook links and alert thresholds are configured via environment variables:
  - `OPS_RUNBOOK_ONCALL_URL`, `OPS_RUNBOOK_INCIDENT_URL`, `OPS_RUNBOOK_PAYMENTS_URL`
  - `OPS_SLO_WINDOW_MINUTES`, `OPS_ALERT_MIN_SAMPLE_SIZE`, `OPS_ALERT_MAX_ERROR_RATE`, `OPS_ALERT_MAX_P95_MS`

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
- Monitor error rate, latency, and booking/hold failure metrics.

Phase 4: Laravel business route decommission
- Completed as final decommission:
  - Legacy Laravel business routes are available only in testing.
  - Runtime rollback flag has been removed from environment configuration.

## Endpoint Parity Checklist

Use this table before cutover. Mark each item `done` only after tests pass.

| Endpoint | Source (Laravel) | Target (`infra/backend`) | Contract test | Staging smoke | Notes |
|---|---|---|---|---|---|
| `GET /api/workations` | yes | implemented | implemented | passed | Live preflight pass on 2026-03-07 |
| `GET /api/workations/{workation}` | yes | implemented | implemented | passed | Live preflight pass on 2026-03-07 |
| `POST /api/workations` | yes | implemented | implemented | passed | Live preflight pass on 2026-03-07 |
| `PUT /api/workations/{workation}` | yes | implemented | implemented | passed | Live preflight pass on 2026-03-07 |
| `DELETE /api/workations/{workation}` | yes | implemented | implemented | passed | Live preflight pass on 2026-03-07 |
| `POST /api/transport/holds` | yes | decommissioned (test-only legacy) | implemented | n/a | Runtime transport flow uses `/api/v1/transports*` routes |
| `POST /api/transport/holds/{hold}/confirm` | yes | decommissioned (test-only legacy) | implemented | n/a | Runtime transport flow uses `/api/v1/transports*` routes |
| `POST /api/transport/holds/{hold}/release` | yes | decommissioned (test-only legacy) | implemented | n/a | Runtime transport flow uses `/api/v1/transports*` routes |

Contract test command
- `cd infra/backend && npm.cmd run contract:test`

Staging smoke command
- `cd . && BASE_URL=https://api.workation.mv SCHEDULE_ID=1 AUTH_BEARER_TOKEN=<jwt> npm.cmd run live:preflight`

Load/performance baseline command (booking + payments)
- `cd . && BASE_URL=https://api.workation.mv AUTH_BEARER_TOKEN=<jwt> PERF_ITERATIONS=10 PERF_CONCURRENCY=4 npm.cmd run perf:booking-payments`
- Artifacts are written to `artifacts/perf/booking-payments-baseline-<timestamp>.json`.

Security audit commands
- `npm run security:secrets`
- `npm audit --omit=dev`
- `npm --prefix infra/backend audit --omit=dev`
- `composer audit`
- Combined command: `npm run security:audit`

Latest security pass snapshot (2026-03-08)
- Secrets audit: pass (`scripts/security/secrets-audit.mjs`, no high-risk token/key signatures in tracked files)
- Root prod dependency audit: pass (`npm audit --omit=dev`, 0 vulnerabilities)
- Backend prod dependency audit: pass (`npm --prefix infra/backend audit --omit=dev`, 0 vulnerabilities)
- Composer audit: pass (`composer audit`, no advisories)
- Auth hardening applied:
  - production header fallback requires explicit `AUTH_ALLOW_HEADER_FALLBACK_IN_PRODUCTION=true`
  - production default for unset `CORS_ORIGIN` denies cross-origin requests
  - warning emitted when header fallback auth is enabled in production

Latest load/performance baseline snapshot (2026-03-08)
- Artifact: `artifacts/perf/booking-payments-baseline-1772969015649.json`
- Run profile: `iterations=10`, `concurrency=4`, `80` requests per domain
- Booking domain: `p95=2301.17ms`, `p99=4499.19ms`, `errorRate=0`
- Payments domain: `p95=1996.18ms`, `p99=3651.49ms`, `errorRate=0`
- Budget check status: breached configured budgets (`booking p95 <= 800ms`, `payments p95 <= 1200ms`)
- Follow-up: run query/index tuning and provider latency analysis before launch gate sign-off.

CI smoke gate workflow
- Workflow: `.github/workflows/live-preflight-gate.yml`
- Triggers:
  - automatic on `pull_request` to `main`
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
- Branch protection required-check context should match actual GitHub check-run context:
  - required context: `live-preflight`

CI promotion gate workflow
- Workflow: `.github/workflows/promote-with-contract-gate.yml`
- Trigger:
  - manual `workflow_dispatch`
- Inputs:
  - `target_ref` (branch/tag/sha to validate, default `main`)
  - `dry_run` (`true` validates only, `false` triggers deploy hook)
- Required check matrix before promotion:
  - `PHPUnit + Build`
  - `JS Coverage`
  - `smoke`
  - `security-audit`
  - `live-preflight`
- Deployment secret:
  - `RENDER_DEPLOY_HOOK_URL` (required only when `dry_run=false`)

## Rollback Playbook

Trigger conditions
- Elevated 5xx rate above agreed threshold for 10 minutes.
- Hold creation/confirm failures exceed threshold.
- Critical booking or payment flow regression detected.

Rollback steps
1. Repoint API ingress to a known-good authority backend release.
2. Disable newly introduced authority backend changes via deployment rollback.
3. Re-run smoke checks on `/api/v1/*` authority paths.
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
