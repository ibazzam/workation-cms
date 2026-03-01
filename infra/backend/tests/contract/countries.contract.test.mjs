import assert from 'node:assert/strict';
import test from 'node:test';
import { PrismaClient } from '@prisma/client';
import { authHeaders, baseUrl, canRun, registerBackendLifecycle, skipReason } from './contract-harness.mjs';

registerBackendLifecycle(test);

const prisma = new PrismaClient();

async function cleanupFixtures() {
  await prisma.country.deleteMany({ where: { code: { startsWith: 'CC' } } });
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

test('GET /api/v1/countries contract', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const code = 'CCG';
  const created = await prisma.country.create({
    data: {
      code,
      name: `Contract Country ${now}`,
      active: true,
    },
  });

  const response = await fetch(`${baseUrl}/countries`);
  assert.equal(response.status, 200);
  const body = await response.json();
  assert.equal(Array.isArray(body), true);
  assert.equal(body.some((item) => item.id === created.id && item.code === code), true);
});

test('POST /api/v1/countries/admin enforces RBAC', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/countries/admin`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-country-user-${Date.now()}`, 'USER'),
    },
    body: JSON.stringify({ code: 'CTA', name: 'Forbidden Country' }),
  });

  assert.equal(response.status, 403);
});

test('Admin CRUD /api/v1/countries/admin works for ADMIN_CARE', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const code = 'CCU';

  const createResponse = await fetch(`${baseUrl}/countries/admin`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-country-admin-care-${now}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify({ code, name: `Country ${now}`, active: true }),
  });

  assert.equal(createResponse.status, 201);
  const created = await createResponse.json();
  assert.equal(created.code, code);

  const updateResponse = await fetch(`${baseUrl}/countries/admin/${created.id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-country-admin-care-update-${now}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify({ active: false }),
  });

  assert.equal(updateResponse.status, 200);
  const updated = await updateResponse.json();
  assert.equal(updated.active, false);

  const deleteResponse = await fetch(`${baseUrl}/countries/admin/${created.id}`, {
    method: 'DELETE',
    headers: authHeaders(`contract-country-admin-care-delete-${now}`, 'ADMIN_CARE'),
  });

  assert.equal(deleteResponse.status, 204);
});
