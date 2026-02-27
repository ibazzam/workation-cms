-- Recreate AccommodationSeasonalRate, AccommodationBlackout and AdminAuditLog
-- This migration reintroduces tables that were previously dropped.

CREATE TABLE IF NOT EXISTS "AccommodationSeasonalRate" (
  "id" TEXT PRIMARY KEY,
  "accommodationId" TEXT NOT NULL,
  "name" TEXT NOT NULL,
  "startDate" TIMESTAMPTZ NOT NULL,
  "endDate" TIMESTAMPTZ NOT NULL,
  "nightlyPrice" NUMERIC(65,30) NOT NULL DEFAULT 0,
  "priority" INTEGER NOT NULL DEFAULT 0,
  "createdAt" TIMESTAMPTZ NOT NULL DEFAULT now(),
  "updatedAt" TIMESTAMPTZ
);

ALTER TABLE "AccommodationSeasonalRate"
  ADD CONSTRAINT "AccommodationSeasonalRate_accommodationId_fkey"
  FOREIGN KEY ("accommodationId") REFERENCES "Accommodation"("id") ON DELETE CASCADE;

CREATE TABLE IF NOT EXISTS "AccommodationBlackout" (
  "id" TEXT PRIMARY KEY,
  "accommodationId" TEXT NOT NULL,
  "startDate" TIMESTAMPTZ NOT NULL,
  "endDate" TIMESTAMPTZ NOT NULL,
  "reason" TEXT,
  "createdAt" TIMESTAMPTZ NOT NULL DEFAULT now(),
  "updatedAt" TIMESTAMPTZ
);

ALTER TABLE "AccommodationBlackout"
  ADD CONSTRAINT "AccommodationBlackout_accommodationId_fkey"
  FOREIGN KEY ("accommodationId") REFERENCES "Accommodation"("id") ON DELETE CASCADE;

CREATE TABLE IF NOT EXISTS "AdminAuditLog" (
  "id" TEXT PRIMARY KEY,
  "actorId" TEXT,
  "actorRole" TEXT NOT NULL,
  "method" TEXT NOT NULL,
  "path" TEXT NOT NULL,
  "success" BOOLEAN NOT NULL DEFAULT FALSE,
  "createdAt" TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS "AdminAuditLog_createdAt_idx" ON "AdminAuditLog" ("createdAt" DESC);

-- end
