# Customer Compensation Policy Matrix

Use this matrix to standardize compensation decisions for customer-impact incidents.

## Policy Principles
- Keep decisions consistent across channels and support agents.
- Prioritize service recovery (rebooking) before monetary compensation where viable.
- Align outcome to policy terms, incident impact, and customer effort required.
- Require documented reason codes and approver identity for every non-zero compensation.

## Compensation Types
- `REBOOK`: Alternate service replacement without direct payout.
- `CREDIT`: Platform credit for future use.
- `PARTIAL_REFUND`: Portion of paid amount returned.
- `FULL_REFUND`: Entire affected amount returned.
- `GOODWILL_CREDIT`: Additional discretionary credit for high-friction incidents.

## Approval Bands
- Band A: Up to 10% of affected order value or equivalent credit
  - Approver: Support Specialist
- Band B: >10% to 30% of affected order value
  - Approver: Support Lead
- Band C: >30% to 100% of affected order value
  - Approver: Duty Manager
- Band D: Above affected order value or exception requests
  - Approver: Executive + Finance signoff

## Decision Matrix

| Incident Type | Typical Trigger | Primary Remedy | Compensation Range | Required Approval |
|---|---|---|---|---|
| Service disruption (weather/operator cancel) | Confirmed disruption with no equivalent replacement | Rebook or refund | 50%-100% of affected segment | Band C |
| Service disruption (replacement accepted) | Replacement offered with moderate downgrade | Rebook + goodwill credit | 5%-20% credit | Band A-B |
| Vendor no-show / denied service | Customer cannot consume booked service | Full refund | 100% of affected segment | Band C |
| Listing inaccuracy (material) | Published terms differ from delivered service | Partial refund or credit | 10%-40% | Band B-C |
| Minor quality complaint | Service delivered with non-critical quality gap | Goodwill credit | 5%-15% | Band A-B |
| Payment duplicate capture | Duplicate/incorrect charge confirmed | Refund overcharge | 100% of overcharged portion | Band B-C |
| Long resolution delay (internal miss) | Platform misses committed SLA checkpoint | Goodwill credit | 5%-10% | Band A |
| Policy-valid cancellation outside refund window | Terms correctly applied | No monetary compensation | 0% | Band A (record reason) |

## Exception Rules
- Safety-related incidents can bypass standard ranges with Duty Manager approval.
- Repeat-impact customers (multiple incidents within 30 days) may receive +5% goodwill uplift.
- Fraud or abuse indicators require hold-and-review before any compensation action.

## Required Reason Codes
- `DISRUPTION_NO_REPLACEMENT`
- `DISRUPTION_DOWNGRADE_ACCEPTED`
- `VENDOR_NO_SHOW`
- `LISTING_INACCURACY`
- `QUALITY_GAP_MINOR`
- `PAYMENT_OVERCHARGE`
- `SLA_MISS_INTERNAL`
- `POLICY_DENIAL_VALID`

## Audit Requirements
- Record affected booking/payment reference.
- Record reason code and customer impact summary.
- Record compensation type, value, and approver.
- Record notification timestamp and channel.

## Review Cadence
- Weekly: Support Lead reviews compensation outliers and reopened cases.
- Monthly: Ops + Finance review compensation cost trend and policy adjustments.
- Quarterly: Policy calibration against incident patterns and customer satisfaction outcomes.
