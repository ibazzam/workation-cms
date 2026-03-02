import assert from 'node:assert/strict';
import test from 'node:test';
import { PrismaClient } from '@prisma/client';
import { authHeaders, baseUrl, canRun, registerBackendLifecycle, skipReason } from './contract-harness.mjs';

registerBackendLifecycle(test);

const prisma = new PrismaClient();

let fixtureAtoll;
let fixtureIsland;
let fixtureOriginAtoll;
let fixtureOriginIsland;
let fixtureVendor;
let fixtureVendorOther;
let fixtureAccommodation;
let fixtureTransport;

async function cleanupFixtures() {
  await prisma.socialLink.deleteMany({ where: { OR: [{ url: { contains: 'contract-social-' } }, { handle: { contains: 'contract-social-' } }] } });
  await prisma.transport.deleteMany({ where: { id: { startsWith: 'contract-social-transport-' } } });
  await prisma.accommodation.deleteMany({ where: { id: { startsWith: 'contract-social-accommodation-' } } });
  await prisma.vendor.deleteMany({ where: { id: { startsWith: 'contract-social-vendor-' } } });
  await prisma.island.deleteMany({ where: { slug: { startsWith: 'contract-social-island-' } } });
  await prisma.atoll.deleteMany({ where: { code: { startsWith: 'CONTRACT-SOCIAL-' } } });
}

test.before(async () => {
  if (!canRun) return;

  await prisma.$connect();
  await cleanupFixtures();

  const now = Date.now();
  fixtureAtoll = await prisma.atoll.create({ data: { code: `CONTRACT-SOCIAL-ATOLL-${now}`, name: `Contract Social Atoll ${now}` } });
  fixtureOriginAtoll = await prisma.atoll.create({ data: { code: `CONTRACT-SOCIAL-ORIGIN-${now}`, name: `Contract Social Origin ${now}` } });

  fixtureIsland = await prisma.island.create({
    data: {
      name: `Contract Social Island ${now}`,
      slug: `contract-social-island-main-${now}`,
      atollId: fixtureAtoll.id,
      lat: 4.1,
      lng: 73.5,
    },
  });

  fixtureOriginIsland = await prisma.island.create({
    data: {
      name: `Contract Social Origin Island ${now}`,
      slug: `contract-social-island-origin-${now}`,
      atollId: fixtureOriginAtoll.id,
      lat: 4.3,
      lng: 73.7,
    },
  });

  fixtureVendor = await prisma.vendor.create({
    data: {
      id: `contract-social-vendor-${now}`,
      name: `Contract Social Vendor ${now}`,
      email: `contract-social-vendor-${now}@example.test`,
    },
  });

  fixtureVendorOther = await prisma.vendor.create({
    data: {
      id: `contract-social-vendor-other-${now}`,
      name: `Contract Social Vendor Other ${now}`,
      email: `contract-social-vendor-other-${now}@example.test`,
    },
  });

  fixtureAccommodation = await prisma.accommodation.create({
    data: {
      id: `contract-social-accommodation-${now}`,
      vendorId: fixtureVendor.id,
      islandId: fixtureIsland.id,
      title: `Contract Social Accommodation ${now}`,
      slug: `contract-social-accommodation-${now}`,
      type: 'GUESTHOUSE',
      rooms: 5,
      price: 180,
    },
  });

  fixtureTransport = await prisma.transport.create({
    data: {
      id: `contract-social-transport-${now}`,
      vendorId: fixtureVendor.id,
      type: 'SPEEDBOAT',
      fromIslandId: fixtureOriginIsland.id,
      toIslandId: fixtureIsland.id,
      departure: new Date('2026-12-01T07:00:00.000Z'),
      arrival: new Date('2026-12-01T09:00:00.000Z'),
      capacity: 20,
      price: 95,
    },
  });
});

test.after(async () => {
  if (!canRun) return;
  await cleanupFixtures();
  await prisma.$disconnect();
});

test('Admin CRUD and public list for accommodation social links', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const createResponse = await fetch(`${baseUrl}/social-links/admin`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-social-admin-${now}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify({
      targetType: 'ACCOMMODATION',
      targetId: fixtureAccommodation.id,
      platform: 'INSTAGRAM',
      url: `https://instagram.com/contract-social-${now}`,
      handle: `contract-social-${now}`,
      verified: true,
      displayOrder: 1,
    }),
  });

  assert.equal(createResponse.status, 201);
  const created = await createResponse.json();
  assert.equal(created.targetType, 'ACCOMMODATION');

  const listResponse = await fetch(`${baseUrl}/social-links/accommodations/${fixtureAccommodation.id}`);
  assert.equal(listResponse.status, 200);
  const listBody = await listResponse.json();
  assert.equal(Array.isArray(listBody), true);
  assert.equal(listBody.some((item) => item.id === created.id), true);

  const updateResponse = await fetch(`${baseUrl}/social-links/admin/${created.id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-social-admin-update-${now}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify({ active: false }),
  });
  assert.equal(updateResponse.status, 200);

  const listAfterDisable = await fetch(`${baseUrl}/social-links/accommodations/${fixtureAccommodation.id}`);
  assert.equal(listAfterDisable.status, 200);
  const listAfterDisableBody = await listAfterDisable.json();
  assert.equal(listAfterDisableBody.some((item) => item.id === created.id), false);

  const deleteResponse = await fetch(`${baseUrl}/social-links/admin/${created.id}`, {
    method: 'DELETE',
    headers: authHeaders(`contract-social-admin-delete-${now}`, 'ADMIN_CARE'),
  });
  assert.equal(deleteResponse.status, 204);
});

test('VENDOR scope is enforced on social links writes', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const allowedCreate = await fetch(`${baseUrl}/social-links/admin`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-social-vendor-${now}`, 'VENDOR', `contract-social-vendor-${now}@example.test`, fixtureVendor.id),
    },
    body: JSON.stringify({
      targetType: 'VENDOR',
      targetId: fixtureVendor.id,
      platform: 'FACEBOOK',
      url: `https://facebook.com/contract-social-${now}`,
    }),
  });

  assert.equal(allowedCreate.status, 201);
  const created = await allowedCreate.json();

  const forbiddenCreate = await fetch(`${baseUrl}/social-links/admin`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-social-vendor-forbidden-${now}`, 'VENDOR', `contract-social-vendor-forbidden-${now}@example.test`, fixtureVendor.id),
    },
    body: JSON.stringify({
      targetType: 'VENDOR',
      targetId: fixtureVendorOther.id,
      platform: 'INSTAGRAM',
      url: `https://instagram.com/contract-social-forbidden-${now}`,
    }),
  });

  assert.equal(forbiddenCreate.status, 403);

  const cleanup = await fetch(`${baseUrl}/social-links/admin/${created.id}`, {
    method: 'DELETE',
    headers: authHeaders(`contract-social-vendor-cleanup-${now}`, 'VENDOR', `contract-social-vendor-cleanup-${now}@example.test`, fixtureVendor.id),
  });

  assert.equal(cleanup.status, 204);
});

test('Social links moderation queue supports approve/hide/flag workflow', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const createResponse = await fetch(`${baseUrl}/social-links/admin`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-social-admin-moderation-${now}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify({
      targetType: 'TRANSPORT',
      targetId: fixtureTransport.id,
      platform: 'YOUTUBE',
      url: `https://youtube.com/@contract-social-${now}`,
      verified: false,
      displayOrder: 0,
    }),
  });

  assert.equal(createResponse.status, 201);
  const created = await createResponse.json();

  const publicBeforeApprove = await fetch(`${baseUrl}/social-links/transports/${fixtureTransport.id}`);
  assert.equal(publicBeforeApprove.status, 200);
  const publicBeforeApproveBody = await publicBeforeApprove.json();
  assert.equal(publicBeforeApproveBody.some((item) => item.id === created.id), false);

  const queueResponse = await fetch(`${baseUrl}/social-links/admin/moderation`, {
    headers: authHeaders(`contract-social-admin-queue-${now}`, 'ADMIN_CARE'),
  });
  assert.equal(queueResponse.status, 200);
  const queue = await queueResponse.json();
  assert.equal(Array.isArray(queue), true);
  assert.equal(queue.some((item) => item.id === created.id), true);

  const approveResponse = await fetch(`${baseUrl}/social-links/admin/${created.id}/approve`, {
    method: 'POST',
    headers: authHeaders(`contract-social-admin-approve-${now}`, 'ADMIN_CARE'),
  });
  assert.equal(approveResponse.status, 201);
  const approved = await approveResponse.json();
  assert.equal(approved.active, true);
  assert.equal(approved.verified, true);

  const publicAfterApprove = await fetch(`${baseUrl}/social-links/transports/${fixtureTransport.id}`);
  assert.equal(publicAfterApprove.status, 200);
  const publicAfterApproveBody = await publicAfterApprove.json();
  assert.equal(publicAfterApproveBody.some((item) => item.id === created.id), true);

  const flagResponse = await fetch(`${baseUrl}/social-links/${created.id}/flag`, {
    method: 'POST',
    headers: authHeaders(`contract-social-user-flag-${now}`, 'USER'),
  });
  assert.equal(flagResponse.status, 201);
  const flagged = await flagResponse.json();
  assert.equal(flagged.active, false);
  assert.equal(flagged.verified, false);

  const hideResponse = await fetch(`${baseUrl}/social-links/admin/${created.id}/hide`, {
    method: 'POST',
    headers: authHeaders(`contract-social-admin-hide-${now}`, 'ADMIN_CARE'),
  });
  assert.equal(hideResponse.status, 201);
  const hidden = await hideResponse.json();
  assert.equal(hidden.active, false);

  const publicAfterHide = await fetch(`${baseUrl}/social-links/transports/${fixtureTransport.id}`);
  assert.equal(publicAfterHide.status, 200);
  const publicAfterHideBody = await publicAfterHide.json();
  assert.equal(publicAfterHideBody.some((item) => item.id === created.id), false);

  const cleanup = await fetch(`${baseUrl}/social-links/admin/${created.id}`, {
    method: 'DELETE',
    headers: authHeaders(`contract-social-admin-cleanup-${now}`, 'ADMIN_CARE'),
  });
  assert.equal(cleanup.status, 204);
});
