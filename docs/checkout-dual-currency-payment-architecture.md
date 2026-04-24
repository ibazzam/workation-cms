# Checkout Dual-Currency Payment Architecture

Date: 2026-04-24
Status: Proposed implementation plan for next checkout milestone

## Goal
Implement payment routing with strict currency rules:
- Maldivian customers must pay in MVR and cannot be charged in USD using local cards.
- Foreign customers must pay in USD (or another non-MVR currency).

## Current Checkout Baseline
- Checkout page route exists at `/booking/checkout/{reservation?}` and currently renders summary only.
- Confirm action is still placeholder (`alert('Payment gateway integration can be connected next.')`).
- Reservation payload already carries nationality and residency context:
  - `primary_nationality`
  - `guest_residency`
- Residency classification is already used in pricing flow via `ReservationPricingPolicy::isForeigner(...)`.

## Recommended Payment Model
Use a gateway router pattern with two payment rails:

1. Local rail (MVR)
- Intended for Maldivian nationals / local residency bookings.
- Currency locked to `MVR`.
- Gateway: local acquirer / domestic processor.

2. International rail (USD)
- Intended for foreign nationals.
- Currency locked to `USD` (future-ready for EUR/GBP etc., but never MVR for foreign flow).
- Gateway: international processor.

## Decision Rules (Server Authoritative)
Define one source of truth in backend service, not in frontend JS.

Inputs:
- `primary_nationality`
- `guest_residency`
- reservation amount/currency

Derived:
- `customer_segment` in {`local_maldivian`, `foreign_national`}
- `allowed_currencies`
- `selected_gateway`

Rules:
- If segment is `local_maldivian`:
  - allowed currency: `MVR`
  - reject any non-MVR checkout currency.
- If segment is `foreign_national`:
  - allowed currencies: non-MVR only (start with `USD`).
  - reject `MVR`.

## Data Model Changes
Add fields to `vendor_reservations` (or companion payment table if preferred):
- `payment_currency` (string, 8)
- `payment_gateway` (string, 40)
- `payment_intent_id` (string, 120)
- `payment_reference` (string, 120)
- `payment_amount` (decimal)
- `payment_status` (existing, keep lifecycle)
- `payment_error` (nullable text)

Optional but recommended:
- `customer_segment` (string, 30)
- `fx_rate_snapshot` (decimal nullable)
- `fx_source` (string nullable)

## Service Layer
Create a dedicated service class:
- `app/Support/CheckoutPaymentRouter.php`

Responsibilities:
- Resolve customer segment from nationality/residency.
- Return allowed currencies.
- Select gateway by currency + segment.
- Validate requested currency against policy.
- Build normalized payment payload for gateway adapters.

Gateway adapter interface:
- `PaymentGatewayAdapterInterface`
- `LocalMvrGatewayAdapter`
- `InternationalUsdGatewayAdapter`

## Route/API Flow
1. Checkout page load (`GET /booking/checkout/{reservation?}`)
- Compute segment and allowed currencies.
- Display payment box with locked currency and gateway label.

2. Create intent (`POST /booking/checkout/{reservation}/payment-intent`)
- Validate reservation ownership and amount.
- Resolve segment from reservation notes/profile.
- Enforce currency rules server-side.
- Create gateway intent/session.
- Persist intent metadata.

3. Confirm/callback (`POST /booking/checkout/{reservation}/payment-confirm` + webhook)
- Verify signature/webhook from gateway.
- Update reservation `payment_status` to `paid` only after verified success.
- Keep idempotency key checks to avoid double capture.

## UI/UX Requirements for Checkout Form
Add a new section above CTA:
- `Payment currency`: read-only value (`MVR` for local, `USD` for foreign).
- `Payment method`: gateway-specific card form/hosted checkout.
- `Policy notice`:
  - Local customers: "Local cards are processed in MVR only."
  - Foreign customers: "Payments are processed in non-MVR currency."

Do not allow user toggle that violates policy.

## Validation and Compliance
Server validations (mandatory):
- Currency must be in allowed list for segment.
- Amount must match reservation total snapshot.
- Reservation must still be payable (`unpaid` / not cancelled).

Operational controls:
- Log all payment attempts with reason codes.
- Use webhook signature verification.
- Store minimal card data only (prefer hosted fields/tokenization).

## Rollout Plan
Phase 1:
- Implement router service + intent routes + UI section with locked currency.
- Wire one local MVR adapter and one international USD adapter.

Phase 2:
- Add webhook handlers and reconciliation dashboard fields.
- Add retry/resume payment links from bookings list.

Phase 3:
- Add multi-currency foreign options (EUR/GBP) if needed.

## Test Matrix (Minimum)
1. Maldivian + MVR -> allowed.
2. Maldivian + USD -> blocked server-side.
3. Foreigner + USD -> allowed.
4. Foreigner + MVR -> blocked server-side.
5. Duplicate webhook event -> idempotent, no duplicate payment.
6. Amount tampering in request -> blocked.
7. Cancelled reservation payment attempt -> blocked.

## Notes for Next Implementation Session
Start implementation in this order:
1. Migration for payment metadata.
2. `CheckoutPaymentRouter` service.
3. Checkout payment-intent route.
4. Checkout view payment section and CTA wiring.
5. Webhook callback route + signature validation.
6. Automated tests for segment/currency enforcement.
