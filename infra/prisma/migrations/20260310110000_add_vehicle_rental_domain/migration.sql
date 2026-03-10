-- Add vehicle rentals marketplace domain with inventory, pickup/dropoff rules, and eligibility constraints.

CREATE TABLE IF NOT EXISTS "VehicleRental" (
  "id" TEXT NOT NULL,
  "vendorId" TEXT NOT NULL,
  "pickupIslandId" INTEGER NOT NULL,
  "dropoffIslandId" INTEGER,
  "vehicleType" TEXT NOT NULL,
  "title" TEXT NOT NULL,
  "description" TEXT,
  "inventoryTotal" INTEGER NOT NULL DEFAULT 1,
  "minDriverAge" INTEGER NOT NULL DEFAULT 18,
  "requiresLicense" BOOLEAN NOT NULL DEFAULT true,
  "acceptedLicenseClasses" TEXT,
  "allowsDifferentDropoff" BOOLEAN NOT NULL DEFAULT false,
  "dailyPrice" DECIMAL(65,30) NOT NULL DEFAULT 0.00,
  "currency" TEXT NOT NULL DEFAULT 'USD',
  "active" BOOLEAN NOT NULL DEFAULT true,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3) NOT NULL,
  CONSTRAINT "VehicleRental_pkey" PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS "VehicleRentalBlackout" (
  "id" TEXT NOT NULL,
  "vehicleRentalId" TEXT NOT NULL,
  "startDate" TIMESTAMP(3) NOT NULL,
  "endDate" TIMESTAMP(3) NOT NULL,
  "unitsBlocked" INTEGER NOT NULL DEFAULT 1,
  "reason" TEXT,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3) NOT NULL,
  CONSTRAINT "VehicleRentalBlackout_pkey" PRIMARY KEY ("id")
);

CREATE INDEX IF NOT EXISTS "VehicleRental_vendorId_active_idx" ON "VehicleRental"("vendorId", "active");
CREATE INDEX IF NOT EXISTS "VehicleRental_pickupIslandId_active_idx" ON "VehicleRental"("pickupIslandId", "active");
CREATE INDEX IF NOT EXISTS "VehicleRental_dropoffIslandId_active_idx" ON "VehicleRental"("dropoffIslandId", "active");
CREATE INDEX IF NOT EXISTS "VehicleRental_vehicleType_active_idx" ON "VehicleRental"("vehicleType", "active");
CREATE INDEX IF NOT EXISTS "VehicleRentalBlackout_vehicleRentalId_startDate_endDate_idx" ON "VehicleRentalBlackout"("vehicleRentalId", "startDate", "endDate");

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'VehicleRental_vendorId_fkey'
  ) THEN
    ALTER TABLE "VehicleRental"
      ADD CONSTRAINT "VehicleRental_vendorId_fkey"
      FOREIGN KEY ("vendorId") REFERENCES "Vendor"("id") ON DELETE RESTRICT ON UPDATE CASCADE;
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'VehicleRental_pickupIslandId_fkey'
  ) THEN
    ALTER TABLE "VehicleRental"
      ADD CONSTRAINT "VehicleRental_pickupIslandId_fkey"
      FOREIGN KEY ("pickupIslandId") REFERENCES "Island"("id") ON DELETE RESTRICT ON UPDATE CASCADE;
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'VehicleRental_dropoffIslandId_fkey'
  ) THEN
    ALTER TABLE "VehicleRental"
      ADD CONSTRAINT "VehicleRental_dropoffIslandId_fkey"
      FOREIGN KEY ("dropoffIslandId") REFERENCES "Island"("id") ON DELETE SET NULL ON UPDATE CASCADE;
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'VehicleRentalBlackout_vehicleRentalId_fkey'
  ) THEN
    ALTER TABLE "VehicleRentalBlackout"
      ADD CONSTRAINT "VehicleRentalBlackout_vehicleRentalId_fkey"
      FOREIGN KEY ("vehicleRentalId") REFERENCES "VehicleRental"("id") ON DELETE CASCADE ON UPDATE CASCADE;
  END IF;
END $$;
