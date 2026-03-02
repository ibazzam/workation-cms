-- Recreate `workations` table if missing to satisfy contract tests
-- This migration uses IF NOT EXISTS to avoid errors on repeated runs

CREATE TABLE IF NOT EXISTS "workations" (
  "id" SERIAL PRIMARY KEY,
  "title" TEXT NOT NULL,
  "description" TEXT,
  "location" TEXT NOT NULL,
  "start_date" TIMESTAMP(3) NOT NULL,
  "end_date" TIMESTAMP(3) NOT NULL,
  "price" DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updatedAt" TIMESTAMP(3) NOT NULL
);

-- Ensure `actorEmail` exists on AdminAuditLog (some CI DB snapshots lacked it)
ALTER TABLE IF EXISTS "AdminAuditLog"
  ADD COLUMN IF NOT EXISTS "actorEmail" TEXT;
