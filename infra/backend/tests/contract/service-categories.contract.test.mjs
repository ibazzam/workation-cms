import assert from 'node:assert/strict';
import test from 'node:test';
import { PrismaClient } from '@prisma/client';
import { authHeaders, baseUrl, canRun, registerBackendLifecycle, skipReason } from './contract-harness.mjs';

registerBackendLifecycle(test);

const prisma = new PrismaClient();

async function cleanupFixtures() {
  await prisma.serviceCategory.deleteMany({ where: { code: { startsWith: 'SC_' } } });
}

test.before(async () => {
  if (!canRun) return;
  await prisma.$connect();
  await cleanupFixtures();
});

test.after(async () => {
  if (!canRun) return;
  await cleanupFixtures();
  await prisma.$disconnect();
});

test('GET /api/v1/service-categories contract', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const code = `SC_${now}`;
  const created = await prisma.serviceCategory.create({
    data: {
      code,
      name: `Service Category ${now}`,
      scope: 'BOTH',
      active: true,
    },
  });

  const response = await fetch(`${baseUrl}/service-categories`);
  assert.equal(response.status, 200);
  const body = await response.json();
  assert.equal(Array.isArray(body), true);
  assert.equal(body.some((item) => item.id === created.id && item.code === code), true);
});

test('POST /api/v1/service-categories/admin enforces RBAC', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/service-categories/admin`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-category-user-${Date.now()}`, 'USER'),
    },
    body: JSON.stringify({ code: 'SC_TEST', name: 'Forbidden Category', scope: 'BOTH' }),
  });

  assert.equal(response.status, 403);
});

test('Admin CRUD /api/v1/service-categories/admin works for ADMIN_CARE', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const code = `SC_${now}`;

  const createResponse = await fetch(`${baseUrl}/service-categories/admin`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-category-admin-care-${now}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify({
      code,
      name: `Category ${now}`,
      scope: 'ACCOMMODATION',
      active: true,
    }),
  });

  assert.equal(createResponse.status, 201);
  const created = await createResponse.json();
  assert.equal(created.code, code);

  const updateResponse = await fetch(`${baseUrl}/service-categories/admin/${created.id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-category-admin-care-update-${now}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify({
      scope: 'BOTH',
      active: false,
    }),
  });

  assert.equal(updateResponse.status, 200);
  const updated = await updateResponse.json();
  assert.equal(updated.scope, 'BOTH');
  assert.equal(updated.active, false);

  const deleteResponse = await fetch(`${baseUrl}/service-categories/admin/${created.id}`, {
    method: 'DELETE',
    headers: authHeaders(`contract-category-admin-care-delete-${now}`, 'ADMIN_CARE'),
  });

  assert.equal(deleteResponse.status, 204);
});
