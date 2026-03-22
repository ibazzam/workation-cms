# Facebook Vendor Login Production Evidence (2026-03-22)

This record captures production checks completed on 2026-03-22 UTC for the P0 item "Stabilize Facebook login end-to-end in production".

## Automated Public Checks

### 1) Vendor Register Availability and UI
- Check URL: https://www.workation.mv/portal/vendor/register?mode=email
- HTTP status: 200
- Social buttons visible:
  - Google: PASS
  - Facebook: PASS
  - Apple: PASS
- Social status diagnostics block present: PASS

### 2) OAuth Health Endpoint
- Check URL: https://www.workation.mv/portal/vendor/oauth/health
- HTTP status: 200
- `ok`: true
- Facebook provider configured: true
- Facebook redirect uses HTTPS: true
- Facebook redirect host matches app host: true
- App URL reported by health endpoint: https://www.workation.mv

### 3) Canonical Host Redirect
- Source URL: https://workation.mv/portal/vendor/register?mode=email
- Result: HTTP 301 redirect to canonical host
- Location header: https://www.workation.mv/portal/vendor/register?mode=email
- Verdict: PASS

### 4) Facebook OAuth Start Redirect
- Source URL: https://www.workation.mv/portal/vendor/oauth/facebook/redirect
- Result: HTTP 302 redirect to Facebook OAuth dialog
- Location host: www.facebook.com
- Callback embedded in redirect URI: https://www.workation.mv/portal/vendor/oauth/facebook/callback
- Scope in redirect URI: public_profile
- Verdict: PASS

### 5) Facebook OAuth Negative Callback Path (Provider Deny)
- Source URL: https://www.workation.mv/portal/vendor/oauth/facebook/callback?error=access_denied&error_reason=user_denied
- Result: HTTP 302 redirect
- Location header: https://www.workation.mv/portal/vendor/register
- Verdict: PASS

## Remaining Manual Authenticated Checks (Required for Final Sign-Off)
- [ ] Complete Facebook OAuth consent and callback with test vendor account
- [ ] Confirm callback lands on canonical `www` host and returns to vendor flow
- [ ] Confirm vendor session is established post-auth
- [ ] Run negative path (cancel consent) and verify retry guidance in UI
- [ ] Correlate Render app logs for init/callback during the verification window
- [ ] Record final PASS/FAIL and sign-off owner

## Final Sign-Off Entry (Fill After Live Authenticated Run)

### Verification Window
- Start time (UTC):
- End time (UTC):
- Build/commit under test:
- Environment: Render production + Neon production DB

### Authenticated Smoke Results
| Check | Result (PASS/FAIL) | Evidence link/path | Notes |
| --- | --- | --- | --- |
| Facebook consent + callback completed |  |  |  |
| Callback landed on canonical `www` host |  |  |  |
| Vendor session established post-auth |  |  |  |
| Vendor-authenticated page/endpoint accessible |  |  |  |
| Negative path retry guidance shown |  |  |  |

### Render Log Correlation
| Check | Result (YES/NO) | Evidence link/path | Notes |
| --- | --- | --- | --- |
| Auth init route observed in window |  |  |  |
| Callback route observed in window |  |  |  |
| No unhandled exceptions in window |  |  |  |
| No repeated state/nonce mismatch errors |  |  |  |

### Approval
- Facebook final status: PASS / FAIL
- OTP fallback path check: PASS / FAIL
- Final sign-off owner:
- Sign-off timestamp (UTC):
- Release notes reference (optional):

### Closure Rule
If `Facebook final status = PASS` and all Render log checks are `YES`, mark the remaining Facebook roadmap sub-item and parent item as complete.

## Related Docs
- Runbook: `docs/vendor-social-auth-production-runbook.md`
- Focused worksheet: `docs/vendor-facebook-production-signoff-template.md`
