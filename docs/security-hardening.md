# Security Hardening Operations

This guide captures operational controls for abuse prevention, secret rotation, and dependency scanning.

## Abuse Prevention and Rate Limits
- Write rate limits are enforced via `WriteRateLimitGuard` and `@WriteRateLimit(...)` metadata.
- Existing trust and safety write endpoints (reviews/social links) are rate-limited.
- Payments write and webhook endpoints are rate-limited to reduce abuse amplification and noisy retries.

## Secret Rotation Runbook
Rotate secrets on a fixed cadence (recommended: every 30-90 days, or immediately after suspected exposure).

Scope:
- `AUTH_JWT_SECRET`
- `BML_API_KEY`, `BML_WEBHOOK_SECRET`
- `MIB_API_KEY`, `MIB_WEBHOOK_SECRET`
- `STRIPE_WEBHOOK_SECRET`

Procedure:
1. Generate new secrets in secret manager (do not store in terminal history).
2. Update environment variables in Render/CI secrets.
3. Deploy backend and verify health endpoints.
4. Run smoke checks:
   - `npm run live:preflight`
5. Validate payment webhook signature verification in logs/alerts.
6. Revoke old secrets after cutover verification window.

## Dependency and Secrets Scanning Cadence
- CI gate workflow: `.github/workflows/security-audit.yml`
- Commands:
  - `npm run security:secrets`
  - `npm audit --omit=dev`
  - `npm --prefix infra/backend audit --omit=dev`
  - `composer audit`

Recommended operating cadence:
- Run full audit on every PR (already enforced by CI).
- Run manual weekly review and monthly dependency refresh.
- Track remediation status in roadmap delivery notes.