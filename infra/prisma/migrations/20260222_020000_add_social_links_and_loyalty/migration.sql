CREATE TABLE "SocialLink" (
  "id" TEXT NOT NULL,
  "targetType" TEXT NOT NULL,
  "accommodationId" TEXT,
  "transportId" TEXT,
  "vendorId" TEXT,
  "platform" TEXT NOT NULL,
  "url" TEXT NOT NULL,
  "handle" TEXT,
  "verified" BOOLEAN NOT NULL DEFAULT false,
  "displayOrder" INTEGER NOT NULL DEFAULT 0,
  "active" BOOLEAN NOT NULL DEFAULT true,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT "SocialLink_pkey" PRIMARY KEY ("id"),
  CONSTRAINT "SocialLink_accommodationId_fkey" FOREIGN KEY ("accommodationId") REFERENCES "Accommodation"("id") ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT "SocialLink_transportId_fkey" FOREIGN KEY ("transportId") REFERENCES "Transport"("id") ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT "SocialLink_vendorId_fkey" FOREIGN KEY ("vendorId") REFERENCES "Vendor"("id") ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX "SocialLink_targetType_accommodationId_active_idx" ON "SocialLink"("targetType", "accommodationId", "active");
CREATE INDEX "SocialLink_targetType_transportId_active_idx" ON "SocialLink"("targetType", "transportId", "active");
CREATE INDEX "SocialLink_targetType_vendorId_active_idx" ON "SocialLink"("targetType", "vendorId", "active");

CREATE TABLE "LoyaltyAccount" (
  "userId" TEXT NOT NULL,
  "pointsBalance" INTEGER NOT NULL DEFAULT 0,
  "lifetimePoints" INTEGER NOT NULL DEFAULT 0,
  "tier" TEXT NOT NULL DEFAULT 'BRONZE',
  "updatedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT "LoyaltyAccount_pkey" PRIMARY KEY ("userId"),
  CONSTRAINT "LoyaltyAccount_userId_fkey" FOREIGN KEY ("userId") REFERENCES "User"("id") ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE "LoyaltyTransaction" (
  "id" TEXT NOT NULL,
  "userId" TEXT NOT NULL,
  "bookingId" TEXT,
  "type" TEXT NOT NULL,
  "points" INTEGER NOT NULL,
  "amount" DECIMAL(65,30) DEFAULT 0.00,
  "currency" TEXT,
  "description" TEXT,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT "LoyaltyTransaction_pkey" PRIMARY KEY ("id"),
  CONSTRAINT "LoyaltyTransaction_userId_fkey" FOREIGN KEY ("userId") REFERENCES "User"("id") ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT "LoyaltyTransaction_bookingId_fkey" FOREIGN KEY ("bookingId") REFERENCES "Booking"("id") ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX "LoyaltyTransaction_userId_createdAt_idx" ON "LoyaltyTransaction"("userId", "createdAt");
CREATE INDEX "LoyaltyTransaction_bookingId_type_idx" ON "LoyaltyTransaction"("bookingId", "type");

CREATE TABLE "VendorLoyaltyOffer" (
  "id" TEXT NOT NULL,
  "vendorId" TEXT NOT NULL,
  "title" TEXT NOT NULL,
  "description" TEXT,
  "pointsMultiplier" DECIMAL(65,30) NOT NULL DEFAULT 1.00,
  "discountPercent" INTEGER NOT NULL DEFAULT 0,
  "active" BOOLEAN NOT NULL DEFAULT true,
  "startsAt" TIMESTAMP(3),
  "endsAt" TIMESTAMP(3),
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT "VendorLoyaltyOffer_pkey" PRIMARY KEY ("id"),
  CONSTRAINT "VendorLoyaltyOffer_vendorId_fkey" FOREIGN KEY ("vendorId") REFERENCES "Vendor"("id") ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX "VendorLoyaltyOffer_vendorId_active_idx" ON "VendorLoyaltyOffer"("vendorId", "active");
