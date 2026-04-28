# Payment Gateway Production ENV Checklist (Render/Laravel Cloud)

Use this checklist on the platform hosting your live app. If production runs on Render, set these in Render. If production runs on Laravel Cloud, set these in Laravel Cloud.

## Required mode and checkout URL keys

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
WORKATION_PAYMENT_BML_MVR_MODE=external
WORKATION_PAYMENT_BML_MVR_CHECKOUT_URL=
WORKATION_PAYMENT_BML_MVR_CHECKOUT_SIGNING_SECRET=
WORKATION_PAYMENT_BML_MVR_WEBHOOK_SECRET=

# BML USD (foreign)
WORKATION_PAYMENT_BML_USD_MODE=external
WORKATION_PAYMENT_BML_USD_CHECKOUT_URL=
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
2. For any gateway set to `external`, ensure `*_CHECKOUT_URL` is not empty.
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
- If a gateway is external but missing checkout URL, checkout is blocked by design.

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

1. Keep gateway endpoints separate:
	- `WORKATION_PAYMENT_BML_MVR_CHECKOUT_URL` for local MVR flow.
	- `WORKATION_PAYMENT_BML_USD_CHECKOUT_URL` for foreign USD flow.

2. You can use shared secrets for both gateways:
	- Set `WORKATION_PAYMENT_BML_CHECKOUT_SIGNING_SECRET` once.
	- Set `WORKATION_PAYMENT_BML_WEBHOOK_SECRET` once.

3. If BML gives distinct secrets per gateway later, set per-gateway values:
	- `WORKATION_PAYMENT_BML_MVR_CHECKOUT_SIGNING_SECRET`, `WORKATION_PAYMENT_BML_MVR_WEBHOOK_SECRET`
	- `WORKATION_PAYMENT_BML_USD_CHECKOUT_SIGNING_SECRET`, `WORKATION_PAYMENT_BML_USD_WEBHOOK_SECRET`

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
