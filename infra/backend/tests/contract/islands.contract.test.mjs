import assert from 'node:assert/strict';
import test from 'node:test';
import { PrismaClient } from '@prisma/client';
import { baseUrl, canRun, registerBackendLifecycle, skipReason } from './contract-harness.mjs';

registerBackendLifecycle(test);

const prisma = new PrismaClient();

let fixtureAtoll;
let fixtureIsland;
const atollCodePrefix = 'CONTRACT-ISLAND-';
const islandSlugPrefix = 'contract-island-';

async function cleanupFixtures() {
  await prisma.island.deleteMany({ where: { slug: { startsWith: islandSlugPrefix } } });
  await prisma.atoll.deleteMany({ where: { code: { startsWith: atollCodePrefix } } });
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
      code: `${atollCodePrefix}${now}`,
      name: `Contract Atoll ${now}`,
    },
  });

  fixtureIsland = await prisma.island.create({
    data: {
      name: `Contract Island ${now}`,
      slug: `${islandSlugPrefix}${now}`,
      atollId: fixtureAtoll.id,
      lat: 4.17,
      lng: 73.5,
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

test('GET /api/v1/atolls contract', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/atolls`);
  assert.equal(response.status, 200);

  const body = await response.json();
  assert.equal(Array.isArray(body), true);
  assert.equal(body.some((item) => item.id === fixtureAtoll.id && item.code === fixtureAtoll.code), true);
});

test('GET /api/v1/islands filtered by atollId contract', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/islands?atollId=${fixtureAtoll.id}`);
  assert.equal(response.status, 200);

  const body = await response.json();
  assert.equal(Array.isArray(body), true);
  assert.equal(body.some((item) => item.id === fixtureIsland.id && item.atoll?.id === fixtureAtoll.id), true);
});

test('GET /api/v1/islands/:id contract', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/islands/${fixtureIsland.id}`);
  assert.equal(response.status, 200);

  const body = await response.json();
  assert.equal(body.id, fixtureIsland.id);
  assert.equal(body.slug, fixtureIsland.slug);
  assert.equal(body.atoll?.id, fixtureAtoll.id);
});

test('GET /api/v1/islands/:id returns 404 for missing island', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/islands/99999999`);
  assert.equal(response.status, 404);
});
