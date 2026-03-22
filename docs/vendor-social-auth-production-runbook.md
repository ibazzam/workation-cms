# Vendor Social Auth Production Verification Runbook

This runbook defines the production verification flow for vendor social login providers (Google, Facebook, Apple).

Infrastructure source of truth:
- Backend runtime: Render
- Database: Neon Postgres
- Public app host: `https://www.workation.mv`
- Public API host: `https://api.workation.mv`

## Purpose
- Verify social login is working end-to-end in production before and after releases.
- Detect callback, session, state, and provider-configuration regressions early.
- Provide a repeatable rollback and incident path if verification fails.

## Prerequisites
- Admin access to Render environment variables for production service.
- Admin access to provider dashboards:
  - Google Cloud OAuth consent + credentials
  - Meta for Developers (Facebook Login)
  - Apple Developer (Sign in with Apple)
- Access to vendor portal auth page:
  - `https://www.workation.mv/portal/vendor/register?mode=email`
- At least one test account per provider that can complete login.

## Canonical Host and Redirect Rules
- All social auth entry and callback URLs must use the canonical host `www.workation.mv`.
- If non-canonical host is used during init/callback, request must redirect to canonical host.
- Callback URL in provider dashboards must exactly match production callback route.

Validation checklist:
1. Open vendor register page from canonical host and confirm all provider buttons render.
2. Click each provider and ensure navigation starts from canonical host.
3. Confirm callback lands on canonical host and returns user to vendor flow.

## Provider Configuration Verification
Verify these items before running live checks:

1. Google
- Authorized JavaScript origin includes `https://www.workation.mv`
- Authorized redirect URI matches production callback
- OAuth consent screen is published for intended audience

2. Facebook
- App domain includes `workation.mv`
- Valid OAuth redirect URI matches production callback
- App mode and permissions are correct for production users

3. Apple
- Service ID and Return URL match production callback
- Team ID, Key ID, and private key pair are valid and not expired
- Domain association setup remains valid after certificate changes

## Production Smoke Procedure
Run this sequence for each provider (Google, Facebook, Apple):

1. Start login from:
- `https://www.workation.mv/portal/vendor/register?mode=email`

2. Complete provider auth and consent.

3. Verify post-auth result:
- User is signed into vendor portal without server error.
- Session indicators are present (vendor user visible in portal header).
- Vendor portal endpoints requiring vendor auth can be called successfully.

4. Negative-path check:
- Cancel provider consent midway and verify retry guidance is shown in vendor UI.

5. Capture evidence:
- UTC timestamp
- Provider name
- Result (`PASS`/`FAIL`)
- Screenshot of final result screen
- Correlated app logs from Render for the same window

## Observability and Log Checks
During each smoke test, verify:
- Render application logs show expected auth init and callback path.
- No unhandled exceptions for social callback routes.
- No repeated state/nonce mismatch errors.
- Authentication failures produce actionable user-facing guidance.

Recommended checks:
1. Review latest deployment and confirm commit SHA under test.
2. Correlate timestamped login attempts with logs.
3. Confirm no spike in auth-related 4xx/5xx around verification window.

## Rollback and Recovery
If any provider fails verification after deploy:

1. Immediate containment
- Announce incident in ops channel with provider + first failure timestamp.
- Keep unaffected providers enabled when safe.

2. Rollback decision
- If failure is release-related and reproducible, roll back on Render to last known good deploy.
- Re-run provider smoke checks after rollback.

3. Temporary mitigation
- Keep email/phone OTP vendor login path available.
- Add a visible vendor notice for impacted provider while recovery is in progress.

4. Closure
- Document root cause and fix in release notes.
- Attach evidence links and final pass run.

## Weekly Verification Cadence
- Run full provider smoke checks at least once per week.
- Run full provider smoke checks after any auth or routing related production deployment.
- Store latest evidence links in the weekly release verification record.

## Sign-Off Template
- Verification date (UTC):
- Build/commit under test:
- Google: PASS/FAIL
- Facebook: PASS/FAIL
- Apple: PASS/FAIL
- OTP fallback path check: PASS/FAIL
- Render log review completed: YES/NO
- Final sign-off owner:
