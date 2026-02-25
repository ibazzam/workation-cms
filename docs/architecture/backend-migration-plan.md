# Backend Migration Plan: Laravel -> NestJS

Date: 2026-02-20  
Decision reference: `docs/architecture/adr-0001-backend-decision.md`

## Objective

Migrate backend ownership from Laravel to NestJS with zero critical downtime, preserving existing behavior while enabling the target architecture.

## Target state

- Single backend runtime: NestJS (`infra/backend`)
- ORM/data layer: Prisma + PostgreSQL
- Auth and authorization: NextAuth session/JWT validation + RBAC (User/Vendor/Admin)
- Payment orchestration: Stripe + local gateway adapter interface
- Laravel decommissioned after parity and stability window

## Current baseline

- Laravel product API routes are decommissioned (`/api/*` returns HTTP `410`).
- NestJS is the active product API runtime under `/api/v1` for core domains.
- Prisma schema is PostgreSQL-backed and used by contract/smoke suites in CI.

## Migration strategy

Use a **strangler pattern**:

1. Build new capability in NestJS.
2. Run both backends in parallel for a bounded period.
3. Switch traffic endpoint-by-endpoint.
4. Decommission Laravel after parity + soak period.

## Phases

## Phase 0 (Week 0-1): Platform hardening

### Deliverables

- [x] Switch Prisma datasource to PostgreSQL for all environments.
- [ ] Provision dev/staging PostgreSQL and baseline schema migration.
- [ ] Establish NestJS module skeletons:
  - [ ] `auth`
  - [ ] `users`
  - [ ] `islands`
  - [ ] `accommodations`
  - [ ] `transports`
  - [ ] `bookings`
  - [ ] `payments`
  - [ ] `vendors`
  - [ ] `admin`
- [ ] Add API versioning (`/api/v1`) and global validation/error filters.
- [ ] Add structured logging and request correlation IDs.

### Exit criteria

- NestJS boots in dev/staging with PostgreSQL.
- CI runs lint/typecheck/test successfully for backend workspace.

## Phase 1 (Week 1-2): Parity for existing Laravel `workations`

### Deliverables

- [ ] Implement `workations` module in NestJS with same request/response semantics as Laravel.
- [ ] Add contract tests to verify parity with existing behavior.
- [ ] Introduce feature flag for read traffic shadowing (optional but recommended).

### Exit criteria

- Functional parity confirmed by automated tests.
- Frontend can switch `workations` calls to NestJS in staging.

## Phase 2 (Week 2-4): Core MVP domains

### Deliverables

- [ ] Islands/Atolls APIs (search/list/detail).
- [ ] Accommodations APIs (inventory + availability).
- [ ] Transport APIs (speedboat/domestic flight schedules, capacity, fare).
- [ ] Booking APIs with dependency checks (transport leg constraints).
- [ ] RBAC enforcement across User/Vendor/Admin routes.

### Exit criteria

- End-to-end booking flow works in staging for at least one full itinerary path.
- Authorization tests passing for role boundaries.

## Phase 3 (Week 4-5): Payments + operations

### Deliverables

- [ ] Payment provider interface with Stripe adapter.
- [ ] Local gateway adapter stub + integration contract.
- [ ] Webhook ingestion with signature verification and idempotency keys.
- [ ] Vendor basic operations endpoints (availability, pricing, blackout dates).

### Exit criteria

- Successful payment intent -> confirmation -> booking state transition in staging.
- Webhook replay safety verified.

## Phase 4 (Week 5-6): Cutover and decommission

### Deliverables

- [ ] Switch frontend API base to NestJS for all migrated modules.
- [ ] Freeze Laravel routes (read-only fallback window if needed).
- [ ] Remove Laravel domain endpoints after soak period.
- [ ] Update runbooks, on-call docs, and rollback procedures.

### Exit criteria

- 7-day stability window with no Sev-1/Sev-2 backend incidents.
- Laravel backend no longer on request path for product APIs.

## API compatibility rules during migration

- Maintain stable JSON envelopes per endpoint migration wave.
- Additive changes first; breaking changes only in explicit version bumps.
- Keep error shapes consistent (`code`, `message`, `details`).

## Data migration rules

- PostgreSQL is source of truth once module is cut over.
- Backfill scripts are idempotent and checkpointed.
- Use UTC timestamps; convert display timezone at frontend layer.

## Risk register and mitigations

1. **Dual-write inconsistencies**  
   Mitigation: avoid dual writes where possible; perform module-level hard cutovers.

2. **Payment duplication risk**  
   Mitigation: idempotency keys and webhook replay protection.

3. **Contract drift between frontend and API**  
   Mitigation: generated client types + contract tests in CI.

4. **Team context split across frameworks**  
   Mitigation: feature freeze on Laravel except critical hotfixes.

## Immediate next sprint (start now)

1. Convert Prisma datasource to PostgreSQL and create initial migration.
2. Implement NestJS `workations` parity module.
3. Add API contract tests for `GET/POST /workations`.
4. Wire frontend `RemoteStatus` and one list page to NestJS `/api/v1/workations`.

## Ownership model

- Platform lead: architecture, CI/CD, observability, rollout gates.
- Backend squad: NestJS modules, Prisma schema/migrations, contracts.
- Frontend squad: API client migration and feature-flagged cutovers.
