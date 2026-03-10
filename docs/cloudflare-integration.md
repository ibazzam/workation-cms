# Cloudflare Integration and Staging Cutover

## Scope
This document captures the Cloudflare staging cutover checks for `api.workation.mv` and the repeatable preflight command used to verify DNS, SSL edge routing, cache behavior, and protected write-path handling.

## Baseline Setup
- Cloudflare proxy enabled for `api.workation.mv` (orange cloud).
- TLS mode configured as Full (strict).
- API traffic routed to Render origin.
- Cache bypass expected for dynamic API endpoints (`/api/*`) where responses should remain `DYNAMIC`.

## Verification Command
Run from repository root:

```powershell
BASE_URL=https://api.workation.mv npm.cmd run cloudflare:staging:preflight
```

Script path:
- `tests/e2e/cloudflare-staging-preflight.mjs`

What this verifies:
- DNS resolution for `api.workation.mv`.
- Hosted health endpoint reachable over HTTPS and served via Cloudflare (`server`, `cf-ray`, `cf-cache-status` headers present).
- Protected write endpoint rejects unauthenticated requests (`401`/`403`) while still traversing Cloudflare edge headers.

## Latest Execution Evidence (2026-03-10)
- DNS resolution returned edge IPs for `api.workation.mv`.
- `GET https://api.workation.mv/api/v1/health` returned `200`, `Server: cloudflare`, and `cf-cache-status: DYNAMIC`.
- `POST https://api.workation.mv/api/v1/bookings` without auth returned `401` with Cloudflare headers present (`Server: cloudflare`, `CF-RAY`, `cf-cache-status: DYNAMIC`).
- Hosted `live-preflight` execution on `https://api.workation.mv` completed with `Live preflight passed` using public-safe checks.

## Operational Notes
- If `AUTH_BEARER_TOKEN` in shell is expired, unset it before running `live:preflight` to avoid false-negative auth failures for optional protected checks.
- Keep this check in release/cutover runbooks as a prerequisite gate before staging or production promotions.
