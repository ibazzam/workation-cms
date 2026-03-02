-- Add eventType to PaymentWebhookEvent (safe, non-destructive)
BEGIN;
ALTER TABLE "PaymentWebhookEvent"
  ADD COLUMN IF NOT EXISTS "eventType" text;
COMMIT;
