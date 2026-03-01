import { PrismaClient } from '@prisma/client';
(async () => {
  const prisma = new PrismaClient();
  try {
    const exists = await prisma.$queryRawUnsafe("SELECT to_regclass('public.\"AccommodationSeasonalRate\"')::text as exists");
    if (exists && exists[0] && exists[0].exists) {
      console.log('AccommodationSeasonalRate already exists');
      return;
    }

    console.log('Creating AccommodationSeasonalRate table');
    await prisma.$executeRawUnsafe(
      `CREATE TABLE IF NOT EXISTS "AccommodationSeasonalRate" (
        "id" TEXT NOT NULL,
        "accommodationId" TEXT NOT NULL,
        "name" TEXT NOT NULL,
        "startDate" TIMESTAMP(3) NOT NULL,
        "endDate" TIMESTAMP(3) NOT NULL,
        "nightlyPrice" DECIMAL(65,30) NOT NULL DEFAULT 0.00,
        "minNights" INTEGER,
        "priority" INTEGER NOT NULL DEFAULT 0,
        "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
        "updatedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT "AccommodationSeasonalRate_pkey" PRIMARY KEY ("id"),
        CONSTRAINT "AccommodationSeasonalRate_accommodationId_fkey" FOREIGN KEY ("accommodationId") REFERENCES "Accommodation"("id") ON DELETE CASCADE ON UPDATE CASCADE
      );`
    );
    await prisma.$executeRawUnsafe(
      `CREATE INDEX IF NOT EXISTS "AccommodationSeasonalRate_accommodationId_startDate_endDate_idx"
      ON "AccommodationSeasonalRate"("accommodationId", "startDate", "endDate");`
    );
    await prisma.$executeRawUnsafe(
      `CREATE INDEX IF NOT EXISTS "AccommodationSeasonalRate_accommodationId_priority_idx"
      ON "AccommodationSeasonalRate"("accommodationId", "priority");`
    );

    console.log('Created AccommodationSeasonalRate successfully');
  } catch (e) {
    console.error('Failed to ensure table:', e);
    process.exitCode = 1;
  } finally {
    await prisma.$disconnect();
  }
})();
