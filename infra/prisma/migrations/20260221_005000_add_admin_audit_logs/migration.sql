CREATE TABLE IF NOT EXISTS "AdminAuditLog" (
  "id" TEXT PRIMARY KEY,
  "actorUserId" TEXT,
  "actorRole" TEXT,
  "actorEmail" TEXT,
  "actorVendorId" TEXT,
  "method" TEXT NOT NULL,
  "path" TEXT NOT NULL,
  "statusCode" INTEGER NOT NULL,
  "success" BOOLEAN NOT NULL,
  "requestBody" TEXT,
  "errorMessage" TEXT,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "AdminAuditLog_actorRole_createdAt_idx"
  ON "AdminAuditLog"("actorRole", "createdAt");

CREATE INDEX IF NOT EXISTS "AdminAuditLog_method_createdAt_idx"
  ON "AdminAuditLog"("method", "createdAt");
