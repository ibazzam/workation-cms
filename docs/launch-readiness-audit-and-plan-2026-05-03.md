# Launch Readiness Audit and Late-May Plan (2026-05-03)

## Scope
This document consolidates current completion status from:
- docs/portal-ui-roadmap-todo.md
- docs/non-accommodation-service-amendment-checklist.md

And validates against implemented routes/views/tests where possible.

## Git vs Local Comparison Status (Updated from local path)
Local repository used:
- D:/OneDrive/Apps/workation-cms

Observed git state:
- Current branch: feat/vendor-listings-wizard-flow-20260324
- Tracking branch status: upstream is gone on remote (origin/feat/vendor-listings-wizard-flow-20260324: gone)
- Local working tree changes: 4
	- Modified: resources/views/vendor-portal/partials/category-operations.blade.php
	- Modified: resources/views/vendor-portal/partials/portal-styles.blade.php
	- Deleted: storage/app/public/.gitignore
	- Untracked: tmp/customer_restore.php

Diff summary for tracked local changes:
- 3 tracked files changed, 5 insertions, 3 deletions

Branch divergence against origin/main (informational):
- Ahead: 766 commits
- Behind: 4 commits

Interpretation:
- The branch should not be considered release-track safe until upstream branch strategy is corrected (restore tracking branch or rebase/merge onto an active remote branch).
- Local uncommitted changes are small and focused, but include a storage .gitignore deletion that should be reviewed before any release cut.

## Snapshot Summary (2026-05-03)

### 1) Portal Roadmap Progress
- Checked items: 49
- Unchecked items: 157
- Approx completion by checkbox count: 23.8%

Note: This includes many QA evidence tasks that are naturally pending even when core implementation exists.

### 2) Non-Accommodation Amendment Checklist Progress
- Checked items: 0
- Unchecked items: 52
- Completion by checkbox count: 0%

Important: Code evidence suggests parts of this are already implemented, but not yet validated and marked complete.

## Code Evidence (Implemented but Not Fully Signed-Off)

### A. Non-accommodation booking flows exist
- Category booking route and validation logic exist for all target categories.
- Route: routes/web/booking.php (category booking GET/POST)

### B. Category-specific fields are already wired
Examples from booking/category flow:
- Marine/Land transport origin and destination
- Vehicle rental pickup/drop-off
- Conference room attendees/event type
- Category details persisted into reservation notes

### C. Checkout and payment handoff exist
- Checkout route and payment intent route are present.
- Gateway quote/routing and provider handling are present.
- Tests exist for CheckoutPaymentRouter and booking flow scenarios.

### D. Customer portal and auth are present
- Customer portal route exists with booking aggregation and category grouping.
- Customer auth and social routes are present (register/login/oauth/verify/reset patterns).

## Key Gap Detected (Critical for Non-Accommodation Checklist)
A dedicated transfer selection step is still part of the checkout flow path:
- /booking/checkout/{reservation}/transfer route exists and is used in redirect flow.

This conflicts with checklist requirements that some non-accommodation categories must not show a separate transfer step.

## Launch Risk Assessment

### P0 Risks (Must close before launch)
1. Production sign-off evidence still missing for:
- Facebook social login production smoke
- WhatsApp OTP production smoke and fallback verification

2. Non-accommodation checklist remains unverified (0/52 checked)
- Especially transfer-step behavior vs expected per category
- Payment handoff validation by category

3. Frontend/customer end-to-end QA matrix not yet executed
- Route matrix, checkout accuracy, and release sign-off artifacts pending

### P1 Risks (Should close before launch)
1. Admin UX consistency tasks (modals/empty states/pagination)
2. Landing conversion and accessibility/lighthouse improvements
3. Shared UI consistency cleanup and error/loading state standardization

## Late-May Launch Plan (Target: 2026-05-29)

## Week 1 (May 4-10): P0 Validation Baseline
1. Execute Route Matrix Task 1 from portal roadmap.
2. Validate all non-accommodation category booking forms end-to-end.
3. Decide and implement transfer-step policy by category:
- remove separate step where not allowed
- keep inline transfer details where required
4. Produce first defect backlog (P0/P1/P2) with owners.

Exit criteria:
- Route matrix v1 complete
- Non-accommodation flow pass/fail matrix v1 complete
- Transfer-step policy finalized and reflected in code

## Week 2 (May 11-17): Payment and Auth Hardening
1. Run booking + checkout readiness runbook in production-like validation.
2. Complete Facebook production sign-off evidence and mark roadmap done.
3. Complete WhatsApp OTP smoke evidence and mark roadmap done.
4. Validate payment handoff for all non-accommodation categories.

Exit criteria:
- Payment matrix completed (Stripe/BML/MIB where enabled)
- Social + OTP production evidence attached
- P0 auth/payment blockers closed

## Week 3 (May 18-24): Full Regression and UX Polish
1. Execute customer portal QA tasks 7-11 from roadmap sequence.
2. Close responsive/accessibility blockers for booking-critical pages.
3. Fix top conversion issues on homepage and key category pages.
4. Produce screenshot evidence pack for all critical journeys.

Exit criteria:
- No open P0 defects
- P1 reduced to accepted residuals only
- Regression evidence pack compiled

## Week 4 (May 25-29): Release Gate and Launch
1. Final regression pass after fixes.
2. Freeze scope to launch-critical only.
3. Run go/no-go review with product, engineering, ops.
4. Publish launch runbook and rollback triggers.

Exit criteria:
- Go/No-Go signed
- Release notes published
- Monitoring and incident ownership confirmed

## Immediate Next 5 Actions (Start Today)
1. Build and commit Route Matrix v1 artifact (home, catalog, property, room, category booking, checkout, customer portal).
	- Draft created: docs/route-matrix-v1-2026-05-03.md
2. Build non-accommodation pass/fail matrix for all 8 categories.
	- Draft created: docs/non-accommodation-validation-matrix-v1-2026-05-03.md
3. Patch flow so non-accommodation categories that should skip separate transfer step bypass /transfer page.
4. Execute payment handoff test per category and record evidence.
5. Update both checklists with verified [x] and [~] states from evidence only.

## New Execution Artifacts Created
- docs/route-matrix-v1-2026-05-03.md
- docs/non-accommodation-validation-matrix-v1-2026-05-03.md

## Ownership Model
- Product: acceptance criteria, go/no-go
- Backend: booking, checkout, payment, transfer rules
- Frontend: route integrity, UX consistency, accessibility
- QA: evidence matrix, regression logs
- Ops: production verification, monitoring, rollback

## Launch Recommendation (Current)
Status: Not launch-ready yet.

Reason: Implementation depth is strong in core flows, but verification/sign-off gap is still large and key P0 evidence is incomplete.

With strict execution of the weekly plan above, a late-May launch remains achievable.
