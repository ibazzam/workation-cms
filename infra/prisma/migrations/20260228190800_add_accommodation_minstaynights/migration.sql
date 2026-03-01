-- Add minStayNights to Accommodation (safe, non-destructive)
BEGIN;
ALTER TABLE "Accommodation"
  ADD COLUMN IF NOT EXISTS "minStayNights" integer;

COMMIT;
