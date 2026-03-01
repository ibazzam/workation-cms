-- Restore missing columns observed in run 22519386637
-- Non-destructive: use IF NOT EXISTS and safe defaults
BEGIN;

-- AdminAuditLog: restore vendor and error fields
ALTER TABLE IF EXISTS "AdminAuditLog" ADD COLUMN IF NOT EXISTS "actorVendorId" TEXT;
ALTER TABLE IF EXISTS "AdminAuditLog" ADD COLUMN IF NOT EXISTS "errorMessage" TEXT;

-- ServiceCategory: ensure active flag exists
ALTER TABLE IF EXISTS "ServiceCategory" ADD COLUMN IF NOT EXISTS "active" BOOLEAN DEFAULT true;

-- Country: ensure active flag exists (contracts may expect this)
ALTER TABLE IF EXISTS "Country" ADD COLUMN IF NOT EXISTS "active" BOOLEAN DEFAULT true;

-- Transport: ensure updatedAt exists
ALTER TABLE IF EXISTS "Transport" ADD COLUMN IF NOT EXISTS "updatedAt" TIMESTAMPTZ DEFAULT now();

-- Booking: common missing fields seen across runs (no-op if present)
ALTER TABLE IF EXISTS "Booking" ADD COLUMN IF NOT EXISTS "transportFareClassCode" TEXT;
ALTER TABLE IF EXISTS "Booking" ADD COLUMN IF NOT EXISTS "minStayNights" INTEGER;

COMMIT;
