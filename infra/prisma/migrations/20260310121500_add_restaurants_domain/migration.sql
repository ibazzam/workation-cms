-- Add restaurant reservation domain with seating windows and deposit policies.

CREATE TABLE IF NOT EXISTS "Restaurant" (
  "id" TEXT NOT NULL,
  "vendorId" TEXT NOT NULL,
  "islandId" INTEGER NOT NULL,
  "name" TEXT NOT NULL,
  "description" TEXT,
  "cuisineType" TEXT NOT NULL,
  "totalTables" INTEGER NOT NULL DEFAULT 1,
  "minPartySize" INTEGER NOT NULL DEFAULT 1,
  "maxPartySize" INTEGER NOT NULL DEFAULT 10,
  "depositPolicyType" TEXT NOT NULL DEFAULT 'NONE',
  "depositAmount" DECIMAL(65,30) NOT NULL DEFAULT 0.00,
  "depositCurrency" TEXT NOT NULL DEFAULT 'USD',
  "active" BOOLEAN NOT NULL DEFAULT true,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3) NOT NULL,
  CONSTRAINT "Restaurant_pkey" PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS "RestaurantSeatingWindow" (
  "id" TEXT NOT NULL,
  "restaurantId" TEXT NOT NULL,
  "startAt" TIMESTAMP(3) NOT NULL,
  "endAt" TIMESTAMP(3) NOT NULL,
  "tableCountOverride" INTEGER,
  "minPartySizeOverride" INTEGER,
  "maxPartySizeOverride" INTEGER,
  "status" TEXT NOT NULL DEFAULT 'OPEN',
  "note" TEXT,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3) NOT NULL,
  CONSTRAINT "RestaurantSeatingWindow_pkey" PRIMARY KEY ("id")
);

CREATE INDEX IF NOT EXISTS "Restaurant_vendorId_active_idx" ON "Restaurant"("vendorId", "active");
CREATE INDEX IF NOT EXISTS "Restaurant_islandId_active_idx" ON "Restaurant"("islandId", "active");
CREATE INDEX IF NOT EXISTS "Restaurant_cuisineType_active_idx" ON "Restaurant"("cuisineType", "active");
CREATE INDEX IF NOT EXISTS "RestaurantSeatingWindow_restaurantId_startAt_idx" ON "RestaurantSeatingWindow"("restaurantId", "startAt");
CREATE INDEX IF NOT EXISTS "RestaurantSeatingWindow_status_startAt_idx" ON "RestaurantSeatingWindow"("status", "startAt");

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'Restaurant_vendorId_fkey'
  ) THEN
    ALTER TABLE "Restaurant"
      ADD CONSTRAINT "Restaurant_vendorId_fkey"
      FOREIGN KEY ("vendorId") REFERENCES "Vendor"("id") ON DELETE RESTRICT ON UPDATE CASCADE;
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'Restaurant_islandId_fkey'
  ) THEN
    ALTER TABLE "Restaurant"
      ADD CONSTRAINT "Restaurant_islandId_fkey"
      FOREIGN KEY ("islandId") REFERENCES "Island"("id") ON DELETE RESTRICT ON UPDATE CASCADE;
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'RestaurantSeatingWindow_restaurantId_fkey'
  ) THEN
    ALTER TABLE "RestaurantSeatingWindow"
      ADD CONSTRAINT "RestaurantSeatingWindow_restaurantId_fkey"
      FOREIGN KEY ("restaurantId") REFERENCES "Restaurant"("id") ON DELETE CASCADE ON UPDATE CASCADE;
  END IF;
END $$;
