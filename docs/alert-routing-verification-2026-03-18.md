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
- Authenticated preflight re-attempt after secret setup:
  - Run URL: `https://github.com/ibazzam/workation-cms/actions/runs/22948887462`
  - Result: `failure`
  - Verified cause from logs: preflight failed with `HTTP 500` after health check.
  - Local checkpoint repro with same bearer token narrowed failure to `GET /api/v1/bookings` returning `500` during checkout reliability validation.
- Promotion attempt for remediation rollout:
  - Run URL: `https://github.com/ibazzam/workation-cms/actions/runs/22951012154`
  - Result: `failure`
  - Verified cause from logs: `RENDER_DEPLOY_HOOK_URL` secret missing, so deployment hook was not triggered.
- Promotion rerun after workflow/remediation fixes:
  - Run URL: `https://github.com/ibazzam/workation-cms/actions/runs/22991538950`
  - Result: `success`
  - Outcome: deploy remediation path completed and strict rerun could proceed.
- Strict authenticated preflight rerun after rollout:
  - Run URL: `https://github.com/ibazzam/workation-cms/actions/runs/22991556615`
  - Result: `success`
  - Outcome: checkout reliability and required strict gates passed.

## Verification Status
- Config-level readiness: PASS (routing config and docs exist)
- Strict production gate status: PASS (required strict preflight checks completed)
- End-to-end channel delivery receipts: PENDING (manual controlled alert proof capture not yet attached)

## Required Final Checks
- [x] Set repository secret `LIVE_PREFLIGHT_BEARER_TOKEN` with valid launch/admin bearer token.
- [x] Set repository secret `RENDER_DEPLOY_HOOK_URL` so promotion workflow can deploy remediation build.
- [x] Remediate production `GET /api/v1/bookings` `500` under bearer-authenticated path.
- [x] Re-run workflow: `Live preflight gate` with strict options enabled.
- [x] Confirm workflow passes checkout reliability and remaining strict checks.
- [ ] Trigger a controlled test alert for each channel (pager/slack/email).
- [ ] Capture receipt screenshots/log references in each target channel.
- [ ] Record acknowledgment timestamps and responder identity.

## Pending Receipt Capture

| Channel | Test Triggered | Receipt Link / Evidence | Acknowledged By | Ack Timestamp (UTC) | Status |
|---|---|---|---|---|---|
| Pager | Pending | Pending | Pending | Pending | OPEN |
| Slack | Pending | Pending | Pending | Pending | OPEN |
| Email | Pending | Pending | Pending | Pending | OPEN |

## Execution Commands
Set secret (run once with a valid token value):

```powershell
gh secret set LIVE_PREFLIGHT_BEARER_TOKEN --body "<valid_launch_admin_bearer_token>"
```

Run authenticated endpoint probe + optional strict workflow dispatch:

```powershell
$env:AUTH_BEARER_TOKEN = "<valid_launch_admin_bearer_token>"
powershell -ExecutionPolicy Bypass -File scripts/ops/verify-alert-routing.ps1 -RunWorkflow
```

## References
- `docs/observability-stack.md`
- `docs/go-no-go-rehearsal-2026-03-18.md`
