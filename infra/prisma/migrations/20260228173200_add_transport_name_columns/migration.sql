-- Add name column to Transport and TransportFareClass (safe, non-destructive)
BEGIN;
ALTER TABLE "Transport"
  ADD COLUMN IF NOT EXISTS "name" text;

ALTER TABLE "TransportFareClass"
  ADD COLUMN IF NOT EXISTS "name" text;
COMMIT;
