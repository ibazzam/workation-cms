import pg from 'pg';
const { Client } = pg;

async function run() {
  const databaseUrl = process.env.DATABASE_URL;
  if (!databaseUrl) {
    console.error('Missing DATABASE_URL env var');
    process.exit(2);
  }

  const client = new Client({ connectionString: databaseUrl, ssl: { rejectUnauthorized: false } });
  await client.connect();
  try {
    console.log('Connected. Checking ServiceCategory.id...');

    const colRes = await client.query(
      `SELECT column_default, identity_generation
       FROM information_schema.columns
       WHERE table_schema='public' AND table_name='ServiceCategory' AND column_name='id'`);

    const row = colRes.rows[0] || {};
    console.log('information_schema result:', row);

    const hasIdentity = row.identity_generation !== null;
    const hasDefault = row.column_default !== null;

    if (hasIdentity || hasDefault) {
      console.log('ServiceCategory.id already has identity or default — nothing to change.');
    } else {
      console.log('No identity/default found — applying safe sequence + default.');

      await client.query('BEGIN');

      // create sequence if missing
      await client.query("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_class WHERE relkind='S' AND relname='servicecategory_id_seq') THEN CREATE SEQUENCE servicecategory_id_seq; END IF; END$$;");

      // set sequence to max(id) (cast id to bigint to handle text columns)
      await client.query("SELECT setval('servicecategory_id_seq', COALESCE((SELECT MAX(\"id\")::bigint FROM \"ServiceCategory\"), 0) + 1, false);");

      // attach as default
      await client.query("ALTER TABLE \"ServiceCategory\" ALTER COLUMN \"id\" SET DEFAULT nextval('servicecategory_id_seq')");

      // set ownership
      await client.query("ALTER SEQUENCE servicecategory_id_seq OWNED BY \"ServiceCategory\".\"id\"");

      await client.query('COMMIT');
      console.log('Sequence created/attached and default set.');
    }

    // Show updated column info
    const after = await client.query(
      `SELECT column_default, identity_generation
       FROM information_schema.columns
       WHERE table_schema='public' AND table_name='ServiceCategory' AND column_name='id'`);
    console.log('After change:', after.rows[0]);

    // Determine the actual sequence name (if any) associated with the id column
    const seqNameRes = await client.query("SELECT pg_get_serial_sequence('\"ServiceCategory\"','id') AS serial_seq");
    const seqName = seqNameRes.rows[0] && seqNameRes.rows[0].serial_seq;
    console.log('pg_get_serial_sequence:', seqName);

    if (seqName) {
      console.log('Setting sequence to MAX(id) to avoid conflicts and ensuring ownership.');
      const maxRes = await client.query('SELECT COALESCE(MAX("id"::bigint), NULL) as max_id FROM "ServiceCategory"');
      const maxId = maxRes.rows[0] && maxRes.rows[0].max_id;

      if (maxId && Number(maxId) >= 1) {
        // set to MAX(id)+1 and mark as not called so nextval returns MAX(id)+1
        await client.query('SELECT setval($1, $2, false)', [seqName, Number(maxId) + 1]);
      } else {
        // empty table: set sequence to 1 and mark as not called so nextval returns 1
        await client.query('SELECT setval($1, $2, false)', [seqName, 1]);
      }

      // Safely quote the sequence identifier (may include schema)
      function quoteIdentFromPgGetSerial(name) {
        // name can be like public.seq or "public"."Seq" — normalize by removing outer quotes then quote each part
        const parts = name.split('.');
        return parts.map(p => {
          const stripped = p.replace(/^"(.*)"$/, '$1');
          const doubled = stripped.replace(/"/g, '""');
          return `"${doubled}"`;
        }).join('.');
      }

      const quotedSeq = quoteIdentFromPgGetSerial(seqName);
      await client.query(`ALTER SEQUENCE ${quotedSeq} OWNED BY "ServiceCategory"."id"`);

      const seqVal = await client.query('SELECT nextval($1) as v', [seqName]);
      console.log('sample nextval from sequence:', seqVal.rows[0]);
    } else {
      console.log('No associated sequence found via pg_get_serial_sequence().');
    }

  } catch (err) {
    try { await client.query('ROLLBACK'); } catch (e) {}
    console.error('Error running SQL fix:', err);
    process.exit(3);
  } finally {
    await client.end();
  }
}

run();
