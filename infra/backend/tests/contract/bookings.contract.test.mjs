import assert from 'node:assert/strict';
import test from 'node:test';
import { PrismaClient } from '@prisma/client';
import { authHeaders, baseUrl, canRun, registerBackendLifecycle, skipReason } from './contract-harness.mjs';

registerBackendLifecycle(test);

const prisma = new PrismaClient();

let fixtureAtoll;
let fixtureIsland;
let otherAtoll;
let otherIsland;
let fixtureVendor;
let fixtureAccommodation;
let fixtureTransport;
let mismatchedTransport;
let fixtureFlightTransport;
let fixtureTransferIsland;
let fixtureItineraryLegOne;
let fixtureItineraryLegTwo;

async function createLifecycleBookingFixtures(suffix) {
  const accommodation = await prisma.accommodation.create({
    data: {
      id: `contract-accommodation-booking-lifecycle-${suffix}`,
      vendorId: fixtureVendor.id,
      islandId: fixtureIsland.id,
      title: `Contract Lifecycle Accommodation ${suffix}`,
      slug: `contract-booking-lifecycle-accommodation-${suffix}`,
      description: 'Lifecycle contract fixture accommodation',
      type: 'GUESTHOUSE',
      rooms: 12,
      minStayNights: 1,
      price: 180,
    },
  });

  const transport = await prisma.transport.create({
    data: {
      id: `contract-transport-booking-lifecycle-${suffix}`,
      vendorId: fixtureVendor.id,
      type: 'SPEEDBOAT',
      fromIslandId: otherIsland.id,
      toIslandId: fixtureIsland.id,
      departure: new Date('2026-08-01T07:00:00.000Z'),
      arrival: new Date('2026-08-01T09:00:00.000Z'),
      capacity: 20,
      price: 75,
    },
  });

  return {
    accommodationId: accommodation.id,
    transportId: transport.id,
  };
}

async function cleanupFixtures() {
  await prisma.booking.deleteMany({ where: { userId: { startsWith: 'contract-booking-user-' } } });
  await prisma.user.deleteMany({ where: { id: { startsWith: 'contract-booking-user-' } } });
  await prisma.accommodationSeasonalRate.deleteMany({ where: { accommodationId: { startsWith: 'contract-accommodation-booking-' } } });
  await prisma.transportDisruption.deleteMany({
    where: {
      OR: [
        { transportId: { startsWith: 'contract-transport-booking-' } },
        { replacementTransportId: { startsWith: 'contract-transport-booking-' } },
      ],
    },
  });
  await prisma.transportFareClass.deleteMany({ where: { transportId: { startsWith: 'contract-transport-booking-' } } });
  await prisma.transport.deleteMany({ where: { id: { startsWith: 'contract-transport-booking-' } } });
  await prisma.accommodation.deleteMany({ where: { id: { startsWith: 'contract-accommodation-booking-' } } });
  await prisma.vendor.deleteMany({ where: { id: { startsWith: 'contract-vendor-booking-' } } });
  await prisma.island.deleteMany({ where: { slug: { startsWith: 'contract-booking-island-' } } });
  await prisma.atoll.deleteMany({ where: { code: { startsWith: 'CONTRACT-BOOKING-' } } });
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
      code: `CONTRACT-BOOKING-ATOLL-${now}`,
      name: `Contract Booking Atoll ${now}`,
    },
  });

  otherAtoll = await prisma.atoll.create({
    data: {
      code: `CONTRACT-BOOKING-OTHER-${now}`,
      name: `Contract Booking Other Atoll ${now}`,
    },
  });

  fixtureIsland = await prisma.island.create({
    data: {
      name: `Contract Booking Island ${now}`,
      slug: `contract-booking-island-main-${now}`,
      atollId: fixtureAtoll.id,
      lat: 4.22,
      lng: 73.6,
    },
  });

  otherIsland = await prisma.island.create({
    data: {
      name: `Contract Booking Other Island ${now}`,
      slug: `contract-booking-island-other-${now}`,
      atollId: otherAtoll.id,
      lat: 4.4,
      lng: 73.8,
    },
  });

  fixtureVendor = await prisma.vendor.create({
    data: {
      id: `contract-vendor-booking-${now}`,
      name: `Contract Booking Vendor ${now}`,
      email: `contract-booking-vendor-${now}@example.test`,
    },
  });

  fixtureAccommodation = await prisma.accommodation.create({
    data: {
      id: `contract-accommodation-booking-${now}`,
      vendorId: fixtureVendor.id,
      islandId: fixtureIsland.id,
      title: `Contract Booking Accommodation ${now}`,
      slug: `contract-booking-accommodation-${now}`,
      description: 'Contract booking accommodation fixture',
      type: 'GUESTHOUSE',
      rooms: 4,
      minStayNights: 3,
      price: 180,
    },
  });

  fixtureTransport = await prisma.transport.create({
    data: {
      id: `contract-transport-booking-main-${now}`,
      vendorId: fixtureVendor.id,
      type: 'SPEEDBOAT',
      fromIslandId: otherIsland.id,
      toIslandId: fixtureIsland.id,
      departure: new Date('2026-08-01T07:00:00.000Z'),
      arrival: new Date('2026-08-01T09:00:00.000Z'),
      capacity: 5,
      price: 70,
    },
  });

  mismatchedTransport = await prisma.transport.create({
    data: {
      id: `contract-transport-booking-mismatch-${now}`,
      vendorId: fixtureVendor.id,
      type: 'SPEEDBOAT',
      fromIslandId: fixtureIsland.id,
      toIslandId: otherIsland.id,
      departure: new Date('2026-08-01T07:00:00.000Z'),
      arrival: new Date('2026-08-01T09:00:00.000Z'),
      capacity: 5,
      price: 50,
    },
  });

  fixtureFlightTransport = await prisma.transport.create({
    data: {
      id: `contract-transport-booking-flight-${now}`,
      vendorId: fixtureVendor.id,
      type: 'DOMESTIC_FLIGHT',
      fromIslandId: otherIsland.id,
      toIslandId: fixtureIsland.id,
      departure: new Date('2026-08-01T10:00:00.000Z'),
      arrival: new Date('2026-08-01T12:00:00.000Z'),
      capacity: 10,
      price: 120,
    },
  });

  await prisma.transportFareClass.createMany({
    data: [
      {
        transportId: fixtureFlightTransport.id,
        code: 'ECONOMY',
        name: 'Economy',
        seats: 2,
        baggageKg: 20,
        price: 110,
      },
      {
        transportId: fixtureFlightTransport.id,
        code: 'FLEX',
        name: 'Flex',
        seats: 1,
        baggageKg: 30,
        price: 160,
      },
    ],
  });

  fixtureTransferIsland = await prisma.island.create({
    data: {
      name: `Contract Booking Transfer Island ${now}`,
      slug: `contract-booking-island-transfer-${now}`,
      atollId: fixtureAtoll.id,
      lat: 4.3,
      lng: 73.7,
    },
  });

  fixtureItineraryLegOne = await prisma.transport.create({
    data: {
      id: `contract-transport-booking-itinerary-1-${now}`,
      vendorId: fixtureVendor.id,
      type: 'SPEEDBOAT',
      fromIslandId: otherIsland.id,
      toIslandId: fixtureTransferIsland.id,
      departure: new Date('2026-08-01T05:00:00.000Z'),
      arrival: new Date('2026-08-01T06:00:00.000Z'),
      capacity: 20,
      price: 60,
    },
  });

  fixtureItineraryLegTwo = await prisma.transport.create({
    data: {
      id: `contract-transport-booking-itinerary-2-${now}`,
      vendorId: fixtureVendor.id,
      type: 'SPEEDBOAT',
      fromIslandId: fixtureTransferIsland.id,
      toIslandId: fixtureIsland.id,
      departure: new Date('2026-08-01T06:30:00.000Z'),
      arrival: new Date('2026-08-01T07:30:00.000Z'),
      capacity: 20,
      price: 65,
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

test('POST /api/v1/bookings requires auth headers', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({}),
  });

  assert.equal(response.status, 401);
});

test('POST /api/v1/bookings enforces transport dependency for accommodation', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-booking-user-${Date.now()}`, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: fixtureAccommodation.id,
      startDate: '2026-08-02',
      endDate: '2026-08-05',
      guests: 2,
    }),
  });

  assert.equal(response.status, 400);
});

test('POST /api/v1/bookings validates transport destination for accommodation island', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-booking-user-${Date.now()}`, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: fixtureAccommodation.id,
      transportId: mismatchedTransport.id,
      startDate: '2026-08-02',
      endDate: '2026-08-05',
      guests: 2,
    }),
  });

  assert.equal(response.status, 400);
});

test('POST /api/v1/bookings creates booking when dependencies are valid', { skip: !canRun ? skipReason : false }, async () => {
  const userId = `contract-booking-user-${Date.now()}`;
  const response = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(userId, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: fixtureAccommodation.id,
      transportId: fixtureTransport.id,
      startDate: '2026-08-02',
      endDate: '2026-08-05',
      guests: 2,
    }),
  });

  assert.equal(response.status, 201);
  const body = await response.json();
  assert.equal(body.userId, userId);
  assert.equal(body.accommodation?.id, fixtureAccommodation.id);
  assert.equal(body.transport?.id, fixtureTransport.id);
  assert.equal(body.status, 'HOLD');
  assert.equal(typeof body.holdExpiresAt, 'string');
});

test('POST /api/v1/bookings enforces accommodation minimum stay', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-booking-user-${Date.now()}`, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: fixtureAccommodation.id,
      transportId: fixtureTransport.id,
      startDate: '2026-08-02',
      endDate: '2026-08-03',
      guests: 1,
    }),
  });

  assert.equal(response.status, 400);
});

test('POST /api/v1/bookings rejects accommodation blackout windows', { skip: !canRun ? skipReason : false }, async () => {
  await prisma.accommodationBlackout.create({
    data: {
      accommodationId: fixtureAccommodation.id,
      startDate: new Date('2026-08-01T00:00:00.000Z'),
      endDate: new Date('2026-08-06T00:00:00.000Z'),
      reason: 'Contract blackout',
    },
  });

  const response = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-booking-user-${Date.now()}`, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: fixtureAccommodation.id,
      transportId: fixtureTransport.id,
      startDate: '2026-08-02',
      endDate: '2026-08-05',
      guests: 1,
    }),
  });

  assert.equal(response.status, 400);

  await prisma.accommodationBlackout.deleteMany({
    where: {
      accommodationId: fixtureAccommodation.id,
      reason: 'Contract blackout',
    },
  });
});

test('POST /api/v1/bookings rejects when accommodation inventory is exhausted', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const inventoryAccommodation = await prisma.accommodation.create({
    data: {
      id: `contract-accommodation-booking-inventory-${now}`,
      vendorId: fixtureVendor.id,
      islandId: fixtureIsland.id,
      title: `Contract Inventory Accommodation ${now}`,
      slug: `contract-inventory-accommodation-${now}`,
      type: 'GUESTHOUSE',
      rooms: 1,
      minStayNights: 1,
      price: 200,
    },
  });

  const inventoryUserId = `contract-booking-user-inventory-${now}`;
  await prisma.user.upsert({
    where: { id: inventoryUserId },
    update: {
      role: 'USER',
      email: `contract-booking-user-inventory-${now}@example.test`,
    },
    create: {
      id: inventoryUserId,
      role: 'USER',
      email: `contract-booking-user-inventory-${now}@example.test`,
    },
  });

  await prisma.booking.create({
    data: {
      userId: inventoryUserId,
      accommodationId: inventoryAccommodation.id,
      transportId: fixtureTransport.id,
      startDate: new Date('2026-08-02T00:00:00.000Z'),
      endDate: new Date('2026-08-05T00:00:00.000Z'),
      guests: 1,
      totalPrice: 200,
      status: 'CONFIRMED',
    },
  });

  const response = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-booking-user-${Date.now()}`, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: inventoryAccommodation.id,
      transportId: fixtureTransport.id,
      startDate: '2026-08-03',
      endDate: '2026-08-04',
      guests: 1,
    }),
  });

  assert.equal(response.status, 400);
});

test('POST /api/v1/bookings enforces transport seat decrement based on reserved seats', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const seatAccommodation = await prisma.accommodation.create({
    data: {
      id: `contract-accommodation-booking-seat-${now}`,
      vendorId: fixtureVendor.id,
      islandId: fixtureIsland.id,
      title: `Contract Seat Accommodation ${now}`,
      slug: `contract-seat-accommodation-${now}`,
      type: 'GUESTHOUSE',
      rooms: 10,
      minStayNights: 1,
      price: 190,
    },
  });

  const tightTransport = await prisma.transport.create({
    data: {
      id: `contract-transport-booking-tight-${now}`,
      vendorId: fixtureVendor.id,
      type: 'SPEEDBOAT',
      fromIslandId: otherIsland.id,
      toIslandId: fixtureIsland.id,
      departure: new Date('2026-08-02T07:00:00.000Z'),
      arrival: new Date('2026-08-02T09:00:00.000Z'),
      capacity: 5,
      price: 80,
    },
  });

  const firstUserId = `contract-booking-user-seat-1-${now}`;
  const secondUserId = `contract-booking-user-seat-2-${now}`;

  const firstResponse = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(firstUserId, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: seatAccommodation.id,
      transportId: tightTransport.id,
      startDate: '2026-08-03',
      endDate: '2026-08-06',
      guests: 4,
    }),
  });

  assert.equal(firstResponse.status, 201);

  const secondResponse = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(secondUserId, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: seatAccommodation.id,
      transportId: tightTransport.id,
      startDate: '2026-08-03',
      endDate: '2026-08-06',
      guests: 2,
    }),
  });

  assert.equal(secondResponse.status, 400);
});

test('POST /api/v1/bookings requires fare class for domestic flight booking', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-booking-user-${Date.now()}`, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: fixtureAccommodation.id,
      transportId: fixtureFlightTransport.id,
      startDate: '2026-08-02',
      endDate: '2026-08-05',
      guests: 1,
    }),
  });

  assert.equal(response.status, 400);
});

test('POST /api/v1/bookings enforces fare class seat inventory for domestic flights', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const firstResponse = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-booking-user-flight-seat-1-${now}`, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: fixtureAccommodation.id,
      transportId: fixtureFlightTransport.id,
      transportFareClassCode: 'ECONOMY',
      startDate: '2026-08-02',
      endDate: '2026-08-05',
      guests: 2,
    }),
  });

  assert.equal(firstResponse.status, 201);

  const secondResponse = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-booking-user-flight-seat-2-${now}`, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: fixtureAccommodation.id,
      transportId: fixtureFlightTransport.id,
      transportFareClassCode: 'ECONOMY',
      startDate: '2026-08-02',
      endDate: '2026-08-05',
      guests: 1,
    }),
  });

  assert.equal(secondResponse.status, 400);
});

test('POST /api/v1/bookings applies accommodation seasonal nightly pricing to totalPrice', { skip: !canRun ? skipReason : false }, async () => {
  await prisma.accommodationSeasonalRate.create({
    data: {
      accommodationId: fixtureAccommodation.id,
      name: 'Contract Seasonal Rate',
      startDate: new Date('2026-08-02T00:00:00.000Z'),
      endDate: new Date('2026-08-06T00:00:00.000Z'),
      nightlyPrice: 300,
      priority: 10,
    },
  });

  const userId = `contract-booking-user-seasonal-${Date.now()}`;
  const response = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(userId, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: fixtureAccommodation.id,
      transportId: fixtureTransport.id,
      startDate: '2026-08-02',
      endDate: '2026-08-05',
      guests: 1,
    }),
  });

  assert.equal(response.status, 201);
  const body = await response.json();
  assert.equal(Number(body.totalPrice), 970);
});

test('POST /api/v1/bookings rejects booking on cancelled disrupted transport', { skip: !canRun ? skipReason : false }, async () => {
  const replacement = await prisma.transport.create({
    data: {
      id: `contract-transport-booking-replacement-${Date.now()}`,
      vendorId: fixtureVendor.id,
      type: 'SPEEDBOAT',
      fromIslandId: otherIsland.id,
      toIslandId: fixtureIsland.id,
      departure: new Date('2026-08-01T11:00:00.000Z'),
      arrival: new Date('2026-08-01T13:00:00.000Z'),
      capacity: 8,
      price: 95,
    },
  });

  await prisma.transportDisruption.create({
    data: {
      transportId: fixtureTransport.id,
      status: 'CANCELLED',
      reason: 'Harbor closure',
      replacementTransportId: replacement.id,
      startsAt: new Date('2026-08-01T06:00:00.000Z'),
    },
  });

  const response = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-booking-user-disruption-${Date.now()}`, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: fixtureAccommodation.id,
      transportId: fixtureTransport.id,
      startDate: '2026-08-02',
      endDate: '2026-08-05',
      guests: 1,
    }),
  });

  assert.equal(response.status, 400);
  const body = await response.json();
  assert.equal(typeof body.message, 'string');
  assert.equal(body.message.includes('cancelled'), true);

  await prisma.transportDisruption.deleteMany({
    where: {
      transportId: fixtureTransport.id,
      status: 'CANCELLED',
    },
  });

  await prisma.transport.deleteMany({
    where: {
      id: replacement.id,
    },
  });
});

test('PATCH /api/v1/bookings/:id/confirm transitions HOLD booking to CONFIRMED', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const fixtures = await createLifecycleBookingFixtures(`confirm-${now}`);
  const userId = `contract-booking-user-confirm-${now}`;
  const createResponse = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(userId, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: fixtures.accommodationId,
      transportId: fixtures.transportId,
      startDate: '2026-08-02',
      endDate: '2026-08-05',
      guests: 1,
    }),
  });

  assert.equal(createResponse.status, 201);
  const created = await createResponse.json();
  assert.equal(created.status, 'HOLD');

  const confirmResponse = await fetch(`${baseUrl}/bookings/${created.id}/confirm`, {
    method: 'PATCH',
    headers: {
      ...authHeaders(userId, 'USER'),
    },
  });

  assert.equal(confirmResponse.status, 200);
  const confirmed = await confirmResponse.json();
  assert.equal(confirmed.status, 'CONFIRMED');
  assert.equal(confirmed.holdExpiresAt, null);
});

test('PATCH /api/v1/bookings/:id/confirm rejects expired HOLD booking', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const fixtures = await createLifecycleBookingFixtures(`expired-${now}`);
  const userId = `contract-booking-user-expire-${now}`;
  const createResponse = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(userId, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: fixtures.accommodationId,
      transportId: fixtures.transportId,
      startDate: '2026-08-02',
      endDate: '2026-08-05',
      guests: 1,
    }),
  });

  assert.equal(createResponse.status, 201);
  const created = await createResponse.json();

  await prisma.booking.update({
    where: { id: created.id },
    data: {
      holdExpiresAt: new Date('2026-01-01T00:00:00.000Z'),
    },
  });

  const confirmResponse = await fetch(`${baseUrl}/bookings/${created.id}/confirm`, {
    method: 'PATCH',
    headers: {
      ...authHeaders(userId, 'USER'),
    },
  });

  assert.equal(confirmResponse.status, 400);

  const persisted = await prisma.booking.findUnique({ where: { id: created.id } });
  assert.equal(persisted?.status, 'CANCELLED');
});

test('PATCH /api/v1/bookings/:id/refund allows ADMIN_FINANCE for cancelled booking', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const fixtures = await createLifecycleBookingFixtures(`refund-${now}`);
  const userId = `contract-booking-user-refund-${now}`;
  const createResponse = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(userId, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: fixtures.accommodationId,
      transportId: fixtures.transportId,
      startDate: '2026-08-02',
      endDate: '2026-08-05',
      guests: 1,
    }),
  });

  assert.equal(createResponse.status, 201);
  const created = await createResponse.json();

  const cancelResponse = await fetch(`${baseUrl}/bookings/${created.id}/cancel`, {
    method: 'PATCH',
    headers: {
      ...authHeaders(userId, 'USER'),
    },
  });

  assert.equal(cancelResponse.status, 200);

  const refundResponse = await fetch(`${baseUrl}/bookings/${created.id}/refund`, {
    method: 'PATCH',
    headers: {
      ...authHeaders(`contract-booking-admin-finance-${Date.now()}`, 'ADMIN_FINANCE'),
    },
  });

  assert.equal(refundResponse.status, 200);
  const refunded = await refundResponse.json();
  assert.equal(refunded.status, 'REFUNDED');
});

test('POST /api/v1/bookings/:id/rebook creates replacement booking and cancels original', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const fixtures = await createLifecycleBookingFixtures(`rebook-${now}`);
  const userId = `contract-booking-user-rebook-${now}`;

  const createResponse = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(userId, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: fixtures.accommodationId,
      transportId: fixtures.transportId,
      startDate: '2026-08-02',
      endDate: '2026-08-05',
      guests: 1,
    }),
  });

  assert.equal(createResponse.status, 201);
  const original = await createResponse.json();

  const replacementTransport = await prisma.transport.create({
    data: {
      id: `contract-transport-booking-rebook-replacement-${now}`,
      vendorId: fixtureVendor.id,
      type: 'SPEEDBOAT',
      fromIslandId: otherIsland.id,
      toIslandId: fixtureIsland.id,
      departure: new Date('2026-08-03T08:00:00.000Z'),
      arrival: new Date('2026-08-03T10:00:00.000Z'),
      capacity: 20,
      price: 85,
    },
  });

  const rebookResponse = await fetch(`${baseUrl}/bookings/${original.id}/rebook`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(userId, 'USER'),
    },
    body: JSON.stringify({
      transportId: replacementTransport.id,
      startDate: '2026-08-04',
      endDate: '2026-08-07',
      guests: 2,
    }),
  });

  assert.equal(rebookResponse.status, 201);
  const rebookBody = await rebookResponse.json();
  assert.equal(rebookBody.rebookedFromBookingId, original.id);
  assert.equal(rebookBody.replacementBooking.transport?.id, replacementTransport.id);
  assert.equal(rebookBody.replacementBooking.status, 'HOLD');
  assert.equal(rebookBody.replacementBooking.id === original.id, false);

  const originalPersisted = await prisma.booking.findUnique({ where: { id: original.id } });
  assert.equal(originalPersisted?.status, 'CANCELLED');
});

test('POST /api/v1/bookings/:id/rebook rejects non-owner user', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const fixtures = await createLifecycleBookingFixtures(`rebook-owner-${now}`);
  const ownerUserId = `contract-booking-user-rebook-owner-${now}`;
  const anotherUserId = `contract-booking-user-rebook-other-${now}`;

  const createResponse = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(ownerUserId, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: fixtures.accommodationId,
      transportId: fixtures.transportId,
      startDate: '2026-08-02',
      endDate: '2026-08-05',
      guests: 1,
    }),
  });

  assert.equal(createResponse.status, 201);
  const original = await createResponse.json();

  const rebookResponse = await fetch(`${baseUrl}/bookings/${original.id}/rebook`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(anotherUserId, 'USER'),
    },
    body: JSON.stringify({
      startDate: '2026-08-03',
      endDate: '2026-08-06',
    }),
  });

  assert.equal(rebookResponse.status, 403);
});

test('POST /api/v1/bookings/:id/rebook rejects cancelled booking', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const fixtures = await createLifecycleBookingFixtures(`rebook-cancelled-${now}`);
  const userId = `contract-booking-user-rebook-cancelled-${now}`;

  const createResponse = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(userId, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: fixtures.accommodationId,
      transportId: fixtures.transportId,
      startDate: '2026-08-02',
      endDate: '2026-08-05',
      guests: 1,
    }),
  });

  assert.equal(createResponse.status, 201);
  const original = await createResponse.json();

  const cancelResponse = await fetch(`${baseUrl}/bookings/${original.id}/cancel`, {
    method: 'PATCH',
    headers: authHeaders(userId, 'USER'),
  });
  assert.equal(cancelResponse.status, 200);

  const rebookResponse = await fetch(`${baseUrl}/bookings/${original.id}/rebook`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(userId, 'USER'),
    },
    body: JSON.stringify({
      startDate: '2026-08-04',
      endDate: '2026-08-07',
    }),
  });

  assert.equal(rebookResponse.status, 400);
});

test('POST /api/v1/bookings/itinerary/validate validates multi-leg itinerary alignment', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/bookings/itinerary/validate`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-booking-user-itinerary-${Date.now()}`, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: fixtureAccommodation.id,
      startDate: '2026-08-02',
      itineraryTransportIds: [fixtureItineraryLegOne.id, fixtureItineraryLegTwo.id],
    }),
  });

  assert.equal(response.status, 201);
  const body = await response.json();
  assert.equal(body.valid, true);
  assert.equal(Array.isArray(body.violations), true);
  assert.equal(body.violations.length, 0);
  assert.equal(body.itinerary.finalTransportId, fixtureItineraryLegTwo.id);
});

test('POST /api/v1/bookings/itinerary/validate rejects invalid leg continuity', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/bookings/itinerary/validate`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-booking-user-itinerary-invalid-${Date.now()}`, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: fixtureAccommodation.id,
      startDate: '2026-08-02',
      itineraryTransportIds: [fixtureItineraryLegTwo.id, fixtureItineraryLegOne.id],
    }),
  });

  assert.equal(response.status, 201);
  const body = await response.json();
  assert.equal(body.valid, false);
  assert.equal(body.violations.length > 0, true);
});

test('POST /api/v1/bookings creates booking from itineraryTransportIds using final leg', { skip: !canRun ? skipReason : false }, async () => {
  const userId = `contract-booking-user-itinerary-create-${Date.now()}`;
  const response = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(userId, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: fixtureAccommodation.id,
      itineraryTransportIds: [fixtureItineraryLegOne.id, fixtureItineraryLegTwo.id],
      startDate: '2026-08-02',
      endDate: '2026-08-05',
      guests: 1,
    }),
  });

  assert.equal(response.status, 201);
  const body = await response.json();
  assert.equal(body.transport?.id, fixtureItineraryLegTwo.id);
});

test('GET /api/v1/bookings includes management eligibility metadata', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const fixtures = await createLifecycleBookingFixtures(`list-meta-${now}`);
  const userId = `contract-booking-user-list-meta-${now}`;

  const createResponse = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(userId, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: fixtures.accommodationId,
      transportId: fixtures.transportId,
      startDate: '2026-08-02',
      endDate: '2026-08-05',
      guests: 1,
    }),
  });

  assert.equal(createResponse.status, 201);
  const created = await createResponse.json();

  const listResponse = await fetch(`${baseUrl}/bookings`, {
    headers: authHeaders(userId, 'USER'),
  });
  assert.equal(listResponse.status, 200);

  const listBody = await listResponse.json();
  const listed = listBody.find((item) => item.id === created.id);
  assert.ok(listed);
  assert.equal(typeof listed.management, 'object');
  assert.equal(typeof listed.management.holdExpired, 'boolean');
  assert.equal(typeof listed.management.canConfirm, 'boolean');
  assert.equal(typeof listed.management.canCancel, 'boolean');
  assert.equal(typeof listed.management.canRebook, 'boolean');
  assert.equal(listed.management.canRebook, true);
});

test('GET /api/v1/bookings/:id/rebook/template returns defaults for owner', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const fixtures = await createLifecycleBookingFixtures(`rebook-template-${now}`);
  const userId = `contract-booking-user-rebook-template-${now}`;

  const createResponse = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(userId, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: fixtures.accommodationId,
      transportId: fixtures.transportId,
      startDate: '2026-08-02',
      endDate: '2026-08-05',
      guests: 2,
    }),
  });

  assert.equal(createResponse.status, 201);
  const created = await createResponse.json();

  const templateResponse = await fetch(`${baseUrl}/bookings/${created.id}/rebook/template`, {
    headers: authHeaders(userId, 'USER'),
  });
  assert.equal(templateResponse.status, 200);

  const body = await templateResponse.json();
  assert.equal(body.booking.id, created.id);
  assert.equal(body.template.canRebook, true);
  assert.equal(body.template.defaults.accommodationId, fixtures.accommodationId);
  assert.equal(body.template.defaults.transportId, fixtures.transportId);
  assert.equal(body.template.defaults.guests, 2);
});

test('GET /api/v1/bookings/:id/rebook/template rejects non-owner user', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const fixtures = await createLifecycleBookingFixtures(`rebook-template-forbidden-${now}`);
  const ownerUserId = `contract-booking-user-rebook-template-owner-${now}`;
  const anotherUserId = `contract-booking-user-rebook-template-other-${now}`;

  const createResponse = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(ownerUserId, 'USER'),
    },
    body: JSON.stringify({
      accommodationId: fixtures.accommodationId,
      transportId: fixtures.transportId,
      startDate: '2026-08-02',
      endDate: '2026-08-05',
      guests: 1,
    }),
  });

  assert.equal(createResponse.status, 201);
  const created = await createResponse.json();

  const templateResponse = await fetch(`${baseUrl}/bookings/${created.id}/rebook/template`, {
    headers: authHeaders(anotherUserId, 'USER'),
  });
  assert.equal(templateResponse.status, 403);
});
