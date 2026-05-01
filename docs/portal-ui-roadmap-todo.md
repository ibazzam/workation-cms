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
- [x] Island Atlas directory system (atoll-based grouping with type filtering) (2026-04-10)
- [x] Island classification integrity locked (190 inhabited, 1 resort, 888 uninhabited) (2026-04-10)
- [x] Atoll/Island API endpoints for cascading dropdowns across app (2026-04-10)
- [x] Vendor profile form with atoll/island business location fields (2026-04-10)
- [x] Shared Blade component for cascading atoll/island select dropdowns (2026-04-10)

## P0 Launch-Critical (Finish First)
- [ ] Stabilize Facebook login end-to-end in production
	- [x] Added callback fallback network resiliency (timeout + retry) for Graph token/profile fetch path (2026-03-22)
	- [x] Added one-run Facebook production sign-off worksheet (`docs/vendor-facebook-production-signoff-template.md`) (2026-03-22)
	- [x] Captured production public pre-check evidence (`docs/evidence/facebook-prod-signoff-2026-03-22.md`) (2026-03-22)
	- [x] Captured production OAuth redirect/callback-path header evidence (`docs/evidence/facebook-prod-signoff-2026-03-22.md`) (2026-03-22)
	- [x] Prepared final sign-off entry scaffold in evidence doc (`docs/evidence/facebook-prod-signoff-2026-03-22.md`) (2026-03-22)
	- [ ] Capture production smoke evidence and sign-off (PASS/FAIL + Render log correlation) per runbook
- [ ] Complete atoll/island integration across app (subsidiary of frontend work)
	- [x] Backend: API endpoints (/api/atoll-island/*) for cascading data (2026-04-10)
	- [x] Vendor: Profile form with atoll/island location selection (2026-04-10)
	- [ ] Vendor: Listing forms with atoll/island per category
	- [ ] Frontend: Search form with cascading atoll/island filters
	- [ ] Frontend: Homepage "most loved destinations" with island photo carousel
	- [ ] Frontend: Category cards showing atoll/island thumbnails
	- [ ] Frontend: Listing cards with island/atoll location info
	- [ ] Testing: End-to-end validation of cascading select behavior
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
- [x] Release post-booking vendor contact panel (call, WhatsApp, email) with finalized-booking gating and support fallback (2026-05-01)

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

## Frontend + Customer Portal A-Z QA Checklist (Execution Draft)

### Phase 1 - Route and Navigation Integrity
- [ ] Verify all homepage category cards route correctly to the expected category pages (no 404, no wrong category mapping)
- [ ] Verify all homepage promotional cards and links resolve to valid filtered catalog pages
- [ ] Verify global footer customer links route to valid customer and catalog targets
- [ ] Verify back-navigation consistency from property, room, category booking, and checkout pages
- [ ] Verify all major entry points work on desktop, tablet, and mobile breakpoints

### Phase 2 - Homepage Quality and Conversion
- [ ] Validate homepage hero image rendering quality and loading speed on desktop and mobile
- [ ] Validate homepage hero fallback behavior when managed hero image is missing
- [ ] Validate category grid icon, title, and subtitle consistency with actual catalog destinations
- [ ] Validate homepage sections (trending, weekend deals, loved stays) for stale or broken links
- [ ] Validate primary CTA hierarchy (search, browse category, continue booking) for visual clarity

### Phase 3 - Category Catalog Pages
- [ ] Validate each category hero image renders correctly from admin-managed media
- [ ] Validate category filters (query, atoll, island, sort, date-related fields) persist and apply correctly
- [ ] Validate empty state UX per category when no listings match filters
- [ ] Validate listing cards for image fallback, title, location, pricing, and CTA correctness
- [ ] Validate category-specific fields and labels for each vertical (accommodation, transport, excursion, workspace, conference, resort day, restaurant, vehicle rental)

### Phase 4 - Property and Room Journey
- [ ] Validate property details page media gallery quality, ordering, and fallback behavior
- [ ] Validate room list rendering and room-level media display for each property
- [ ] Validate check-in/check-out, guest counts, and prefill propagation from catalog to property to room pages
- [ ] Validate room booking form required fields and inline error states
- [ ] Validate room booking summary calculations and date/night calculations in UI

### Phase 5 - Category Booking Journey (Non-room flows)
- [ ] Validate category booking pages for each supported service type and required custom fields
- [ ] Validate category details are captured and passed to reservation notes correctly
- [ ] Validate service-first flow (collect service details before checkout) for all categories
- [ ] Validate validation errors are user-friendly and preserve entered values
- [ ] Validate redirect to checkout with complete summary payload for all category paths

### Phase 6 - Checkout and Payment Experience
- [ ] Validate checkout page renders full summary correctly for room and category reservations
- [ ] Validate invoice breakdown (subtotal, discounts, taxes, transfers, total) against reservation data
- [ ] Validate tax line and inclusions display behavior for missing and populated data
- [ ] Validate payment status UX and wording in checkout and customer portal booking cards
- [ ] Replace placeholder Confirm and Pay behavior with production payment flow requirements checklist
- [ ] Validate payment method display strategy (card, transfer, wallet, pay-later) and failure/retry UX

### Phase 7 - Customer Authentication and Session Flow
- [ ] Validate customer register, login, forgot password, reset password, and verify-email flows end-to-end
- [ ] Validate continue URL logic after login/register from catalog, property, and checkout entry points
- [ ] Validate social auth entry and callback flow for customer login/register
- [ ] Validate logout from customer portal and account menu across pages
- [ ] Validate session-expired behavior and protected-action prompts

### Phase 8 - Customer Portal Functional Completeness
- [ ] Validate bookings tabs (all, awaiting payment, upcoming, awaiting review) counts and data integrity
- [ ] Validate booking category pills and sidebar booking category filters
- [ ] Validate booking card actions (view, delete/cancel behavior, book again flow) and expected outcomes
- [ ] Validate profile update form persistence and confirmation messaging
- [ ] Validate frequent traveller, contact info, cards, promo, and gift card sections for real backend integration status
- [ ] Validate account menu links from catalog and other pages to the correct customer portal anchors

### Phase 9 - Frontend System Quality (Shared)
- [ ] Standardize loading states (skeleton/spinner) across catalog, property, checkout, and customer portal
- [ ] Standardize empty states, no-results states, and error state copy across core pages
- [ ] Standardize button variants, form controls, card spacing, and typography tokens across pages
- [ ] Verify responsive behavior at key breakpoints (<=480, <=768, <=980, >=1280)
- [ ] Validate image performance strategy (compression, dimensions, lazy loading, fallback placeholders)

### Phase 10 - Accessibility and UX Reliability
- [ ] Validate keyboard navigation and focus order for all primary booking and account forms
- [ ] Validate form labels, aria usage, and error association for inputs
- [ ] Validate color contrast for text, badges, and CTA buttons across major sections
- [ ] Validate semantic heading structure for homepage, category, property, checkout, and portal pages
- [ ] Validate touch target sizes and interaction spacing on mobile

### Phase 11 - Data and Business Rule Integrity
- [ ] Validate availability and reservation data synchronization with displayed UI status
- [ ] Validate pricing rules and rounding consistency between form, checkout summary, and stored reservation totals
- [ ] Validate currency consistency across all customer-facing pages and components
- [ ] Validate cancellation policy and inclusions source-of-truth consistency across property and checkout
- [ ] Validate booking status transitions and payment status transitions against expected lifecycle

### Phase 12 - Release Readiness and Sign-off
- [ ] Produce screenshot evidence set for homepage, each category, property, room, checkout, and customer portal tabs
- [ ] Produce route matrix with pass/fail and defect IDs for every critical journey
- [ ] Produce payment flow readiness report (implemented, placeholder, blocked, next action)
- [ ] Produce prioritized bug backlog by severity (P0/P1/P2) and assign owners
- [ ] Run final regression pass after fixes and capture release sign-off record

### Parallel Vendor Portal Follow-up (After Customer Frontend Sign-off)
- [ ] Build equivalent A-Z checklist for vendor portal overview, listings, operations, pricing, billing, reports, and promotions
- [ ] Validate every vendor form contract (field to request key to persistence to publish/readiness checks)
- [ ] Validate vendor media, availability, reservations, and billing workflows end-to-end

## Frontend + Customer Portal Working Tasklist (Run One by One)

Execution rule: complete each task fully (verify + fix + evidence) before moving to the next.

### Task 1 - Route Matrix and Link Integrity (P0)
- [ ] Build route matrix for: home, all category pages, property, room, category-booking, checkout, customer portal
- [ ] Click-test all homepage cards, promo links, and footer links
- [ ] Log broken/mismatched routes with severity and owner
- [ ] Acceptance: zero 404s and zero wrong-target links for critical booking flow
- [ ] Evidence: route matrix table + screenshots of each critical route

### Task 2 - Homepage UX and Hero Quality (P0)
- [ ] Verify homepage hero quality on desktop and mobile after admin upload
- [ ] Verify fallback behavior when no managed hero image exists
- [ ] Validate category card labels and subtitles against actual destinations
- [ ] Acceptance: hero renders sharp and all homepage CTAs route correctly
- [ ] Evidence: desktop/mobile screenshots + CTA click results

### Task 3 - Category Page Functional QA (P0)
- [ ] Validate hero rendering for each category from admin-managed media
- [ ] Validate search/filter/sort query behavior and URL persistence
- [ ] Validate listing cards (image, price, location, CTA) and empty states
- [ ] Acceptance: all categories usable with correct filtering behavior
- [ ] Evidence: one pass screenshot set per category

### Task 4 - Property and Room Journey QA (P0)
- [ ] Validate property media gallery, room media, and fallback behavior
- [ ] Validate prefill propagation: check-in/check-out/adults/children/rooms
- [ ] Validate room booking form errors and required fields
- [ ] Acceptance: property-to-room journey is consistent and error-safe
- [ ] Evidence: journey screenshots + issue list (if any)

### Task 5 - Non-room Category Booking QA (P0)
- [ ] Validate each category-booking form and required category-specific fields
- [ ] Validate category detail capture and reservation note payload completeness
- [ ] Validate redirect to checkout with correct summary data
- [ ] Acceptance: all category-booking flows reach checkout with correct context
- [ ] Evidence: flow-by-flow payload and UI checks

### Task 6 - Checkout Accuracy and Payment UX (P0)
- [ ] Validate checkout summary values against reservation inputs
- [ ] Validate invoice rows: subtotal, discount, taxes, transfer, total
- [ ] Validate payment status wording and CTA behavior
- [ ] Define production-ready replacement plan for placeholder Confirm and Pay behavior
- [ ] Acceptance: no pricing mismatch and clear payment progression UX
- [ ] Evidence: checkout comparison sheet (input vs displayed totals)

### Task 7 - Customer Auth and Session Continuity (P1)
- [ ] Validate register, login, verify-email, forgot/reset password
- [ ] Validate continue URL return behavior from catalog/property/checkout
- [ ] Validate logout and session-expiry handling
- [ ] Acceptance: auth flows are reliable with no dead-end redirects
- [ ] Evidence: auth flow test log + screenshots

### Task 8 - Customer Portal Functional QA (P1)
- [ ] Validate booking tabs and counts (all, awaiting payment, upcoming, awaiting review)
- [ ] Validate booking category filter behavior in sidebar and pills
- [ ] Validate profile update persistence and messaging
- [ ] Validate account sections marked as placeholder vs integrated
- [ ] Acceptance: customer portal sections behave as labeled and expected
- [ ] Evidence: section-wise pass/fail sheet

### Task 9 - Responsive and Accessibility Pass (P1)
- [ ] Validate key pages at <=480, <=768, <=980, >=1280
- [ ] Validate keyboard navigation and focus order on booking/auth forms
- [ ] Validate labels, aria, and contrast issues for top flows
- [ ] Acceptance: no blocking responsive or accessibility defects on core journey
- [ ] Evidence: breakpoint screenshot set + a11y findings list

### Task 10 - Frontend Consistency and UI System Cleanup (P2)
- [ ] Standardize buttons, cards, form controls, and state components
- [ ] Standardize loading, empty, and error states across customer journey
- [ ] Reduce repeated inline styles into reusable classes/tokens where feasible
- [ ] Acceptance: consistent interaction and visual language across pages
- [ ] Evidence: before/after UI consistency checklist

### Task 11 - Data and Business Rule Validation (P1)
- [ ] Validate availability/status synchronization with displayed UI state
- [ ] Validate currency and rounding consistency across catalog, booking, checkout, portal
- [ ] Validate cancellation policy and inclusions consistency across touchpoints
- [ ] Acceptance: no data integrity regressions in user-visible booking flow
- [ ] Evidence: rule validation matrix with sample records

### Task 12 - Release Readiness and Sign-off Pack (P0 final gate)
- [ ] Compile final defects list grouped by P0/P1/P2
- [ ] Confirm all P0 items resolved and retested
- [ ] Produce final screenshot evidence pack for critical user journeys
- [ ] Create go/no-go recommendation with residual risk notes
- [ ] Acceptance: explicit sign-off for frontend + customer portal
- [ ] Evidence: release sign-off document and pass summary

## Immediate Next Step
- [ ] Start Task 1 now and produce the first route matrix pass with defects list