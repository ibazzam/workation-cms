-- Add fare class support for domestic flights
ALTER TABLE "Booking"
ADD COLUMN "transportFareClassCode" TEXT;

CREATE TABLE "TransportFareClass" (
  "id" TEXT NOT NULL,
  "transportId" TEXT NOT NULL,
  "code" TEXT NOT NULL,
  "name" TEXT NOT NULL,
  "baggageKg" INTEGER,
  "seats" INTEGER,
  "price" DECIMAL(65,30) NOT NULL DEFAULT 0.00,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT "TransportFareClass_pkey" PRIMARY KEY ("id")
);

CREATE UNIQUE INDEX "TransportFareClass_transportId_code_key"
ON "TransportFareClass"("transportId", "code");

CREATE INDEX "TransportFareClass_transportId_idx"
ON "TransportFareClass"("transportId");

ALTER TABLE "TransportFareClass"
ADD CONSTRAINT "TransportFareClass_transportId_fkey"
FOREIGN KEY ("transportId") REFERENCES "Transport"("id")
ON DELETE RESTRICT ON UPDATE CASCADE;
