# Alert Routing Verification Record (2026-03-18)

This record tracks launch-day alert routing verification status.

## Objective
Validate that pager/slack/email routing is functioning end-to-end for launch critical alerts.

## Current Evidence
- Health endpoint probe:
  - `GET https://api.workation.mv/api/v1/health` -> `200`
- Auth-protected ops endpoints:
  - `GET https://api.workation.mv/api/v1/ops/alerts` -> `401` (bearer token required)
  - `GET https://api.workation.mv/api/v1/ops/runbooks` -> `401` (bearer token required)
- Authenticated preflight attempt via GitHub Actions:
  - Run URL: `https://github.com/ibazzam/workation-cms/actions/runs/22947199645`
  - Result: `failure`
  - Verified cause from logs: `AUTH_BEARER_TOKEN` was empty in workflow env, so strict auth checks failed (`ops/slo-summary is required but unavailable`).

## Verification Status
- Config-level readiness: PASS (routing config and docs exist)
- End-to-end channel delivery: BLOCKED (missing bearer token secret for authenticated ops verification)

## Required Final Checks
- [ ] Set repository secret `LIVE_PREFLIGHT_BEARER_TOKEN` with valid launch/admin bearer token.
- [ ] Re-run workflow: `Live preflight gate` with strict options enabled.
- [ ] Confirm workflow shows authenticated ops checks pass (no auth-related skip/failure).
- [ ] Trigger a controlled test alert for each channel (pager/slack/email).
- [ ] Capture receipt screenshots/log references in each target channel.
- [ ] Record acknowledgment timestamps and responder identity.

## References
- `docs/observability-stack.md`
- `docs/go-no-go-rehearsal-2026-03-18.md`
