-- Add source to Payment and PaymentWebhookEvent (safe, non-destructive)
BEGIN;
ALTER TABLE "Payment"
  ADD COLUMN IF NOT EXISTS "source" text;

ALTER TABLE "PaymentWebhookEvent"
  ADD COLUMN IF NOT EXISTS "source" text;
COMMIT;
