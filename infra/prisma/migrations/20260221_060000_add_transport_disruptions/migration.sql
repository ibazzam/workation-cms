CREATE TABLE "TransportDisruption" (
  "id" TEXT NOT NULL,
  "transportId" TEXT NOT NULL,
  "status" TEXT NOT NULL,
  "reason" TEXT,
  "delayMinutes" INTEGER,
  "replacementTransportId" TEXT,
  "startsAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "resolvedAt" TIMESTAMP(3),
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT "TransportDisruption_pkey" PRIMARY KEY ("id")
);

CREATE INDEX "TransportDisruption_transportId_resolvedAt_idx"
ON "TransportDisruption"("transportId", "resolvedAt");

CREATE INDEX "TransportDisruption_replacementTransportId_idx"
ON "TransportDisruption"("replacementTransportId");

ALTER TABLE "TransportDisruption"
ADD CONSTRAINT "TransportDisruption_transportId_fkey"
FOREIGN KEY ("transportId") REFERENCES "Transport"("id")
ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE "TransportDisruption"
ADD CONSTRAINT "TransportDisruption_replacementTransportId_fkey"
FOREIGN KEY ("replacementTransportId") REFERENCES "Transport"("id")
ON DELETE SET NULL ON UPDATE CASCADE;
