import assert from 'node:assert/strict';
import test from 'node:test';
import { PrismaClient } from '@prisma/client';
import { authHeaders, baseUrl, canRun, registerBackendLifecycle, skipReason } from './contract-harness.mjs';

registerBackendLifecycle(test);

const prisma = new PrismaClient();

async function cleanupFixtures() {
  await prisma.transport.deleteMany({ where: { id: { startsWith: 'contract-vendor-transport-' } } });
  await prisma.accommodation.deleteMany({ where: { id: { startsWith: 'contract-vendor-accommodation-' } } });
  await prisma.vendor.deleteMany({ where: { id: { startsWith: 'contract-vendor-' } } });
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

test('GET /api/v1/vendors contract', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  await prisma.vendor.create({
    data: {
      id: `contract-vendor-${now}`,
      name: `Contract Vendor ${now}`,
      email: `contract-vendor-${now}@example.test`,
    },
  });

  const response = await fetch(`${baseUrl}/vendors`);
  assert.equal(response.status, 200);

  const body = await response.json();
  assert.equal(Array.isArray(body), true);
  assert.equal(body.some((item) => item.id === `contract-vendor-${now}`), true);
});

test('POST /api/v1/vendors/admin enforces RBAC', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/vendors/admin`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-vendor-user-${Date.now()}`, 'USER'),
    },
    body: JSON.stringify({
      name: `Forbidden Vendor ${Date.now()}`,
      email: `forbidden-vendor-${Date.now()}@example.test`,
    }),
  });

  assert.equal(response.status, 403);
});

test('Admin CRUD /api/v1/vendors/admin works for ADMIN_CARE', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();

  const createResponse = await fetch(`${baseUrl}/vendors/admin`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-vendor-admin-care-${now}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify({
      name: `Vendor Care ${now}`,
      email: `vendor-care-${now}@example.test`,
      phone: '+9607770000',
    }),
  });

  assert.equal(createResponse.status, 201);
  const created = await createResponse.json();
  assert.equal(created.name, `Vendor Care ${now}`);

  const updateResponse = await fetch(`${baseUrl}/vendors/admin/${created.id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-vendor-admin-care-update-${now}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify({
      phone: '+9607771111',
    }),
  });

  assert.equal(updateResponse.status, 200);
  const updated = await updateResponse.json();
  assert.equal(updated.phone, '+9607771111');

  const getResponse = await fetch(`${baseUrl}/vendors/${created.id}`);
  assert.equal(getResponse.status, 200);

  const deleteResponse = await fetch(`${baseUrl}/vendors/admin/${created.id}`, {
    method: 'DELETE',
    headers: authHeaders(`contract-vendor-admin-care-delete-${now}`, 'ADMIN_CARE'),
  });

  assert.equal(deleteResponse.status, 204);
});
