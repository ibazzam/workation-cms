-- Add accommodation content moderation fields (idempotent)
ALTER TABLE public."Accommodation"
  ADD COLUMN IF NOT EXISTS "contentModerationStatus" text DEFAULT 'APPROVED',
  ADD COLUMN IF NOT EXISTS "contentModerationReason" text,
  ADD COLUMN IF NOT EXISTS "contentModeratedAt" timestamp(3);

UPDATE public."Accommodation"
SET "contentModerationStatus" = 'APPROVED'
WHERE "contentModerationStatus" IS NULL;

ALTER TABLE public."Accommodation"
  ALTER COLUMN "contentModerationStatus" SET DEFAULT 'APPROVED';
