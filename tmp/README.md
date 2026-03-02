# Helper scripts for deploy, seeding, and backups

This folder contains small helper scripts used during troubleshooting, seeding and backups for the deployed backend.

Scripts
- `insert_islands.js` — directly inserts an Atoll and Island into the Postgres DB using `DATABASE_URL`.
- `create_accom_direct.js` — POSTs an accommodation to the deployed admin API using `VENDOR_ID` and `ISLAND_ID`.
- `create_transport_direct.js` — POSTs a transport with explicit `FROM_ISLAND_ID` and `TO_ISLAND_ID`.
- `seed_fixtures.js` — attempts to seed atolls/islands/vendors/accommodations/transports using admin endpoints (requires admin header fallback).
- `create_accommodation.js` — helper that queries islands then posts an accommodation (uses admin headers).
- `hosted_e2e.js` — hosted end-to-end test runner (uses admin header fallback to perform booking flows).
- `render_update_deploy.js`, `set_render_env.js`, `render_set_env.js` — helpers to set Render env vars and trigger deploys via the Render API.
- `dump_database.js` / `dump_database.cjs` — produce SQL dumps of the Neon DB (used to create off-site backups).
- `smoke_check.js` — quick smoke checks for `/api/v1/health`, `/api/v1/service-categories`, and `/api/v1/workations`.

Quick usage examples

1. Insert islands (uses `DATABASE_URL` env var):

```powershell
cd tmp
$env:DATABASE_URL = "<your database url>"
node insert_islands.js
```

2. Create accommodation via admin API (requires admin header fallback enabled):

```powershell
cd tmp
$env:VENDOR_ID = "<vendor-id>"
$env:ISLAND_ID = "<island-id>"
node create_accom_direct.js
```

3. Run hosted smoke checks:

```powershell
cd tmp
node smoke_check.js
```

Security note
- Several scripts use header-based admin auth (`x-user-id`, `x-user-role`) and require `AUTH_ALLOW_HEADER_FALLBACK=true` on the deployed service to work; do NOT leave that flag enabled in production longer than necessary.

Keep this folder tidy: these are convenience scripts used during troubleshooting and should be reviewed before committing to production workflows.
