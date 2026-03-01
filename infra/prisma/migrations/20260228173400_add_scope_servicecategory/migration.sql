-- Add scope column to ServiceCategory (safe, non-destructive)
BEGIN;
ALTER TABLE "ServiceCategory"
  ADD COLUMN IF NOT EXISTS "scope" text;
COMMIT;
