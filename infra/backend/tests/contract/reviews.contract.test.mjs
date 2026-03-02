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
let fixtureAccommodation;
let fixtureTransport;
let fixtureUser;
let fixtureUserTwo;

async function cleanupFixtures() {
  await prisma.review.deleteMany({ where: { userId: { startsWith: 'contract-review-user-' } } });
  await prisma.booking.deleteMany({ where: { userId: { startsWith: 'contract-review-user-' } } });
  await prisma.user.deleteMany({ where: { id: { startsWith: 'contract-review-user-' } } });
  await prisma.transport.deleteMany({ where: { id: { startsWith: 'contract-review-transport-' } } });
  await prisma.accommodation.deleteMany({ where: { id: { startsWith: 'contract-review-accommodation-' } } });
  await prisma.vendor.deleteMany({ where: { id: { startsWith: 'contract-review-vendor-' } } });
  await prisma.island.deleteMany({ where: { slug: { startsWith: 'contract-review-island-' } } });
  await prisma.atoll.deleteMany({ where: { code: { startsWith: 'CONTRACT-REVIEW-' } } });
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
      code: `CONTRACT-REVIEW-ATOLL-${now}`,
      name: `Contract Review Atoll ${now}`,
    },
  });

  fixtureOriginAtoll = await prisma.atoll.create({
    data: {
      code: `CONTRACT-REVIEW-ORIGIN-${now}`,
      name: `Contract Review Origin Atoll ${now}`,
    },
  });

  fixtureIsland = await prisma.island.create({
    data: {
      name: `Contract Review Island ${now}`,
      slug: `contract-review-island-main-${now}`,
      atollId: fixtureAtoll.id,
      lat: 4.21,
      lng: 73.6,
    },
  });

  fixtureOriginIsland = await prisma.island.create({
    data: {
      name: `Contract Review Origin Island ${now}`,
      slug: `contract-review-island-origin-${now}`,
      atollId: fixtureOriginAtoll.id,
      lat: 4.31,
      lng: 73.7,
    },
  });

  fixtureVendor = await prisma.vendor.create({
    data: {
      id: `contract-review-vendor-${now}`,
      name: `Contract Review Vendor ${now}`,
      email: `contract-review-vendor-${now}@example.test`,
    },
  });

  fixtureAccommodation = await prisma.accommodation.create({
    data: {
      id: `contract-review-accommodation-${now}`,
      vendorId: fixtureVendor.id,
      islandId: fixtureIsland.id,
      title: `Contract Review Accommodation ${now}`,
      slug: `contract-review-accommodation-${now}`,
      type: 'GUESTHOUSE',
      rooms: 8,
      minStayNights: 1,
      price: 180,
    },
  });

  fixtureTransport = await prisma.transport.create({
    data: {
      id: `contract-review-transport-${now}`,
      vendorId: fixtureVendor.id,
      type: 'SPEEDBOAT',
      fromIslandId: fixtureOriginIsland.id,
      toIslandId: fixtureIsland.id,
      departure: new Date('2026-11-01T07:00:00.000Z'),
      arrival: new Date('2026-11-01T09:00:00.000Z'),
      capacity: 30,
      price: 95,
    },
  });

  fixtureUser = await prisma.user.create({
    data: {
      id: `contract-review-user-${now}`,
      email: `contract-review-user-${now}@example.test`,
      role: 'USER',
    },
  });

  fixtureUserTwo = await prisma.user.create({
    data: {
      id: `contract-review-user-secondary-${now}`,
      email: `contract-review-user-secondary-${now}@example.test`,
      role: 'USER',
    },
  });

  await prisma.booking.create({
    data: {
      userId: fixtureUser.id,
      accommodationId: fixtureAccommodation.id,
      transportId: fixtureTransport.id,
      guests: 2,
      totalPrice: 500,
      status: 'CONFIRMED',
    },
  });

  await prisma.booking.create({
    data: {
      userId: fixtureUserTwo.id,
      accommodationId: fixtureAccommodation.id,
      transportId: fixtureTransport.id,
      guests: 1,
      totalPrice: 420,
      status: 'CONFIRMED',
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

test('POST /api/v1/reviews creates verified accommodation review for confirmed user', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/reviews`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(fixtureUser.id, 'USER', fixtureUser.email),
    },
    body: JSON.stringify({
      targetType: 'ACCOMMODATION',
      targetId: fixtureAccommodation.id,
      rating: 5,
      title: 'Great stay',
      comment: 'Everything matched expectations',
    }),
  });

  assert.equal(response.status, 201);
  const body = await response.json();
  assert.equal(body.targetType, 'ACCOMMODATION');
  assert.equal(body.accommodationId, fixtureAccommodation.id);
  assert.equal(body.verifiedStay, true);
  assert.equal(body.rating, 5);
});

test('POST /api/v1/reviews creates verified transport review and prevents duplicates', { skip: !canRun ? skipReason : false }, async () => {
  const firstResponse = await fetch(`${baseUrl}/reviews`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(fixtureUser.id, 'USER', fixtureUser.email),
    },
    body: JSON.stringify({
      targetType: 'TRANSPORT',
      targetId: fixtureTransport.id,
      rating: 4,
      comment: 'On-time transfer',
    }),
  });

  assert.equal(firstResponse.status, 201);

  const secondResponse = await fetch(`${baseUrl}/reviews`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(fixtureUser.id, 'USER', fixtureUser.email),
    },
    body: JSON.stringify({
      targetType: 'TRANSPORT',
      targetId: fixtureTransport.id,
      rating: 3,
      comment: 'Second attempt should fail',
    }),
  });

  assert.equal(secondResponse.status, 400);
});

test('GET /api/v1/reviews/accommodations/:id returns published reviews and summary', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/reviews/accommodations/${fixtureAccommodation.id}`);
  assert.equal(response.status, 200);

  const body = await response.json();
  assert.equal(body.targetType, 'ACCOMMODATION');
  assert.equal(body.targetId, fixtureAccommodation.id);
  assert.equal(Array.isArray(body.items), true);
  assert.equal(body.items.length >= 1, true);
  assert.equal(typeof body.ratingSummary.count, 'number');
  assert.equal(typeof body.ratingSummary.average, 'number');
});

test('GET /api/v1/reviews/transports/:id returns published reviews and summary', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/reviews/transports/${fixtureTransport.id}`);
  assert.equal(response.status, 200);

  const body = await response.json();
  assert.equal(body.targetType, 'TRANSPORT');
  assert.equal(body.targetId, fixtureTransport.id);
  assert.equal(Array.isArray(body.items), true);
  assert.equal(typeof body.ratingSummary.count, 'number');
  assert.equal(typeof body.ratingSummary.average, 'number');
});

test('Reviews moderation flow supports flag queue hide and publish', { skip: !canRun ? skipReason : false }, async () => {
  const createResponse = await fetch(`${baseUrl}/reviews`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(fixtureUserTwo.id, 'USER', fixtureUserTwo.email),
    },
    body: JSON.stringify({
      targetType: 'ACCOMMODATION',
      targetId: fixtureAccommodation.id,
      rating: 4,
      title: 'Moderation review',
      comment: 'Needs moderation lifecycle validation',
    }),
  });

  assert.equal(createResponse.status, 201);
  const created = await createResponse.json();

  const flagResponse = await fetch(`${baseUrl}/reviews/${created.id}/flag`, {
    method: 'POST',
    headers: authHeaders(`contract-review-flagger-${Date.now()}`, 'USER'),
  });
  assert.equal(flagResponse.status, 201);
  const flagged = await flagResponse.json();
  assert.equal(flagged.status, 'FLAGGED');

  const publicAfterFlag = await fetch(`${baseUrl}/reviews/accommodations/${fixtureAccommodation.id}`);
  assert.equal(publicAfterFlag.status, 200);
  const publicAfterFlagBody = await publicAfterFlag.json();
  assert.equal(publicAfterFlagBody.items.some((item) => item.id === created.id), false);

  const queueResponse = await fetch(`${baseUrl}/reviews/admin/moderation`, {
    headers: authHeaders(`contract-review-admin-${Date.now()}`, 'ADMIN_CARE'),
  });
  assert.equal(queueResponse.status, 200);
  const queue = await queueResponse.json();
  assert.equal(Array.isArray(queue), true);
  assert.equal(queue.some((item) => item.id === created.id), true);

  const hideResponse = await fetch(`${baseUrl}/reviews/admin/${created.id}/hide`, {
    method: 'POST',
    headers: authHeaders(`contract-review-admin-hide-${Date.now()}`, 'ADMIN_CARE'),
  });
  assert.equal(hideResponse.status, 201);
  const hidden = await hideResponse.json();
  assert.equal(hidden.status, 'HIDDEN');

  const publishResponse = await fetch(`${baseUrl}/reviews/admin/${created.id}/publish`, {
    method: 'POST',
    headers: authHeaders(`contract-review-admin-publish-${Date.now()}`, 'ADMIN_CARE'),
  });
  assert.equal(publishResponse.status, 201);
  const published = await publishResponse.json();
  assert.equal(published.status, 'PUBLISHED');

  const publicAfterPublish = await fetch(`${baseUrl}/reviews/accommodations/${fixtureAccommodation.id}`);
  assert.equal(publicAfterPublish.status, 200);
  const publicAfterPublishBody = await publicAfterPublish.json();
  assert.equal(publicAfterPublishBody.items.some((item) => item.id === created.id), true);
});
