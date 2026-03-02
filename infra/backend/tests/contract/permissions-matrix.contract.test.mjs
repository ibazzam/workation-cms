import assert from 'node:assert/strict';
import test from 'node:test';
import { PrismaClient } from '@prisma/client';
import { authHeaders, baseUrl, canRun, registerBackendLifecycle, skipReason } from './contract-harness.mjs';

registerBackendLifecycle(test);

const prisma = new PrismaClient();

let fixtureVendor;
let fixtureVendorOther;

async function cleanupFixtures() {
  await prisma.vendor.deleteMany({ where: { id: { startsWith: 'contract-perm-vendor-' } } });
}

test.before(async () => {
  if (!canRun) {
    return;
  }

  await prisma.$connect();
  await cleanupFixtures();

  const now = Date.now();
  fixtureVendor = await prisma.vendor.create({
    data: {
      id: `contract-perm-vendor-${now}`,
      name: `Permission Vendor ${now}`,
      email: `permission-vendor-${now}@example.test`,
    },
  });

  fixtureVendorOther = await prisma.vendor.create({
    data: {
      id: `contract-perm-vendor-other-${now}`,
      name: `Permission Vendor Other ${now}`,
      email: `permission-vendor-other-${now}@example.test`,
    },
  });
});

test.after(async () => {
  if (!canRun) {
    return;
  }

  await cleanupFixtures();
  await prisma.$disconnect();
});

test('Permission matrix: USER cannot create workations', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/workations`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-perm-user-${Date.now()}`, 'USER'),
    },
    body: JSON.stringify({
      title: `Permission Workation ${Date.now()}`,
      location: 'Male',
      start_date: '2026-09-01',
      end_date: '2026-09-02',
      price: 100,
    }),
  });

  assert.equal(response.status, 403);
});

test('Permission matrix: ADMIN_CARE can create workations; ADMIN_FINANCE cannot', { skip: !canRun ? skipReason : false }, async () => {
  const careResponse = await fetch(`${baseUrl}/workations`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-perm-admin-care-${Date.now()}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify({
      title: `Permission Workation Care ${Date.now()}`,
      location: 'Male',
      start_date: '2026-09-03',
      end_date: '2026-09-05',
      price: 150,
    }),
  });

  assert.equal(careResponse.status, 201);

  const financeResponse = await fetch(`${baseUrl}/workations`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-perm-admin-finance-${Date.now()}`, 'ADMIN_FINANCE'),
    },
    body: JSON.stringify({
      title: `Permission Workation Finance ${Date.now()}`,
      location: 'Male',
      start_date: '2026-09-06',
      end_date: '2026-09-07',
      price: 170,
    }),
  });

  assert.equal(financeResponse.status, 403);
});

test('Permission matrix: ADMIN_FINANCE can write commercial settings; ADMIN_CARE cannot', { skip: !canRun ? skipReason : false }, async () => {
  const financeResponse = await fetch(`${baseUrl}/admin/settings/commercial`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-perm-settings-finance-${Date.now()}`, 'ADMIN_FINANCE'),
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

  assert.equal(financeResponse.status, 201);

  const careResponse = await fetch(`${baseUrl}/admin/settings/commercial`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-perm-settings-care-${Date.now()}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify({
      loyalty: {
        enabled: false,
        pointsPerUnitSpend: 1,
        unitCurrency: 'USD',
        redemptionValuePerPoint: 0.01,
        minimumPointsToRedeem: 50,
      },
    }),
  });

  assert.equal(careResponse.status, 403);
});

test('Permission matrix: VENDOR can update own profile and cannot update another vendor profile', { skip: !canRun ? skipReason : false }, async () => {
  const ownUpdateResponse = await fetch(`${baseUrl}/vendors/me`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-perm-vendor-user-${Date.now()}`, 'VENDOR', `contract-perm-vendor-user-${Date.now()}@example.test`, fixtureVendor.id),
    },
    body: JSON.stringify({
      phone: '+9607000001',
    }),
  });

  assert.equal(ownUpdateResponse.status, 200);
  const ownUpdated = await ownUpdateResponse.json();
  assert.equal(ownUpdated.id, fixtureVendor.id);
  assert.equal(ownUpdated.phone, '+9607000001');

  const foreignUpdateResponse = await fetch(`${baseUrl}/vendors/admin/${fixtureVendorOther.id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-perm-vendor-foreign-${Date.now()}`, 'VENDOR', `contract-perm-vendor-foreign-${Date.now()}@example.test`, fixtureVendor.id),
    },
    body: JSON.stringify({
      phone: '+9607009999',
    }),
  });

  assert.equal(foreignUpdateResponse.status, 403);
});
