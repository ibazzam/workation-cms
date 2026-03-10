-- Expand social links trust/safety controls and content quality tooling (idempotent)
ALTER TABLE public."SocialLink"
  ADD COLUMN IF NOT EXISTS "trustSafetyStatus" text NOT NULL DEFAULT 'CLEAR',
  ADD COLUMN IF NOT EXISTS "moderationReasonCode" text,
  ADD COLUMN IF NOT EXISTS "moderationReviewerNote" text,
  ADD COLUMN IF NOT EXISTS "flaggedCount" integer NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS "lastFlaggedAt" timestamp(3),
  ADD COLUMN IF NOT EXISTS "escalatedToQueue" text,
  ADD COLUMN IF NOT EXISTS "escalatedAt" timestamp(3),
  ADD COLUMN IF NOT EXISTS "actionedAt" timestamp(3),
  ADD COLUMN IF NOT EXISTS "contentQualityStatus" text NOT NULL DEFAULT 'GOOD',
  ADD COLUMN IF NOT EXISTS "contentQualityScore" integer NOT NULL DEFAULT 100,
  ADD COLUMN IF NOT EXISTS "contentQualityNotes" text,
  ADD COLUMN IF NOT EXISTS "qualityReviewedAt" timestamp(3);

CREATE INDEX IF NOT EXISTS "SocialLink_trustSafetyStatus_contentQualityStatus_idx"
  ON public."SocialLink" ("trustSafetyStatus", "contentQualityStatus");
