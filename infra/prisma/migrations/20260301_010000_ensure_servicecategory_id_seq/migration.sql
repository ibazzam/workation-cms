-- Ensure ServiceCategory.id has an auto-incrementing default (sequence)
BEGIN;

DO $$
BEGIN
  -- If the column has an identity or a default, nothing to do
  IF EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'ServiceCategory'
      AND column_name = 'id'
      AND (identity_generation IS NOT NULL OR column_default IS NOT NULL)
  ) THEN
    RAISE NOTICE 'ServiceCategory.id already has identity or default; skipping.';
  ELSE
    -- Create a sequence if it doesn't exist
    IF NOT EXISTS (SELECT 1 FROM pg_class WHERE relkind = 'S' AND relname = 'servicecategory_id_seq') THEN
      CREATE SEQUENCE servicecategory_id_seq;
    END IF;

    -- Set sequence value to max(id) so nextval will be higher than existing rows
    PERFORM setval('servicecategory_id_seq', COALESCE((SELECT MAX("id") FROM "ServiceCategory"), 0));

    -- Attach sequence as default for the id column
    EXECUTE 'ALTER TABLE "ServiceCategory" ALTER COLUMN "id" SET DEFAULT nextval(''servicecategory_id_seq'')';

    -- Ensure sequence ownership is set to the column (best-effort)
    EXECUTE 'ALTER SEQUENCE servicecategory_id_seq OWNED BY "ServiceCategory"."id"';
    RAISE NOTICE 'ServiceCategory.id default set to nextval(servicecategory_id_seq)';
  END IF;
END
$$;

COMMIT;
