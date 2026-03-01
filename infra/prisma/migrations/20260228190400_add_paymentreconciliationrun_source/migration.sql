-- Add source to PaymentReconciliationRun (safe, non-destructive)
BEGIN;
ALTER TABLE "PaymentReconciliationRun"
  ADD COLUMN IF NOT EXISTS "source" text;
COMMIT;
