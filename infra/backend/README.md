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
