# Facebook Vendor Login Production Sign-Off Worksheet

Use this worksheet to complete the remaining Facebook stabilization step in one production verification run.

## Run Metadata
- Verification date (UTC):
- Sign-off owner:
- Build/commit under test:
- Render service/environment:
- Tester account used:

## Provider Config Pre-Check (Meta)
- [ ] App domain includes workation.mv
- [ ] Valid OAuth redirect URI exactly matches production callback
- [ ] App mode/permissions are correct for production users

## Smoke Steps
1. Open https://www.workation.mv/portal/vendor/register?mode=email.
2. Click Continue with Facebook.
3. Complete provider auth/consent.
4. Confirm callback returns to canonical host and lands in vendor flow.
5. Confirm vendor session indicators are visible in portal.
6. Confirm a vendor-authenticated endpoint/page is reachable.

## Negative Path
- [ ] Cancel consent midway
- [ ] Confirm retry guidance is shown in vendor register UI

## Evidence Capture
| UTC timestamp | Checkpoint | Result (PASS/FAIL) | Evidence link/path | Notes |
| --- | --- | --- | --- | --- |
|  | Facebook login success path |  |  |  |
|  | Callback host canonical (www) |  |  |  |
|  | Vendor session established |  |  |  |
|  | Negative path guidance shown |  |  |  |
|  | Render log correlation complete |  |  |  |

## Render Log Correlation
- [ ] Auth init route observed in logs for test window
- [ ] Callback route observed in logs for test window
- [ ] No unhandled exceptions
- [ ] No repeated state/nonce mismatch errors

## Final Decision
- Facebook: PASS / FAIL
- OTP fallback path check: PASS / FAIL
- Final sign-off comment:

## Roadmap Update Rule
After this worksheet is completed with Facebook PASS and log correlation complete:
1. Mark the Facebook parent item as done in docs/portal-ui-roadmap-todo.md.
2. Mark the evidence/sign-off sub-item as done in docs/portal-ui-roadmap-todo.md.
3. Add the evidence reference path under the same section.

Record the final approved outcome in:
- `docs/evidence/facebook-prod-signoff-2026-03-22.md` under "Final Sign-Off Entry (Fill After Live Authenticated Run)".
