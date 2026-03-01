ALTER TABLE "Booking"
  ADD COLUMN "holdExpiresAt" TIMESTAMP(3);

CREATE INDEX "Booking_status_holdExpiresAt_idx"
  ON "Booking"("status", "holdExpiresAt");
