-- Add remote work spaces domain with desk inventory, connectivity quality, and pass windows.

CREATE TABLE IF NOT EXISTS "RemoteWorkspace" (
  "id" TEXT NOT NULL,
  "vendorId" TEXT NOT NULL,
  "islandId" INTEGER NOT NULL,
  "name" TEXT NOT NULL,
  "description" TEXT,
  "deskInventory" INTEGER NOT NULL DEFAULT 1,
  "privateBoothInventory" INTEGER NOT NULL DEFAULT 0,
  "dayPassPrice" DECIMAL(65,30) NOT NULL DEFAULT 0.00,
  "weeklyPassPrice" DECIMAL(65,30) NOT NULL DEFAULT 0.00,
  "currency" TEXT NOT NULL DEFAULT 'USD',
  "minMbps" INTEGER NOT NULL DEFAULT 10,
  "connectivityQuality" TEXT NOT NULL DEFAULT 'BASIC',
  "hasMeetingRooms" BOOLEAN NOT NULL DEFAULT false,
  "active" BOOLEAN NOT NULL DEFAULT true,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3) NOT NULL,
  CONSTRAINT "RemoteWorkspace_pkey" PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS "RemoteWorkspacePassWindow" (
  "id" TEXT NOT NULL,
  "remoteWorkspaceId" TEXT NOT NULL,
  "startAt" TIMESTAMP(3) NOT NULL,
  "endAt" TIMESTAMP(3) NOT NULL,
  "deskInventoryOverride" INTEGER,
  "privateBoothInventoryOverride" INTEGER,
  "status" TEXT NOT NULL DEFAULT 'OPEN',
  "note" TEXT,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3) NOT NULL,
  CONSTRAINT "RemoteWorkspacePassWindow_pkey" PRIMARY KEY ("id")
);

CREATE INDEX IF NOT EXISTS "RemoteWorkspace_vendorId_active_idx" ON "RemoteWorkspace"("vendorId", "active");
CREATE INDEX IF NOT EXISTS "RemoteWorkspace_islandId_active_idx" ON "RemoteWorkspace"("islandId", "active");
CREATE INDEX IF NOT EXISTS "RemoteWorkspace_connectivityQuality_active_idx" ON "RemoteWorkspace"("connectivityQuality", "active");
CREATE INDEX IF NOT EXISTS "RemoteWorkspacePassWindow_remoteWorkspaceId_startAt_idx" ON "RemoteWorkspacePassWindow"("remoteWorkspaceId", "startAt");
CREATE INDEX IF NOT EXISTS "RemoteWorkspacePassWindow_status_startAt_idx" ON "RemoteWorkspacePassWindow"("status", "startAt");

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'RemoteWorkspace_vendorId_fkey'
  ) THEN
    ALTER TABLE "RemoteWorkspace"
      ADD CONSTRAINT "RemoteWorkspace_vendorId_fkey"
      FOREIGN KEY ("vendorId") REFERENCES "Vendor"("id") ON DELETE RESTRICT ON UPDATE CASCADE;
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'RemoteWorkspace_islandId_fkey'
  ) THEN
    ALTER TABLE "RemoteWorkspace"
      ADD CONSTRAINT "RemoteWorkspace_islandId_fkey"
      FOREIGN KEY ("islandId") REFERENCES "Island"("id") ON DELETE RESTRICT ON UPDATE CASCADE;
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'RemoteWorkspacePassWindow_remoteWorkspaceId_fkey'
  ) THEN
    ALTER TABLE "RemoteWorkspacePassWindow"
      ADD CONSTRAINT "RemoteWorkspacePassWindow_remoteWorkspaceId_fkey"
      FOREIGN KEY ("remoteWorkspaceId") REFERENCES "RemoteWorkspace"("id") ON DELETE CASCADE ON UPDATE CASCADE;
  END IF;
END $$;
