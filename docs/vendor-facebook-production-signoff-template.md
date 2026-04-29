# Payment Gateway Production ENV Checklist (Render/Laravel Cloud)

Use this checklist on the platform hosting your live app. If production runs on Render, set these in Render. If production runs on Laravel Cloud, set these in Laravel Cloud.

## Required mode and gateway keys

```dotenv
# Stripe
WORKATION_PAYMENT_STRIPE_MODE=external
WORKATION_PAYMENT_STRIPE_CHECKOUT_URL=
WORKATION_PAYMENT_STRIPE_CHECKOUT_SIGNING_SECRET=
WORKATION_PAYMENT_STRIPE_WEBHOOK_SECRET=
WORKATION_PAYMENT_STRIPE_SECRET_KEY=
WORKATION_PAYMENT_STRIPE_PUBLISHABLE_KEY=

# MIB MVR (local)
WORKATION_PAYMENT_MIB_MVR_MODE=external
WORKATION_PAYMENT_MIB_MVR_CHECKOUT_URL=
WORKATION_PAYMENT_MIB_MVR_CHECKOUT_SIGNING_SECRET=
WORKATION_PAYMENT_MIB_MVR_WEBHOOK_SECRET=

# MIB USD (foreign)
WORKATION_PAYMENT_MIB_USD_MODE=external
WORKATION_PAYMENT_MIB_USD_CHECKOUT_URL=
WORKATION_PAYMENT_MIB_USD_CHECKOUT_SIGNING_SECRET=
WORKATION_PAYMENT_MIB_USD_WEBHOOK_SECRET=

# BML MVR (local)
WORKATION_PAYMENT_BML_MVR_MODE=production
WORKATION_PAYMENT_BML_MVR_API_KEY=
WORKATION_PAYMENT_BML_MVR_APP_ID=
WORKATION_PAYMENT_BML_MVR_CHECKOUT_SIGNING_SECRET=
WORKATION_PAYMENT_BML_MVR_WEBHOOK_SECRET=

# BML USD (foreign)
WORKATION_PAYMENT_BML_USD_MODE=production
WORKATION_PAYMENT_BML_USD_API_KEY=
WORKATION_PAYMENT_BML_USD_APP_ID=
WORKATION_PAYMENT_BML_USD_CHECKOUT_SIGNING_SECRET=
WORKATION_PAYMENT_BML_USD_WEBHOOK_SECRET=

# Optional shared BML secrets for both MVR + USD gateways
WORKATION_PAYMENT_BML_CHECKOUT_SIGNING_SECRET=
WORKATION_PAYMENT_BML_WEBHOOK_SECRET=
```

## Currency routing keys

```dotenv
WORKATION_PAYMENT_LOCAL_ALLOWED_CURRENCIES=MVR,USD
WORKATION_PAYMENT_FOREIGN_ALLOWED_CURRENCIES=USD
WORKATION_PAYMENT_STRIPE_SUPPORTED_CURRENCIES=MVR,USD
WORKATION_PAYMENT_FX_MVR_PER_USD=15.42
```

## Deployment checklist

1. Set all required keys in the live environment (do not store secrets in repository).
2. For external gateways, ensure checkout handoff is configured:
	- MIB: `*_CHECKOUT_URL` must be set.
	- BML Connect: `*_API_KEY` and `*_APP_ID` must be set. Use `*_MODE=production` for live and `*_MODE=sandbox` for UAT.
	- Stripe: set either `WORKATION_PAYMENT_STRIPE_SECRET_KEY` (native session) or `WORKATION_PAYMENT_STRIPE_CHECKOUT_URL` (custom handoff).
3. Redeploy/restart so Laravel loads updated environment values.
4. Clear cached configuration:

```bash
php artisan optimize:clear
```

5. Run live smoke tests:
	- Local segment booking -> MVR route opens external gateway page.
	- Foreign segment booking -> USD route opens external gateway page.
	- Webhook callback marks reservation as paid/cancelled correctly.

## Safety notes

- Never paste live API secrets into chat.
- Use distinct keys for staging vs production.
- If MIB is external but missing checkout URL, checkout is blocked by design.
- If BML Connect is missing API key or App ID, BML checkout options are hidden by design.
- Stripe can run without a checkout URL when `WORKATION_PAYMENT_STRIPE_SECRET_KEY` is set.

## Stripe key mapping (important)

Stripe gives you:

- Publishable key (`pk_live_...`)
- Secret key (`sk_live_...`)
- Webhook signing secret (`whsec_...`) per webhook endpoint

In this project:

1. `WORKATION_PAYMENT_STRIPE_WEBHOOK_SECRET`
	- Use Stripe endpoint signing secret (`whsec_...`).
	- Get it from Stripe Dashboard -> Developers -> Webhooks -> select endpoint -> Reveal signing secret.

2. Webhook URL to register in Stripe
	- Use your live app endpoint:
	- `https://www.workation.mv/booking/payment/webhooks/stripe`

3. `WORKATION_PAYMENT_STRIPE_CHECKOUT_URL`
	- Optional now for Stripe. Native Stripe session creation can use `WORKATION_PAYMENT_STRIPE_SECRET_KEY` directly.
	- Keep for fallback/custom handoff flows if required.

4. Stripe publishable/secret keys (`pk_live`, `sk_live`)
	- Set `WORKATION_PAYMENT_STRIPE_SECRET_KEY` to your `sk_live_...`.
	- Set `WORKATION_PAYMENT_STRIPE_PUBLISHABLE_KEY` to your `pk_live_...`.
	- Webhook verification still uses `WORKATION_PAYMENT_STRIPE_WEBHOOK_SECRET` (`whsec_...`).

## BML USD and MVR mapping (same credential base, different gateways)

If BML provides one credential base for both currencies:

1. Keep gateway credentials separate by flow:
	- `WORKATION_PAYMENT_BML_MVR_API_KEY` + `WORKATION_PAYMENT_BML_MVR_APP_ID` for local MVR flow.
	- `WORKATION_PAYMENT_BML_USD_API_KEY` + `WORKATION_PAYMENT_BML_USD_APP_ID` for foreign USD flow.

2. You can use shared secrets for both gateways:
	- Set `WORKATION_PAYMENT_BML_CHECKOUT_SIGNING_SECRET` once.
	- Set `WORKATION_PAYMENT_BML_WEBHOOK_SECRET` once.

3. If BML gives distinct secrets per gateway later, set per-gateway values:
	- `WORKATION_PAYMENT_BML_MVR_CHECKOUT_SIGNING_SECRET`, `WORKATION_PAYMENT_BML_MVR_WEBHOOK_SECRET`
	- `WORKATION_PAYMENT_BML_USD_CHECKOUT_SIGNING_SECRET`, `WORKATION_PAYMENT_BML_USD_WEBHOOK_SECRET`

## BML Connect field mapping (from your screenshots)

You now have two BML apps in Connect:

1. MVR app (local flow)
2. USD app (foreign flow)

Use them like this:

1. Set app credentials in ENV:
	- WORKATION_PAYMENT_BML_MVR_API_KEY and WORKATION_PAYMENT_BML_MVR_APP_ID
	- WORKATION_PAYMENT_BML_USD_API_KEY and WORKATION_PAYMENT_BML_USD_APP_ID
	- MODE: use `production` for live, `sandbox` for testing

2. Register webhook endpoints in BML Connect:
	- MVR webhook URL: https://www.workation.mv/booking/payment/webhooks/bml_mvr
	- USD webhook URL: https://www.workation.mv/booking/payment/webhooks/bml_usd

3. For signing and webhook secrets in this app:
	- If BML gives one shared signing/webhook secret, use:
		- WORKATION_PAYMENT_BML_CHECKOUT_SIGNING_SECRET
		- WORKATION_PAYMENT_BML_WEBHOOK_SECRET
	- If BML gives per-app/per-currency secrets, use per-gateway variables instead.

4. BML Connect Application ID / API Key (secret) / Public Key:
	- These are bank credentials for your BML integration layer.
	- Do not commit these values to repository files.
	- Store in production environment variables or secret manager only.

## Routing behavior (confirmed)

- Local customers continue to route to MVR gateways.
- Foreign customers continue to route to USD gateways.
- This behavior is unchanged by BML Connect migration.

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
