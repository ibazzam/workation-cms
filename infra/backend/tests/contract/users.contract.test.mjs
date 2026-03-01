import assert from 'node:assert/strict';
import test from 'node:test';
import { PrismaClient } from '@prisma/client';
import { authHeaders, baseUrl, canRun, registerBackendLifecycle, skipReason } from './contract-harness.mjs';

registerBackendLifecycle(test);

const prisma = new PrismaClient();

async function cleanupFixtures() {
  await prisma.appConfig.deleteMany({ where: { key: { startsWith: 'user:profile:contract-user-profile-' } } });
  await prisma.user.deleteMany({ where: { id: { startsWith: 'contract-user-profile-' } } });
}

test.before(async () => {
  if (!canRun) {
    return;
  }

  await prisma.$connect();
  await cleanupFixtures();
});

test.after(async () => {
  if (!canRun) {
    return;
  }

  await cleanupFixtures();
  await prisma.$disconnect();
});

test('GET /api/v1/users/me/profile returns baseline profile state', { skip: !canRun ? skipReason : false }, async () => {
  const userId = `contract-user-profile-${Date.now()}`;
  const response = await fetch(`${baseUrl}/users/me/profile`, {
    headers: authHeaders(userId, 'USER', `${userId}@example.test`),
  });

  assert.equal(response.status, 200);
  const body = await response.json();

  assert.equal(body.user.id, userId);
  assert.equal(body.user.email, `${userId}@example.test`);
  assert.equal(body.user.role, 'USER');
  assert.equal(Array.isArray(body.profileCompleteness.missingFields), true);
  assert.equal(typeof body.profileCompleteness.score, 'number');
  assert.equal(body.profileCompleteness.status === 'PARTIAL' || body.profileCompleteness.status === 'COMPLETE', true);
});

test('PUT /api/v1/users/me/profile updates preferences and improves completeness', { skip: !canRun ? skipReason : false }, async () => {
  const userId = `contract-user-profile-${Date.now()}`;

  const updateResponse = await fetch(`${baseUrl}/users/me/profile`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(userId, 'USER', `${userId}@example.test`),
    },
    body: JSON.stringify({
      name: 'Contract User',
      preferences: {
        preferredAtollIds: [1, 2],
        preferredServiceCategories: ['accommodation', 'transport'],
        travelPace: 'FAST',
        budgetBand: 'PREMIUM',
      },
    }),
  });

  assert.equal(updateResponse.status, 200);
  const updateBody = await updateResponse.json();

  assert.equal(updateBody.user.name, 'Contract User');
  assert.deepEqual(updateBody.preferences.preferredAtollIds, [1, 2]);
  assert.deepEqual(updateBody.preferences.preferredServiceCategories, ['ACCOMMODATION', 'TRANSPORT']);
  assert.equal(updateBody.preferences.travelPace, 'FAST');
  assert.equal(updateBody.preferences.budgetBand, 'PREMIUM');
  assert.equal(updateBody.profileCompleteness.status, 'COMPLETE');
  assert.equal(updateBody.profileCompleteness.score, 100);

  const readBack = await fetch(`${baseUrl}/users/me/profile`, {
    headers: authHeaders(userId, 'USER', `${userId}@example.test`),
  });

  assert.equal(readBack.status, 200);
  const readBackBody = await readBack.json();
  assert.equal(readBackBody.user.name, 'Contract User');
  assert.equal(readBackBody.profileCompleteness.score, 100);
});
