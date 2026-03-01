-- Add missing AdminAuditLog.statusCode required by CI contract tests
-- Safe, non-destructive: only adds column if it doesn't exist

ALTER TABLE IF EXISTS "AdminAuditLog"
  ADD COLUMN IF NOT EXISTS "statusCode" INTEGER;
