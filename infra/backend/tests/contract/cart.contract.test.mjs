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

async function cleanupFixtures() {
  await prisma.booking.deleteMany({ where: { userId: { startsWith: 'contract-cart-user-' } } });
  await prisma.user.deleteMany({ where: { id: { startsWith: 'contract-cart-user-' } } });
  await prisma.appConfig.deleteMany({ where: { key: { startsWith: 'cart:user:contract-cart-user-' } } });
  await prisma.transport.deleteMany({ where: { id: { startsWith: 'contract-cart-transport-' } } });
  await prisma.accommodation.deleteMany({ where: { id: { startsWith: 'contract-cart-accommodation-' } } });
  await prisma.vendor.deleteMany({ where: { id: { startsWith: 'contract-cart-vendor-' } } });
  await prisma.island.deleteMany({ where: { slug: { startsWith: 'contract-cart-island-' } } });
  await prisma.atoll.deleteMany({ where: { code: { startsWith: 'CONTRACT-CART-' } } });
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
      code: `CONTRACT-CART-ATOLL-${now}`,
      name: `Contract Cart Atoll ${now}`,
    },
  });

  otherAtoll = await prisma.atoll.create({
    data: {
      code: `CONTRACT-CART-OTHER-${now}`,
      name: `Contract Cart Other Atoll ${now}`,
    },
  });

  fixtureIsland = await prisma.island.create({
    data: {
      name: `Contract Cart Island ${now}`,
      slug: `contract-cart-island-main-${now}`,
      atollId: fixtureAtoll.id,
      lat: 4.22,
      lng: 73.6,
    },
  });

  otherIsland = await prisma.island.create({
    data: {
      name: `Contract Cart Other Island ${now}`,
      slug: `contract-cart-island-other-${now}`,
      atollId: otherAtoll.id,
      lat: 4.4,
      lng: 73.8,
    },
  });

  fixtureVendor = await prisma.vendor.create({
    data: {
      id: `contract-cart-vendor-${now}`,
      name: `Contract Cart Vendor ${now}`,
      email: `contract-cart-vendor-${now}@example.test`,
    },
  });

  fixtureAccommodation = await prisma.accommodation.create({
    data: {
      id: `contract-cart-accommodation-${now}`,
      vendorId: fixtureVendor.id,
      islandId: fixtureIsland.id,
      title: `Contract Cart Accommodation ${now}`,
      slug: `contract-cart-accommodation-${now}`,
      description: 'Contract cart accommodation fixture',
      type: 'GUESTHOUSE',
      rooms: 6,
      minStayNights: 2,
      price: 200,
    },
  });

  fixtureTransport = await prisma.transport.create({
    data: {
      id: `contract-cart-transport-${now}`,
      vendorId: fixtureVendor.id,
      type: 'SPEEDBOAT',
      fromIslandId: otherIsland.id,
      toIslandId: fixtureIsland.id,
      departure: new Date('2026-09-01T07:00:00.000Z'),
      arrival: new Date('2026-09-01T09:00:00.000Z'),
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

test('Cart contract requires auth', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/cart`);
  assert.equal(response.status, 401);
});

test('Cart contract: add transport+accommodation items and checkout bundle', { skip: !canRun ? skipReason : false }, async () => {
  const userId = `contract-cart-user-${Date.now()}`;

  const transportAdd = await fetch(`${baseUrl}/cart/items`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(userId, 'USER'),
    },
    body: JSON.stringify({
      serviceType: 'TRANSPORT',
      transportId: fixtureTransport.id,
      guests: 2,
    }),
  });

  assert.equal(transportAdd.status, 201);
  const transportCart = await transportAdd.json();
  const transportItem = transportCart.items.find((item) => item.serviceType === 'TRANSPORT');
  assert.ok(transportItem);

  const accommodationAdd = await fetch(`${baseUrl}/cart/items`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(userId, 'USER'),
    },
    body: JSON.stringify({
      serviceType: 'ACCOMMODATION',
      accommodationId: fixtureAccommodation.id,
      relatedTransportItemId: transportItem.id,
      startDate: '2026-09-02',
      endDate: '2026-09-05',
      guests: 2,
    }),
  });

  assert.equal(accommodationAdd.status, 201);

  const checkout = await fetch(`${baseUrl}/cart/checkout`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(userId, 'USER'),
    },
    body: JSON.stringify({}),
  });

  assert.equal(checkout.status, 201);
  const checkoutBody = await checkout.json();
  assert.equal(checkoutBody.checkout.createdCount, 2);
  assert.equal(checkoutBody.cartCleared, true);

  const cartAfter = await fetch(`${baseUrl}/cart`, {
    headers: authHeaders(userId, 'USER'),
  });

  assert.equal(cartAfter.status, 200);
  const cartAfterBody = await cartAfter.json();
  assert.equal(Array.isArray(cartAfterBody.items), true);
  assert.equal(cartAfterBody.items.length, 0);
});
