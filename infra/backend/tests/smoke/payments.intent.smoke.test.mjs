import assert from 'node:assert/strict';
import test from 'node:test';
import { PrismaClient } from '@prisma/client';
import { authHeaders, baseUrl, canRun, registerBackendLifecycle, skipReason } from '../contract/contract-harness.mjs';

registerBackendLifecycle(test);

const prisma = new PrismaClient();

let fixtureUser;
let fixtureBooking;
const smokeSummaryRows = [];

async function cleanupFixtures() {
  await prisma.payment.deleteMany({ where: { bookingId: { startsWith: 'smoke-payment-booking-' } } });
  await prisma.booking.deleteMany({ where: { id: { startsWith: 'smoke-payment-booking-' } } });
  await prisma.user.deleteMany({ where: { id: { startsWith: 'smoke-payment-user-' } } });
}

test.before(async () => {
  if (!canRun) {
    return;
  }

  await prisma.$connect();
  await cleanupFixtures();

  const now = Date.now();
  fixtureUser = await prisma.user.create({
    data: {
      id: `smoke-payment-user-${now}`,
      email: `smoke-payment-user-${now}@example.test`,
      role: 'USER',
    },
  });

  fixtureBooking = await prisma.booking.create({
    data: {
      id: `smoke-payment-booking-${now}`,
      userId: fixtureUser.id,
      guests: 1,
      totalPrice: 250,
      status: 'PENDING',
    },
  });
});

test.after(async () => {
  if (!canRun) {
    return;
  }

  if (smokeSummaryRows.length > 0) {
    console.log('\n[smoke-summary] payments intent matrix');
    console.table(smokeSummaryRows);
  }

  await cleanupFixtures();
  await prisma.$disconnect();
});

test('Seeded smoke: create payment intent returns 201', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/payments/intents`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(fixtureUser.id, 'USER', fixtureUser.email),
    },
    body: JSON.stringify({
      bookingId: fixtureBooking.id,
      provider: 'BML',
      currency: 'USD',
    }),
  });

  assert.equal(response.status, 201);
  const body = await response.json();
  assert.equal(body.payment.bookingId, fixtureBooking.id);
  assert.equal(body.payment.provider, 'BML');
  assert.equal(body.payment.currency, 'USD');
  assert.equal(typeof body.payment.providerId, 'string');

  smokeSummaryRows.push({
    provider: body.payment.provider,
    currency: body.payment.currency,
    httpStatus: response.status,
    bookingId: body.payment.bookingId,
  });
});

test('Seeded smoke: create payment intent supports MVR', { skip: !canRun ? skipReason : false }, async () => {
  const booking = await prisma.booking.create({
    data: {
      id: `smoke-payment-booking-${Date.now()}-mvr`,
      userId: fixtureUser.id,
      guests: 2,
      totalPrice: 400,
      status: 'PENDING',
    },
  });

  const response = await fetch(`${baseUrl}/payments/intents`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(fixtureUser.id, 'USER', fixtureUser.email),
    },
    body: JSON.stringify({
      bookingId: booking.id,
      provider: 'MIB',
      currency: 'MVR',
    }),
  });

  assert.equal(response.status, 201);
  const body = await response.json();
  assert.equal(body.payment.bookingId, booking.id);
  assert.equal(body.payment.provider, 'MIB');
  assert.equal(body.payment.currency, 'MVR');
  assert.equal(typeof body.payment.providerId, 'string');

  smokeSummaryRows.push({
    provider: body.payment.provider,
    currency: body.payment.currency,
    httpStatus: response.status,
    bookingId: body.payment.bookingId,
  });
});

test('Seeded smoke: create payment intent supports MIB with USD', { skip: !canRun ? skipReason : false }, async () => {
  const booking = await prisma.booking.create({
    data: {
      id: `smoke-payment-booking-${Date.now()}-mib-usd`,
      userId: fixtureUser.id,
      guests: 1,
      totalPrice: 310,
      status: 'PENDING',
    },
  });

  const response = await fetch(`${baseUrl}/payments/intents`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(fixtureUser.id, 'USER', fixtureUser.email),
    },
    body: JSON.stringify({
      bookingId: booking.id,
      provider: 'MIB',
      currency: 'USD',
    }),
  });

  assert.equal(response.status, 201);
  const body = await response.json();
  assert.equal(body.payment.bookingId, booking.id);
  assert.equal(body.payment.provider, 'MIB');
  assert.equal(body.payment.currency, 'USD');
  assert.equal(typeof body.payment.providerId, 'string');

  smokeSummaryRows.push({
    provider: body.payment.provider,
    currency: body.payment.currency,
    httpStatus: response.status,
    bookingId: body.payment.bookingId,
  });
});

test('Seeded smoke: create payment intent supports BML with MVR', { skip: !canRun ? skipReason : false }, async () => {
  const booking = await prisma.booking.create({
    data: {
      id: `smoke-payment-booking-${Date.now()}-bml-mvr`,
      userId: fixtureUser.id,
      guests: 1,
      totalPrice: 275,
      status: 'PENDING',
    },
  });

  const response = await fetch(`${baseUrl}/payments/intents`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(fixtureUser.id, 'USER', fixtureUser.email),
    },
    body: JSON.stringify({
      bookingId: booking.id,
      provider: 'BML',
      currency: 'MVR',
    }),
  });

  assert.equal(response.status, 201);
  const body = await response.json();
  assert.equal(body.payment.bookingId, booking.id);
  assert.equal(body.payment.provider, 'BML');
  assert.equal(body.payment.currency, 'MVR');
  assert.equal(typeof body.payment.providerId, 'string');

  smokeSummaryRows.push({
    provider: body.payment.provider,
    currency: body.payment.currency,
    httpStatus: response.status,
    bookingId: body.payment.bookingId,
  });
});
