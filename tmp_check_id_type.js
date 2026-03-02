import pg from 'pg';
const { Client } = pg;
(async()=>{
  const c=new Client({connectionString:process.env.DATABASE_URL, ssl:{rejectUnauthorized:false}});
  await c.connect();
  const r=await c.query("SELECT data_type, udt_name, column_default FROM information_schema.columns WHERE table_schema='public' AND table_name='ServiceCategory' AND column_name='id'");
  console.log(r.rows);
  await c.end();
})();
