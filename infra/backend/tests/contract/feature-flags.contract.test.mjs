import assert from 'node:assert/strict';
import test from 'node:test';
import { baseUrl, canRun, registerBackendLifecycle, skipReason } from './contract-harness.mjs';

process.env.FEATURE_DOMAIN_WORKATIONS = 'false';

registerBackendLifecycle(test);

test('Feature flags: disabled workations domain returns 503', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/workations`);
  assert.equal(response.status, 503);

  const body = await response.json();
  assert.equal(typeof body.message, 'string');
  assert.equal(body.message.includes('workations'), true);
});

test('Feature flags: non-disabled domains remain reachable', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/health`);
  assert.equal(response.status, 200);

  const body = await response.json();
  assert.equal(body.status, 'ok');
});
