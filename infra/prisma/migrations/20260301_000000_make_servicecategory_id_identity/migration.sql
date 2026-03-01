-- Convert ServiceCategory.id from SERIAL to IDENTITY if not already
BEGIN;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'ServiceCategory'
      AND column_name = 'id'
      AND identity_generation IS NOT NULL
  ) THEN
    -- Drop existing default (nextval from SERIAL) if present
    ALTER TABLE "ServiceCategory" ALTER COLUMN "id" DROP DEFAULT;

    -- Add GENERATED ALWAYS AS IDENTITY (Postgres 10+)
    ALTER TABLE "ServiceCategory" ALTER COLUMN "id" ADD GENERATED ALWAYS AS IDENTITY;
  END IF;
END$$;

COMMIT;
