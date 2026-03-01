-- Add attempts to PaymentBackgroundJob (safe, non-destructive)
BEGIN;
ALTER TABLE "PaymentBackgroundJob"
  ADD COLUMN IF NOT EXISTS "attempts" integer DEFAULT 0;

-- Backfill existing rows to 0 where NULL
UPDATE "PaymentBackgroundJob" SET "attempts" = 0 WHERE "attempts" IS NULL;

COMMIT;
