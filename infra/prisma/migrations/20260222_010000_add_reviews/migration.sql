CREATE TABLE "Review" (
  "id" TEXT NOT NULL,
  "userId" TEXT NOT NULL,
  "targetType" TEXT NOT NULL,
  "accommodationId" TEXT,
  "transportId" TEXT,
  "rating" INTEGER NOT NULL,
  "title" TEXT,
  "comment" TEXT,
  "verifiedStay" BOOLEAN NOT NULL DEFAULT false,
  "status" TEXT NOT NULL DEFAULT 'PUBLISHED',
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT "Review_pkey" PRIMARY KEY ("id"),
  CONSTRAINT "Review_userId_fkey" FOREIGN KEY ("userId") REFERENCES "User"("id") ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT "Review_accommodationId_fkey" FOREIGN KEY ("accommodationId") REFERENCES "Accommodation"("id") ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT "Review_transportId_fkey" FOREIGN KEY ("transportId") REFERENCES "Transport"("id") ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX "Review_targetType_accommodationId_idx" ON "Review"("targetType", "accommodationId");
CREATE INDEX "Review_targetType_transportId_idx" ON "Review"("targetType", "transportId");
CREATE INDEX "Review_userId_targetType_accommodationId_idx" ON "Review"("userId", "targetType", "accommodationId");
CREATE INDEX "Review_userId_targetType_transportId_idx" ON "Review"("userId", "targetType", "transportId");
