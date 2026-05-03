# Non-Accommodation Validation Matrix V1 (2026-05-03)

Purpose: Convert docs/non-accommodation-service-amendment-checklist.md into executable QA and sign-off records.

Status legend:
- PASS: Verified in UI + persisted correctly + checkout handoff valid
- FAIL: Behavior incorrect or incomplete
- BLOCKED: Missing test data/environment dependency
- TODO: Not executed yet

## Global Rules to Validate for Each Category
1. Category listing page UX refinement complete and usable.
2. Individual service page refinement complete and usable.
3. Booking form shows required category-specific fields.
4. Booking details persist to reservation notes correctly.
5. Transfer-step behavior matches agreed policy (no incorrect separate transfer step).
6. Payment gateway handoff works from checkout/payment-intent.
7. Cancellation policy and policy copy are category-accurate.
8. Mobile and desktop behavior are both acceptable.

## Category Matrix

| Category | Listing UX | Service Page UX | Booking Form Fields | Transfer Behavior | Payment Handoff | Policy Accuracy | Responsive (M/D) | Status | Severity | Owner | Evidence | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Marine Transport | TODO | TODO | TODO | TODO | TODO | TODO | TODO | TODO | P0 | Product + Backend + Frontend |  | origin/destination + pax required |
| Land Transport | TODO | TODO | TODO | TODO | TODO | TODO | TODO | TODO | P0 | Product + Backend + Frontend |  | pickup/drop-off + pax required |
| Excursion | TODO | TODO | TODO | TODO | TODO | TODO | TODO | TODO | P0 | Product + Backend + Frontend |  | activity date + guest count + transfer details |
| Remote Workspace | TODO | TODO | TODO | TODO | TODO | TODO | TODO | TODO | P0 | Product + Backend + Frontend |  | work date(s) + team/guest count |
| Conference Room | TODO | TODO | TODO | TODO | TODO | TODO | TODO | TODO | P0 | Product + Backend + Frontend |  | event date + attendee count |
| Resort Day Visit | TODO | TODO | TODO | TODO | TODO | TODO | TODO | TODO | P0 | Product + Backend + Frontend |  | visit date + guest count |
| Restaurant | TODO | TODO | TODO | TODO | TODO | TODO | TODO | TODO | P0 | Product + Backend + Frontend |  | reservation datetime + guest count |
| Vehicle Rental | TODO | TODO | TODO | TODO | TODO | TODO | TODO | TODO | P0 | Product + Backend + Frontend |  | pickup/return datetime + renter terms |

## Test Case Template (Use Per Category)

| Field | Value |
| --- | --- |
| Category |  |
| Listing ID / Property ID |  |
| Test URL |  |
| Test Device | Desktop / Mobile |
| Input Payload Summary |  |
| Expected Outcome |  |
| Actual Outcome |  |
| Notes Persisted Check | PASS/FAIL |
| Payment Intent Check | PASS/FAIL |
| Evidence Links |  |
| Tester |  |
| Date |  |

## Transfer Policy Decision Log (Required Before Final Sign-Off)

| Category | Separate Transfer Step Allowed? | Required Inline Transfer Fields | Approved By | Date |
| --- | --- | --- | --- | --- |
| Marine Transport | TBD | TBD |  |  |
| Land Transport | TBD | TBD |  |  |
| Excursion | TBD | TBD |  |  |
| Remote Workspace | TBD | TBD |  |  |
| Conference Room | TBD | TBD |  |  |
| Resort Day Visit | TBD | TBD |  |  |
| Restaurant | TBD | TBD |  |  |
| Vehicle Rental | TBD | TBD |  |  |

## Defect Backlog (Non-Accommodation Only)

| Defect ID | Category | Description | Severity | Status | Assignee | ETA | Evidence |
| --- | --- | --- | --- | --- | --- | --- | --- |
|  |  |  | P0/P1/P2 | Open |  |  |  |

## Exit Criteria
1. All categories show PASS for P0 columns.
2. Transfer policy is explicitly decided, implemented, and verified.
3. Payment handoff validated for each category.
4. Evidence attached for desktop and mobile for each category.
