import pg from 'pg';
const { Client } = pg;
import fs from 'fs';
import path from 'path';

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
    const diagnostics = {};

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

    // Determine an existing sequence associated with the ServiceCategory.id column (if any)
    // This looks up sequences which are linked via dependency to the table column.
    const seqLookup = await client.query(
      `SELECT n.nspname AS seq_schema, c.relname AS seq_name
       FROM pg_class c
       JOIN pg_namespace n ON n.oid = c.relnamespace
       JOIN pg_depend d ON d.objid = c.oid
       JOIN pg_class t ON d.refobjid = t.oid
       WHERE c.relkind = 'S' AND t.relname = 'ServiceCategory' LIMIT 1;`
    );
    const seqRow = seqLookup.rows[0];
    let seqName = null;
    if (seqRow) {
      seqName = seqRow.seq_schema ? `${seqRow.seq_schema}.${seqRow.seq_name}` : seqRow.seq_name;
    }
    console.log('discovered sequence for ServiceCategory.id:', seqName);

    if (seqName) {
      console.log('Setting sequence to MAX(id)+1 to avoid conflicts and ensuring ownership.');
      const maxRes = await client.query('SELECT COALESCE(MAX("id"::bigint), NULL) as max_id FROM "ServiceCategory"');
      const maxId = maxRes.rows[0] && maxRes.rows[0].max_id;

      const desired = (maxId && Number(maxId) >= 1) ? Number(maxId) + 1 : 1;

      // Use fully qualified sequence name as parameterized identifier isn't supported for NEXTVAL/setval calls with schema
      // We'll call setval using the text name and then ensure ownership via ALTER SEQUENCE with quoted identifiers.
      await client.query('SELECT setval($1, $2, false)', [seqName, desired]);

      // Quote identifiers safely for ALTER SEQUENCE
      const parts = seqName.split('.');
      const quoted = parts.map(p => `"${p.replace(/"/g, '""')}"`).join('.');
      await client.query(`ALTER SEQUENCE ${quoted} OWNED BY "ServiceCategory"."id"`);

      const seqVal = await client.query('SELECT nextval($1) as v', [seqName]);
      console.log('sample nextval from sequence:', seqVal.rows[0]);
      diagnostics.sample_nextval = seqVal.rows[0] || null;
      } else {
        console.log('No sequence discovered for ServiceCategory.id. Consider creating one or allowing migrations to handle it.');
        diagnostics.sample_nextval = null;
      }

    // Write diagnostics file to infra/backend/artifacts/diagnostics if possible
    try {
      const outDir = path.resolve(process.cwd(), 'infra', 'backend', 'artifacts', 'diagnostics');
      fs.mkdirSync(outDir, { recursive: true });
      const outPath = path.join(outDir, 'servicecategory_id_check.json');
      const payload = {
        information_schema: row || null,
        after_column: (after && after.rows && after.rows[0]) || null,
        discovered_sequence: seqName || null,
        diagnostics: diagnostics || null,
        timestamp: new Date().toISOString(),
      };
      fs.writeFileSync(outPath, JSON.stringify(payload, null, 2));
      console.log('Wrote diagnostics to', outPath);
    } catch (e) {
      console.warn('Failed to write diagnostics file:', e && e.message);
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
