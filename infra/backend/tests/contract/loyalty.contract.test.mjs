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

async function cleanupFixtures() {
  await prisma.loyaltyTransaction.deleteMany({ where: { userId: { startsWith: 'contract-loyalty-user-' } } });
  await prisma.loyaltyAccount.deleteMany({ where: { userId: { startsWith: 'contract-loyalty-user-' } } });
  await prisma.vendorLoyaltyOffer.deleteMany({ where: { title: { startsWith: 'Contract Loyalty Offer' } } });
  await prisma.booking.deleteMany({ where: { userId: { startsWith: 'contract-loyalty-user-' } } });
  await prisma.user.deleteMany({ where: { id: { startsWith: 'contract-loyalty-user-' } } });
  await prisma.transport.deleteMany({ where: { id: { startsWith: 'contract-loyalty-transport-' } } });
  await prisma.accommodation.deleteMany({ where: { id: { startsWith: 'contract-loyalty-accommodation-' } } });
  await prisma.vendor.deleteMany({ where: { id: { startsWith: 'contract-loyalty-vendor-' } } });
  await prisma.island.deleteMany({ where: { slug: { startsWith: 'contract-loyalty-island-' } } });
  await prisma.atoll.deleteMany({ where: { code: { startsWith: 'CONTRACT-LOYALTY-' } } });
}

test.before(async () => {
  if (!canRun) return;

  await prisma.$connect();
  await cleanupFixtures();

  await prisma.appConfig.upsert({
    where: { key: 'COMMERCIAL_SETTINGS' },
    update: {
      value: {
        currency: { baseCurrency: 'USD', supportedCurrencies: ['USD', 'MVR'] },
        exchangeRates: {
          rates: [
            { from: 'USD', to: 'MVR', rate: 15.5 },
            { from: 'MVR', to: 'USD', rate: 0.064516 },
          ],
          updatedAt: new Date().toISOString(),
        },
        loyalty: {
          enabled: true,
          pointsPerUnitSpend: 2,
          unitCurrency: 'USD',
          redemptionValuePerPoint: 0.02,
          minimumPointsToRedeem: 50,
        },
      },
    },
    create: {
      key: 'COMMERCIAL_SETTINGS',
      value: {
        currency: { baseCurrency: 'USD', supportedCurrencies: ['USD', 'MVR'] },
        exchangeRates: {
          rates: [
            { from: 'USD', to: 'MVR', rate: 15.5 },
            { from: 'MVR', to: 'USD', rate: 0.064516 },
          ],
          updatedAt: new Date().toISOString(),
        },
        loyalty: {
          enabled: true,
          pointsPerUnitSpend: 2,
          unitCurrency: 'USD',
          redemptionValuePerPoint: 0.02,
          minimumPointsToRedeem: 50,
        },
      },
    },
  });

  const now = Date.now();
  fixtureAtoll = await prisma.atoll.create({ data: { code: `CONTRACT-LOYALTY-ATOLL-${now}`, name: `Contract Loyalty Atoll ${now}` } });
  fixtureOriginAtoll = await prisma.atoll.create({ data: { code: `CONTRACT-LOYALTY-ORIGIN-${now}`, name: `Contract Loyalty Origin ${now}` } });

  fixtureIsland = await prisma.island.create({
    data: {
      name: `Contract Loyalty Island ${now}`,
      slug: `contract-loyalty-island-main-${now}`,
      atollId: fixtureAtoll.id,
      lat: 4.22,
      lng: 73.61,
    },
  });

  fixtureOriginIsland = await prisma.island.create({
    data: {
      name: `Contract Loyalty Origin Island ${now}`,
      slug: `contract-loyalty-island-origin-${now}`,
      atollId: fixtureOriginAtoll.id,
      lat: 4.31,
      lng: 73.71,
    },
  });

  fixtureVendor = await prisma.vendor.create({
    data: {
      id: `contract-loyalty-vendor-${now}`,
      name: `Contract Loyalty Vendor ${now}`,
      email: `contract-loyalty-vendor-${now}@example.test`,
    },
  });

  fixtureAccommodation = await prisma.accommodation.create({
    data: {
      id: `contract-loyalty-accommodation-${now}`,
      vendorId: fixtureVendor.id,
      islandId: fixtureIsland.id,
      title: `Contract Loyalty Accommodation ${now}`,
      slug: `contract-loyalty-accommodation-${now}`,
      type: 'GUESTHOUSE',
      rooms: 8,
      minStayNights: 1,
      price: 220,
    },
  });

  fixtureTransport = await prisma.transport.create({
    data: {
      id: `contract-loyalty-transport-${now}`,
      vendorId: fixtureVendor.id,
      type: 'SPEEDBOAT',
      fromIslandId: fixtureOriginIsland.id,
      toIslandId: fixtureIsland.id,
      departure: new Date('2026-11-03T07:00:00.000Z'),
      arrival: new Date('2026-11-03T09:00:00.000Z'),
      capacity: 30,
      price: 110,
    },
  });

  fixtureUser = await prisma.user.create({
    data: {
      id: `contract-loyalty-user-${now}`,
      email: `contract-loyalty-user-${now}@example.test`,
      role: 'USER',
    },
  });
});

test.after(async () => {
  if (!canRun) return;
  await cleanupFixtures();
  await prisma.$disconnect();
});

test('Loyalty: wallet initializes and booking confirmation accrues points', { skip: !canRun ? skipReason : false }, async () => {
  const createBooking = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(fixtureUser.id, 'USER', fixtureUser.email),
    },
    body: JSON.stringify({
      accommodationId: fixtureAccommodation.id,
      transportId: fixtureTransport.id,
      startDate: '2026-11-04',
      endDate: '2026-11-06',
      guests: 1,
    }),
  });

  assert.equal(createBooking.status, 201);
  const createdBooking = await createBooking.json();

  const confirmResponse = await fetch(`${baseUrl}/bookings/${createdBooking.id}/confirm`, {
    method: 'PATCH',
    headers: authHeaders(fixtureUser.id, 'USER', fixtureUser.email),
  });

  assert.equal(confirmResponse.status, 200);

  const walletResponse = await fetch(`${baseUrl}/loyalty/me`, {
    headers: authHeaders(fixtureUser.id, 'USER', fixtureUser.email),
  });

  assert.equal(walletResponse.status, 200);
  const wallet = await walletResponse.json();
  assert.equal(typeof wallet.account.pointsBalance, 'number');
  assert.equal(wallet.account.pointsBalance > 0, true);

  const txResponse = await fetch(`${baseUrl}/loyalty/me/transactions`, {
    headers: authHeaders(fixtureUser.id, 'USER', fixtureUser.email),
  });

  assert.equal(txResponse.status, 200);
  const txBody = await txResponse.json();
  assert.equal(Array.isArray(txBody.items), true);
  assert.equal(txBody.items.some((item) => item.type === 'EARN' && item.bookingId === createdBooking.id), true);
});

test('Loyalty: vendor offer CRUD and public listing', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const createResponse = await fetch(`${baseUrl}/loyalty/offers/admin`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-loyalty-admin-${now}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify({
      vendorId: fixtureVendor.id,
      title: `Contract Loyalty Offer ${now}`,
      description: 'Boosted points for speedboat + stay bundles',
      pointsMultiplier: 1.5,
      discountPercent: 10,
      active: true,
    }),
  });

  assert.equal(createResponse.status, 201);
  const offer = await createResponse.json();

  const listResponse = await fetch(`${baseUrl}/loyalty/offers/vendors/${fixtureVendor.id}`);
  assert.equal(listResponse.status, 200);
  const listBody = await listResponse.json();
  assert.equal(Array.isArray(listBody), true);
  assert.equal(listBody.some((item) => item.id === offer.id), true);

  const updateResponse = await fetch(`${baseUrl}/loyalty/offers/admin/${offer.id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-loyalty-admin-update-${now}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify({ pointsMultiplier: 2.0 }),
  });

  assert.equal(updateResponse.status, 200);
  const updated = await updateResponse.json();
  assert.equal(Number(updated.pointsMultiplier), 2.0);

  const deleteResponse = await fetch(`${baseUrl}/loyalty/offers/admin/${offer.id}`, {
    method: 'DELETE',
    headers: authHeaders(`contract-loyalty-admin-delete-${now}`, 'ADMIN_CARE'),
  });
  assert.equal(deleteResponse.status, 204);
});

test('Loyalty: redeem points against booking lowers totalPrice', { skip: !canRun ? skipReason : false }, async () => {
  const bookingResponse = await fetch(`${baseUrl}/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(fixtureUser.id, 'USER', fixtureUser.email),
    },
    body: JSON.stringify({
      accommodationId: fixtureAccommodation.id,
      transportId: fixtureTransport.id,
      startDate: '2026-11-10',
      endDate: '2026-11-12',
      guests: 1,
    }),
  });

  assert.equal(bookingResponse.status, 201);
  const booking = await bookingResponse.json();

  const redeemResponse = await fetch(`${baseUrl}/loyalty/me/redeem`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(fixtureUser.id, 'USER', fixtureUser.email),
    },
    body: JSON.stringify({
      bookingId: booking.id,
      points: 50,
    }),
  });

  assert.equal(redeemResponse.status, 200);
  const redeemed = await redeemResponse.json();
  assert.equal(redeemed.redeemedPoints, 50);

  const refreshedBooking = await prisma.booking.findUnique({ where: { id: booking.id } });
  assert.ok(refreshedBooking);
  assert.equal(Number(refreshedBooking.totalPrice) < Number(booking.totalPrice), true);
});
