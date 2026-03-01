-- Add updatedAt to Booking (safe, non-destructive)
BEGIN;
ALTER TABLE "Booking"
  ADD COLUMN IF NOT EXISTS "updatedAt" timestamptz;

-- Backfill existing rows to avoid nulls where tests expect a timestamp
UPDATE "Booking" SET "updatedAt" = now() WHERE "updatedAt" IS NULL;

COMMIT;
