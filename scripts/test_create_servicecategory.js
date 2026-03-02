#!/usr/bin/env node
import { PrismaClient } from '@prisma/client';

const prisma = new PrismaClient();

async function main() {
  console.log('Creating ServiceCategory test record...');
  const ts = Date.now();
  const data = {
    name: `CI_TEST_${ts}`,
    code: `CI_TEST_${ts}`,
    scope: 'BOTH',
    active: true,
  };

  const created = await prisma.serviceCategory.create({ data });
  console.log('Created:', created);

  // cleanup
  try {
    await prisma.serviceCategory.delete({ where: { id: created.id } });
    console.log('Cleaned up created record.');
  } catch (e) {
    console.warn('Cleanup failed:', e && e.message);
  }

  await prisma.$disconnect();
}

main().catch((e) => {
  console.error('Error in test_create_servicecategory:', e && e.message);
  prisma.$disconnect().finally(() => process.exit(1));
});
