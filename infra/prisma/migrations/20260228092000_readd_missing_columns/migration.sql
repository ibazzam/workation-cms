-- Recreate missing columns and indexes required by CI contract/smoke tests
-- Safe: uses IF NOT EXISTS / IF EXISTS to avoid destructive changes

ALTER TABLE IF EXISTS "TransportFareClass"
  ADD COLUMN IF NOT EXISTS "seats" INTEGER;

ALTER TABLE IF EXISTS "Booking"
  ADD COLUMN IF NOT EXISTS "holdExpiresAt" TIMESTAMP(3);

CREATE INDEX IF NOT EXISTS "Booking_status_holdExpiresAt_idx"
  ON "Booking"("status", "holdExpiresAt");

ALTER TABLE IF EXISTS "AdminAuditLog"
  ADD COLUMN IF NOT EXISTS "actorUserId" TEXT;
