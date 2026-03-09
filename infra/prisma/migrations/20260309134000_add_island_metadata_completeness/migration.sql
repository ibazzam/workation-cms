-- Add island metadata completeness fields (idempotent)
ALTER TABLE public."Island"
  ADD COLUMN IF NOT EXISTS "facilitiesSummary" text,
  ADD COLUMN IF NOT EXISTS "connectivitySummary" text,
  ADD COLUMN IF NOT EXISTS "emergencyServicesInfo" text;
