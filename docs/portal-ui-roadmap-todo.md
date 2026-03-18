# Portal UI Product Roadmap & To-Do List

## General & Landing Page
- [ ] Improve responsiveness and accessibility for all portals and landing page
- [x] Display error and success messages for actions (UI feedback, error box, success box)
- [x] Enhance visual styling and theme consistency (fonts, colors, spacing)
- [ ] Add animated transitions for page loads and actions
- [ ] Optimize welcome page content and CTAs for conversion

## Admin Portal
- [x] Enhance user moderation UI: vertical list, inline edit, delete, create user, status/role display
- [x] Add confirmation dialogs for critical actions (delete user confirmation)
- [x] Client-side validation for moderation form
- [x] Role and status display for users (role-pill, state badge)
- [x] Responsive layout for moderation panel and forms
- [x] Add loading indicators and error states for API actions (run buttons now show loading/disabled state, plus existing success/error feedback)
- [x] Improve token input UX (validation, feedback, expiry warning)
- [x] Enhance user moderation UI: add search, filter, and bulk actions
- [ ] Add role management and permission display for users (role shown, but no full management UI)
- [x] Provide audit logs and activity history for admin actions
- [x] Improve navigation (breadcrumbs, sidebar, or tabs)
- [ ] Add dashboard widgets (metrics, alerts, system health)

## Vendor Portal
- [ ] Add vendor-specific dashboard (bookings, payments, analytics)
- [ ] Improve authentication flow and error handling
- [ ] Add vendor profile management and settings
- [ ] Provide notifications and status indicators for backend connectivity
- [ ] Add support/help links and documentation access

## Customer Portal
- [ ] Implement customer-facing portal (if not present): booking, profile, support
- [ ] Add login, registration, and password reset flows with error handling
- [ ] Improve UI for booking management, payment history, and feedback
- [ ] Add customer notifications and messaging

## Styling & Components
- [ ] Refactor inline styles to reusable CSS classes or Tailwind (some custom CSS, but not fully refactored)
- [ ] Unify button, input, card, and modal components across portals (partially unified, not fully componentized)
- [ ] Add dark mode support
- [x] Improve mobile and tablet layouts

## Error Handling
- [ ] Add global error boundary for React/Vue/JS components
- [ ] Display clear error messages for failed API calls
- [ ] Log errors for admin review

## New Features
- [ ] Add multi-language support
- [ ] Implement user activity tracking and analytics
- [ ] Add portal-specific onboarding guides and tooltips

---

Review, update, and check off tasks as completed. Adjust priorities as needed for your product vision.