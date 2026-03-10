-- Add excursions and leisure booking domain with slot, capacity, and equipment constraints.

CREATE TABLE IF NOT EXISTS "Excursion" (
  "id" TEXT NOT NULL,
  "vendorId" TEXT NOT NULL,
  "islandId" INTEGER NOT NULL,
  "type" TEXT NOT NULL,
  "title" TEXT NOT NULL,
  "description" TEXT,
  "durationMinutes" INTEGER NOT NULL,
  "baseCapacity" INTEGER NOT NULL DEFAULT 1,
  "equipmentRequired" BOOLEAN NOT NULL DEFAULT false,
  "equipmentStock" INTEGER NOT NULL DEFAULT 0,
  "price" DECIMAL(65,30) NOT NULL DEFAULT 0.00,
  "currency" TEXT NOT NULL DEFAULT 'USD',
  "active" BOOLEAN NOT NULL DEFAULT true,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3) NOT NULL,
  CONSTRAINT "Excursion_pkey" PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS "ExcursionSlot" (
  "id" TEXT NOT NULL,
  "excursionId" TEXT NOT NULL,
  "startAt" TIMESTAMP(3) NOT NULL,
  "endAt" TIMESTAMP(3) NOT NULL,
  "capacityOverride" INTEGER,
  "equipmentStockOverride" INTEGER,
  "status" TEXT NOT NULL DEFAULT 'OPEN',
  "note" TEXT,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3) NOT NULL,
  CONSTRAINT "ExcursionSlot_pkey" PRIMARY KEY ("id")
);

CREATE INDEX IF NOT EXISTS "Excursion_vendorId_active_idx" ON "Excursion"("vendorId", "active");
CREATE INDEX IF NOT EXISTS "Excursion_islandId_active_idx" ON "Excursion"("islandId", "active");
CREATE INDEX IF NOT EXISTS "Excursion_type_active_idx" ON "Excursion"("type", "active");
CREATE INDEX IF NOT EXISTS "ExcursionSlot_excursionId_startAt_idx" ON "ExcursionSlot"("excursionId", "startAt");
CREATE INDEX IF NOT EXISTS "ExcursionSlot_status_startAt_idx" ON "ExcursionSlot"("status", "startAt");

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'Excursion_vendorId_fkey'
  ) THEN
    ALTER TABLE "Excursion"
      ADD CONSTRAINT "Excursion_vendorId_fkey"
      FOREIGN KEY ("vendorId") REFERENCES "Vendor"("id") ON DELETE RESTRICT ON UPDATE CASCADE;
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'Excursion_islandId_fkey'
  ) THEN
    ALTER TABLE "Excursion"
      ADD CONSTRAINT "Excursion_islandId_fkey"
      FOREIGN KEY ("islandId") REFERENCES "Island"("id") ON DELETE RESTRICT ON UPDATE CASCADE;
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'ExcursionSlot_excursionId_fkey'
  ) THEN
    ALTER TABLE "ExcursionSlot"
      ADD CONSTRAINT "ExcursionSlot_excursionId_fkey"
      FOREIGN KEY ("excursionId") REFERENCES "Excursion"("id") ON DELETE CASCADE ON UPDATE CASCADE;
  END IF;
END $$;
