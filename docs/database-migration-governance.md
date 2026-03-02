# Database Migration Governance (Prisma + PostgreSQL)

Date: 2026-02-21
Scope: `infra/prisma` schema and migrations for backend domains.

## Purpose

Prevent schema drift, ensure safe deployments, and provide a repeatable rollback/recovery flow.

## Change policy

- All production-intent schema changes must be delivered via Prisma migration files under `infra/prisma/migrations`.
- Do not modify applied migration SQL files after merge.
- Keep migration scope small and reversible where practical.
- Any destructive change (`DROP`, irreversible type conversion, required column without backfill) requires explicit rollout notes.

## Pre-merge checklist

- [ ] Prisma schema updated in `infra/prisma/schema.prisma`.
- [ ] New migration generated and committed (`infra/prisma/migrations/<timestamp>_<name>`).
- [ ] Migration reviewed for destructive operations.
- [ ] Contract/smoke suites pass against migrated database.
- [ ] Rollback strategy documented in PR description.

## CI schema drift gate

The backend CI workflow runs drift detection after `prisma migrate deploy` for contract and smoke jobs:

- Command:
  - `npx prisma migrate diff --from-url "$DATABASE_URL" --to-schema-datamodel ../prisma/schema.prisma --exit-code`
- Expected result:
  - Exit `0` means schema and datamodel are aligned.
  - Non-zero fails CI and blocks merge.

This catches schema/datamodel mismatch and missing migration scenarios before merge.

## Deployment runbook

1. Confirm backup/restore point exists for target DB.
2. Deploy backend artifact that includes migration files.
3. Run:
   - `npx prisma migrate deploy --schema=infra/prisma/schema.prisma`
4. Run post-migration verification:
   - Backend health endpoint (`/api/v1/health`)
   - Targeted contract/smoke checks for impacted domains
   - Prisma drift check command above

## Rollback playbook

Preferred strategy is **roll-forward** with a corrective migration. Use rollback only when service restoration requires immediate revert.

### A) Roll-forward (default)

1. Identify failing migration/effect.
2. Prepare corrective migration in a hotfix branch.
3. Validate in staging with representative data.
4. Deploy corrective migration.

### B) Point-in-time restore (emergency)

1. Put write traffic in maintenance mode.
2. Restore DB to the most recent verified backup/snapshot before the failed migration.
3. Redeploy last known-good backend release.
4. Verify health + critical read/write flows.
5. Reconcile any lost writes from event/audit logs where possible.

## Seed strategy

- Use deterministic fixture prefixes in test/contract data (already used in backend suites).
- Keep seeds idempotent and environment-safe.
- Never rely on ad-hoc manual SQL for baseline test data in CI.

## Ownership

- Data + Backend own migration authoring and review.
- Platform owns CI gate integrity.
- Incident commander (on-call) executes restore path when emergency rollback is needed.
