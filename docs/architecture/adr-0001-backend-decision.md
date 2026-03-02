# ADR-0001: Backend Platform Decision

- Status: Accepted
- Date: 2026-02-20
- Owner: Platform / Architecture

## Context

The repository currently contains:

- A working Laravel CRUD API for `workations` (tested).
- A minimal NestJS scaffold under `infra/backend`.
- A Prisma domain draft under `infra/prisma`.

The product vision requires complex domain orchestration across Maldives-specific travel constraints:

- Atoll/island graph and transport dependencies (speedboat + domestic flight).
- Multi-leg itinerary-aware booking.
- Vendor and admin operations.
- Payment orchestration and webhooks.

Keeping two active backends (Laravel + NestJS) will split engineering capacity and slow delivery.

## Decision

We standardize on **NestJS + Prisma + PostgreSQL** as the single long-term backend platform.

- NestJS becomes the source of truth for all new domain APIs.
- Laravel enters maintenance mode and is only kept temporarily for backward compatibility while parity is reached.
- PostgreSQL is the canonical database in all environments (dev/staging/prod).

## Why this decision

1. Aligns with the target production architecture already selected.
2. Strong modular boundaries for domains (inventory, transport, booking, payments, vendor/admin).
3. Better long-term maintainability for typed contracts and scaling service complexity.
4. Avoids duplicating business logic in two backend stacks.

## Scope and constraints

- No net-new product features are implemented in Laravel from this decision date.
- Any required Laravel hotfixes must be minimal and mirrored in NestJS migration backlog.
- API contracts are versioned and migrated behind feature flags.

## Consequences

### Positive

- Single engineering direction and reduced architecture ambiguity.
- Cleaner path to RBAC, payment adapters, transport scheduling, and observability.

### Trade-offs

- Short-term migration overhead.
- Temporary coexistence complexity until full API parity and cutover.

## Success criteria

- 100% parity on agreed MVP endpoints in NestJS.
- Frontend switched to NestJS APIs.
- Laravel endpoints disabled or removed after stability window.

## Linked plan

Execution plan: `docs/architecture/backend-migration-plan.md`
