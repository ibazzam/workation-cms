import { PrismaClient } from '@prisma/client';
(async () => {
  const prisma = new PrismaClient();
  try {
    const res = await prisma.$queryRawUnsafe("SELECT id, migration_name, finished_at FROM \"_prisma_migrations\" ORDER BY finished_at DESC NULLS LAST LIMIT 50");
    console.log(res);
  } catch (e) {
    console.error(e);
    process.exitCode = 1;
  } finally {
    await prisma.$disconnect();
  }
})();
