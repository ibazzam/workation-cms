# Portal UI Product Roadmap & To-Do List

Target: complete launch-critical portal work by March 31, 2026.

## Completed Foundation
- [x] Display error and success messages for key actions
- [x] Improve visual styling and theme consistency
- [x] Improve mobile and tablet layouts
- [x] Admin moderation UX: create, edit, delete, search, filter, bulk actions
- [x] Admin audit logs and activity history
- [x] Admin navigation improvements
- [x] Admin dashboard widgets and system health blocks
- [x] Privacy Policy page published
- [x] Terms of Service page published
- [x] Vendor social auth baseline (Google, Facebook, Apple) implemented

## P0 Launch-Critical (Finish First)
- [ ] Stabilize Facebook login end-to-end in production
	- [x] Added callback fallback network resiliency (timeout + retry) for Graph token/profile fetch path (2026-03-22)
	- [x] Added one-run Facebook production sign-off worksheet (`docs/vendor-facebook-production-signoff-template.md`) (2026-03-22)
	- [x] Captured production public pre-check evidence (`docs/evidence/facebook-prod-signoff-2026-03-22.md`) (2026-03-22)
	- [x] Captured production OAuth redirect/callback-path header evidence (`docs/evidence/facebook-prod-signoff-2026-03-22.md`) (2026-03-22)
	- [x] Prepared final sign-off entry scaffold in evidence doc (`docs/evidence/facebook-prod-signoff-2026-03-22.md`) (2026-03-22)
	- [ ] Capture production smoke evidence and sign-off (PASS/FAIL + Render log correlation) per runbook
- [ ] Stabilize WhatsApp OTP delivery for vendor auth (Twilio template/sandbox and production sender rollout)
	- [x] Added channel-mode hardening for `TWILIO_PHONE_CHANNEL=auto` with WhatsApp-first then SMS fallback behavior (2026-03-22)
	- [x] Added OTP delivery regression coverage for WhatsApp guidance, template fallback, and auto SMS fallback (`tests/Feature/VendorPhoneOtpDeliveryTest.php`) (2026-03-22)
	- [x] Added production runbook for Twilio WhatsApp OTP rollout (`docs/vendor-whatsapp-otp-production-runbook.md`) (2026-03-22)
	- [ ] Capture production WhatsApp OTP smoke evidence and sign-off (explicit WhatsApp mode + auto fallback mode)
- [x] Add visible "Social login status" diagnostics in vendor register UI
- [x] Add clear retry guidance for OAuth failures in vendor register UI
- [x] Ensure canonical host consistency across all auth entry points (www vs non-www)
- [x] Add vendor register page smoke test for each social provider button visibility
- [x] Add production runbook section for social auth verification steps
- [x] Verify accessibility basics on portal login/register screens (labels, focus states, contrast)

## P1 Core Portal UX

### Admin Portal
- [ ] Complete role management and permission display matrix (human-readable permissions)
- [ ] Add confirmation modal consistency for all destructive actions
- [ ] Add empty states for audit logs, users list, and request queues
- [ ] Add pagination controls and row counts for long admin lists

### Vendor Portal
- [x] Add vendor dashboard summary cards (bookings, payouts, status)
- [x] Add vendor profile management and account settings section
- [x] Add backend connectivity status indicator and last sync time
- [x] Add support/help links with contact paths and docs links
- [x] Add database-backed operations management sections (properties, services, availability, reservations, pricing, billing)
- [x] Add category-based onboarding wizard for vendor vertical selection (transport/accommodation/excursions/remote workspace/resort day visit/restaurants/vehicle rentals)
- [x] Add room category setup flow for accommodation vendors (quantity, occupancy, amenities, price)
- [x] Add vendor listing photo upload workflow (entity-based image uploads + media list)
- [x] Unify vendor category operations UX so availability and reservations are managed together per category card
- [x] Enforce accommodation room-level operations (property -> room linking, room-only availability updates, room-only accommodation reservations in operations view)
- [x] Split heavy vendor portal Blade into partials (sidebar, profile, billing settings, category operations, pricing, billing collection)
- [x] Add route-per-page vendor portal aliases (`/vendor/overview`, `/vendor/listings`, `/vendor/operations`, `/vendor/pricing`, `/vendor/billing`) with shared layout/nav wrapper
- [x] Add category-specific advanced listing forms (transport schedule fields, restaurant table windows, excursion slot constraints, rental license rules)
- [x] Add per-category publish readiness checks and mandatory image/field validation before listing publish
- [x] Add listing refinement QA scenario matrix for accommodation/transport/excursion/water sports (`docs/vendor-listings-refinement-scenario-matrix-2026-04-07.md`) (2026-04-07)
- [x] Add listing field contract mapping (UI field -> request key -> persisted details -> publish checklist hook) (`docs/vendor-listings-field-contract.md`) (2026-04-07)
- [x] Capture listing refinement implementation evidence and conditional sign-off record (`docs/evidence/vendor-listings-refinement-signoff-2026-04-07.md`) (2026-04-07)

#### Vendor Portal Multi-Page Migration Task List (Execution Tracker)
- [x] Step 1: Introduce URL-based vendor section endpoints (`/vendor/profile`, `/vendor/listings`, `/vendor/reservations`, `/vendor/availability`, `/vendor/pricing`, `/vendor/billing`, `/vendor/promotions`, `/vendor/reports`) and map each to a section context.
- [x] Step 2: Switch vendor sidebar links from in-page anchors to route-driven section URLs.
- [x] Step 3: Support direct section URLs by honoring `?page=` as default panel state on first load.
- [x] Step 4: Extract listings management block into standalone partial (`resources/views/vendor-portal/partials/listings-console.blade.php`).
- [x] Step 5: Render reservations + availability section only for reservations routes (`?page=reservations|operations|availability`).
- [x] Step 6: Render pricing section only for pricing routes (`?page=pricing`).
- [x] Step 7: Render billing + collections section only for billing routes (`?page=billing`).
- [x] Step 8: Render profile/account/compliance section only for profile routes (`?page=profile`).
- [x] Step 9: Add promotions/loyalty dedicated page view and route-level policy checks.
- [x] Step 10: Add reports dedicated page view with export/download actions.

### Landing Page
- [ ] Improve CTA hierarchy and conversion-focused content blocks
- [ ] Add lightweight page transition and section reveal animations
- [ ] Improve lighthouse/accessibility score for landing page

## P2 Customer Portal Scope
- [ ] Build customer portal shell (layout, nav, auth guard)
- [ ] Add customer login/registration/reset flow UI
- [ ] Add bookings list and booking detail views
- [ ] Add payment history and downloadable receipt UI
- [ ] Add customer notifications/messages center

## P2 UI System & Quality
- [ ] Refactor repeated inline styles into reusable CSS tokens/classes
- [ ] Unify buttons, inputs, cards, badges, and modal patterns across portals
- [ ] Add consistent loading, skeleton, and empty states across key pages
- [ ] Add error state components for API and network failures
- [ ] Add visual regression checklist for critical pages

## P3 Enhancements
- [ ] Add multilingual/i18n support framework
- [ ] Add onboarding coach marks/tooltips for first-time portal users
- [ ] Add activity analytics dashboard for product insights
- [ ] Evaluate dark mode after launch stabilization

## Execution Checklist (Weekly)
- [ ] Review this checklist every Monday and reorder by risk
- [ ] Demo completed items every Friday with screenshots
- [ ] Convert completed roadmap items into release notes
- [ ] Keep only one source of truth in this file (no duplicates)