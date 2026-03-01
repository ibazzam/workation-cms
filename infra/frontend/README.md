# Workation Frontend (minimal)

This is a minimal Next.js App Router scaffold with TypeScript and Tailwind CSS.

Environment:

- Optional: `WORKATION_API_BASE_URL` (default: `http://localhost:3000/api/v1`)

Bookings page includes a payment intent form:

- Route: `/bookings`
- Booking management panel:
	- Lists current user bookings with lifecycle eligibility metadata.
	- Supports confirm/cancel actions for eligible bookings.
	- Supports owner-scoped rebook flow via template defaults and submit action.

- Provider selection: `STRIPE`, `BML`, `MIB`
- Currency selection: `USD`, `MVR`
- Sends request to `POST /payments/intents`
- Auto-fills request user headers from `GET /api/auth/session` when available
- Falls back to locally saved header values when no auth session is present
- When session exists, manual header fields are hidden unless "Override headers manually for testing" is enabled

Admin operations page:

- Route: `/admin/payments`
- Alerts panel powered by `GET /payments/admin/alerts`
- Jobs list powered by `GET /payments/admin/jobs`
- Job actions: `POST /payments/admin/jobs/:id/requeue|cancel|complete`

Admin commercial settings page:

- Route: `/admin/settings`
- Loads/saves `GET|POST /admin/settings/commercial`
- Covers currency settings, exchange rates, and loyalty program configuration

Admin services expansion page:

- Route: `/admin/services`
- CRUD for vendors via `/vendors/admin`
- CRUD for accommodations via `/accommodations/admin`
- CRUD for transports via `/transports/admin`
- CRUD for countries via `/countries/admin`
- CRUD for service categories via `/service-categories/admin`

Quick start:

```powershell
cd infra\frontend
npm.cmd install
npm.cmd run dev
```

Open http://localhost:3000
