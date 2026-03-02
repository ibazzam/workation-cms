import pg from "pg";
const { Client } = pg;
(async () => {
  const url = process.env.DATABASE_URL;
  if (!url) { console.error("Set DATABASE_URL first"); process.exit(2); }
  const c = new Client({ connectionString: url, ssl: { rejectUnauthorized: false }});
  await c.connect();
  try {
    const col = await c.query(`
      SELECT column_default, identity_generation
      FROM information_schema.columns
      WHERE table_schema='public' AND table_name='ServiceCategory' AND column_name='id'
    `);
    console.log("column:", col.rows[0] || null);

    const seqRes = await c.query("SELECT pg_get_serial_sequence('\"ServiceCategory\"','id') AS seq");
    const seq = seqRes.rows[0] && seqRes.rows[0].seq;
    console.log("pg_get_serial_sequence:", seq);

    if (seq) {
      const seqInfo = await c.query(`SELECT last_value, is_called FROM ${seq}`);
      console.log("sequence info:", seqInfo.rows[0]);
    } else {
      console.log("No sequence attached (pg_get_serial_sequence returned null).");
    }
  } catch (err) {
    console.error(err);
  } finally {
    await c.end();
  }
})();
