## Local Development

This repository currently has two runtime surfaces:
- Laravel app at workspace root (legacy business API currently active)
- New backend/frontend scaffolds under `infra/backend` and `infra/frontend`

## Hosted Environment (Source of Truth)

- Backend is deployed on Render.
- PostgreSQL is hosted on Neon.
- Production/staging backend services should use a Neon `DATABASE_URL` injected through Render environment variables.

Do not commit live credentials to repository files. Keep Render and Neon secrets in environment variable managers only.

## Database Defaults

Project default for non-test environments is PostgreSQL.

Hosted baseline:
- Render service env var: `DATABASE_URL` -> Neon connection string
- SSL required for hosted Neon connections (`sslmode=require` in URL)

Root Laravel `.env` expected baseline:
- `DB_CONNECTION=pgsql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=5432`
- `DB_DATABASE=workation`
- `DB_USERNAME=postgres`
- `DB_PASSWORD=postgres`

For local PostgreSQL, you can run:

```powershell
cd infra
docker compose up -d postgres
```

For local parity with Neon behavior, prefer testing with a non-production Neon branch URL as `DATABASE_URL`.

## Laravel App (root)

```powershell
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm.cmd install
npm.cmd run dev
```

Run tests:

```powershell
composer test
```

Note: tests intentionally use in-memory SQLite via `phpunit.xml` for speed/isolation.

## New Backend (NestJS scaffold)

```powershell
cd infra\backend
npm.cmd install
npx.cmd prisma generate --schema=..\prisma\schema.prisma
npx.cmd prisma migrate dev --schema=..\prisma\schema.prisma
npm.cmd run start:dev
```

## New Frontend (Next.js scaffold)

```powershell
cd infra\frontend
npm.cmd install
npm.cmd run dev
```

## Cutover Note

Until `WB-201` is complete, Laravel remains the active business API surface. Use `docs/wb-201-authority-cutover-runbook.md` as the source of truth for cutover sequencing and rollback.

## Deployment Notes (Render + Neon)

- Set `DATABASE_URL` in Render for each environment (staging/prod) and rotate credentials through Neon when needed.
- Keep `APP_ENV`, logging, and CORS values environment-specific in Render.
- Run migrations against the target Neon branch before promoting backend image/revision.