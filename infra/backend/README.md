# Workation Backend (minimal)

This folder contains a minimal NestJS + Prisma scaffold. From `infra/backend` use these Windows PowerShell–safe commands:

```powershell
cd .\infra\backend
npm.cmd install
npx.cmd prisma generate --schema=..\prisma\schema.prisma
npx.cmd prisma migrate dev --schema=..\prisma\schema.prisma
npm.cmd run start:dev
```

Use `npm.cmd`/`npx.cmd` to avoid PowerShell script-execution policy issues.

Deployment baseline
- Runtime host: Render
- Database: Neon PostgreSQL
- Required env var in hosted environments: `DATABASE_URL` (Neon connection string)

Security note
- Do not store real Render/Neon credentials in tracked files.
- Keep secrets in Render environment settings and rotate from Neon as needed.
