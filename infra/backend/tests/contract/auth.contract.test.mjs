import assert from 'node:assert/strict';
import { createHmac } from 'node:crypto';
import test from 'node:test';
import { authHeaders, baseUrl, canRun, registerBackendLifecycle, skipReason } from './contract-harness.mjs';

registerBackendLifecycle(test);

function toBase64Url(value) {
  return Buffer.from(value)
    .toString('base64')
    .replace(/\+/g, '-')
    .replace(/\//g, '_')
    .replace(/=+$/g, '');
}

function signJwt(payload, secret) {
  const header = { alg: 'HS256', typ: 'JWT' };
  const encodedHeader = toBase64Url(JSON.stringify(header));
  const encodedPayload = toBase64Url(JSON.stringify(payload));
  const signingInput = `${encodedHeader}.${encodedPayload}`;
  const signature = createHmac('sha256', secret)
    .update(signingInput)
    .digest('base64')
    .replace(/\+/g, '-')
    .replace(/\//g, '_')
    .replace(/=+$/g, '');

  return `${signingInput}.${signature}`;
}

test('Auth contract: 401 for missing headers', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/auth/me`);
  assert.equal(response.status, 401);
});

test('Auth contract: /me with valid user headers', { skip: !canRun ? skipReason : false }, async () => {
  const userId = `contract-user-${Date.now()}`;
  const response = await fetch(`${baseUrl}/auth/me`, {
    headers: authHeaders(userId, 'USER'),
  });

  assert.equal(response.status, 200);
  const body = await response.json();
  assert.equal(body.id, userId);
  assert.equal(body.role, 'USER');
});

test('Auth contract: /me with valid bearer token', { skip: !canRun ? skipReason : false }, async () => {
  const userId = `contract-user-jwt-${Date.now()}`;
  const role = 'USER';
  const email = `${userId}@example.test`;
  const nowSeconds = Math.floor(Date.now() / 1000);
  const secret = process.env.AUTH_JWT_SECRET ?? 'dev-auth-secret';

  const token = signJwt(
    {
      sub: userId,
      role,
      email,
      iat: nowSeconds,
      exp: nowSeconds + 300,
    },
    secret,
  );

  const response = await fetch(`${baseUrl}/auth/me`, {
    headers: {
      authorization: `Bearer ${token}`,
    },
  });

  assert.equal(response.status, 200);
  const body = await response.json();
  assert.equal(body.id, userId);
  assert.equal(body.role, role);
  assert.equal(body.email, email);
});

test('RBAC contract: 403 for USER on admin route', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/auth/admin/ping`, {
    headers: authHeaders(`contract-user-forbidden-${Date.now()}`, 'USER'),
  });

  assert.equal(response.status, 403);
});

test('RBAC contract: ADMIN allowed on admin route', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/auth/admin/ping`, {
    headers: authHeaders(`contract-admin-${Date.now()}`, 'ADMIN'),
  });

  assert.equal(response.status, 200);
  const body = await response.json();
  assert.equal(body.status, 'ok');
  assert.equal(body.scope, 'admin');
});

test('RBAC contract: ADMIN_CARE allowed on admin route', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/auth/admin/ping`, {
    headers: authHeaders(`contract-admin-care-${Date.now()}`, 'ADMIN_CARE'),
  });

  assert.equal(response.status, 200);
  const body = await response.json();
  assert.equal(body.status, 'ok');
  assert.equal(body.scope, 'admin');
});
