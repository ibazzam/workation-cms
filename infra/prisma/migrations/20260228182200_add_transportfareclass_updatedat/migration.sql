-- Add updatedAt to TransportFareClass (safe, non-destructive)
BEGIN;
ALTER TABLE "TransportFareClass"
  ADD COLUMN IF NOT EXISTS "updatedAt" timestamptz;

-- Backfill existing rows to avoid nulls where tests expect a timestamp
UPDATE "TransportFareClass" SET "updatedAt" = now() WHERE "updatedAt" IS NULL;

COMMIT;
