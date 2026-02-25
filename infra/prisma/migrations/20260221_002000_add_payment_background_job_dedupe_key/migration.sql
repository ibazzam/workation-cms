-- AlterTable
ALTER TABLE "PaymentBackgroundJob"
ADD COLUMN "dedupeKey" TEXT;

-- CreateIndex
CREATE UNIQUE INDEX "PaymentBackgroundJob_type_dedupeKey_key" ON "PaymentBackgroundJob"("type", "dedupeKey");
