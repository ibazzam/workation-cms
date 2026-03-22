# Vendor WhatsApp OTP Production Stabilization Runbook

Infrastructure source of truth:
- Backend runtime: Render
- Database: Neon Postgres
- Public app host: https://www.workation.mv

## Goal
Stabilize vendor OTP delivery when Twilio phone channel is set to WhatsApp, with clear fallback behavior and production verification evidence.

## Required Environment Variables (Render)
- `TWILIO_ACCOUNT_SID`
- `TWILIO_AUTH_TOKEN`
- `TWILIO_PHONE_CHANNEL` (`whatsapp`, `wa`, `auto`, or `sms`)
- `TWILIO_WHATSAPP_FROM` (required for explicit WhatsApp mode)
- `TWILIO_WHATSAPP_CONTENT_SID` (optional template SID)
- `TWILIO_FROM_NUMBER` (required for SMS mode and fallback)

## Configuration Expectations
1. If `TWILIO_PHONE_CHANNEL=whatsapp`:
- `TWILIO_WHATSAPP_FROM` must be configured.
- If `TWILIO_WHATSAPP_CONTENT_SID` is set and template send fails, app retries once with plain body.

2. If `TWILIO_PHONE_CHANNEL=auto`:
- App attempts WhatsApp first when possible.
- If WhatsApp delivery fails, app falls back to SMS when `TWILIO_FROM_NUMBER` is configured.

3. If `TWILIO_PHONE_CHANNEL=sms`:
- App uses SMS only and requires `TWILIO_FROM_NUMBER`.

## Pre-Deploy Validation
- Run OTP feature tests:
  - `php artisan test tests/Feature/VendorPhoneOtpDeliveryTest.php tests/Feature/VendorEmailOtpAuthTest.php`
- Confirm no failures in OTP send/verify flow.

## Production Smoke Procedure
1. Open vendor auth page:
- `https://www.workation.mv/portal/vendor/register?mode=email`

2. Send OTP to a controlled test phone identifier.

3. Verify expected channel behavior:
- WhatsApp mode: OTP arrives in WhatsApp.
- Auto mode: if WhatsApp blocked/unavailable, OTP arrives via SMS fallback.

4. Submit OTP and verify login or minimal registration flow proceeds.

5. Negative-path checks:
- Temporarily misconfigure channel/from in non-critical window and confirm user receives actionable retry guidance.

6. Capture evidence:
- UTC timestamp
- Channel mode used (`whatsapp` / `auto` / `sms`)
- PASS/FAIL
- Screenshot/message receipt proof
- Correlated Render logs for send and verify requests

## Sign-Off Fields
- Verification date (UTC):
- Build/commit under test:
- WhatsApp explicit mode: PASS/FAIL
- Auto fallback mode: PASS/FAIL
- OTP verify end-to-end: PASS/FAIL
- Render log correlation complete: YES/NO
- Final sign-off owner:
