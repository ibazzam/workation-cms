# Data Governance: PII Retention, Backup/Restore, and GDPR-like Controls

This document defines operational data governance controls for Workation (Render + Neon).

## Scope
- PII retention and minimization
- Backup and restore drill procedure
- GDPR-like request handling (access, correction, deletion)

## PII Handling Baseline
- Primary PII fields in platform flows include user email/name, vendor email/phone, and audit actor email.
- Admin write-audit payload snapshots are now redacted for sensitive/PII keys before persistence.
- Redacted key patterns include password/secret/token/authorization/api-key/email/phone variants.

## Retention Policy
- Admin audit logs:
  - default retention: `90` days
  - prune command: `npm --prefix infra/backend run audit:prune`
  - env controls:
    - `AUDIT_LOG_RETENTION_DAYS` (default `90`)
    - `AUDIT_LOG_PRUNE_DRY_RUN` (default `true`)
- Payment/reconciliation records:
  - keep according to finance and compliance requirements (minimum 365 days unless stricter legal rules apply)
- Operational logs and metrics:
  - keep according to observability storage policy and cost/compliance constraints

## Backup and Restore Drill (Neon)
Run at least quarterly.

1. Confirm latest successful backups/PITR window in Neon project dashboard.
2. Create restore target branch or point-in-time restore in Neon.
3. Point a staging backend instance to restored branch and run checks:
   - `npm run live:preflight`
   - `npm run perf:booking-payments` (optional baseline check)
4. Verify critical data classes:
   - users/bookings/payments presence and integrity
   - reconciliation and background job records
   - admin audit log continuity
5. Record drill evidence in change log with date, restore target timestamp, and pass/fail outcome.

## GDPR-like Operational Controls
- Access/export requests:
  - query user profile, bookings, payments, loyalty, review, and support-related data by `userId`
- Correction requests:
  - process profile updates via authenticated user endpoints
- Deletion/anonymization requests:
  - perform scoped anonymization where legal retention blocks hard-delete
  - preserve finance and fraud/audit records as required by law while removing direct identifiers where allowed

## Verification Checklist
- Security audits green:
  - `npm run security:secrets`
  - `npm audit --omit=dev`
  - `npm --prefix infra/backend audit --omit=dev`
  - `composer audit`
- Audit prune dry-run reviewed monthly.
- Backup/restore drill completed and documented quarterly.