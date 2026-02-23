import assert from 'node:assert/strict';
import test from 'node:test';
import { authHeaders, baseUrl, canRun, registerBackendLifecycle, skipReason } from './contract-harness.mjs';

registerBackendLifecycle(test);

test('GET /api/v1/admin/settings/commercial enforces RBAC', { skip: !canRun ? skipReason : false }, async () => {
  const userResponse = await fetch(`${baseUrl}/admin/settings/commercial`, {
    headers: authHeaders(`contract-admin-settings-user-${Date.now()}`, 'USER'),
  });

  assert.equal(userResponse.status, 403);
});

test('GET /api/v1/admin/settings/commercial returns defaults for ADMIN_FINANCE', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/admin/settings/commercial`, {
    headers: authHeaders(`contract-admin-settings-finance-${Date.now()}`, 'ADMIN_FINANCE'),
  });

  assert.equal(response.status, 200);
  const body = await response.json();

  assert.equal(typeof body.currency, 'object');
  assert.equal(body.currency.baseCurrency === 'USD' || body.currency.baseCurrency === 'MVR', true);
  assert.equal(Array.isArray(body.currency.supportedCurrencies), true);

  assert.equal(typeof body.exchangeRates, 'object');
  assert.equal(Array.isArray(body.exchangeRates.rates), true);
  assert.equal(typeof body.loyalty, 'object');
  assert.equal(typeof body.loyalty.enabled, 'boolean');
});

test('POST /api/v1/admin/settings/commercial updates settings for ADMIN_FINANCE', { skip: !canRun ? skipReason : false }, async () => {
  const payload = {
    currency: {
      baseCurrency: 'MVR',
      supportedCurrencies: ['USD', 'MVR'],
    },
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
      unitCurrency: 'MVR',
      redemptionValuePerPoint: 0.1,
      minimumPointsToRedeem: 250,
    },
  };

  const response = await fetch(`${baseUrl}/admin/settings/commercial`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-admin-settings-finance-update-${Date.now()}`, 'ADMIN_FINANCE'),
    },
    body: JSON.stringify(payload),
  });

  assert.equal(response.status, 201);
  const body = await response.json();
  assert.equal(body.currency.baseCurrency, 'MVR');
  assert.equal(body.loyalty.enabled, true);
  assert.equal(body.loyalty.minimumPointsToRedeem, 250);

  const readBack = await fetch(`${baseUrl}/admin/settings/commercial`, {
    headers: authHeaders(`contract-admin-settings-finance-readback-${Date.now()}`, 'ADMIN_FINANCE'),
  });

  assert.equal(readBack.status, 200);
  const readBackBody = await readBack.json();
  assert.equal(readBackBody.currency.baseCurrency, 'MVR');
  assert.equal(readBackBody.loyalty.enabled, true);
});

test('POST /api/v1/admin/settings/commercial denies ADMIN_CARE writes', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/admin/settings/commercial`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-admin-settings-care-write-${Date.now()}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify({
      loyalty: {
        enabled: true,
        pointsPerUnitSpend: 3,
        unitCurrency: 'USD',
        redemptionValuePerPoint: 0.02,
        minimumPointsToRedeem: 100,
      },
    }),
  });

  assert.equal(response.status, 403);
});
