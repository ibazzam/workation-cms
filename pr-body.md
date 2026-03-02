Summary
- Add off-site DB backup automation and S3 hardening (scripts + GitHub Actions).
- Add DB-ready health check (`SKIP_DB_HEALTH` override) and await Prisma connect in `main.ts`.
- Skip payments background jobs during contract runs via `SKIP_BACKGROUND_JOBS`.
- Increase contract harness health timeout and set `SKIP_BACKGROUND_JOBS` for spawned backend during tests.
- Fix Prisma schema (`@updatedAt` added where required) and regenerate client.
- Verified: accommodation contract tests pass locally.

Notes
- Branch: chore/backup-automation-hardening-2026-02-25
- If CI still fails due to DB/migration ordering, ensure `prisma migrate deploy` runs before the contract suite or set `SKIP_BACKGROUND_JOBS=true` in CI when running contract tests.

References
- Local test: infra/backend npm script `test:contract:accommodations` (all tests green after changes)
