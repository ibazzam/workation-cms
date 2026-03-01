ALTER TABLE "Accommodation"
ADD COLUMN "minStayNights" INTEGER NOT NULL DEFAULT 1;

CREATE TABLE "AccommodationBlackout" (
  "id" TEXT NOT NULL,
  "accommodationId" TEXT NOT NULL,
  "startDate" TIMESTAMP(3) NOT NULL,
  "endDate" TIMESTAMP(3) NOT NULL,
  "reason" TEXT,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3) NOT NULL,
  CONSTRAINT "AccommodationBlackout_pkey" PRIMARY KEY ("id"),
  CONSTRAINT "AccommodationBlackout_accommodationId_fkey" FOREIGN KEY ("accommodationId") REFERENCES "Accommodation"("id") ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX "AccommodationBlackout_accommodationId_startDate_endDate_idx"
  ON "AccommodationBlackout"("accommodationId", "startDate", "endDate");
