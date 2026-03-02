import assert from 'node:assert/strict';
import { createHmac } from 'node:crypto';
import test from 'node:test';
import { authHeaders, baseUrl, canRun, registerBackendLifecycle, skipReason } from './contract-harness.mjs';

process.env.AUTH_ALLOW_HEADER_FALLBACK = 'false';
if (!process.env.AUTH_JWT_SECRET) {
  process.env.AUTH_JWT_SECRET = 'dev-auth-secret';
}

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

test('Auth strict contract: header auth rejected when fallback disabled', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/auth/me`, {
    headers: authHeaders(`contract-user-strict-${Date.now()}`, 'USER'),
  });

  assert.equal(response.status, 401);
});

test('Auth strict contract: bearer auth allowed when fallback disabled', { skip: !canRun ? skipReason : false }, async () => {
  const userId = `contract-user-strict-jwt-${Date.now()}`;
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
