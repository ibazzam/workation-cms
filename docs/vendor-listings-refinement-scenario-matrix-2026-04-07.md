# Vendor Listings Refinement Scenario Matrix (2026-04-07)

Purpose: verify launch-critical behavior for the latest listing refinement bundle across accommodation, transport, excursion, and water sports.

Scope:
- Publish readiness checklist behavior (missing-item messages).
- Request validation and semantic validation behavior.
- Create and edit parity for each required field.

## Preconditions
- Vendor user is authenticated and listing management is unlocked.
- At least one listing photo upload path is available for each listing under test.
- Test with at least one listing in each category:
  - accommodation
  - transport (land and marine profile where possible)
  - excursion
  - water_sports

## Matrix

| ID | Category | Flow | Test Setup | Action | Expected Result |
|---|---|---|---|---|---|
| ACC-01 | accommodation | create | Omit extra_guest_fee | Save as draft/publish check | Missing item contains: Set accommodation extra guest fee policy |
| ACC-02 | accommodation | create | Omit child_fee | Save as draft/publish check | Missing item contains: Set accommodation child fee policy |
| ACC-03 | accommodation | create | Omit child_policy | Save as draft/publish check | Missing item contains: Add accommodation child policy |
| ACC-04 | accommodation | create | Set extra_guest_fee, child_fee, child_policy, meal_plan, room category, and media | Publish check | No accommodation-policy missing items |
| ACC-05 | accommodation | edit | Existing listing with blank late_check_out_fee | Edit and save with fee value | Value persists after reload |
| TRN-01 | transport | create | Omit schedule_start_time | Save as draft/publish check | Missing item contains: Set transport operating schedule start and end times |
| TRN-02 | transport | create | Omit schedule_end_time | Save as draft/publish check | Missing item contains: Set transport operating schedule start and end times |
| TRN-03 | transport | create | Omit booking_cutoff_minutes | Save as draft/publish check | Missing item contains: Set transport booking cutoff time |
| TRN-04 | transport | create | Omit boarding_instructions | Save as draft/publish check | Missing item contains: Add transport boarding instructions |
| TRN-05 | transport | create | schedule_start_time >= schedule_end_time | Save | Semantic validation error: Transport operating schedule end time must be after start time. |
| TRN-06 | transport | create | booking_cutoff_minutes = 10081 | Save | Semantic validation error: Transport booking cutoff must be between 0 and 10080 minutes. |
| TRN-07 | transport | edit | Set schedule start/end, cutoff, boarding instructions | Save and reload | All values persist after reload |
| EXW-01 | excursion | create | Omit safety_waiver_required | Save as draft/publish check | Missing item contains: Set whether safety waiver is required |
| EXW-02 | water_sports | create | Omit weather_cancellation_policy | Save as draft/publish check | Missing item contains: Add weather cancellation policy |
| EXW-03 | excursion | create | equipment_included = [] | Save as draft/publish check | Missing item contains: Select at least one included equipment item |
| EXW-04 | water_sports | create | excursion_min_pax = 8, excursion_max_pax = 4 | Save | Semantic validation error: Excursion minimum participants cannot be greater than maximum participants. |
| EXW-05 | excursion | create | equipment_included includes unsupported key | Save | Semantic validation error: Equipment included contains unsupported values. |
| EXW-06 | excursion | create | Set all required fields including duration, type, waiver, equipment, weather policy | Publish check | No excursion/water-sports missing checklist items |
| EXW-07 | water_sports | edit | Set min/max pax, min age, meeting point, inclusions, exclusions | Save and reload | All values persist after reload |

## Execution Notes
- Run every case for both create and edit when applicable.
- For publish-check cases, validate message text from checklist output, not just boolean readiness.
- Capture screenshots for failed cases and include listing ID and category.

## Evidence Template (per failed case)
- Case ID:
- Listing category:
- Listing ID:
- Input values:
- Actual result:
- Expected result:
- Screenshot path:
- Follow-up fix PR:
