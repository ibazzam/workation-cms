-- Re-add AdminAuditLog.requestBody and AppConfig.updatedAt required by CI
-- Safe, non-destructive: add columns only if missing and set sensible defaults

ALTER TABLE IF EXISTS "AdminAuditLog"
  ADD COLUMN IF NOT EXISTS "requestBody" TEXT;

ALTER TABLE IF EXISTS "AppConfig"
  ADD COLUMN IF NOT EXISTS "updatedAt" TIMESTAMP(3);

-- Backfill from createdAt where possible
UPDATE "AppConfig" SET "updatedAt" = "createdAt" WHERE "updatedAt" IS NULL;

-- Set default for new rows
ALTER TABLE IF EXISTS "AppConfig" ALTER COLUMN "updatedAt" SET DEFAULT now();
