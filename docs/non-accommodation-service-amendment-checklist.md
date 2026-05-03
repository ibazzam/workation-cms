# Non-Accommodation Service Amendment Checklist

Purpose: Track category-by-category UX and booking-flow amendments for non-accommodation listings.

Owner: Product + Vendor Portal
Last Updated: 2026-05-03
Status Legend: [ ] Not Started, [~] In Progress, [x] Done

## Marine Transport
- [ ] Category listing page UX refinement
- [ ] Individual service page refinement
- [x] Booking form: origin and destination fields (`origin_point` / `destination_point` required fields)
- [x] Booking form: passenger counts (adult/child) (generic Adults / Pax and Children fields)
- [x] Confirm no transfer step is shown (reserve-category now redirects directly to checkout, skipping /transfer page)
- [ ] Payment gateway handoff validation

## Land Transport 
- [ ] Category listing page UX refinement
- [ ] Individual service page refinement
- [x] Booking form: pickup and drop-off fields (`origin_point` / `destination_point` required fields)
- [x] Booking form: passenger counts (adult/child) (generic Adults / Pax and Children fields)
- [x] Confirm no transfer step is shown (reserve-category now redirects directly to checkout, skipping /transfer page)
- [ ] Payment gateway handoff validation

## Excursion
- [ ] Category listing page UX refinement
- [ ] Individual service page refinement
- [x] Booking form: activity date and guest count (adult/child/infant with per-unit pricing)
- [x] Booking form: transfer inclusive details (departure area/jetty, departure time, return slot) – shown when listing has transfer options
- [x] Confirm no separate transfer step is shown (skipped in redirect; transfer selection inline on booking form)
- [ ] Payment gateway handoff validation
- [x] Category listing page UX refinement
- [x] Individual service page refinement
- [x] Booking form: activity date and guest count
- [x] Booking form: transfer inclusive details (departure area/jetty, departure time, return slot)
- [x] Confirm no separate transfer step is shown
- [x] Payment gateway handoff validation

## Remote Workspace
- [ ] Category listing page UX refinement
- [ ] Individual service page refinement
- [x] Booking form: work date(s) and team/guest count (date range + Adults / Pax)
- [x] Booking form: transfer inclusive details (departure area/jetty, departure time, return slot) as optional fields
- [x] Confirm no separate transfer step is shown (skipped in redirect)
- [ ] Payment gateway handoff validation

## Conference Room
- [ ] Category listing page UX refinement
- [ ] Individual service page refinement
- [x] Booking form: event date and attendee count (event_type, expected_capacity, date fields)
- [x] Booking form: transfer inclusive details for resort venues (departure area/jetty, departure time, return slot) as optional fields
- [x] Confirm no separate transfer step is shown when transfer is included (skipped in redirect)
- [ ] Payment gateway handoff validation

## Resort Day Visit
- [ ] Category listing page UX refinement
- [ ] Individual service page refinement
- [x] Booking form: visit date and guest count (visit_package, date, Adults / Pax)
- [x] Booking form: transfer inclusive details (departure area/jetty, departure time, return slot) as optional fields
- [x] Confirm no separate transfer step is shown (skipped in redirect)
- [ ] Payment gateway handoff validation

## Restaurant
- [ ] Category listing page UX refinement
- [ ] Individual service page refinement
- [x] Booking form: reservation date/time and guest count (datetime-local start/end, Adults / Pax)
- [x] Booking form: transfer inclusive details for applicable venues (departure area/jetty, departure time, return slot) as optional fields
- [x] Confirm no separate transfer step is shown when transfer is included (skipped in redirect)
- [ ] Payment gateway handoff validation

## Vehicle Rental
- [ ] Category listing page UX refinement
- [ ] Individual service page refinement
- [x] Booking form: pickup and return date/time (service_start_date / service_end_date)
- [x] Booking form: renter details and terms (driver_license_number field + rental terms notice displayed)
- [x] Confirm no transfer step is shown (skipped in redirect)
- [ ] Payment gateway handoff validation

## Cross-Cutting Validation
- [ ] Ensure cancellation policy is vendor-defined across all non-accommodation categories
- [ ] Ensure payment-page policy copy is category-accurate and not misleading
- [x] Ensure booking step labels match actual flow per category (label maps defined per category in GET + POST routes)
- [ ] Ensure mobile and desktop UX consistency for category booking pages
