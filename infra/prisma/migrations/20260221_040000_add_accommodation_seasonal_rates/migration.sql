CREATE TABLE "AccommodationSeasonalRate" (
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
);

CREATE INDEX "AccommodationSeasonalRate_accommodationId_startDate_endDate_idx"
ON "AccommodationSeasonalRate"("accommodationId", "startDate", "endDate");

CREATE INDEX "AccommodationSeasonalRate_accommodationId_priority_idx"
ON "AccommodationSeasonalRate"("accommodationId", "priority");
