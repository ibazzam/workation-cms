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
- [ ] Add production runbook section for social auth verification steps
- [ ] Verify accessibility basics on portal login/register screens (labels, focus states, contrast)

## P1 Core Portal UX

### Admin Portal
- [ ] Complete role management and permission display matrix (human-readable permissions)
- [ ] Add confirmation modal consistency for all destructive actions
- [ ] Add empty states for audit logs, users list, and request queues
- [ ] Add pagination controls and row counts for long admin lists

### Vendor Portal
- [x] Add vendor dashboard summary cards (bookings, payouts, status)
- [x] Add vendor profile management and account settings section
- [ ] Add vendor profile management and account settings section
- [x] Add backend connectivity status indicator and last sync time
- [x] Add support/help links with contact paths and docs links

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