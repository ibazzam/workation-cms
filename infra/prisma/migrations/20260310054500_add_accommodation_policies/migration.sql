-- Add accommodation policy fields (idempotent)
ALTER TABLE public."Accommodation"
  ADD COLUMN IF NOT EXISTS "cancellationPolicy" text,
  ADD COLUMN IF NOT EXISTS "noShowPolicy" text,
  ADD COLUMN IF NOT EXISTS "childrenPolicy" text,
  ADD COLUMN IF NOT EXISTS "taxesAndFeesPolicy" text;
