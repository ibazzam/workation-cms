/*
  Warnings:

  - You are about to drop the column `minStayNights` on the `Accommodation` table. All the data in the column will be lost.
  - You are about to drop the column `holdExpiresAt` on the `Booking` table. All the data in the column will be lost.
  - You are about to drop the column `transportFareClassCode` on the `Booking` table. All the data in the column will be lost.
  - You are about to drop the `AccommodationBlackout` table. If the table is not empty, all the data it contains will be lost.
  - You are about to drop the `AccommodationSeasonalRate` table. If the table is not empty, all the data it contains will be lost.
  - You are about to drop the `AdminAuditLog` table. If the table is not empty, all the data it contains will be lost.
  - You are about to drop the `AppConfig` table. If the table is not empty, all the data it contains will be lost.
  - You are about to drop the `Country` table. If the table is not empty, all the data it contains will be lost.
  - You are about to drop the `LoyaltyAccount` table. If the table is not empty, all the data it contains will be lost.
  - You are about to drop the `LoyaltyTransaction` table. If the table is not empty, all the data it contains will be lost.
  - You are about to drop the `PaymentBackgroundJob` table. If the table is not empty, all the data it contains will be lost.
  - You are about to drop the `PaymentReconciliationRun` table. If the table is not empty, all the data it contains will be lost.
  - You are about to drop the `PaymentWebhookEvent` table. If the table is not empty, all the data it contains will be lost.
  - You are about to drop the `Review` table. If the table is not empty, all the data it contains will be lost.
  - You are about to drop the `ServiceCategory` table. If the table is not empty, all the data it contains will be lost.
  - You are about to drop the `SocialLink` table. If the table is not empty, all the data it contains will be lost.
  - You are about to drop the `TransportDisruption` table. If the table is not empty, all the data it contains will be lost.
  - You are about to drop the `TransportFareClass` table. If the table is not empty, all the data it contains will be lost.
  - You are about to drop the `VendorLoyaltyOffer` table. If the table is not empty, all the data it contains will be lost.
  - You are about to drop the `workations` table. If the table is not empty, all the data it contains will be lost.

*/
-- DropForeignKey
ALTER TABLE "AccommodationBlackout" DROP CONSTRAINT "AccommodationBlackout_accommodationId_fkey";

-- DropForeignKey
ALTER TABLE "AccommodationSeasonalRate" DROP CONSTRAINT "AccommodationSeasonalRate_accommodationId_fkey";

-- DropForeignKey
ALTER TABLE "LoyaltyAccount" DROP CONSTRAINT "LoyaltyAccount_userId_fkey";

-- DropForeignKey
ALTER TABLE "LoyaltyTransaction" DROP CONSTRAINT "LoyaltyTransaction_bookingId_fkey";

-- DropForeignKey
ALTER TABLE "LoyaltyTransaction" DROP CONSTRAINT "LoyaltyTransaction_userId_fkey";

-- DropForeignKey
ALTER TABLE "Review" DROP CONSTRAINT "Review_accommodationId_fkey";

-- DropForeignKey
ALTER TABLE "Review" DROP CONSTRAINT "Review_transportId_fkey";

-- DropForeignKey
ALTER TABLE "Review" DROP CONSTRAINT "Review_userId_fkey";

-- DropForeignKey
ALTER TABLE "SocialLink" DROP CONSTRAINT "SocialLink_accommodationId_fkey";

-- DropForeignKey
ALTER TABLE "SocialLink" DROP CONSTRAINT "SocialLink_transportId_fkey";

-- DropForeignKey
ALTER TABLE "SocialLink" DROP CONSTRAINT "SocialLink_vendorId_fkey";

-- DropForeignKey
ALTER TABLE "TransportDisruption" DROP CONSTRAINT "TransportDisruption_replacementTransportId_fkey";

-- DropForeignKey
ALTER TABLE "TransportDisruption" DROP CONSTRAINT "TransportDisruption_transportId_fkey";

-- DropForeignKey
ALTER TABLE "TransportFareClass" DROP CONSTRAINT "TransportFareClass_transportId_fkey";

-- DropForeignKey
ALTER TABLE "VendorLoyaltyOffer" DROP CONSTRAINT "VendorLoyaltyOffer_vendorId_fkey";

-- DropIndex
DROP INDEX "Booking_status_holdExpiresAt_idx";

-- AlterTable
ALTER TABLE "Accommodation" DROP COLUMN "minStayNights",
ALTER COLUMN "price" SET DATA TYPE DECIMAL(65,30);

-- AlterTable
ALTER TABLE "Booking" DROP COLUMN "holdExpiresAt",
DROP COLUMN "transportFareClassCode",
ALTER COLUMN "totalPrice" SET DATA TYPE DECIMAL(65,30);

-- AlterTable
ALTER TABLE "Payment" ALTER COLUMN "amount" SET DATA TYPE DECIMAL(65,30);

-- AlterTable
ALTER TABLE "Transport" ALTER COLUMN "price" SET DATA TYPE DECIMAL(65,30);

-- DropTable
DROP TABLE "AccommodationBlackout";

-- DropTable
DROP TABLE "AccommodationSeasonalRate";

-- DropTable
DROP TABLE "AdminAuditLog";

-- DropTable
DROP TABLE "AppConfig";

-- DropTable
DROP TABLE "Country";

-- DropTable
DROP TABLE "LoyaltyAccount";

-- DropTable
DROP TABLE "LoyaltyTransaction";

-- DropTable
DROP TABLE "PaymentBackgroundJob";

-- DropTable
DROP TABLE "PaymentReconciliationRun";

-- DropTable
DROP TABLE "PaymentWebhookEvent";

-- DropTable
DROP TABLE "Review";

-- DropTable
DROP TABLE "ServiceCategory";

-- DropTable
DROP TABLE "SocialLink";

-- DropTable
DROP TABLE "TransportDisruption";

-- DropTable
DROP TABLE "TransportFareClass";

-- DropTable
DROP TABLE "VendorLoyaltyOffer";

-- DropTable
DROP TABLE "workations";
