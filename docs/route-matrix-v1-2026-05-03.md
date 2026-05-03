# Route Matrix V1 (2026-05-03)

Purpose: Execute Task 1 from portal roadmap with a single source of truth for route integrity.

Status legend:
- PASS: Route loads with expected content
- FAIL: Broken route, wrong destination, or blocking UI error
- BLOCKED: Requires environment or data dependency not available
- TODO: Not executed yet

## Critical Journey Routes

| ID | Route | Area | Expected Result | Desktop | Tablet | Mobile | Status | Severity | Owner | Evidence | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| R-001 | / | Homepage | Home loads with hero and category cards | TODO | TODO | TODO | TODO | P0 | Frontend |  |  |
| R-002 | /catalog/accommodation | Catalog | Accommodation listing page loads | TODO | TODO | TODO | TODO | P0 | Frontend |  |  |
| R-003 | /catalog/marine-transport | Catalog | Marine transport page loads with filter controls | TODO | TODO | TODO | TODO | P0 | Frontend |  |  |
| R-004 | /catalog/land-transport | Catalog | Land transport page loads with filter controls | TODO | TODO | TODO | TODO | P0 | Frontend |  |  |
| R-005 | /catalog/excursion | Catalog | Excursion page loads with filter controls | TODO | TODO | TODO | TODO | P0 | Frontend |  |  |
| R-006 | /catalog/remote_workspace | Catalog | Remote workspace page loads with filter controls | TODO | TODO | TODO | TODO | P0 | Frontend |  |  |
| R-007 | /catalog/conference_room | Catalog | Conference room page loads with filter controls | TODO | TODO | TODO | TODO | P0 | Frontend |  |  |
| R-008 | /catalog/resort_day_visit | Catalog | Resort day visit page loads with filter controls | TODO | TODO | TODO | TODO | P0 | Frontend |  |  |
| R-009 | /catalog/restaurant | Catalog | Restaurant page loads with atoll/island controls | TODO | TODO | TODO | TODO | P0 | Frontend |  |  |
| R-010 | /catalog/vehicle_rental | Catalog | Vehicle rental page loads with pickup island controls | TODO | TODO | TODO | TODO | P0 | Frontend |  |  |
| R-011 | /category-booking/marine-transport/{property_id} | Booking | Category booking form loads | TODO | TODO | TODO | TODO | P0 | Backend + Frontend |  | Use a valid property_id from seed/prod |
| R-012 | /category-booking/land-transport/{property_id} | Booking | Category booking form loads | TODO | TODO | TODO | TODO | P0 | Backend + Frontend |  | Use a valid property_id |
| R-013 | /category-booking/excursion/{property_id} | Booking | Category booking form loads | TODO | TODO | TODO | TODO | P0 | Backend + Frontend |  | Use a valid property_id |
| R-014 | /category-booking/remote_workspace/{property_id} | Booking | Category booking form loads | TODO | TODO | TODO | TODO | P0 | Backend + Frontend |  | Use a valid property_id |
| R-015 | /category-booking/conference_room/{property_id} | Booking | Category booking form loads | TODO | TODO | TODO | TODO | P0 | Backend + Frontend |  | Use a valid property_id |
| R-016 | /category-booking/resort_day_visit/{property_id} | Booking | Category booking form loads | TODO | TODO | TODO | TODO | P0 | Backend + Frontend |  | Use a valid property_id |
| R-017 | /category-booking/restaurant/{property_id} | Booking | Category booking form loads | TODO | TODO | TODO | TODO | P0 | Backend + Frontend |  | Use a valid property_id |
| R-018 | /category-booking/vehicle_rental/{property_id} | Booking | Category booking form loads | TODO | TODO | TODO | TODO | P0 | Backend + Frontend |  | Use a valid property_id |
| R-019 | /booking/checkout/{reservation_id} | Checkout | Checkout loads with expected summary | TODO | TODO | TODO | TODO | P0 | Backend + Frontend |  | Use a pending reservation |
| R-020 | /booking/checkout/{reservation_id}/transfer | Checkout | Transfer step behavior matches category policy | TODO | TODO | TODO | TODO | P0 | Backend + Product |  | Critical for non-accommodation alignment |
| R-021 | /portal/customer/login | Auth | Login form loads and accepts valid credentials | TODO | TODO | TODO | TODO | P0 | Auth |  |  |
| R-022 | /portal/customer/register | Auth | Register form loads with social options as configured | TODO | TODO | TODO | TODO | P0 | Auth |  |  |
| R-023 | /customer | Portal | Authenticated customer portal loads bookings section | TODO | TODO | TODO | TODO | P0 | Backend + Frontend |  |  |

## Homepage Link Integrity

| Link Source | Link Label | Destination | Status | Severity | Owner | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| Hero CTA | Search/Browse CTA | /catalog/accommodation or configured destination | TODO | P0 | Frontend |  |
| Category Card | Marine Transport | /catalog/marine-transport | TODO | P0 | Frontend |  |
| Category Card | Land Transport | /catalog/land-transport | TODO | P0 | Frontend |  |
| Category Card | Excursion | /catalog/excursion | TODO | P0 | Frontend |  |
| Category Card | Remote Workspace | /catalog/remote_workspace | TODO | P0 | Frontend |  |
| Category Card | Conference Room | /catalog/conference_room | TODO | P0 | Frontend |  |
| Category Card | Resort Day Visit | /catalog/resort_day_visit | TODO | P0 | Frontend |  |
| Category Card | Restaurant | /catalog/restaurant | TODO | P0 | Frontend |  |
| Category Card | Vehicle Rental | /catalog/vehicle_rental | TODO | P0 | Frontend |  |
| Footer | Customer Portal | /customer | TODO | P1 | Frontend |  |

## Defect Log

| Defect ID | Route/Area | Description | Severity | Status | Assignee | Fix PR/Commit | Evidence |
| --- | --- | --- | --- | --- | --- | --- | --- |
|  |  |  |  | Open |  |  |  |

## Daily Execution Cadence
1. Run full matrix once per day until all P0 routes are PASS.
2. Log every FAIL with reproducible steps and screenshot/video evidence.
3. Re-test fixed routes in same day and update status to PASS only after evidence.
