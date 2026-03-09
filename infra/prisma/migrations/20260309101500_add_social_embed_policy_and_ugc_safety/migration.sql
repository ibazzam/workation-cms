-- Add embed policy and UGC safety controls to SocialLink (idempotent)
ALTER TABLE public."SocialLink"
  ADD COLUMN IF NOT EXISTS "embedPolicy" text NOT NULL DEFAULT 'LINK_ONLY',
  ADD COLUMN IF NOT EXISTS "ugcSafetyStatus" text NOT NULL DEFAULT 'SAFE',
  ADD COLUMN IF NOT EXISTS "ugcSafetyReason" text;

-- Backfill legacy rows defensively
UPDATE public."SocialLink"
SET "embedPolicy" = COALESCE(NULLIF("embedPolicy", ''), 'LINK_ONLY'),
    "ugcSafetyStatus" = COALESCE(NULLIF("ugcSafetyStatus", ''), 'SAFE')
WHERE "embedPolicy" IS NULL OR "ugcSafetyStatus" IS NULL OR "embedPolicy" = '' OR "ugcSafetyStatus" = '';

CREATE INDEX IF NOT EXISTS "SocialLink_targetType_active_verified_ugcSafetyStatus_idx"
  ON public."SocialLink" ("targetType", "active", "verified", "ugcSafetyStatus");
