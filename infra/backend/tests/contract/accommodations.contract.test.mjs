import assert from 'node:assert/strict';
import test from 'node:test';
import { PrismaClient } from '@prisma/client';
import { authHeaders, baseUrl, canRun, registerBackendLifecycle, skipReason } from './contract-harness.mjs';

registerBackendLifecycle(test);

const prisma = new PrismaClient();

let fixtureAtoll;
let fixtureIsland;
let fixtureVendor;
let fixtureVendorOther;
let fixtureAccommodation;

async function cleanupFixtures() {
  await prisma.booking.deleteMany({ where: { accommodationId: { startsWith: 'contract-accommodation-' } } });
  await prisma.booking.deleteMany({ where: { transportId: { startsWith: 'contract-transport-' } } });
  await prisma.accommodationSeasonalRate.deleteMany({ where: { accommodationId: { startsWith: 'contract-accommodation-' } } });
  await prisma.accommodation.deleteMany({
    where: {
      OR: [
        { id: { startsWith: 'contract-accommodation-' } },
        { vendorId: { startsWith: 'contract-vendor-' } },
      ],
    },
  });
  await prisma.transport.deleteMany({
    where: {
      OR: [
        { id: { startsWith: 'contract-transport-' } },
        { vendorId: { startsWith: 'contract-vendor-' } },
      ],
    },
  });
  await prisma.vendor.deleteMany({ where: { id: { startsWith: 'contract-vendor-' } } });
  await prisma.island.deleteMany({ where: { slug: { startsWith: 'contract-acc-island-' } } });
  await prisma.atoll.deleteMany({ where: { code: { startsWith: 'CONTRACT-ACC-' } } });
}

async function findLatestAdminCareAudit(path) {
  const maxAttempts = 10;
  for (let attempt = 0; attempt < maxAttempts; attempt += 1) {
    const latestAudit = await prisma.adminAuditLog.findFirst({
      where: {
        actorRole: 'ADMIN_CARE',
        method: 'PUT',
        path,
        success: true,
      },
      orderBy: { createdAt: 'desc' },
    });

    if (latestAudit) {
      return latestAudit;
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

  const now = Date.now();
  fixtureAtoll = await prisma.atoll.create({
    data: {
      code: `CONTRACT-ACC-${now}`,
      name: `Contract Acc Atoll ${now}`,
    },
  });

  fixtureIsland = await prisma.island.create({
    data: {
      name: `Contract Acc Island ${now}`,
      slug: `contract-acc-island-${now}`,
      atollId: fixtureAtoll.id,
      lat: 4.19,
      lng: 73.52,
    },
  });

  fixtureVendor = await prisma.vendor.create({
    data: {
      id: `contract-vendor-${now}`,
      name: `Contract Vendor ${now}`,
      email: `contract-vendor-${now}@example.test`,
    },
  });

  fixtureVendorOther = await prisma.vendor.create({
    data: {
      id: `contract-vendor-other-${now}`,
      name: `Contract Vendor Other ${now}`,
      email: `contract-vendor-other-${now}@example.test`,
    },
  });

  fixtureAccommodation = await prisma.accommodation.create({
    data: {
      id: `contract-accommodation-${now}`,
      vendorId: fixtureVendor.id,
      islandId: fixtureIsland.id,
      title: `Contract Accommodation ${now}`,
      slug: `contract-accommodation-${now}`,
      description: 'Contract accommodation fixture',
      type: 'GUESTHOUSE',
      rooms: 6,
      price: 220,
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

test('GET /api/v1/accommodations contract', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/accommodations`);
  assert.equal(response.status, 200);

  const body = await response.json();
  assert.equal(Array.isArray(body), true);
  assert.equal(body.some((item) => item.id === fixtureAccommodation.id), true);
});

test('GET /api/v1/accommodations filtered by islandId', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/accommodations?islandId=${fixtureIsland.id}`);
  assert.equal(response.status, 200);

  const body = await response.json();
  assert.equal(Array.isArray(body), true);
  assert.equal(body.some((item) => item.id === fixtureAccommodation.id && item.island?.id === fixtureIsland.id), true);
});

test('GET /api/v1/accommodations/:id contract', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/accommodations/${fixtureAccommodation.id}`);
  assert.equal(response.status, 200);

  const body = await response.json();
  assert.equal(body.id, fixtureAccommodation.id);
  assert.equal(body.vendor?.id, fixtureVendor.id);
  assert.equal(body.island?.id, fixtureIsland.id);
});

test('GET /api/v1/accommodations/:id returns 404 for missing id', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/accommodations/missing-accommodation-id`);
  assert.equal(response.status, 404);
});

test('GET /api/v1/accommodations/:id/availability contract', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(
    `${baseUrl}/accommodations/${fixtureAccommodation.id}/availability?startDate=2026-09-01&endDate=2026-09-04&roomsRequested=1`,
  );
  assert.equal(response.status, 200);

  const body = await response.json();
  assert.equal(body.accommodationId, fixtureAccommodation.id);
  assert.equal(body.available, true);
  assert.equal(body.roomsRequested, 1);
  assert.equal(typeof body.stayNights, 'number');
  assert.equal(Array.isArray(body.blackouts), true);
});

test('GET /api/v1/accommodations/:id/quote uses seasonal nightly pricing', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const seasonalRateResponse = await fetch(`${baseUrl}/accommodations/admin/${fixtureAccommodation.id}/seasonal-rates`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-accommodation-seasonal-${now}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify({
      name: 'Peak Season',
      startDate: '2026-12-20',
      endDate: '2026-12-26',
      nightlyPrice: 350,
      priority: 10,
    }),
  });

  assert.equal(seasonalRateResponse.status, 201);
  const seasonalRate = await seasonalRateResponse.json();
  assert.equal(seasonalRate.accommodationId, fixtureAccommodation.id);

  const quoteResponse = await fetch(
    `${baseUrl}/accommodations/${fixtureAccommodation.id}/quote?startDate=2026-12-22&endDate=2026-12-25&roomsRequested=1`,
  );
  assert.equal(quoteResponse.status, 200);

  const quoteBody = await quoteResponse.json();
  assert.equal(quoteBody.accommodationId, fixtureAccommodation.id);
  assert.equal(quoteBody.quote.nightlyTotal, 1050);
  assert.equal(Array.isArray(quoteBody.quote.nightlyBreakdown), true);
  assert.equal(quoteBody.quote.nightlyBreakdown.length, 3);
  assert.equal(quoteBody.quote.nightlyBreakdown.every((night) => night.source === 'SEASONAL'), true);

  const deleteRateResponse = await fetch(`${baseUrl}/accommodations/admin/${fixtureAccommodation.id}/seasonal-rates/${seasonalRate.id}`, {
    method: 'DELETE',
    headers: authHeaders(`contract-accommodation-seasonal-delete-${now}`, 'ADMIN_CARE'),
  });

  assert.equal(deleteRateResponse.status, 204);
});

test('POST /api/v1/accommodations/admin enforces RBAC', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/accommodations/admin`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-accommodation-user-${Date.now()}`, 'USER'),
    },
    body: JSON.stringify({
      vendorId: fixtureVendor.id,
      islandId: fixtureIsland.id,
      title: `RBAC Accommodation ${Date.now()}`,
      price: 150,
    }),
  });

  assert.equal(response.status, 403);
});

test('Admin CRUD /api/v1/accommodations/admin works for ADMIN_CARE', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const createResponse = await fetch(`${baseUrl}/accommodations/admin`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-accommodation-admin-care-${now}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify({
      vendorId: fixtureVendor.id,
      islandId: fixtureIsland.id,
      title: `Contract Admin Accommodation ${now}`,
      type: 'RESTAURANT',
      rooms: 0,
      price: 320,
    }),
  });

  assert.equal(createResponse.status, 201);
  const created = await createResponse.json();
  assert.equal(created.type, 'RESTAURANT');

  const updateResponse = await fetch(`${baseUrl}/accommodations/admin/${created.id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-accommodation-admin-care-update-${now}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify({
      type: 'WATER_SPORTS',
      price: 450,
    }),
  });

  assert.equal(updateResponse.status, 200);
  const updated = await updateResponse.json();
  assert.equal(updated.type, 'WATER_SPORTS');

  const latestAudit = await findLatestAdminCareAudit(`/api/v1/accommodations/admin/${created.id}`);

  assert.ok(latestAudit);

  const deleteResponse = await fetch(`${baseUrl}/accommodations/admin/${created.id}`, {
    method: 'DELETE',
    headers: authHeaders(`contract-accommodation-admin-care-delete-${now}`, 'ADMIN_CARE'),
  });

  assert.equal(deleteResponse.status, 204);
});

test('VENDOR is scope-limited for /api/v1/accommodations/admin writes', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const createResponse = await fetch(`${baseUrl}/accommodations/admin`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-accommodation-vendor-${now}`, 'VENDOR', `contract-accommodation-vendor-${now}@example.test`, fixtureVendor.id),
    },
    body: JSON.stringify({
      vendorId: fixtureVendor.id,
      islandId: fixtureIsland.id,
      title: `Vendor Scoped Accommodation ${now}`,
      price: 199,
    }),
  });

  assert.equal(createResponse.status, 201);
  const created = await createResponse.json();
  assert.equal(created.vendorId, fixtureVendor.id);

  const forbiddenCreateResponse = await fetch(`${baseUrl}/accommodations/admin`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-accommodation-vendor-forbidden-${now}`, 'VENDOR', `contract-accommodation-vendor-forbidden-${now}@example.test`, fixtureVendor.id),
    },
    body: JSON.stringify({
      vendorId: fixtureVendorOther.id,
      islandId: fixtureIsland.id,
      title: `Vendor Forbidden Accommodation ${now}`,
      price: 210,
    }),
  });

  assert.equal(forbiddenCreateResponse.status, 403);

  const forbiddenUpdateResponse = await fetch(`${baseUrl}/accommodations/admin/${fixtureAccommodation.id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-accommodation-vendor-update-${now}`, 'VENDOR', `contract-accommodation-vendor-update-${now}@example.test`, fixtureVendorOther.id),
    },
    body: JSON.stringify({
      price: 333,
    }),
  });

  assert.equal(forbiddenUpdateResponse.status, 403);

  const cleanupResponse = await fetch(`${baseUrl}/accommodations/admin/${created.id}`, {
    method: 'DELETE',
    headers: authHeaders(`contract-accommodation-vendor-cleanup-${now}`, 'VENDOR', `contract-accommodation-vendor-cleanup-${now}@example.test`, fixtureVendor.id),
  });

  assert.equal(cleanupResponse.status, 204);
});

test('Accommodation blackout admin endpoint blocks availability', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const createBlackoutResponse = await fetch(`${baseUrl}/accommodations/admin/${fixtureAccommodation.id}/blackouts`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-accommodation-blackout-admin-${now}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify({
      startDate: '2026-10-01',
      endDate: '2026-10-05',
      reason: 'Maintenance window',
    }),
  });

  assert.equal(createBlackoutResponse.status, 201);
  const blackout = await createBlackoutResponse.json();
  assert.equal(blackout.accommodationId, fixtureAccommodation.id);

  const availabilityResponse = await fetch(
    `${baseUrl}/accommodations/${fixtureAccommodation.id}/availability?startDate=2026-10-02&endDate=2026-10-03&roomsRequested=1`,
  );
  assert.equal(availabilityResponse.status, 200);

  const availabilityBody = await availabilityResponse.json();
  assert.equal(availabilityBody.blocked.blackout, true);
  assert.equal(availabilityBody.available, false);

  const deleteBlackoutResponse = await fetch(`${baseUrl}/accommodations/admin/${fixtureAccommodation.id}/blackouts/${blackout.id}`, {
    method: 'DELETE',
    headers: authHeaders(`contract-accommodation-blackout-delete-${now}`, 'ADMIN_CARE'),
  });

  assert.equal(deleteBlackoutResponse.status, 204);
});
