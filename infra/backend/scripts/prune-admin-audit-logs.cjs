const { PrismaClient } = require('@prisma/client');

const retentionDays = Number(process.env.AUDIT_LOG_RETENTION_DAYS ?? 90);
const dryRun = String(process.env.AUDIT_LOG_PRUNE_DRY_RUN ?? 'true').toLowerCase() === 'true';

if (!Number.isFinite(retentionDays) || retentionDays <= 0) {
  console.error('AUDIT_LOG_RETENTION_DAYS must be a positive integer');
  process.exit(2);
}

const prisma = new PrismaClient();

async function run() {
  const cutoff = new Date(Date.now() - retentionDays * 24 * 60 * 60 * 1000);

  let candidates;
  try {
    candidates = await prisma.adminAuditLog.count({
      where: {
        createdAt: { lt: cutoff },
      },
    });
  } catch (error) {
    if (error && error.code === 'P2021') {
      console.warn('AdminAuditLog table not found in current database; skipping prune operation.');
      return;
    }
    throw error;
  }

  if (dryRun) {
    console.log(`Dry run: ${candidates} admin audit rows older than ${retentionDays} days (cutoff ${cutoff.toISOString()})`);
    return;
  }

  const deleted = await prisma.adminAuditLog.deleteMany({
    where: {
      createdAt: { lt: cutoff },
    },
  });

  console.log(`Deleted ${deleted.count} admin audit rows older than ${retentionDays} days (cutoff ${cutoff.toISOString()})`);
}

run()
  .catch((error) => {
    console.error(error);
    process.exitCode = 1;
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
