-- Migration: add missing columns observed in CI run 22517236744
-- Non-destructive: uses IF NOT EXISTS and safe backfills where appropriate
BEGIN;

ALTER TABLE "Accommodation" ADD COLUMN IF NOT EXISTS "minStayNights" integer;

ALTER TABLE "Booking" ADD COLUMN IF NOT EXISTS "minStayNights" integer;

-- AppConfig.updatedAt: add, backfill, and set default
ALTER TABLE "AppConfig" ADD COLUMN IF NOT EXISTS "updatedAt" timestamptz;
UPDATE "AppConfig" SET "updatedAt" = now() WHERE "updatedAt" IS NULL;
ALTER TABLE "AppConfig" ALTER COLUMN "updatedAt" SET DEFAULT now();

-- Audit / request payloads
ALTER TABLE "AdminAuditLog" ADD COLUMN IF NOT EXISTS "requestBody" jsonb;

-- Payment webhook event id
ALTER TABLE "PaymentWebhookEvent" ADD COLUMN IF NOT EXISTS "eventId" text;

-- Service category code
ALTER TABLE "ServiceCategory" ADD COLUMN IF NOT EXISTS "code" text;

-- Transport display name
ALTER TABLE "Transport" ADD COLUMN IF NOT EXISTS "name" text;

-- Booking transport fare class code
ALTER TABLE "Booking" ADD COLUMN IF NOT EXISTS "transportFareClassCode" text;

COMMIT;
