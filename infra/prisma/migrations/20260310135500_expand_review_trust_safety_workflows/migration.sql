-- Expand review trust/safety workflows with persisted moderation state.

ALTER TABLE public."Review"
  ADD COLUMN IF NOT EXISTS "trustSafetyStatus" text NOT NULL DEFAULT 'CLEAR',
  ADD COLUMN IF NOT EXISTS "moderationReasonCode" text,
  ADD COLUMN IF NOT EXISTS "moderationReviewerNote" text,
  ADD COLUMN IF NOT EXISTS "flaggedCount" integer NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS "lastFlaggedAt" timestamp(3),
  ADD COLUMN IF NOT EXISTS "escalatedToQueue" text,
  ADD COLUMN IF NOT EXISTS "escalatedAt" timestamp(3),
  ADD COLUMN IF NOT EXISTS "actionedAt" timestamp(3);

CREATE INDEX IF NOT EXISTS "Review_status_trustSafetyStatus_idx"
  ON public."Review" ("status", "trustSafetyStatus");
