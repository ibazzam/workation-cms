-- Add type and status to PaymentBackgroundJob (safe, non-destructive)
BEGIN;
ALTER TABLE "PaymentBackgroundJob"
  ADD COLUMN IF NOT EXISTS "type" text;

ALTER TABLE "PaymentBackgroundJob"
  ADD COLUMN IF NOT EXISTS "status" text;
COMMIT;
