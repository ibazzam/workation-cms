-- Add minNights to AccommodationSeasonalRate (safe, non-destructive)
BEGIN;
ALTER TABLE "AccommodationSeasonalRate"
  ADD COLUMN IF NOT EXISTS "minNights" integer;
COMMIT;
