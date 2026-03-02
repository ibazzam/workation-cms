#!/usr/bin/env node
import fs from 'fs';
import path from 'path';
import { spawnSync } from 'child_process';
import pg from 'pg';
const { Client } = pg;

async function applyMigrationSql(migrationPath, databaseUrl) {
  const sql = fs.readFileSync(migrationPath, 'utf8');
  const client = new Client({ connectionString: databaseUrl, ssl: { rejectUnauthorized: false } });
  await client.connect();
  try {
    console.log('Applying migration SQL from', migrationPath);
    // Execute whole file; it contains idempotent CREATE / DO blocks
    await client.query(sql);
    console.log('Migration SQL applied (or was already idempotent).');
  } finally {
    await client.end();
  }
}

function runDbFixScript() {
  console.log('Running existing db_fix_servicecategory.js script...');
  const res = spawnSync(process.execPath, [path.join('scripts', 'db_fix_servicecategory.js')], {
    stdio: 'inherit',
    env: { ...process.env },
  });
  if (res.error) {
    console.error('Failed to run db_fix_servicecategory.js:', res.error);
    process.exit(2);
  }
  if (res.status !== 0) {
    console.warn('db_fix_servicecategory.js exited with status', res.status);
  }
}

async function main() {
  const databaseUrl = process.env.DATABASE_URL;
  if (!databaseUrl) {
    console.error('Missing DATABASE_URL');
    process.exit(1);
  }

  const migrationPath = path.resolve(process.cwd(), 'infra', 'prisma', 'migrations', '20260302074730_add_servicecategory', 'migration.sql');
  if (!fs.existsSync(migrationPath)) {
    console.error('Migration SQL not found at', migrationPath);
    process.exit(2);
  }

  try {
    await applyMigrationSql(migrationPath, databaseUrl);
  } catch (e) {
    console.error('Error applying migration SQL:', e && e.message);
    process.exit(3);
  }

  // Now run runtime fix to ensure sequence/default is attached
  runDbFixScript();

  console.log('ensure_servicecategory finished.');
}

main();
