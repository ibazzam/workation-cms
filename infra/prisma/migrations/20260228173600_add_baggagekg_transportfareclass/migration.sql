-- Add baggageKg to TransportFareClass (safe, non-destructive)
BEGIN;
ALTER TABLE "TransportFareClass"
  ADD COLUMN IF NOT EXISTS "baggageKg" integer;
COMMIT;
