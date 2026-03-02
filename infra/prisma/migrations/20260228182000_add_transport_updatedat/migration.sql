-- Add updatedAt to Transport (safe, non-destructive)
BEGIN;
ALTER TABLE "Transport"
  ADD COLUMN IF NOT EXISTS "updatedAt" timestamptz;

-- Backfill existing rows to avoid nulls where tests expect a timestamp
UPDATE "Transport" SET "updatedAt" = now() WHERE "updatedAt" IS NULL;

COMMIT;
