-- Add activity/service target refs for Review model (idempotent)
ALTER TABLE public."Review"
  ADD COLUMN IF NOT EXISTS "activityRefId" text,
  ADD COLUMN IF NOT EXISTS "serviceRefId" text;

CREATE INDEX IF NOT EXISTS "Review_targetType_activityRefId_idx"
  ON public."Review" ("targetType", "activityRefId");

CREATE INDEX IF NOT EXISTS "Review_targetType_serviceRefId_idx"
  ON public."Review" ("targetType", "serviceRefId");

CREATE INDEX IF NOT EXISTS "Review_userId_targetType_activityRefId_idx"
  ON public."Review" ("userId", "targetType", "activityRefId");

CREATE INDEX IF NOT EXISTS "Review_userId_targetType_serviceRefId_idx"
  ON public."Review" ("userId", "targetType", "serviceRefId");
