-- Recreate minimal tables that were removed by a prior migration to unblock CI
-- This migration is intentionally minimal: columns required by tests/code are included,
-- foreign keys omitted to avoid ordering/constraint issues in CI environments.

CREATE TABLE IF NOT EXISTS "LoyaltyAccount" (
  "id" TEXT PRIMARY KEY,
  "userId" TEXT NOT NULL,
  "balance" NUMERIC DEFAULT 0,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3)
);

CREATE TABLE IF NOT EXISTS "LoyaltyTransaction" (
  "id" TEXT PRIMARY KEY,
  "userId" TEXT NOT NULL,
  "bookingId" TEXT,
  "amount" NUMERIC DEFAULT 0,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "VendorLoyaltyOffer" (
  "id" TEXT PRIMARY KEY,
  "vendorId" TEXT NOT NULL,
  "active" BOOLEAN DEFAULT true,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "Review" (
  "id" TEXT PRIMARY KEY,
  "userId" TEXT NOT NULL,
  "accommodationId" TEXT,
  "transportId" TEXT,
  "rating" INTEGER NOT NULL,
  "body" TEXT,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3)
);

CREATE TABLE IF NOT EXISTS "SocialLink" (
  "id" TEXT PRIMARY KEY,
  "vendorId" TEXT,
  "accommodationId" TEXT,
  "transportId" TEXT,
  "url" TEXT NOT NULL,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3)
);

CREATE TABLE IF NOT EXISTS "PaymentBackgroundJob" (
  "id" TEXT PRIMARY KEY,
  "name" TEXT,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "PaymentReconciliationRun" (
  "id" TEXT PRIMARY KEY,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "PaymentWebhookEvent" (
  "id" TEXT PRIMARY KEY,
  "provider" TEXT,
  "payload" TEXT,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "TransportDisruption" (
  "id" TEXT PRIMARY KEY,
  "transportId" TEXT NOT NULL,
  "replacementTransportId" TEXT,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "TransportFareClass" (
  "id" TEXT PRIMARY KEY,
  "transportId" TEXT NOT NULL,
  "code" TEXT,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "AppConfig" (
  "key" TEXT PRIMARY KEY,
  "value" TEXT,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "Country" (
  "id" SERIAL PRIMARY KEY,
  "name" TEXT NOT NULL,
  "code" TEXT UNIQUE NOT NULL,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3)
);

CREATE TABLE IF NOT EXISTS "ServiceCategory" (
  "id" TEXT PRIMARY KEY,
  "name" TEXT NOT NULL,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3)
);

-- AdminAuditLog (recreate if missing)
CREATE TABLE IF NOT EXISTS "AdminAuditLog" (
  "id" TEXT PRIMARY KEY,
  "actorUserId" TEXT,
  "actorRole" TEXT,
  "actorEmail" TEXT,
  "actorVendorId" TEXT,
  "method" TEXT NOT NULL,
  "path" TEXT NOT NULL,
  "statusCode" INTEGER NOT NULL DEFAULT 0,
  "success" BOOLEAN NOT NULL DEFAULT false,
  "requestBody" TEXT,
  "errorMessage" TEXT,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- End of minimal recreate migration
