-- Idempotent migration: ensure ServiceCategory.scope exists and is NOT NULL
BEGIN;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'ServiceCategory'
      AND column_name = 'scope'
  ) THEN
    -- Add column nullable, populate with safe default, then set NOT NULL
    ALTER TABLE "ServiceCategory" ADD COLUMN "scope" TEXT;
    UPDATE "ServiceCategory" SET "scope" = 'BOTH' WHERE "scope" IS NULL;
    ALTER TABLE "ServiceCategory" ALTER COLUMN "scope" SET NOT NULL;
    RAISE NOTICE 'Added ServiceCategory.scope column and populated defaults.';
  ELSE
    RAISE NOTICE 'ServiceCategory.scope already exists; skipping.';
  END IF;
END$$;

COMMIT;
