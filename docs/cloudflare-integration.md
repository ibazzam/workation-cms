# Cloudflare Integration Runbook (Workation CMS)

Date: 2026-02-22

## Objective

Protect and accelerate public app and API traffic with Cloudflare while preserving backend correctness for auth, moderation, and payment flows.

## 1) Target Edge Topology

- App frontend: app.your-domain.com -> Cloudflare proxy -> frontend origin
- API backend: api.your-domain.com -> Cloudflare proxy -> backend origin
- Optional admin frontend: admin.your-domain.com -> Cloudflare proxy -> frontend origin

### Workation.mv recommended mapping

- Public app: `https://workation.mv`
- API: `https://api.workation.mv/api/v1`
- Optional admin UI: `https://admin.workation.mv`

If app and API are hosted in the same Plesk server, keep separate subdomains and separate origin app configs to simplify cache and WAF rules.

## 2) Required Origin Configuration

Backend env (infra/backend/.env):

- APP_TRUST_PROXY=true
- CORS_ORIGIN=https://workation.mv,https://admin.workation.mv

Frontend env (infra/frontend/.env.local or hosting env):

- WORKATION_API_BASE_URL=https://api.workation.mv/api/v1

## 2.1) Plesk hosting notes (from your environment)

Your screenshot confirms `workation.mv` is active in Plesk and Node.js is available. Recommended setup:

1. Keep `workation.mv` for frontend app.
2. Add subdomain `api.workation.mv` in Plesk for backend service.
3. Run backend under Node.js app in Plesk (`npm run start` on built backend) and expose via subdomain.
4. Ensure backend process uses production env vars (including `APP_TRUST_PROXY` and `CORS_ORIGIN`).
5. Point frontend runtime env `WORKATION_API_BASE_URL` to `https://api.workation.mv/api/v1`.

## 3) Cloudflare DNS and SSL

1. Create DNS records (proxied/orange-cloud):
   - app CNAME -> frontend host
   - api CNAME -> backend host
   - admin CNAME -> frontend host (optional)
2. SSL/TLS mode: Full (strict)
3. Enable Always Use HTTPS
4. Enable Automatic HTTPS Rewrites
5. Enable HSTS only after successful staging verification

For `workation.mv`:

- `workation.mv` -> proxied A/AAAA/CNAME to frontend origin
- `api.workation.mv` -> proxied A/AAAA/CNAME to backend origin
- `admin.workation.mv` (optional) -> proxied A/AAAA/CNAME to frontend/admin origin

## 4) Cache and Performance Rules

Create Cache Rules:

- Bypass cache for API:
  - If hostname equals api.your-domain.com then Cache eligibility: Bypass
- Keep app static assets cacheable:
  - For app.your-domain.com paths with static assets (_next/static, build assets), allow cache

Recommended toggles:

- Brotli: ON
- HTTP/3: ON
- Rocket Loader: OFF (unless explicitly tested)

## 5) Security/WAF Rules

Use Cloudflare Managed WAF + custom rules:

1. Managed ruleset: ON (default sensitivity)
2. Rate limit challenge/block for write endpoints:
   - /api/v1/reviews*
   - /api/v1/social-links*
   - /api/v1/auth/* (if exposed)
3. Skip/chill bot fight on payment webhooks path to avoid provider callback issues:
   - /api/v1/payments/webhooks/*

## 6) Webhook Safety

Payment webhooks should remain reachable and unmodified:

- /api/v1/payments/webhooks/stripe
- /api/v1/payments/webhooks/bml
- /api/v1/payments/webhooks/mib

Checks:

- No JS challenge/interstitial on webhook paths
- No forced body rewriting
- No cache on webhook responses

## 7) Preflight Verification

Run from repo root:

- powershell -ExecutionPolicy Bypass -File scripts/cloudflare_preflight_check.ps1 -AppUrl https://app.your-domain.com -ApiUrl https://api.your-domain.com/api/v1

For your domain:

- powershell -ExecutionPolicy Bypass -File scripts/cloudflare_preflight_check.ps1 -AppUrl https://workation.mv -ApiUrl https://api.workation.mv/api/v1

Expected:

- Edge headers include Cloudflare indicators (cf-ray/server)
- App and API health return HTTP 200
- API endpoint is non-cached

## 8) Post-Deploy Smoke

1. Run app journey in hosted domain:
   - search -> book -> pay -> manage
2. Verify moderation hosted flow:
   - review flag/hide/publish
   - social flag/approve/hide
3. Verify ops endpoints:
   - /payments/admin/alerts
   - /payments/admin/reconcile/status
   - /payments/admin/jobs/health

## 9) Rollback Plan

- Set DNS records to DNS-only (gray-cloud) for app/api as emergency bypass
- Keep previous origin release available for fast rollback
- Re-run hosted smoke checks after rollback

## 10) Go-Live Gate

Promote to production only when all are true:

- Contract matrix: pass
- Hosted smoke journey: pass
- Hosted moderation checks: pass
- Hosted webhook callback test: pass
- Ops health endpoints: pass
