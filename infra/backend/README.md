# Workation Backend

This folder contains the NestJS + Prisma backend. It now exposes API routes under `/api/v1`.

Prerequisites:

- PostgreSQL running locally (or via `infra/docker-compose.yml`)
- `infra/backend/.env` with `DATABASE_URL`

From `infra/backend` use these Windows PowerShell–safe commands:

```powershell
cd .\infra\backend
npm.cmd install
npx.cmd prisma migrate dev --schema=..\prisma\schema.prisma
npx.cmd prisma generate --schema=..\prisma\schema.prisma
npm.cmd run start:dev
```

Use `npm.cmd`/`npx.cmd` to avoid PowerShell script-execution policy issues.

Cloudflare integration (recommended for staging/prod):

- Enable reverse-proxy trust in backend with `APP_TRUST_PROXY=true` (or set proxy hop count, e.g. `1`/`2`).
- Set strict browser origin allowlist with `CORS_ORIGIN=https://app.example.com,https://admin.example.com`.
- Keep API behind Cloudflare proxy (`orange cloud`) and use Full (strict) TLS mode.
- Apply cache bypass rule for `/api/*` and challenge/rate-limit rules for write-heavy endpoints.
- Use `docs/cloudflare-integration.md` for full DNS, SSL, cache, WAF, and rollout checklist.

Test commands:

- `npm.cmd run test:quality:all` — CI-style full quality gate (`e2e journey + contract matrix`).
- `npm.cmd run test:contract:all` — full API contract matrix.
- `npm.cmd run test:e2e:journey` — end-to-end user journey (`search -> book -> pay -> manage`).

Authentication modes (protected routes):

- Preferred: `Authorization: Bearer <jwt>` with HS256 signature using `AUTH_JWT_SECRET`.
- Backward-compatible fallback (for local/contracts): `x-user-id`, `x-user-role`, `x-user-email` headers.
- Optional tenant scope header for vendor-bound write access: `x-vendor-id`.
- Toggle fallback via `AUTH_ALLOW_HEADER_FALLBACK` (`false` by default; set `true` only for temporary local legacy testing).

Domain rollout feature flags:

- Global default: `FEATURE_FLAGS_DEFAULT` (defaults to enabled when unset).
- Per-domain toggles (all default to enabled if unset):
	- `FEATURE_DOMAIN_WORKATIONS`
	- `FEATURE_DOMAIN_COUNTRIES`
	- `FEATURE_DOMAIN_ISLANDS`
	- `FEATURE_DOMAIN_SERVICE_CATEGORIES`
	- `FEATURE_DOMAIN_VENDORS`
	- `FEATURE_DOMAIN_ACCOMMODATIONS`
	- `FEATURE_DOMAIN_TRANSPORTS`
	- `FEATURE_DOMAIN_BOOKINGS`
	- `FEATURE_DOMAIN_PAYMENTS`
	- `FEATURE_DOMAIN_ADMIN_SETTINGS`
- Disabled domains return HTTP `503` (`Service Unavailable`) for their routes.

Admin roles (tiered access):

- `ADMIN` and `ADMIN_SUPER`: full admin access.
- `ADMIN_FINANCE`: billing/accounting/finance actions and visibility.
- `ADMIN_CARE`: customer/vendor care + vendor troubleshooting, including property/workation management.

Role-to-endpoint matrix:

| Endpoint area | ADMIN / ADMIN_SUPER | ADMIN_FINANCE | ADMIN_CARE |
|---|---|---|---|
| `GET /payments/admin/bml/health`, `GET /payments/admin/mib/health` | ✅ | ✅ | ✅ |
| `GET /payments/admin/reconcile/status`, `/history`, `/alerts` | ✅ | ✅ | ✅ |
| `POST /payments/admin/reconcile/pending`, `/run-now` | ✅ | ✅ | ❌ |
| `GET /payments/admin/jobs/health`, `GET /payments/admin/jobs` | ✅ | ✅ | ✅ |
| `POST /payments/admin/jobs/prune`, `POST /payments/admin/jobs/:id/requeue`, `:id/cancel`, `:id/complete` | ✅ | ✅ | ❌ |
| `POST /workations`, `PUT /workations/:id`, `DELETE /workations/:id` | ✅ | ❌ | ✅ |
| `GET /auth/admin/ping` | ✅ | ✅ | ✅ |

For a non-technical staff version, see [docs/admin-access-guide.md](docs/admin-access-guide.md).
For access approvals, use [docs/admin-role-request-template.md](docs/admin-role-request-template.md).
For quick chat/email approvals, use the template's short format in [docs/admin-role-request-template.md](docs/admin-role-request-template.md).

Automated admin role review report:

- Create `docs/admin-role-assignments.json` from [docs/admin-role-assignments.example.json](docs/admin-role-assignments.example.json).
- Run `npm run admin:review:roles` to print current admin users, counts by role, and review issues.
- Run `npm run admin:review:roles:strict` to fail with exit code `2` when issues are detected.
- Optional env: `ADMIN_ROLE_METADATA_PATH` to point to a custom metadata JSON file.
- Optional env: `ADMIN_ROLE_REVIEW_INCLUDE_TEST_USERS=true` to include synthetic test users in the report.
- Issues include: missing metadata, role mismatch, missing/expired temporary end date, overdue review date, and metadata-only entries.

Current implemented endpoints:

- `GET /api/v1/health`
- `GET /api/v1/admin/settings/commercial` (`ADMIN|ADMIN_SUPER|ADMIN_CARE|ADMIN_FINANCE`)
	- Returns commercial configuration: currency/base currency, exchange rates, loyalty program settings.
- `POST /api/v1/admin/settings/commercial` (`ADMIN|ADMIN_SUPER|ADMIN_FINANCE`)
	- Updates commercial configuration payload for currency, exchange rates, and loyalty rules.
- `GET /api/v1/workations`
- `GET /api/v1/workations/:id`
- `POST /api/v1/workations` (`ADMIN|ADMIN_SUPER|ADMIN_CARE`)
- `PUT /api/v1/workations/:id` (`ADMIN|ADMIN_SUPER|ADMIN_CARE`)
- `DELETE /api/v1/workations/:id` (`ADMIN|ADMIN_SUPER|ADMIN_CARE`)
- `POST /api/v1/payments/intents` (providers: `STRIPE`, `BML`, `MIB`; currencies: `USD`, `MVR`)
- `POST /api/v1/vendors/admin` (`ADMIN|ADMIN_SUPER|ADMIN_CARE`)
- `GET /api/v1/vendors/me` (`VENDOR`)
- `PUT /api/v1/vendors/me` (`VENDOR`)
- `PUT /api/v1/vendors/admin/:id` (`ADMIN|ADMIN_SUPER|ADMIN_CARE|VENDOR`)
- `DELETE /api/v1/vendors/admin/:id` (`ADMIN|ADMIN_SUPER|ADMIN_CARE`)
- `POST /api/v1/accommodations/admin` (`ADMIN|ADMIN_SUPER|ADMIN_CARE|VENDOR`)
- `PUT /api/v1/accommodations/admin/:id` (`ADMIN|ADMIN_SUPER|ADMIN_CARE|VENDOR`)
- `DELETE /api/v1/accommodations/admin/:id` (`ADMIN|ADMIN_SUPER|ADMIN_CARE|VENDOR`)
- `GET /api/v1/accommodations/:id/availability` (public)
	- Query params: `startDate`, `endDate`, optional `roomsRequested`.
	- Returns availability decision with blackout/min-stay/inventory blockers.
- `GET /api/v1/accommodations/:id/quote` (public)
	- Query params: `startDate`, `endDate`, optional `roomsRequested`.
	- Returns nightly pricing quote with seasonal/base rate breakdown and total amount.
- `POST /api/v1/accommodations/admin/:id/blackouts` (`ADMIN|ADMIN_SUPER|ADMIN_CARE|VENDOR`)
- `DELETE /api/v1/accommodations/admin/:id/blackouts/:blackoutId` (`ADMIN|ADMIN_SUPER|ADMIN_CARE|VENDOR`)
- `POST /api/v1/accommodations/admin/:id/seasonal-rates` (`ADMIN|ADMIN_SUPER|ADMIN_CARE|VENDOR`)
- `DELETE /api/v1/accommodations/admin/:id/seasonal-rates/:rateId` (`ADMIN|ADMIN_SUPER|ADMIN_CARE|VENDOR`)
- `POST /api/v1/transports/admin` (`ADMIN|ADMIN_SUPER|ADMIN_CARE|VENDOR`)
- `PUT /api/v1/transports/admin/:id` (`ADMIN|ADMIN_SUPER|ADMIN_CARE|VENDOR`)
- `DELETE /api/v1/transports/admin/:id` (`ADMIN|ADMIN_SUPER|ADMIN_CARE|VENDOR`)
- `GET /api/v1/transports/schedule` (public)
	- Query params: `date` (required, `YYYY-MM-DD`), optional `fromIslandId`, `toIslandId`, `type`.
	- Returns date-filtered transport schedules with seat inventory fields (`capacity`, `reservedSeats`, `availableSeats`, `soldOut`).
- `GET /api/v1/transports/flights/schedule` (public)
	- Query params: `date` (required, `YYYY-MM-DD`), optional `fromIslandId`, `toIslandId`.
	- Returns `DOMESTIC_FLIGHT` schedules with both transport seat inventory and fare-class seat inventory.
- `GET /api/v1/transports/:id/fare-classes` (public)
	- Returns fare classes for a transport with per-class seat inventory (`capacity`, `reservedSeats`, `availableSeats`, `soldOut`).
- `POST /api/v1/transports/admin/:id/disruptions` (`ADMIN|ADMIN_SUPER|ADMIN_CARE|VENDOR`)
	- Creates an active disruption (`DELAYED|CANCELLED|WEATHER_CANCELLED`) for a transport with optional replacement transport.
- `PATCH /api/v1/transports/admin/:id/disruptions/:disruptionId/resolve` (`ADMIN|ADMIN_SUPER|ADMIN_CARE|VENDOR`)
	- Resolves an active disruption.
- `POST /api/v1/transports/admin/:id/disruptions/:disruptionId/reaccommodate` (`ADMIN|ADMIN_SUPER|ADMIN_CARE|VENDOR`)
	- Attempts to move `PENDING|HOLD|CONFIRMED` bookings to the configured replacement transport, respecting capacity and fare-class inventory.
- `GET /api/v1/bookings` (authenticated user)
	- Returns current user's bookings with accommodation/transport/payment context and management eligibility metadata.
- `POST /api/v1/bookings` (authenticated user)
	- Creates booking in `HOLD` state with `holdExpiresAt` based on `BOOKING_HOLD_MINUTES` (default `30`).
- `PATCH /api/v1/bookings/:id/hold` (authenticated booking owner)
	- Moves booking to `HOLD` and refreshes hold expiry.
- `PATCH /api/v1/bookings/:id/confirm` (authenticated booking owner)
	- Confirms valid `HOLD`/legacy `PENDING` booking; expired holds are auto-cancelled.
- `PATCH /api/v1/bookings/:id/cancel` (authenticated booking owner)
	- Cancels booking when current lifecycle state allows transition.
- `POST /api/v1/bookings/:id/rebook` (authenticated booking owner)
	- Cancels original `HOLD|PENDING|CONFIRMED` booking and creates replacement booking from merged original+override payload.
- `GET /api/v1/bookings/:id/rebook/template` (authenticated booking owner)
	- Returns owner-scoped rebook defaults and current eligibility for rebook action.
- `PATCH /api/v1/bookings/:id/refund` (`ADMIN|ADMIN_SUPER|ADMIN_FINANCE`)
	- Marks eligible cancelled/confirmed booking as `REFUNDED`.
- `POST /api/v1/bookings/itinerary/validate` (authenticated user)
	- Validates multi-leg arrival+transfer itinerary alignment against accommodation island and start date.
- `GET /api/v1/cart` (authenticated user)
	- Returns current user's cart with in-progress item list.
- `POST /api/v1/cart/items` (authenticated user)
	- Adds `TRANSPORT` or `ACCOMMODATION` item to current user's cart.
- `DELETE /api/v1/cart/items/:itemId` (authenticated user)
	- Removes a cart item by id.
- `POST /api/v1/cart/checkout` (authenticated user)
	- Creates bookings for all cart items and clears cart on success.
- `GET /api/v1/countries`
- `POST /api/v1/countries/admin` (`ADMIN|ADMIN_SUPER|ADMIN_CARE`)
- `PUT /api/v1/countries/admin/:id` (`ADMIN|ADMIN_SUPER|ADMIN_CARE`)
- `DELETE /api/v1/countries/admin/:id` (`ADMIN|ADMIN_SUPER|ADMIN_CARE`)
- `GET /api/v1/service-categories`
- `POST /api/v1/service-categories/admin` (`ADMIN|ADMIN_SUPER|ADMIN_CARE`)
- `PUT /api/v1/service-categories/admin/:id` (`ADMIN|ADMIN_SUPER|ADMIN_CARE`)
- `DELETE /api/v1/service-categories/admin/:id` (`ADMIN|ADMIN_SUPER|ADMIN_CARE`)
- `POST /api/v1/payments/webhooks/stripe`
- `POST /api/v1/payments/webhooks/bml`
- `POST /api/v1/payments/webhooks/mib`
- `GET /api/v1/payments/admin/bml/health` (`ADMIN|ADMIN_SUPER|ADMIN_CARE|ADMIN_FINANCE`)
	- Includes `responseTimeMs` for provider latency visibility.
- `GET /api/v1/payments/admin/mib/health` (`ADMIN|ADMIN_SUPER|ADMIN_CARE|ADMIN_FINANCE`)
	- Includes `responseTimeMs` for provider latency visibility.
- `POST /api/v1/payments/admin/reconcile/pending` (`ADMIN|ADMIN_SUPER|ADMIN_FINANCE`)
	- Re-checks pending payments against provider source-of-truth status.
	- Optional payload: `provider` (`BML|MIB|STRIPE`), `limit` (`1..500`), `dryRun` (`true|false`).
- `GET /api/v1/payments/admin/reconcile/status` (`ADMIN|ADMIN_SUPER|ADMIN_CARE|ADMIN_FINANCE`)
	- Returns scheduler config + runtime snapshot (`enabled`, `running`, last run timestamps/outcome, latest summary/error).
- `GET /api/v1/payments/admin/reconcile/history?limit=20` (`ADMIN|ADMIN_SUPER|ADMIN_CARE|ADMIN_FINANCE`)
	- Returns persisted recent reconciliation runs (manual + scheduler) with counters, timing, source, and status.
- `POST /api/v1/payments/admin/reconcile/run-now` (`ADMIN|ADMIN_SUPER|ADMIN_FINANCE`)
	- Triggers an immediate reconciliation run using scheduler defaults, with optional `provider|limit|dryRun` overrides.
	- Returns `409` if another reconciliation run is already in progress.
- `GET /api/v1/payments/admin/reconcile/alerts` (`ADMIN|ADMIN_SUPER|ADMIN_CARE|ADMIN_FINANCE`)
	- Returns reconciliation alert status (`staleSuccess`, `errorStreak`, `highErrorsLastRun`) and active alert keys.
- `GET /api/v1/payments/admin/alerts` (`ADMIN|ADMIN_SUPER|ADMIN_CARE|ADMIN_FINANCE`)
	- Unified alert dispatcher for ops: combines reconciliation alerts and jobs runner/queue alerts with active alert messages.
- `GET /api/v1/payments/admin/jobs/health` (`ADMIN|ADMIN_SUPER|ADMIN_FINANCE|ADMIN_CARE`)
	- Returns background queue counts (`PENDING|RETRYABLE|RUNNING|COMPLETED|DEAD`), health metrics (`oldestPendingAgeMs`, `retrySuccessRate`, `deadLetterRate`), next due timestamp, recent failures, and runner runtime snapshot.
- `GET /api/v1/payments/admin/jobs` (`ADMIN|ADMIN_SUPER|ADMIN_FINANCE|ADMIN_CARE`)
	- Lists background jobs with filters: `status`, `type`, `limit`, `offset`.
- `POST /api/v1/payments/admin/jobs/prune` (`ADMIN|ADMIN_SUPER|ADMIN_FINANCE`)
	- Deletes old completed background jobs using payload options: `olderThanHours` (default `168`) and `limit` (default `200`).
- `POST /api/v1/payments/admin/jobs/:id/requeue` (`ADMIN|ADMIN_SUPER|ADMIN_FINANCE`)
	- Requeues a non-running job; optional payload `delaySeconds` (`0..3600`).
- `POST /api/v1/payments/admin/jobs/:id/cancel` (`ADMIN|ADMIN_SUPER|ADMIN_FINANCE`)
	- Cancels a non-running, non-completed job.
- `POST /api/v1/payments/admin/jobs/:id/complete` (`ADMIN|ADMIN_SUPER|ADMIN_FINANCE`)
	- Marks a non-running job as completed.

Vendor-scoped authorization rules:

- `VENDOR` role writes are tenant-scoped for accommodation and transport admin endpoints.
- `VENDOR` can read/update own profile via `/vendors/me` and can update `/vendors/admin/:id` only for own vendor record.
- Scope is resolved from JWT `vendorId` claim or fallback `x-vendor-id` header.
- Cross-vendor writes are rejected with `403`.

Accommodation availability rules:

- Accommodations support `minStayNights` policy and date blackout windows.
- Booking flow enforces min-stay and blackout checks for accommodation bookings.
- Inventory is calculated from room count versus overlapping `PENDING|HOLD|CONFIRMED` bookings.

Accommodation pricing rules:

- Accommodations support seasonal nightly rates with date windows, optional minimum stay, and priority ordering.
- Quote endpoint returns nightly breakdown (`BASE`/`SEASONAL`) and total nightly amount for selected stay dates.
- Booking creation uses the same seasonal pricing logic to compute accommodation total price.

Transport seat inventory rules:

- Schedule/list responses include per-transport seat inventory summary.
- Booking creation decrements available transport seats using existing `PENDING|HOLD|CONFIRMED` booking guests.
- Overbook requests are rejected with `400`.

Domestic flight fare class rules:

- `DOMESTIC_FLIGHT` transports can define `fareClasses` with `code`, `name`, `baggageKg`, `seats`, and `price`.
- Booking creation requires `transportFareClassCode` when the selected domestic flight has configured fare classes.
- Fare-class seat inventory is enforced from existing `PENDING|HOLD|CONFIRMED` bookings per class.

Transport disruption rules:

- Schedule and transport detail responses include `activeDisruption` when an unresolved disruption exists.
- Booking creation rejects transports with unresolved `CANCELLED` or `WEATHER_CANCELLED` disruptions.
- Re-accommodation enforces replacement transport seat/fare-class inventory and returns moved/skipped breakdown.

Booking lifecycle rules:

- Lifecycle statuses supported: `DRAFT`, `PENDING` (legacy), `HOLD`, `CONFIRMED`, `CANCELLED`, `REFUNDED`.
- Allowed transitions: `DRAFT -> HOLD/CANCELLED`, `PENDING -> HOLD/CONFIRMED/CANCELLED`, `HOLD -> CONFIRMED/CANCELLED`, `CONFIRMED -> CANCELLED/REFUNDED`, `CANCELLED -> REFUNDED`.
- `HOLD` expiry is stored in `holdExpiresAt`; default expiry uses `BOOKING_HOLD_MINUTES` (falls back to `30`).

Itinerary dependency rules:

- Booking supports optional `itineraryTransportIds` list; when provided, the final leg is used as booking `transportId`.
- Legs must form a continuous chain (`previous.toIslandId === next.fromIslandId`) with non-overlapping chronology.
- Final leg destination must match accommodation island and final arrival must be on/before booking `startDate`.

Cart/checkout rules:

- Cart is user-scoped and persisted in app config under per-user keys.
- Cart supports mixed service bundles (`TRANSPORT` + `ACCOMMODATION`) in one checkout flow.
- Accommodation cart items may reference a transport item via `relatedTransportItemId` for dependency-safe bundled checkout.
- Checkout creates one booking per cart item using existing booking dependency/lifecycle validations.

Booking rebook/change rules:

- Rebook is owner-scoped and only allowed from `HOLD`, `PENDING`, or `CONFIRMED` source bookings.
- Original booking is moved to `CANCELLED` before replacement availability checks to avoid self-inventory collisions.
- If replacement creation fails, original booking lifecycle status is restored.

Booking management hooks:

- `GET /bookings` includes `management` flags (`canConfirm`, `canCancel`, `canRebook`, `canRefund`, `holdExpired`) per booking.
- Rebook template endpoint returns merged field defaults for UI payload shaping.

Admin write audit logging:

- Admin roles (`ADMIN|ADMIN_SUPER|ADMIN_CARE|ADMIN_FINANCE`) are audited on `POST|PUT|PATCH|DELETE` routes.
- Logs are persisted in `AdminAuditLog` with actor, route, method, status code, and success/error outcome.

Automatic reconciliation scheduler:

- Disabled by default; enable with `PAYMENTS_RECONCILE_ENABLED=true`.
- Tick interval: `PAYMENTS_RECONCILE_INTERVAL_MS` (default `300000`).
- Initial delay: `PAYMENTS_RECONCILE_INITIAL_DELAY_MS` (default `5000`).
- Optional provider filter: `PAYMENTS_RECONCILE_PROVIDER` (`BML|MIB|STRIPE`, empty means all).
- Batch size: `PAYMENTS_RECONCILE_LIMIT` (default `100`, max `500`).
- Dry-run mode: `PAYMENTS_RECONCILE_DRY_RUN=true` to observe summary without DB writes.
- Alert stale multiplier: `PAYMENTS_RECONCILE_ALERT_STALE_MULTIPLIER` (default `2`).
- Alert error streak threshold: `PAYMENTS_RECONCILE_ALERT_ERROR_STREAK` (default `3`).
- Alert last-run errors threshold: `PAYMENTS_RECONCILE_ALERT_ERRORS_THRESHOLD` (default `5`).

Operational alerts thresholds:

- Jobs pending age threshold: `PAYMENTS_JOBS_ALERT_PENDING_AGE_MS` (default `900000`).
- Jobs dead-count threshold: `PAYMENTS_JOBS_ALERT_DEAD_COUNT` (default `5`).
- Jobs dead-letter-rate threshold: `PAYMENTS_JOBS_ALERT_DEAD_LETTER_RATE` (default `0.2`).
- Jobs runner stalled threshold: `PAYMENTS_JOBS_ALERT_STALLED_TICK_MS` (default `max(interval*3, 30000)`).

Automatic background jobs runner:

- Enabled by default; disable with `PAYMENTS_JOBS_ENABLED=false`.
- Tick interval: `PAYMENTS_JOBS_INTERVAL_MS` (default `10000`).
- Initial delay: `PAYMENTS_JOBS_INITIAL_DELAY_MS` (default `3000`).
- Batch size per tick: `PAYMENTS_JOBS_BATCH_SIZE` (default `25`, max `100`).
- Auto-prune toggle: `PAYMENTS_JOBS_AUTO_PRUNE_ENABLED` (default `true`).
- Completed-job retention: `PAYMENTS_JOBS_RETENTION_HOURS` (default `168`, max `8760`).
- Max prune deletes per tick: `PAYMENTS_JOBS_PRUNE_LIMIT` (default `200`, max `2000`).
- Booking confirmation notification jobs are deduplicated per booking to prevent duplicate sends.
- Webhook retry jobs are deduplicated per provider event ID to prevent duplicate retry queue entries.

Payment webhook secrets (optional, defaults exist for local contracts):

- `STRIPE_WEBHOOK_SECRET` (default: `dev-webhook-secret`)
- `BML_WEBHOOK_SECRET` (default: `dev-bml-webhook-secret`)
- `MIB_WEBHOOK_SECRET` (default: `dev-mib-webhook-secret`)

BML Connect details confirmed from public docs:

- UAT API base URL: `https://api.uat.merchants.bankofmaldives.com.mv`
- Production API base URL: `https://api.merchants.bankofmaldives.com.mv`
- Auth header: `Authorization: <BML_API_KEY>`
- Webhook method/header basics: `POST` with `X-Originator: PomeloPay-Webhooks`
- Webhook signature headers: `X-Signature-Nonce`, `X-Signature-Timestamp`, `X-Signature`
- Signature algorithm: `sha256(nonce + timestamp + BML_API_KEY)` (hex)

BML stable processing behavior implemented:

- Incoming BML webhook signatures are verified with the documented headers and SHA-256 scheme.
- On BML webhook events, backend attempts transaction source-of-truth re-query before final status update.
- Re-query endpoint is configured by `BML_API_BASE_URL` + `BML_API_KEY`.

BML live transaction creation mode:

- Set `BML_CONNECT_LIVE_INTENTS=true` to create BML transactions via `POST /public/v2/transactions`.
- Required env vars: `BML_API_BASE_URL`, `BML_API_KEY`.
- Recommended env vars: `BML_WEBHOOK_URL`, `BML_REDIRECT_URL`.
- Optional override: `BML_FORCE_CURRENCY` to pin one currency; otherwise currency comes from each payment intent request.
- If live request fails or config is missing, backend safely falls back to local mock intent generation.

MIB integration modes:

- Default mode keeps current adapter behavior for contracts and internal testing.
- Legacy hosted-gateway mode is available via `MIB_LEGACY_MODE=true` (based on `aharen/Pay` config model).
- Required legacy vars: `MIB_HOST`, `MIB_MER_RESP_URL`, `MIB_ACQ_ID`, `MIB_MER_ID`, `MIB_MER_PASSWORD`.
- Optional legacy vars: `MIB_PURCHASE_CURRENCY`, `MIB_FORCE_PURCHASE_CURRENCY`, `MIB_PURCHASE_CURRENCY_EXPONENT` (default `2`), `MIB_VERSION` (default `1`), `MIB_SIGNATURE_METHOD` (default `SHA1`).
- Legacy mode currency mapping defaults: `MVR -> 462`, `USD -> 840` unless force/default env overrides are set.

Still required from endpoint-specific docs before live API wiring:

- Create payment endpoint path + request/response schema
- Transaction status endpoint path + response schema

Contract test commands:

- `npm.cmd run test:contract:workations`
- `npm.cmd run test:contract:auth`
- `npm.cmd run test:contract:auth-strict` (runs with `AUTH_ALLOW_HEADER_FALLBACK=false`)
- `npm.cmd run test:contract:countries`
- `npm.cmd run test:contract:islands`
- `npm.cmd run test:contract:service-categories`
- `npm.cmd run test:contract:vendors`
- `npm.cmd run test:contract:accommodations`
- `npm.cmd run test:contract:transports`
- `npm.cmd run test:contract:bookings`
- `npm.cmd run test:contract:payments`
- `npm.cmd run test:contract:admin-settings`
- `npm.cmd run test:contract:permissions-matrix`
- `npm.cmd run test:contract:feature-flags`
- `npm.cmd run test:contract:all`
- `npm.cmd run test:contract` (alias of `test:contract:all`)

`test:contract:all` now also writes these artifacts under `infra/backend/test-logs` using the current date:

- `contract-all-YYYY-MM-DD.log` (full console transcript)
- `contract-all-YYYY-MM-DD.summary.md` (human-readable per-suite summary)
- `contract-all-YYYY-MM-DD.summary.json` (CI-ingestible summary)

Seeded smoke command:

- `npm.cmd run test:smoke:payments` (creates test user/booking and verifies `POST /payments/intents` returns `201`)
