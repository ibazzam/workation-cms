import assert from 'node:assert/strict';
import test from 'node:test';
import { PrismaClient } from '@prisma/client';
import { authHeaders, baseUrl, canRun, registerBackendLifecycle, skipReason } from '../contract/contract-harness.mjs';

registerBackendLifecycle(test);

const prisma = new PrismaClient();

let fixtureAtoll;
let fixtureIsland;
let fixtureOriginAtoll;
let fixtureOriginIsland;
let fixtureVendor;
let fixtureAccommodation;
let fixtureTransport;

async function cleanupFixtures() {
  await prisma.payment.deleteMany({ where: { booking: { userId: { startsWith: 'e2e-journey-user-' } } } });
  await prisma.booking.deleteMany({ where: { userId: { startsWith: 'e2e-journey-user-' } } });
  await prisma.user.deleteMany({ where: { id: { startsWith: 'e2e-journey-user-' } } });
  await prisma.transport.deleteMany({ where: { id: { startsWith: 'e2e-journey-transport-' } } });
  await prisma.accommodation.deleteMany({ where: { id: { startsWith: 'e2e-journey-accommodation-' } } });
  await prisma.vendor.deleteMany({ where: { id: { startsWith: 'e2e-journey-vendor-' } } });
  await prisma.island.deleteMany({ where: { slug: { startsWith: 'e2e-journey-island-' } } });
  await prisma.atoll.deleteMany({ where: { code: { startsWith: 'E2E-JOURNEY-' } } });
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
      code: `E2E-JOURNEY-ATOLL-${now}`,
      name: `E2E Journey Atoll ${now}`,
    },
  });

  fixtureOriginAtoll = await prisma.atoll.create({
    data: {
      code: `E2E-JOURNEY-ORIGIN-${now}`,
      name: `E2E Journey Origin Atoll ${now}`,
    },
  });

  fixtureIsland = await prisma.island.create({
    data: {
      name: `E2E Journey Island ${now}`,
      slug: `e2e-journey-island-destination-${now}`,
      atollId: fixtureAtoll.id,
      lat: 4.2,
      lng: 73.6,
    },
  });

  fixtureOriginIsland = await prisma.island.create({
    data: {
      name: `E2E Journey Origin Island ${now}`,
      slug: `e2e-journey-island-origin-${now}`,
      atollId: fixtureOriginAtoll.id,
      lat: 4.35,
      lng: 73.8,
    },
  });

  fixtureVendor = await prisma.vendor.create({
    data: {
      id: `e2e-journey-vendor-${now}`,
      name: `E2E Journey Vendor ${now}`,
      email: `e2e-journey-vendor-${now}@example.test`,
    },
  });

  fixtureAccommodation = await prisma.accommodation.create({
    data: {
      id: `e2e-journey-accommodation-${now}`,
      vendorId: fixtureVendor.id,
      islandId: fixtureIsland.id,
      title: `E2E Journey Accommodation ${now}`,
      slug: `e2e-journey-accommodation-${now}`,
      type: 'GUESTHOUSE',
      rooms: 8,
      minStayNights: 2,
      price: 220,
    },
  });

  fixtureTransport = await prisma.transport.create({
    data: {
      id: `e2e-journey-transport-${now}`,
      vendorId: fixtureVendor.id,
      type: 'SPEEDBOAT',
      fromIslandId: fixtureOriginIsland.id,
      toIslandId: fixtureIsland.id,
      departure: new Date('2026-11-01T07:00:00.000Z'),
      arrival: new Date('2026-11-01T09:00:00.000Z'),
      capacity: 20,
      price: 90,
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

test('E2E journey: search -> book -> pay -> manage', { skip: !canRun ? skipReason : false }, async () => {
  const userId = `e2e-journey-user-${Date.now()}`;

  const searchAccommodation = await fetch(`${baseUrl}/accommodations?islandId=${fixtureIsland.id}`);
  assert.equal(searchAccommodation.status, 200);
  const accommodationRows = await searchAccommodation.json();
  assert.equal(accommodationRows.some((row) => row.id === fixtureAccommodation.id), true);

  const searchTransport = await fetch(`${baseUrl}/transports/schedule?date=2026-11-01&fromIslandId=${fixtureOriginIsland.id}&toIslandId=${fixtureIsland.id}`);
  assert.equal(searchTransport.status, 200);
  const transportRows = await searchTransport.json();
  assert.equal(transportRows.some((row) => row.id === fixtureTransport.id), true);

  const createBooking = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(userId, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: fixtureAccommodation.id,
      transportId: fixtureTransport.id,
      startDate: '2026-11-02',
      endDate: '2026-11-05',
      guests: 2,
    }),
  });

  assert.equal(createBooking.status, 201);
  const createdBooking = await createBooking.json();
  assert.equal(createdBooking.status, 'HOLD');

  const persistedBooking = await prisma.booking.findFirst({
    where: {
      userId,
      accommodationId: fixtureAccommodation.id,
      transportId: fixtureTransport.id,
    },
    orderBy: { createdAt: 'desc' },
  });
  assert.ok(persistedBooking);

  const createPaymentIntent = await fetch(`${baseUrl}/payments/intents`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(userId, 'USER'),
    },
    body: JSON.stringify({
      bookingId: persistedBooking.id,
      provider: 'BML',
      currency: 'USD',
    }),
  });

  assert.equal(createPaymentIntent.status, 201);

  const confirmBooking = await fetch(`${baseUrl}/bookings/${persistedBooking.id}/confirm`, {
    method: 'PATCH',
    headers: authHeaders(userId, 'USER'),
  });
  assert.equal(confirmBooking.status, 200);

  const rebookTemplate = await fetch(`${baseUrl}/bookings/${persistedBooking.id}/rebook/template`, {
    headers: authHeaders(userId, 'USER'),
  });
  assert.equal(rebookTemplate.status, 200);

  const performRebook = await fetch(`${baseUrl}/bookings/${persistedBooking.id}/rebook`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(userId, 'USER'),
    },
    body: JSON.stringify({
      startDate: '2026-11-03',
      endDate: '2026-11-06',
      guests: 2,
    }),
  });

  assert.equal(performRebook.status, 201);
  const rebookBody = await performRebook.json();
  assert.equal(rebookBody.rebookedFromBookingId, persistedBooking.id);

  const cancelReplacement = await fetch(`${baseUrl}/bookings/${rebookBody.replacementBooking.id}/cancel`, {
    method: 'PATCH',
    headers: authHeaders(userId, 'USER'),
  });
  assert.equal(cancelReplacement.status, 200);

  const listBookings = await fetch(`${baseUrl}/bookings`, {
    headers: authHeaders(userId, 'USER'),
  });
  assert.equal(listBookings.status, 200);
  const listedRows = await listBookings.json();
  assert.equal(Array.isArray(listedRows), true);
  assert.equal(listedRows.some((row) => row.id === rebookBody.replacementBooking.id), true);
});
