import assert from 'node:assert/strict';
import test from 'node:test';
import { PrismaClient } from '@prisma/client';
import { authHeaders, baseUrl, canRun, registerBackendLifecycle, skipReason } from './contract-harness.mjs';

registerBackendLifecycle(test);

const prisma = new PrismaClient();

let fromAtoll;
let toAtoll;
let fromIsland;
let toIsland;
let fixtureVendor;
let fixtureVendorOther;
let fixtureTransport;
let fixtureFlightTransport;

async function cleanupFixtures() {
  await prisma.booking.deleteMany({ where: { transportId: { startsWith: 'contract-transport-' } } });
  await prisma.transportDisruption.deleteMany({
    where: {
      OR: [
        { transportId: { startsWith: 'contract-transport-' } },
        { replacementTransportId: { startsWith: 'contract-transport-' } },
      ],
    },
  });
  await prisma.transportFareClass.deleteMany({ where: { transportId: { startsWith: 'contract-transport-' } } });
  await prisma.booking.deleteMany({ where: { accommodationId: { startsWith: 'contract-accommodation-' } } });
  await prisma.transport.deleteMany({
    where: {
      OR: [
        { id: { startsWith: 'contract-transport-' } },
        { vendorId: { startsWith: 'contract-vendor-' } },
      ],
    },
  });
  await prisma.accommodation.deleteMany({
    where: {
      OR: [
        { id: { startsWith: 'contract-accommodation-' } },
        { vendorId: { startsWith: 'contract-vendor-' } },
      ],
    },
  });
  await prisma.vendor.deleteMany({ where: { id: { startsWith: 'contract-vendor-' } } });
  await prisma.island.deleteMany({ where: { slug: { startsWith: 'contract-trans-' } } });
  await prisma.atoll.deleteMany({ where: { code: { startsWith: 'CONTRACT-TRANS-' } } });
}

test.before(async () => {
  if (!canRun) {
    return;
  }

  await prisma.$connect();
  await cleanupFixtures();

  const now = Date.now();
  fromAtoll = await prisma.atoll.create({
    data: {
      code: `CONTRACT-TRANS-FROM-${now}`,
      name: `Contract Trans From Atoll ${now}`,
    },
  });

  toAtoll = await prisma.atoll.create({
    data: {
      code: `CONTRACT-TRANS-TO-${now}`,
      name: `Contract Trans To Atoll ${now}`,
    },
  });

  fromIsland = await prisma.island.create({
    data: {
      name: `Contract Trans From Island ${now}`,
      slug: `contract-trans-from-${now}`,
      atollId: fromAtoll.id,
      lat: 4.1,
      lng: 73.4,
    },
  });

  toIsland = await prisma.island.create({
    data: {
      name: `Contract Trans To Island ${now}`,
      slug: `contract-trans-to-${now}`,
      atollId: toAtoll.id,
      lat: 4.3,
      lng: 73.7,
    },
  });

  fixtureVendor = await prisma.vendor.create({
    data: {
      id: `contract-vendor-${now}`,
      name: `Contract Transport Vendor ${now}`,
      email: `contract-transport-vendor-${now}@example.test`,
    },
  });

  fixtureVendorOther = await prisma.vendor.create({
    data: {
      id: `contract-vendor-other-${now}`,
      name: `Contract Transport Vendor Other ${now}`,
      email: `contract-transport-vendor-other-${now}@example.test`,
    },
  });

  fixtureTransport = await prisma.transport.create({
    data: {
      id: `contract-transport-${now}`,
      vendorId: fixtureVendor.id,
      type: 'SPEEDBOAT',
      code: `CT-${now}`,
      fromIslandId: fromIsland.id,
      toIslandId: toIsland.id,
      departure: new Date('2026-07-01T08:00:00.000Z'),
      arrival: new Date('2026-07-01T10:00:00.000Z'),
      capacity: 20,
      price: 90,
    },
  });

  fixtureFlightTransport = await prisma.transport.create({
    data: {
      id: `contract-transport-flight-${now}`,
      vendorId: fixtureVendor.id,
      type: 'DOMESTIC_FLIGHT',
      code: `Q2-${now}`,
      fromIslandId: fromIsland.id,
      toIslandId: toIsland.id,
      departure: new Date('2026-07-01T11:00:00.000Z'),
      arrival: new Date('2026-07-01T12:00:00.000Z'),
      capacity: 40,
      price: 140,
    },
  });

  await prisma.transportFareClass.createMany({
    data: [
      {
        transportId: fixtureFlightTransport.id,
        code: 'ECONOMY',
        name: 'Economy',
        baggageKg: 20,
        seats: 4,
        price: 135,
      },
      {
        transportId: fixtureFlightTransport.id,
        code: 'FLEX',
        name: 'Flex',
        baggageKg: 30,
        seats: 2,
        price: 185,
      },
    ],
  });
});

test.after(async () => {
  if (!canRun) {
    return;
  }

  await cleanupFixtures();
  await prisma.$disconnect();
});

test('GET /api/v1/transports contract', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/transports`);
  assert.equal(response.status, 200);

  const body = await response.json();
  assert.equal(Array.isArray(body), true);
  assert.equal(body.some((item) => item.id === fixtureTransport.id), true);
});

test('GET /api/v1/transports filtered by from/to islands', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/transports?fromIslandId=${fromIsland.id}&toIslandId=${toIsland.id}&type=speedboat`);
  assert.equal(response.status, 200);

  const body = await response.json();
  assert.equal(Array.isArray(body), true);
  assert.equal(body.some((item) => item.id === fixtureTransport.id && item.type === 'SPEEDBOAT'), true);
});

test('GET /api/v1/transports/schedule returns date-filtered rows with seat inventory', { skip: !canRun ? skipReason : false }, async () => {
  const bookingUserId = `contract-booking-user-transport-schedule-${Date.now()}`;
  await prisma.user.upsert({
    where: { id: bookingUserId },
    update: {
      role: 'USER',
      email: `${bookingUserId}@example.test`,
    },
    create: {
      id: bookingUserId,
      role: 'USER',
      email: `${bookingUserId}@example.test`,
    },
  });

  await prisma.booking.create({
    data: {
      userId: bookingUserId,
      transportId: fixtureTransport.id,
      guests: 3,
      totalPrice: 270,
      status: 'CONFIRMED',
    },
  });

  const response = await fetch(
    `${baseUrl}/transports/schedule?date=2026-07-01&fromIslandId=${fromIsland.id}&toIslandId=${toIsland.id}&type=SPEEDBOAT`,
  );
  assert.equal(response.status, 200);

  const body = await response.json();
  assert.equal(Array.isArray(body), true);

  const scheduled = body.find((item) => item.id === fixtureTransport.id);
  assert.ok(scheduled);
  assert.equal(scheduled.seatInventory.capacity, 20);
  assert.equal(scheduled.seatInventory.reservedSeats >= 3, true);
  assert.equal(typeof scheduled.seatInventory.availableSeats, 'number');
  assert.equal(scheduled.seatInventory.availableSeats <= 17, true);
});

test('GET /api/v1/transports/flights/schedule returns domestic flights with fare class inventory', { skip: !canRun ? skipReason : false }, async () => {
  const bookingUserId = `contract-booking-user-flight-schedule-${Date.now()}`;
  await prisma.user.upsert({
    where: { id: bookingUserId },
    update: {
      role: 'USER',
      email: `${bookingUserId}@example.test`,
    },
    create: {
      id: bookingUserId,
      role: 'USER',
      email: `${bookingUserId}@example.test`,
    },
  });

  await prisma.booking.create({
    data: {
      userId: bookingUserId,
      transportId: fixtureFlightTransport.id,
      transportFareClassCode: 'ECONOMY',
      guests: 3,
      totalPrice: 405,
      status: 'CONFIRMED',
    },
  });

  const response = await fetch(
    `${baseUrl}/transports/flights/schedule?date=2026-07-01&fromIslandId=${fromIsland.id}&toIslandId=${toIsland.id}`,
  );
  assert.equal(response.status, 200);

  const body = await response.json();
  assert.equal(Array.isArray(body), true);

  const flight = body.find((item) => item.id === fixtureFlightTransport.id);
  assert.ok(flight);
  assert.equal(flight.type, 'DOMESTIC_FLIGHT');
  assert.equal(Array.isArray(flight.fareClasses), true);

  const economy = flight.fareClasses.find((fareClass) => fareClass.code === 'ECONOMY');
  assert.ok(economy);
  assert.equal(economy.seatInventory.capacity, 4);
  assert.equal(economy.seatInventory.reservedSeats >= 3, true);
  assert.equal(economy.seatInventory.availableSeats <= 1, true);
});

test('GET /api/v1/transports/:id/fare-classes returns fare classes with seat inventory', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/transports/${fixtureFlightTransport.id}/fare-classes`);
  assert.equal(response.status, 200);

  const body = await response.json();
  assert.equal(Array.isArray(body), true);
  assert.equal(body.length >= 2, true);
  assert.equal(body.some((fareClass) => fareClass.code === 'ECONOMY'), true);
  assert.equal(body.some((fareClass) => fareClass.code === 'FLEX'), true);
});

test('GET /api/v1/transports/:id contract', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/transports/${fixtureTransport.id}`);
  assert.equal(response.status, 200);

  const body = await response.json();
  assert.equal(body.id, fixtureTransport.id);
  assert.equal(body.fromIsland?.id, fromIsland.id);
  assert.equal(body.toIsland?.id, toIsland.id);
});

test('GET /api/v1/transports/:id returns 404 for missing id', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/transports/missing-transport-id`);
  assert.equal(response.status, 404);
});

test('POST /api/v1/transports/admin enforces RBAC', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/transports/admin`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-transport-user-${Date.now()}`, 'USER'),
    },
    body: JSON.stringify({
      vendorId: fixtureVendor.id,
      type: 'SPEEDBOAT',
      fromIslandId: fromIsland.id,
      toIslandId: toIsland.id,
      price: 100,
    }),
  });

  assert.equal(response.status, 403);
});

test('Admin CRUD /api/v1/transports/admin works for ADMIN_CARE', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const createResponse = await fetch(`${baseUrl}/transports/admin`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-transport-admin-care-${now}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify({
      vendorId: fixtureVendor.id,
      type: 'WATER_TAXI',
      fromIslandId: fromIsland.id,
      toIslandId: toIsland.id,
      capacity: 10,
      price: 210,
    }),
  });

  assert.equal(createResponse.status, 201);
  const created = await createResponse.json();
  assert.equal(created.type, 'WATER_TAXI');

  const updateResponse = await fetch(`${baseUrl}/transports/admin/${created.id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-transport-admin-care-update-${now}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify({
      type: 'SEAPLANE',
      price: 520,
    }),
  });

  assert.equal(updateResponse.status, 200);
  const updated = await updateResponse.json();
  assert.equal(updated.type, 'SEAPLANE');

  const deleteResponse = await fetch(`${baseUrl}/transports/admin/${created.id}`, {
    method: 'DELETE',
    headers: authHeaders(`contract-transport-admin-care-delete-${now}`, 'ADMIN_CARE'),
  });

  assert.equal(deleteResponse.status, 204);
});

test('VENDOR is scope-limited for /api/v1/transports/admin writes', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const createResponse = await fetch(`${baseUrl}/transports/admin`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-transport-vendor-${now}`, 'VENDOR', `contract-transport-vendor-${now}@example.test`, fixtureVendor.id),
    },
    body: JSON.stringify({
      vendorId: fixtureVendor.id,
      type: 'SPEEDBOAT',
      fromIslandId: fromIsland.id,
      toIslandId: toIsland.id,
      price: 110,
    }),
  });

  assert.equal(createResponse.status, 201);
  const created = await createResponse.json();
  assert.equal(created.vendorId, fixtureVendor.id);

  const forbiddenCreateResponse = await fetch(`${baseUrl}/transports/admin`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-transport-vendor-forbidden-${now}`, 'VENDOR', `contract-transport-vendor-forbidden-${now}@example.test`, fixtureVendor.id),
    },
    body: JSON.stringify({
      vendorId: fixtureVendorOther.id,
      type: 'SPEEDBOAT',
      fromIslandId: fromIsland.id,
      toIslandId: toIsland.id,
      price: 120,
    }),
  });

  assert.equal(forbiddenCreateResponse.status, 403);

  const forbiddenDeleteResponse = await fetch(`${baseUrl}/transports/admin/${fixtureTransport.id}`, {
    method: 'DELETE',
    headers: authHeaders(`contract-transport-vendor-delete-${now}`, 'VENDOR', `contract-transport-vendor-delete-${now}@example.test`, fixtureVendorOther.id),
  });

  assert.equal(forbiddenDeleteResponse.status, 403);

  const cleanupResponse = await fetch(`${baseUrl}/transports/admin/${created.id}`, {
    method: 'DELETE',
    headers: authHeaders(`contract-transport-vendor-cleanup-${now}`, 'VENDOR', `contract-transport-vendor-cleanup-${now}@example.test`, fixtureVendor.id),
  });

  assert.equal(cleanupResponse.status, 204);
});

test('Admin disruption lifecycle + schedule visibility + re-accommodation works', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const disruptedTransport = await prisma.transport.create({
    data: {
      id: `contract-transport-disrupted-${now}`,
      vendorId: fixtureVendor.id,
      type: 'SPEEDBOAT',
      code: `CD-${now}`,
      fromIslandId: fromIsland.id,
      toIslandId: toIsland.id,
      departure: new Date('2026-07-02T08:00:00.000Z'),
      arrival: new Date('2026-07-02T10:00:00.000Z'),
      capacity: 8,
      price: 120,
    },
  });

  const replacementTransport = await prisma.transport.create({
    data: {
      id: `contract-transport-replacement-${now}`,
      vendorId: fixtureVendor.id,
      type: 'SPEEDBOAT',
      code: `CR-${now}`,
      fromIslandId: fromIsland.id,
      toIslandId: toIsland.id,
      departure: new Date('2026-07-02T08:30:00.000Z'),
      arrival: new Date('2026-07-02T10:30:00.000Z'),
      capacity: 8,
      price: 140,
    },
  });

  const userId = `contract-booking-user-disruption-${now}`;
  await prisma.user.upsert({
    where: { id: userId },
    update: {
      role: 'USER',
      email: `${userId}@example.test`,
    },
    create: {
      id: userId,
      role: 'USER',
      email: `${userId}@example.test`,
    },
  });

  const movedBooking = await prisma.booking.create({
    data: {
      userId,
      transportId: disruptedTransport.id,
      guests: 3,
      totalPrice: 360,
      status: 'CONFIRMED',
    },
  });

  const createDisruptionResponse = await fetch(`${baseUrl}/transports/admin/${disruptedTransport.id}/disruptions`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-transport-admin-disruption-${now}`, 'ADMIN'),
    },
    body: JSON.stringify({
      status: 'CANCELLED',
      reason: 'Weather window closed',
      replacementTransportId: replacementTransport.id,
    }),
  });

  assert.equal(createDisruptionResponse.status, 201);
  const createdDisruption = await createDisruptionResponse.json();
  assert.equal(createdDisruption.transportId, disruptedTransport.id);
  assert.equal(createdDisruption.status, 'CANCELLED');
  assert.equal(createdDisruption.replacementTransportId, replacementTransport.id);

  const scheduleResponse = await fetch(`${baseUrl}/transports/schedule?date=2026-07-02&type=SPEEDBOAT`);
  assert.equal(scheduleResponse.status, 200);
  const scheduleBody = await scheduleResponse.json();
  const disruptedScheduleItem = scheduleBody.find((item) => item.id === disruptedTransport.id);
  assert.ok(disruptedScheduleItem);
  assert.equal(disruptedScheduleItem.activeDisruption?.id, createdDisruption.id);
  assert.equal(disruptedScheduleItem.activeDisruption?.status, 'CANCELLED');

  const reaccommodateResponse = await fetch(
    `${baseUrl}/transports/admin/${disruptedTransport.id}/disruptions/${createdDisruption.id}/reaccommodate`,
    {
      method: 'POST',
      headers: authHeaders(`contract-transport-admin-reaccommodate-${now}`, 'ADMIN'),
    },
  );
  assert.equal(reaccommodateResponse.status, 201);
  const reaccommodateBody = await reaccommodateResponse.json();
  assert.equal(reaccommodateBody.scanned, 1);
  assert.equal(reaccommodateBody.moved, 1);
  assert.equal(reaccommodateBody.skipped, 0);

  const movedBookingRow = await prisma.booking.findUniqueOrThrow({ where: { id: movedBooking.id } });
  assert.equal(movedBookingRow.transportId, replacementTransport.id);

  const resolveResponse = await fetch(
    `${baseUrl}/transports/admin/${disruptedTransport.id}/disruptions/${createdDisruption.id}/resolve`,
    {
      method: 'PATCH',
      headers: authHeaders(`contract-transport-admin-resolve-${now}`, 'ADMIN'),
    },
  );
  assert.equal(resolveResponse.status, 200);
  const resolved = await resolveResponse.json();
  assert.equal(resolved.id, createdDisruption.id);
  assert.equal(resolved.resolvedAt !== null, true);
});
