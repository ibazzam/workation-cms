import assert from 'node:assert/strict';
import test from 'node:test';
import { PrismaClient } from '@prisma/client';
import { authHeaders, baseUrl, canRun, registerBackendLifecycle, skipReason } from './contract-harness.mjs';

registerBackendLifecycle(test);

const prisma = new PrismaClient();

async function cleanupFixtures() {
  await prisma.adminAuditLog.deleteMany({ where: { actorUserId: { startsWith: 'contract-admin-audit-' } } });
  await prisma.workation.deleteMany({ where: { title: { startsWith: 'Contract Admin Audit' } } });
}

async function waitForAudit(where) {
  const maxAttempts = 12;
  for (let attempt = 0; attempt < maxAttempts; attempt += 1) {
    const row = await prisma.adminAuditLog.findFirst({
      where,
      orderBy: { createdAt: 'desc' },
    });

    if (row) {
      return row;
    }

    await new Promise((resolve) => setTimeout(resolve, 100));
  }

  return null;
}

test.before(async () => {
  if (!canRun) {
    return;
  }

  await prisma.$connect();
  await cleanupFixtures();
});

test.after(async () => {
  if (!canRun) {
    return;
  }

  await cleanupFixtures();
  await prisma.$disconnect();
});

test('Admin audit logs successful ADMIN_CARE write on /workations', { skip: !canRun ? skipReason : false }, async () => {
  const actorId = `contract-admin-audit-${Date.now()}`;

  const response = await fetch(`${baseUrl}/workations`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(actorId, 'ADMIN_CARE', `${actorId}@example.test`),
    },
    body: JSON.stringify({
      title: `Contract Admin Audit ${Date.now()}`,
      location: 'Male',
      start_date: '2026-10-01',
      end_date: '2026-10-03',
      price: 250,
    }),
  });

  assert.equal(response.status, 201);

  const auditRow = await waitForAudit({
    actorUserId: actorId,
    method: 'POST',
    path: '/api/v1/workations',
  });

  assert.ok(auditRow);
  assert.equal(auditRow.success, true);
  assert.equal(auditRow.statusCode, 201);
  assert.equal(auditRow.actorRole, 'ADMIN_CARE');
});

test('Admin audit logs denied ADMIN_CARE write on finance-only admin settings route', { skip: !canRun ? skipReason : false }, async () => {
  const actorId = `contract-admin-audit-${Date.now()}`;

  const response = await fetch(`${baseUrl}/admin/settings/commercial`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(actorId, 'ADMIN_CARE', `${actorId}@example.test`),
    },
    body: JSON.stringify({
      loyalty: {
        enabled: true,
        pointsPerUnitSpend: 2,
        unitCurrency: 'USD',
        redemptionValuePerPoint: 0.01,
        minimumPointsToRedeem: 100,
      },
    }),
  });

  assert.equal(response.status, 403);

  const auditRow = await waitForAudit({
    actorUserId: actorId,
    method: 'POST',
    path: '/api/v1/admin/settings/commercial',
  });

  assert.ok(auditRow);
  assert.equal(auditRow.success, false);
  assert.equal(auditRow.statusCode, 403);
  assert.equal(auditRow.actorRole, 'ADMIN_CARE');
});
