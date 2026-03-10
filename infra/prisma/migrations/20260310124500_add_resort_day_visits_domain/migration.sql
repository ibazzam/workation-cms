-- Add resort day visit domain with quota windows, transfer bundles, and pass restrictions.

CREATE TABLE IF NOT EXISTS "ResortDayVisit" (
  "id" TEXT NOT NULL,
  "vendorId" TEXT NOT NULL,
  "islandId" INTEGER NOT NULL,
  "title" TEXT NOT NULL,
  "description" TEXT,
  "quotaPerWindow" INTEGER NOT NULL DEFAULT 1,
  "includesTransfer" BOOLEAN NOT NULL DEFAULT false,
  "transferMode" TEXT NOT NULL DEFAULT 'NONE',
  "transferBundlePrice" DECIMAL(65,30) NOT NULL DEFAULT 0.00,
  "passRestrictionType" TEXT NOT NULL DEFAULT 'NONE',
  "minAllowedAge" INTEGER NOT NULL DEFAULT 0,
  "basePrice" DECIMAL(65,30) NOT NULL DEFAULT 0.00,
  "currency" TEXT NOT NULL DEFAULT 'USD',
  "active" BOOLEAN NOT NULL DEFAULT true,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3) NOT NULL,
  CONSTRAINT "ResortDayVisit_pkey" PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS "ResortDayVisitWindow" (
  "id" TEXT NOT NULL,
  "resortDayVisitId" TEXT NOT NULL,
  "startAt" TIMESTAMP(3) NOT NULL,
  "endAt" TIMESTAMP(3) NOT NULL,
  "quotaOverride" INTEGER,
  "status" TEXT NOT NULL DEFAULT 'OPEN',
  "note" TEXT,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3) NOT NULL,
  CONSTRAINT "ResortDayVisitWindow_pkey" PRIMARY KEY ("id")
);

CREATE INDEX IF NOT EXISTS "ResortDayVisit_vendorId_active_idx" ON "ResortDayVisit"("vendorId", "active");
CREATE INDEX IF NOT EXISTS "ResortDayVisit_islandId_active_idx" ON "ResortDayVisit"("islandId", "active");
CREATE INDEX IF NOT EXISTS "ResortDayVisitWindow_resortDayVisitId_startAt_idx" ON "ResortDayVisitWindow"("resortDayVisitId", "startAt");
CREATE INDEX IF NOT EXISTS "ResortDayVisitWindow_status_startAt_idx" ON "ResortDayVisitWindow"("status", "startAt");

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'ResortDayVisit_vendorId_fkey'
  ) THEN
    ALTER TABLE "ResortDayVisit"
      ADD CONSTRAINT "ResortDayVisit_vendorId_fkey"
      FOREIGN KEY ("vendorId") REFERENCES "Vendor"("id") ON DELETE RESTRICT ON UPDATE CASCADE;
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'ResortDayVisit_islandId_fkey'
  ) THEN
    ALTER TABLE "ResortDayVisit"
      ADD CONSTRAINT "ResortDayVisit_islandId_fkey"
      FOREIGN KEY ("islandId") REFERENCES "Island"("id") ON DELETE RESTRICT ON UPDATE CASCADE;
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'ResortDayVisitWindow_resortDayVisitId_fkey'
  ) THEN
    ALTER TABLE "ResortDayVisitWindow"
      ADD CONSTRAINT "ResortDayVisitWindow_resortDayVisitId_fkey"
      FOREIGN KEY ("resortDayVisitId") REFERENCES "ResortDayVisit"("id") ON DELETE CASCADE ON UPDATE CASCADE;
  END IF;
END $$;
