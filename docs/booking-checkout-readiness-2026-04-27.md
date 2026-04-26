# Booking + Checkout Readiness Plan (2026-04-27)

This runbook is the execution checklist for tomorrow to validate accommodation and category booking through successful payment capture and reservation confirmation.

## Scope
- Booking creation flow
- Availability attribution (slot reserve behavior)
- Checkout summary accuracy
- Payment routing and intent generation
- Payment completion via webhook path (Stripe, BML, MIB)

## Run Metadata
- Date (local): 2026-04-27
- Environment: Production
- Branch under verification: `feat/vendor-listings-wizard-flow-20260324`
- Sign-off owner:
- QA executor:
- Observer (ops/support):

## Team Roles
- Backend owner: validates route logic, DB attribution fields, and payment event updates.
- QA owner: executes booking and payment test matrix.
- Ops owner: monitors logs, webhook events, and incident channel.
- Product owner: final go/no-go sign-off.

## Critical Pre-Flight (Must Pass Before Live Payment Test)
- [ ] Confirm no open production blockers in this list.
- [ ] Confirm gateway webhook secrets are explicitly set (not fallback defaults).
- [ ] Confirm test cards/accounts for Stripe, BML, and MIB are available.
- [ ] Confirm support fallback message is ready if any provider is degraded.
- [ ] Confirm rollback owner and rollback trigger thresholds.

## Pre-Flight Technical Fix Tasks
These are required before final live verification:

1. Block unsafe internal completion in production
- [ ] Ensure hosted internal completion path cannot be used as a substitute for real gateway verification.
- [ ] Keep payment confirmation tied to verified gateway callback/webhook.

2. Correct category units attribution
- [ ] Ensure `unitsRequested` is calculated after guest values are assigned.
- [ ] Re-test category flows where inventory should scale with guest count.

3. Add availability release path
- [ ] Add or verify release logic for cancelled/failed/expired payment outcomes.
- [ ] Verify reserved counts do not drift after failed payment attempts.

## Step-by-Step Execution Sequence

### Step 1: Configuration Snapshot (09:00)
- [ ] Record app commit SHA and deployment ID.
- [ ] Capture payment config snapshot (allowed currencies, gateway priority, webhook modes).
- [ ] Confirm production hostnames and callback URLs.

Evidence:
- Config screenshot/log path:
- Deployment link:

### Step 2: Accommodation Booking Validation (09:30)
Run one local-resident and one foreign-national scenario:
- [ ] Create booking from room page with valid future dates.
- [ ] Verify no past-date and checkout-after-checkin constraints.
- [ ] Verify moderation and availability checks behave correctly.
- [ ] Verify reservation row created with `status=pending`, `payment_status=unpaid`.
- [ ] Verify notes payload has pricing and transfer attribution fields populated.

Evidence:
- Reservation IDs:
- Screenshot paths:
- Notes/DB capture path:

### Step 3: Category Booking Validation (10:15)
Run one transport-like category and one excursion/day-visit category:
- [ ] Submit valid booking with category-specific fields.
- [ ] Verify category details persisted in notes.
- [ ] Verify units attribution uses correct guest/unit counts.
- [ ] Verify slot reservation increment matches expected units.

Evidence:
- Reservation IDs:
- Slot delta captures:

### Step 4: Checkout Summary Accuracy (11:00)
For each created reservation:
- [ ] Verify guest names, nationality/residency, dates, and totals match booking payload.
- [ ] Verify tax lines, discount, transfer, and final total reconcile.
- [ ] Verify payment options shown match segment and policy.

Evidence:
- Checkout screenshots:
- Reconciliation notes:

### Step 5: Payment Intent + Routing Validation (12:00)
For each segment/provider scenario:
- [ ] Create payment intent from checkout.
- [ ] Verify stored fields: segment, currency, gateway, provider, intent id, payment amount.
- [ ] Verify settlement fields: commission, gateway fee, vendor payout.

Evidence:
- Intent IDs:
- DB capture paths:

### Step 6: Provider Execution Matrix (14:00)
Execute provider-specific end-to-end checks:

1. Stripe
- [ ] Local segment allowed path
- [ ] Foreign segment allowed path
- [ ] Success webhook updates reservation to paid/confirmed
- [ ] Failed payment keeps reservation unpaid

2. BML
- [ ] Local MVR route
- [ ] Foreign USD route (if enabled)
- [ ] Success webhook updates reservation to paid/confirmed

3. MIB
- [ ] Local MVR route
- [ ] Foreign USD route (if enabled)
- [ ] Success webhook updates reservation to paid/confirmed

Evidence:
- Gateway event IDs:
- Webhook log references:
- Reservation status snapshots:

### Step 7: Negative and Idempotency Checks (16:00)
- [ ] Re-send same webhook event id and confirm duplicate-safe behavior.
- [ ] Send invalid signature payload and confirm rejection.
- [ ] Attempt payment on cancelled reservation and confirm blocked.
- [ ] Attempt payment on already paid reservation and confirm blocked.

Evidence:
- Request/response snapshots:
- Log references:

### Step 8: Go/No-Go Review (17:00)
- [ ] All critical checks passed.
- [ ] No unresolved P0/P1 defects.
- [ ] Support + ops handoff complete.
- [ ] Final sign-off decision recorded.

Decision:
- Go / No-Go
- Sign-off owner:
- Timestamp:

## Pass/Fail Gates
- Gate A: Booking creation and validation passes for accommodation and category flows.
- Gate B: Checkout summary is accurate and consistent with stored reservation notes.
- Gate C: Payment routing follows segment/currency rules exactly.
- Gate D: Successful provider callbacks/webhooks set `payment_status=paid` and reservation `status=confirmed`.
- Gate E: Failed/cancelled flows do not leave stale paid state or stale slot holds.

If any gate fails, outcome is automatic No-Go until fixed and re-validated.

## Defect Triage Rules
- P0: Payment can be marked paid without verified provider signal, or major overbooking risk.
- P1: Incorrect segment routing, settlement attribution errors, or repeatable checkout total mismatch.
- P2: Non-blocking UI or copy issues.

## Evidence Log Table
| Time (UTC+5) | Scenario | Reservation ID | Provider | Result (PASS/FAIL) | Evidence path | Notes |
| --- | --- | --- | --- | --- | --- | --- |
|  | Accommodation local |  |  |  |  |  |
|  | Accommodation foreign |  |  |  |  |  |
|  | Category flow 1 |  |  |  |  |  |
|  | Category flow 2 |  |  |  |  |  |
|  | Stripe success |  | Stripe |  |  |  |
|  | BML success |  | BML |  |  |  |
|  | MIB success |  | MIB |  |  |  |
|  | Invalid signature reject |  |  |  |  |  |
|  | Duplicate webhook handling |  |  |  |  |  |

## End-of-Day Deliverables
- Completed runbook with evidence links.
- Final go/no-go line and approver signatures.
- Ticket list for unresolved items with owner and ETA.