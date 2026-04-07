# Vendor Listings Field Contract

Purpose: map frontend fields to request keys, persisted listing_details keys, and publish-readiness hooks for the latest refinement set.

## Accommodation Pricing and Policy Fields

| UI Label | Form Key | listing_details Key | Validation Layer | Publish Checklist Hook |
|---|---|---|---|---|
| Extra Guest Fee (MVR) | extra_guest_fee | extra_guest_fee | numeric >= 0 | Set accommodation extra guest fee policy |
| Child Fee (MVR) | child_fee | child_fee | numeric >= 0 | Set accommodation child fee policy |
| Early Check-in Fee (MVR) | early_check_in_fee | early_check_in_fee | numeric >= 0 | none (persisted policy detail) |
| Late Check-out Fee (MVR) | late_check_out_fee | late_check_out_fee | numeric >= 0 | none (persisted policy detail) |
| Child Policy | child_policy | child_policy | string max length | Add accommodation child policy |
| Meal Plan | meal_plan | meal_plan | allowed enum | Choose an accommodation meal plan |

## Transport Schedule and Boarding Fields

| UI Label | Form Key | listing_details Key | Validation Layer | Publish Checklist Hook |
|---|---|---|---|---|
| Schedule Start | schedule_start_time | schedule_start_time | time format HH:MM | Set transport operating schedule start and end times |
| Schedule End | schedule_end_time | schedule_end_time | time format HH:MM + semantic start < end | Set transport operating schedule start and end times |
| Booking Cutoff (minutes) | booking_cutoff_minutes | booking_cutoff_minutes | integer 0..10080 | Set transport booking cutoff time |
| Boarding Instructions | boarding_instructions | boarding_instructions | string max length | Add transport boarding instructions |

## Excursion and Water Sports Safety/Operational Fields

| UI Label | Form Key | listing_details Key | Validation Layer | Publish Checklist Hook |
|---|---|---|---|---|
| Excursion Duration (minutes) | excursion_duration_minutes | excursion_duration_minutes | integer 30..1440 | Add excursion duration |
| Excursion Type | excursion_type | excursion_type | catalog-validated value | Choose an excursion type |
| Minimum Participants | excursion_min_pax | excursion_min_pax | integer 1..1000 + semantic <= max | none (semantic guard applies) |
| Maximum Participants | excursion_max_pax | excursion_max_pax | integer 1..1000 + semantic >= min | none (semantic guard applies) |
| Minimum Age | excursion_min_age | excursion_min_age | integer 0..99 | none |
| Meeting Point | meeting_point | meeting_point | string max length | none |
| Inclusions | inclusions | inclusions | string max length | none |
| Exclusions | exclusions | exclusions | string max length | none |
| Safety Waiver Required | safety_waiver_required | safety_waiver_required | yes/no enum | Set whether safety waiver is required |
| Equipment Rental Available | equipment_rental_available | equipment_rental_available | yes/no enum | none |
| Equipment Included | equipment_included[] | equipment_included | allowed set + array shape | Select at least one included equipment item |
| Weather Cancellation Policy | weather_cancellation_policy | weather_cancellation_policy | required semantic rule for excursion/water_sports | Add weather cancellation policy |

## Allowed Equipment Catalog
- snorkel_gear
- life_jacket
- fins
- wetsuit
- helmet
- gopro_mount

## Category Scope Notes
- excursion and water_sports share the same excursion scope fields.
- water_sports is included in category view ordering and in capacity build/validation paths.

## Source References
- UI form scopes: resources/views/vendor-portal/partials/listings-console.blade.php
- Category flow/scope rendering: resources/views/vendor-portal.blade.php
- Request validation, detail mapping, semantic validation: routes/vendor-operations.php
- Publish readiness checklist: app/Support/portal_helpers.php
