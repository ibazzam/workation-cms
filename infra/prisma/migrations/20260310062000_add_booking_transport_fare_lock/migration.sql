-- Add booking transport fare lock fields (idempotent)
ALTER TABLE public."Booking"
  ADD COLUMN IF NOT EXISTS "fareLockUnitPrice" numeric(65,30),
  ADD COLUMN IF NOT EXISTS "fareLockTotalPrice" numeric(65,30),
  ADD COLUMN IF NOT EXISTS "fareLockCurrency" text,
  ADD COLUMN IF NOT EXISTS "fareLockExpiresAt" timestamp(3);
