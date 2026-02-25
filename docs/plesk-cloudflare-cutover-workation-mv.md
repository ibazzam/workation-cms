# Plesk + Cloudflare Cutover Checklist (workation.mv)

Date: 2026-02-22

## 1) Plesk origin setup

- Ensure `workation.mv` serves frontend runtime.
- Add subdomain `api.workation.mv` in Plesk and bind backend runtime there.
- Backend env required:
  - `APP_TRUST_PROXY=true`
  - `CORS_ORIGIN=https://workation.mv,https://admin.workation.mv`
  - `DATABASE_URL=<prod-postgres-url>`
- Frontend env required:
  - `WORKATION_API_BASE_URL=https://api.workation.mv/api/v1`

## 2) Cloudflare DNS

Create proxied records:

- `workation.mv` -> frontend origin
- `api.workation.mv` -> backend origin
- `admin.workation.mv` (optional) -> frontend/admin origin

## 3) Cloudflare SSL/TLS

- Mode: Full (strict)
- Always Use HTTPS: ON
- Automatic HTTPS Rewrites: ON
- HSTS: enable only after stable verification

## 4) Cloudflare Rules

Cache rule:
- Hostname `api.workation.mv` => Cache bypass

WAF/rate limiting:
- Add limits/challenge on `/api/v1/reviews*` and `/api/v1/social-links*`
- Exclude webhook paths from challenge:
  - `/api/v1/payments/webhooks/*`

## 5) Verification commands

From repo root:

- `npm run cloudflare:preflight:workation-mv`
- `npm --prefix infra/backend run test:e2e:journey`
- `npm --prefix infra/backend run test:contract:reviews`
- `npm --prefix infra/backend run test:contract:social-links`
- `npm --prefix infra/backend run test:contract:payments`

## 6) Go/No-Go

Go when all are true:

- Preflight returns HTTP 200 for app and API health
- Hosted app flow works (search -> book -> pay -> manage)
- Moderation and payments admin checks pass in hosted env
- No Cloudflare challenge/caching on webhook paths

No-Go if any are false; switch DNS records to DNS-only temporarily as rollback.
