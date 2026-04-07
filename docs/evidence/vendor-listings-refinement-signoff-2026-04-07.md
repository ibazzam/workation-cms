# Vendor Listings Refinement Evidence (2026-04-07)

This record captures implementation and verification evidence for the vendor listing refinement bundle completed on 2026-04-07 UTC.

Scope covered:
- Accommodation pricing/policy detail fields and publish checklist hooks.
- Transport schedule/cutoff/boarding fields and semantic validation.
- Excursion + water sports safety/equipment/weather fields and semantic validation.

## Source of Truth
- Scenario matrix: docs/vendor-listings-refinement-scenario-matrix-2026-04-07.md
- Field contract: docs/vendor-listings-field-contract.md

## Implementation Evidence (Code-Level)

### 1) Accommodation pricing/policy fields
- Create/edit form fields added and wired for:
  - extra_guest_fee
  - child_fee
  - early_check_in_fee
  - late_check_out_fee
- Backend validation + persistence for all above fields confirmed.
- Publish readiness checklist hooks confirmed:
  - Set accommodation extra guest fee policy
  - Set accommodation child fee policy

Status: PASS

### 2) Transport schedule/cutoff/boarding
- Create/edit form fields present for:
  - schedule_start_time
  - schedule_end_time
  - booking_cutoff_minutes
  - boarding_instructions
- Backend validation + persistence confirmed.
- Semantic checks confirmed:
  - schedule end must be after start
  - booking cutoff must be between 0 and 10080 minutes
- Publish readiness checklist hooks confirmed:
  - Set transport operating schedule start and end times
  - Set transport booking cutoff time
  - Add transport boarding instructions

Status: PASS

### 3) Excursion + water sports safety/equipment/weather
- Create/edit form fields present for:
  - excursion_min_pax
  - excursion_max_pax
  - excursion_min_age
  - meeting_point
  - inclusions
  - exclusions
  - safety_waiver_required
  - equipment_rental_available
  - equipment_included
  - weather_cancellation_policy
- Backend validation + persistence confirmed.
- Semantic checks confirmed:
  - excursion_min_pax cannot be greater than excursion_max_pax
  - weather cancellation policy required for excursion/water_sports
  - equipment values restricted to allowed catalog
- Publish readiness checklist hooks confirmed:
  - Set whether safety waiver is required
  - Add weather cancellation policy
  - Select at least one included equipment item

Status: PASS

### 4) Water sports category consistency
- Included in category render/order and scoping behavior.
- Included in capacity build/validation paths.

Status: PASS

## Diagnostics Evidence
- Files checked for editor/static errors after implementation:
  - routes/vendor-operations.php
  - app/Support/portal_helpers.php
  - resources/views/vendor-portal/partials/listings-console.blade.php
  - resources/views/vendor-portal.blade.php
  - docs/vendor-listings-refinement-scenario-matrix-2026-04-07.md
  - docs/vendor-listings-field-contract.md
- Result: No errors found.

Status: PASS

## Manual Smoke Checklist (UI/Behavior) - Pending Operator Run

| Check | Result (PASS/FAIL/PENDING) | Evidence link/path | Notes |
| --- | --- | --- | --- |
| ACC-04 complete policy set removes accommodation checklist misses | PENDING |  |  |
| TRN-05 invalid schedule order returns semantic validation error | PENDING |  |  |
| TRN-06 cutoff out of range returns semantic validation error | PENDING |  |  |
| EXW-04 min pax > max pax returns semantic validation error | PENDING |  |  |
| EXW-06 complete excursion data removes checklist misses | PENDING |  |  |
| EXW-07 water sports edit values persist after reload | PENDING |  |  |

## Sign-Off Recommendation
- Engineering implementation verification: PASS
- Static diagnostics verification: PASS
- Manual UI smoke evidence: PENDING

Decision: CONDITIONAL GO after manual smoke checklist completion.

## Approval
- Engineering owner:
- QA owner:
- Product owner:
- Timestamp (UTC):

## Closure Rule
Mark this evidence as final PASS and close any remaining related roadmap verification sub-item after all pending manual smoke checks are recorded as PASS.
