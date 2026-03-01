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
    console.log('Connected. Checking ServiceCategory.scope...');

    const colRes = await client.query(
      `SELECT column_default, is_nullable
       FROM information_schema.columns
       WHERE table_schema='public' AND table_name='ServiceCategory' AND column_name='scope'`);

    const exists = colRes.rows.length > 0;
    if (exists) {
      console.log('ServiceCategory.scope already exists:', colRes.rows[0]);
      process.exit(0);
    }

    console.log('ServiceCategory.scope missing — adding column and populating default.');

    await client.query('BEGIN');
    await client.query('ALTER TABLE "ServiceCategory" ADD COLUMN "scope" TEXT');
    await client.query("UPDATE \"ServiceCategory\" SET \"scope\" = 'BOTH' WHERE \"scope\" IS NULL");
    await client.query('ALTER TABLE "ServiceCategory" ALTER COLUMN "scope" SET NOT NULL');
    await client.query('COMMIT');

    console.log('Added ServiceCategory.scope and set NOT NULL with default values.');
  } catch (err) {
    try { await client.query('ROLLBACK'); } catch (e) {}
    console.error('Error ensuring ServiceCategory.scope:', err);
    process.exit(3);
  } finally {
    await client.end();
  }
}

run();
