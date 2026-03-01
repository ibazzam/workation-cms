-- Add status and resolvedAt to TransportDisruption (safe, non-destructive)
BEGIN;
ALTER TABLE "TransportDisruption"
  ADD COLUMN IF NOT EXISTS "status" text;

ALTER TABLE "TransportDisruption"
  ADD COLUMN IF NOT EXISTS "resolvedAt" timestamptz;

-- Backfill resolvedAt as NULL (no-op) and leave status NULL
COMMIT;
