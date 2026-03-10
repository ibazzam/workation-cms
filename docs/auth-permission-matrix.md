# Auth Permission Matrix

This document is generated from backend controller decorators in `infra/backend/src/**/*.controller.ts`.

Generation command: `npm run permissions:matrix:write`
Validation command: `npm run permissions:matrix:check`

Total endpoint policies: **123**

Role columns: `ANONYMOUS`, `USER`, `VENDOR`, `ADMIN`, `ADMIN_SUPER`, `ADMIN_CARE`, `ADMIN_FINANCE`

| Method | Path | Access Mode | Allowed Roles | ANONYMOUS | USER | VENDOR | ADMIN | ADMIN_SUPER | ADMIN_CARE | ADMIN_FINANCE | Source |
|---|---|---|---|---|---|---|---|---|---|---|---|
| GET | /api/v1/accommodations | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/accommodations/accommodations.controller.ts |
| GET | /api/v1/accommodations/:id | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/accommodations/accommodations.controller.ts |
| GET | /api/v1/accommodations/:id/availability | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/accommodations/accommodations.controller.ts |
| GET | /api/v1/accommodations/:id/quote | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/accommodations/accommodations.controller.ts |
| POST | /api/v1/accommodations/admin | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER, VENDOR | N | N | Y | Y | Y | Y | N | infra/backend/src/accommodations/accommodations.controller.ts |
| DELETE | /api/v1/accommodations/admin/:id | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER, VENDOR | N | N | Y | Y | Y | Y | N | infra/backend/src/accommodations/accommodations.controller.ts |
| PUT | /api/v1/accommodations/admin/:id | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER, VENDOR | N | N | Y | Y | Y | Y | N | infra/backend/src/accommodations/accommodations.controller.ts |
| POST | /api/v1/accommodations/admin/:id/blackouts | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER, VENDOR | N | N | Y | Y | Y | Y | N | infra/backend/src/accommodations/accommodations.controller.ts |
| DELETE | /api/v1/accommodations/admin/:id/blackouts/:blackoutId | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER, VENDOR | N | N | Y | Y | Y | Y | N | infra/backend/src/accommodations/accommodations.controller.ts |
| POST | /api/v1/accommodations/admin/:id/seasonal-rates | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER, VENDOR | N | N | Y | Y | Y | Y | N | infra/backend/src/accommodations/accommodations.controller.ts |
| DELETE | /api/v1/accommodations/admin/:id/seasonal-rates/:rateId | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER, VENDOR | N | N | Y | Y | Y | Y | N | infra/backend/src/accommodations/accommodations.controller.ts |
| GET | /api/v1/admin/settings/commercial | roles | ADMIN, ADMIN_CARE, ADMIN_FINANCE, ADMIN_SUPER | N | N | N | Y | Y | Y | Y | infra/backend/src/admin-settings/admin-settings.controller.ts |
| POST | /api/v1/admin/settings/commercial | roles | ADMIN, ADMIN_FINANCE, ADMIN_SUPER | N | N | N | Y | Y | N | Y | infra/backend/src/admin-settings/admin-settings.controller.ts |
| GET | /api/v1/atolls | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/islands/islands.controller.ts |
| GET | /api/v1/atolls/:id | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/islands/islands.controller.ts |
| GET | /api/v1/auth/admin/ping | roles | ADMIN, ADMIN_CARE, ADMIN_FINANCE, ADMIN_SUPER | N | N | N | Y | Y | Y | Y | infra/backend/src/auth/auth.controller.ts |
| GET | /api/v1/auth/me | authenticated | - | N | Y | Y | Y | Y | Y | Y | infra/backend/src/auth/auth.controller.ts |
| GET | /api/v1/bookings | authenticated | - | N | Y | Y | Y | Y | Y | Y | infra/backend/src/bookings/bookings.controller.ts |
| POST | /api/v1/bookings | authenticated | - | N | Y | Y | Y | Y | Y | Y | infra/backend/src/bookings/bookings.controller.ts |
| PATCH | /api/v1/bookings/:id/cancel | authenticated | - | N | Y | Y | Y | Y | Y | Y | infra/backend/src/bookings/bookings.controller.ts |
| PATCH | /api/v1/bookings/:id/confirm | authenticated | - | N | Y | Y | Y | Y | Y | Y | infra/backend/src/bookings/bookings.controller.ts |
| PATCH | /api/v1/bookings/:id/hold | authenticated | - | N | Y | Y | Y | Y | Y | Y | infra/backend/src/bookings/bookings.controller.ts |
| POST | /api/v1/bookings/:id/rebook | authenticated | - | N | Y | Y | Y | Y | Y | Y | infra/backend/src/bookings/bookings.controller.ts |
| GET | /api/v1/bookings/:id/rebook/template | authenticated | - | N | Y | Y | Y | Y | Y | Y | infra/backend/src/bookings/bookings.controller.ts |
| PATCH | /api/v1/bookings/:id/refund | roles | ADMIN, ADMIN_FINANCE, ADMIN_SUPER | N | N | N | Y | Y | N | Y | infra/backend/src/bookings/bookings.controller.ts |
| POST | /api/v1/bookings/itinerary/validate | authenticated | - | N | Y | Y | Y | Y | Y | Y | infra/backend/src/bookings/bookings.controller.ts |
| GET | /api/v1/cart | authenticated | - | N | Y | Y | Y | Y | Y | Y | infra/backend/src/cart/cart.controller.ts |
| POST | /api/v1/cart/checkout | authenticated | - | N | Y | Y | Y | Y | Y | Y | infra/backend/src/cart/cart.controller.ts |
| POST | /api/v1/cart/items | authenticated | - | N | Y | Y | Y | Y | Y | Y | infra/backend/src/cart/cart.controller.ts |
| DELETE | /api/v1/cart/items/:itemId | authenticated | - | N | Y | Y | Y | Y | Y | Y | infra/backend/src/cart/cart.controller.ts |
| GET | /api/v1/countries | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/countries/countries.controller.ts |
| GET | /api/v1/countries/:id | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/countries/countries.controller.ts |
| POST | /api/v1/countries/admin | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER | N | N | N | Y | Y | Y | N | infra/backend/src/countries/countries.controller.ts |
| DELETE | /api/v1/countries/admin/:id | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER | N | N | N | Y | Y | Y | N | infra/backend/src/countries/countries.controller.ts |
| PUT | /api/v1/countries/admin/:id | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER | N | N | N | Y | Y | Y | N | infra/backend/src/countries/countries.controller.ts |
| GET | /api/v1/health | authenticated | - | N | Y | Y | Y | Y | Y | Y | infra/backend/src/health.controller.ts |
| GET | /api/v1/islands | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/islands/islands.controller.ts |
| GET | /api/v1/islands/:id | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/islands/islands.controller.ts |
| PUT | /api/v1/islands/admin/:id/metadata | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER | N | N | N | Y | Y | Y | N | infra/backend/src/islands/islands.controller.ts |
| GET | /api/v1/loyalty/me | authenticated | - | N | Y | Y | Y | Y | Y | Y | infra/backend/src/loyalty/loyalty.controller.ts |
| POST | /api/v1/loyalty/me/redeem | authenticated | - | N | Y | Y | Y | Y | Y | Y | infra/backend/src/loyalty/loyalty.controller.ts |
| GET | /api/v1/loyalty/me/transactions | authenticated | - | N | Y | Y | Y | Y | Y | Y | infra/backend/src/loyalty/loyalty.controller.ts |
| POST | /api/v1/loyalty/offers/admin | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER, VENDOR | N | N | Y | Y | Y | Y | N | infra/backend/src/loyalty/loyalty.controller.ts |
| DELETE | /api/v1/loyalty/offers/admin/:id | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER, VENDOR | N | N | Y | Y | Y | Y | N | infra/backend/src/loyalty/loyalty.controller.ts |
| PUT | /api/v1/loyalty/offers/admin/:id | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER, VENDOR | N | N | Y | Y | Y | Y | N | infra/backend/src/loyalty/loyalty.controller.ts |
| GET | /api/v1/loyalty/offers/vendors/:vendorId | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/loyalty/loyalty.controller.ts |
| GET | /api/v1/ops/alerts | roles | ADMIN, ADMIN_CARE, ADMIN_FINANCE, ADMIN_SUPER | N | N | N | Y | Y | Y | Y | infra/backend/src/observability/observability.controller.ts |
| GET | /api/v1/ops/metrics | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/observability/observability.controller.ts |
| GET | /api/v1/ops/runbooks | roles | ADMIN, ADMIN_CARE, ADMIN_FINANCE, ADMIN_SUPER | N | N | N | Y | Y | Y | Y | infra/backend/src/observability/observability.controller.ts |
| GET | /api/v1/ops/slo-summary | roles | ADMIN, ADMIN_CARE, ADMIN_FINANCE, ADMIN_SUPER | N | N | N | Y | Y | Y | Y | infra/backend/src/observability/observability.controller.ts |
| GET | /api/v1/payments/admin/alerts | roles | ADMIN, ADMIN_CARE, ADMIN_FINANCE, ADMIN_SUPER | N | N | N | Y | Y | Y | Y | infra/backend/src/payments/payments.controller.ts |
| GET | /api/v1/payments/admin/bml/health | roles | ADMIN, ADMIN_CARE, ADMIN_FINANCE, ADMIN_SUPER | N | N | N | Y | Y | Y | Y | infra/backend/src/payments/payments.controller.ts |
| GET | /api/v1/payments/admin/jobs | roles | ADMIN, ADMIN_CARE, ADMIN_FINANCE, ADMIN_SUPER | N | N | N | Y | Y | Y | Y | infra/backend/src/payments/payments.controller.ts |
| POST | /api/v1/payments/admin/jobs/:id/cancel | roles | ADMIN, ADMIN_FINANCE, ADMIN_SUPER | N | N | N | Y | Y | N | Y | infra/backend/src/payments/payments.controller.ts |
| POST | /api/v1/payments/admin/jobs/:id/complete | roles | ADMIN, ADMIN_FINANCE, ADMIN_SUPER | N | N | N | Y | Y | N | Y | infra/backend/src/payments/payments.controller.ts |
| POST | /api/v1/payments/admin/jobs/:id/requeue | roles | ADMIN, ADMIN_FINANCE, ADMIN_SUPER | N | N | N | Y | Y | N | Y | infra/backend/src/payments/payments.controller.ts |
| GET | /api/v1/payments/admin/jobs/health | roles | ADMIN, ADMIN_CARE, ADMIN_FINANCE, ADMIN_SUPER | N | N | N | Y | Y | Y | Y | infra/backend/src/payments/payments.controller.ts |
| POST | /api/v1/payments/admin/jobs/prune | roles | ADMIN, ADMIN_FINANCE, ADMIN_SUPER | N | N | N | Y | Y | N | Y | infra/backend/src/payments/payments.controller.ts |
| GET | /api/v1/payments/admin/mib/health | roles | ADMIN, ADMIN_CARE, ADMIN_FINANCE, ADMIN_SUPER | N | N | N | Y | Y | Y | Y | infra/backend/src/payments/payments.controller.ts |
| GET | /api/v1/payments/admin/reconcile/alerts | roles | ADMIN, ADMIN_CARE, ADMIN_FINANCE, ADMIN_SUPER | N | N | N | Y | Y | Y | Y | infra/backend/src/payments/payments.controller.ts |
| GET | /api/v1/payments/admin/reconcile/history | roles | ADMIN, ADMIN_CARE, ADMIN_FINANCE, ADMIN_SUPER | N | N | N | Y | Y | Y | Y | infra/backend/src/payments/payments.controller.ts |
| POST | /api/v1/payments/admin/reconcile/pending | roles | ADMIN, ADMIN_FINANCE, ADMIN_SUPER | N | N | N | Y | Y | N | Y | infra/backend/src/payments/payments.controller.ts |
| POST | /api/v1/payments/admin/reconcile/run-now | roles | ADMIN, ADMIN_FINANCE, ADMIN_SUPER | N | N | N | Y | Y | N | Y | infra/backend/src/payments/payments.controller.ts |
| GET | /api/v1/payments/admin/reconcile/status | roles | ADMIN, ADMIN_CARE, ADMIN_FINANCE, ADMIN_SUPER | N | N | N | Y | Y | Y | Y | infra/backend/src/payments/payments.controller.ts |
| POST | /api/v1/payments/admin/refunds | roles | ADMIN, ADMIN_FINANCE, ADMIN_SUPER | N | N | N | Y | Y | N | Y | infra/backend/src/payments/payments.controller.ts |
| GET | /api/v1/payments/admin/settlements/report | roles | ADMIN, ADMIN_FINANCE, ADMIN_SUPER | N | N | N | Y | Y | N | Y | infra/backend/src/payments/payments.controller.ts |
| POST | /api/v1/payments/disputes | authenticated | - | N | Y | Y | Y | Y | Y | Y | infra/backend/src/payments/payments.controller.ts |
| POST | /api/v1/payments/intents | authenticated | - | N | Y | Y | Y | Y | Y | Y | infra/backend/src/payments/payments.controller.ts |
| POST | /api/v1/payments/refunds | authenticated | - | N | Y | Y | Y | Y | Y | Y | infra/backend/src/payments/payments.controller.ts |
| POST | /api/v1/payments/webhooks/bml | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/payments/payments.controller.ts |
| POST | /api/v1/payments/webhooks/mib | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/payments/payments.controller.ts |
| POST | /api/v1/payments/webhooks/stripe | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/payments/payments.controller.ts |
| POST | /api/v1/reviews | roles | ADMIN, ADMIN_CARE, ADMIN_FINANCE, ADMIN_SUPER, USER, VENDOR | N | Y | Y | Y | Y | Y | Y | infra/backend/src/reviews/reviews.controller.ts |
| POST | /api/v1/reviews/:id/flag | roles | ADMIN, ADMIN_CARE, ADMIN_FINANCE, ADMIN_SUPER, USER, VENDOR | N | Y | Y | Y | Y | Y | Y | infra/backend/src/reviews/reviews.controller.ts |
| GET | /api/v1/reviews/accommodations/:id | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/reviews/reviews.controller.ts |
| GET | /api/v1/reviews/activities/:id | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/reviews/reviews.controller.ts |
| POST | /api/v1/reviews/admin/:id/hide | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER | N | N | N | Y | Y | Y | N | infra/backend/src/reviews/reviews.controller.ts |
| POST | /api/v1/reviews/admin/:id/publish | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER | N | N | N | Y | Y | Y | N | infra/backend/src/reviews/reviews.controller.ts |
| GET | /api/v1/reviews/admin/moderation | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER | N | N | N | Y | Y | Y | N | infra/backend/src/reviews/reviews.controller.ts |
| GET | /api/v1/reviews/services/:id | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/reviews/reviews.controller.ts |
| GET | /api/v1/reviews/transports/:id | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/reviews/reviews.controller.ts |
| GET | /api/v1/service-categories | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/service-categories/service-categories.controller.ts |
| GET | /api/v1/service-categories/:id | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/service-categories/service-categories.controller.ts |
| POST | /api/v1/service-categories/admin | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER | N | N | N | Y | Y | Y | N | infra/backend/src/service-categories/service-categories.controller.ts |
| DELETE | /api/v1/service-categories/admin/:id | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER | N | N | N | Y | Y | Y | N | infra/backend/src/service-categories/service-categories.controller.ts |
| PUT | /api/v1/service-categories/admin/:id | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER | N | N | N | Y | Y | Y | N | infra/backend/src/service-categories/service-categories.controller.ts |
| POST | /api/v1/social-links/:id/flag | roles | ADMIN, ADMIN_CARE, ADMIN_FINANCE, ADMIN_SUPER, USER, VENDOR | N | Y | Y | Y | Y | Y | Y | infra/backend/src/social-links/social-links.controller.ts |
| GET | /api/v1/social-links/accommodations/:id | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/social-links/social-links.controller.ts |
| POST | /api/v1/social-links/admin | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER, VENDOR | N | N | Y | Y | Y | Y | N | infra/backend/src/social-links/social-links.controller.ts |
| DELETE | /api/v1/social-links/admin/:id | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER, VENDOR | N | N | Y | Y | Y | Y | N | infra/backend/src/social-links/social-links.controller.ts |
| PUT | /api/v1/social-links/admin/:id | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER, VENDOR | N | N | Y | Y | Y | Y | N | infra/backend/src/social-links/social-links.controller.ts |
| POST | /api/v1/social-links/admin/:id/approve | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER | N | N | N | Y | Y | Y | N | infra/backend/src/social-links/social-links.controller.ts |
| POST | /api/v1/social-links/admin/:id/hide | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER | N | N | N | Y | Y | Y | N | infra/backend/src/social-links/social-links.controller.ts |
| GET | /api/v1/social-links/admin/moderation | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER | N | N | N | Y | Y | Y | N | infra/backend/src/social-links/social-links.controller.ts |
| GET | /api/v1/social-links/transports/:id | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/social-links/social-links.controller.ts |
| GET | /api/v1/social-links/vendors/:id | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/social-links/social-links.controller.ts |
| GET | /api/v1/taxonomy/categories | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/taxonomy/taxonomy.controller.ts |
| GET | /api/v1/transports | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/transports/transports.controller.ts |
| GET | /api/v1/transports/:id | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/transports/transports.controller.ts |
| GET | /api/v1/transports/:id/fare-classes | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/transports/transports.controller.ts |
| GET | /api/v1/transports/:id/quote | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/transports/transports.controller.ts |
| POST | /api/v1/transports/admin | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER, VENDOR | N | N | Y | Y | Y | Y | N | infra/backend/src/transports/transports.controller.ts |
| DELETE | /api/v1/transports/admin/:id | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER, VENDOR | N | N | Y | Y | Y | Y | N | infra/backend/src/transports/transports.controller.ts |
| PUT | /api/v1/transports/admin/:id | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER, VENDOR | N | N | Y | Y | Y | Y | N | infra/backend/src/transports/transports.controller.ts |
| POST | /api/v1/transports/admin/:id/disruptions | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER, VENDOR | N | N | Y | Y | Y | Y | N | infra/backend/src/transports/transports.controller.ts |
| POST | /api/v1/transports/admin/:id/disruptions/:disruptionId/reaccommodate | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER, VENDOR | N | N | Y | Y | Y | Y | N | infra/backend/src/transports/transports.controller.ts |
| PATCH | /api/v1/transports/admin/:id/disruptions/:disruptionId/resolve | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER, VENDOR | N | N | Y | Y | Y | Y | N | infra/backend/src/transports/transports.controller.ts |
| GET | /api/v1/transports/flights/schedule | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/transports/transports.controller.ts |
| GET | /api/v1/transports/schedule | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/transports/transports.controller.ts |
| GET | /api/v1/users/me/profile | authenticated | - | N | Y | Y | Y | Y | Y | Y | infra/backend/src/users/users.controller.ts |
| PUT | /api/v1/users/me/profile | authenticated | - | N | Y | Y | Y | Y | Y | Y | infra/backend/src/users/users.controller.ts |
| GET | /api/v1/vendors | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/vendors/vendors.controller.ts |
| GET | /api/v1/vendors/:id | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/vendors/vendors.controller.ts |
| POST | /api/v1/vendors/admin | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER | N | N | N | Y | Y | Y | N | infra/backend/src/vendors/vendors.controller.ts |
| DELETE | /api/v1/vendors/admin/:id | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER | N | N | N | Y | Y | Y | N | infra/backend/src/vendors/vendors.controller.ts |
| PUT | /api/v1/vendors/admin/:id | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER, VENDOR | N | N | Y | Y | Y | Y | N | infra/backend/src/vendors/vendors.controller.ts |
| GET | /api/v1/vendors/me | roles | VENDOR | N | N | Y | N | N | N | N | infra/backend/src/vendors/vendors.controller.ts |
| PUT | /api/v1/vendors/me | roles | VENDOR | N | N | Y | N | N | N | N | infra/backend/src/vendors/vendors.controller.ts |
| GET | /api/v1/workations | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/workations/workations.controller.ts |
| POST | /api/v1/workations | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER | N | N | N | Y | Y | Y | N | infra/backend/src/workations/workations.controller.ts |
| DELETE | /api/v1/workations/:id | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER | N | N | N | Y | Y | Y | N | infra/backend/src/workations/workations.controller.ts |
| GET | /api/v1/workations/:id | public | - | Y | Y | Y | Y | Y | Y | Y | infra/backend/src/workations/workations.controller.ts |
| PUT | /api/v1/workations/:id | roles | ADMIN, ADMIN_CARE, ADMIN_SUPER | N | N | N | Y | Y | Y | N | infra/backend/src/workations/workations.controller.ts |

