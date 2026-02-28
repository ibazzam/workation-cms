-- Add Transport.name and TransportFareClass.price if missing
-- Non-destructive: uses IF NOT EXISTS
BEGIN;

ALTER TABLE IF EXISTS "Transport" ADD COLUMN IF NOT EXISTS "name" TEXT;

ALTER TABLE IF EXISTS "TransportFareClass" ADD COLUMN IF NOT EXISTS "price" DECIMAL(10,2) DEFAULT 0.00;

COMMIT;
