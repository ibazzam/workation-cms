import assert from 'node:assert/strict';
import test from 'node:test';
import { authHeaders, baseUrl, canRun, registerBackendLifecycle, skipReason } from './contract-harness.mjs';

registerBackendLifecycle(test);

test('GET and POST /api/v1/workations contract', { skip: !canRun ? skipReason : false }, async () => {
  const getBefore = await fetch(`${baseUrl}/workations`);
  assert.equal(getBefore.status, 200);
  const beforeBody = await getBefore.json();
  assert.equal(Array.isArray(beforeBody), true);

  const payload = {
    title: `Contract Test ${Date.now()}`,
    description: 'Contract test workation payload',
    location: 'Male',
    start_date: '2026-03-01',
    end_date: '2026-03-08',
    price: 999.99,
  };

  const postResponse = await fetch(`${baseUrl}/workations`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-workation-admin-care-${Date.now()}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify(payload),
  });

  assert.equal(postResponse.status, 201);
  const created = await postResponse.json();
  assert.equal(typeof created.id, 'number');
  assert.equal(created.title, payload.title);
  assert.equal(created.location, payload.location);
  assert.equal(Number(created.price), payload.price);

  const getAfter = await fetch(`${baseUrl}/workations`);
  assert.equal(getAfter.status, 200);
  const afterBody = await getAfter.json();
  assert.equal(Array.isArray(afterBody), true);
  assert.equal(afterBody.some((item) => item.id === created.id && item.title === payload.title), true);
});

test('PUT and DELETE /api/v1/workations contract', { skip: !canRun ? skipReason : false }, async () => {
  const createPayload = {
    title: `Contract Update Target ${Date.now()}`,
    description: 'Initial description',
    location: 'Male',
    start_date: '2026-04-01',
    end_date: '2026-04-05',
    price: 500,
  };

  const createResponse = await fetch(`${baseUrl}/workations`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-workation-admin-care-create-${Date.now()}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify(createPayload),
  });

  assert.equal(createResponse.status, 201);
  const created = await createResponse.json();
  assert.equal(typeof created.id, 'number');

  const updatePayload = {
    title: `${createPayload.title} Updated`,
    location: 'Hulhumale',
    price: 650.5,
  };

  const updateResponse = await fetch(`${baseUrl}/workations/${created.id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-workation-admin-care-update-${Date.now()}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify(updatePayload),
  });

  assert.equal(updateResponse.status, 200);
  const updated = await updateResponse.json();
  assert.equal(updated.id, created.id);
  assert.equal(updated.title, updatePayload.title);
  assert.equal(updated.location, updatePayload.location);
  assert.equal(Number(updated.price), updatePayload.price);

  const deleteResponse = await fetch(`${baseUrl}/workations/${created.id}`, {
    method: 'DELETE',
    headers: authHeaders(`contract-workation-admin-care-delete-${Date.now()}`, 'ADMIN_CARE'),
  });

  assert.equal(deleteResponse.status, 204);

  const getAfterDelete = await fetch(`${baseUrl}/workations/${created.id}`);
  assert.equal(getAfterDelete.status, 404);
});

test('POST /api/v1/workations validation contract', { skip: !canRun ? skipReason : false }, async () => {
  const badDatePayload = {
    title: `Contract Invalid Date ${Date.now()}`,
    description: 'Invalid date order payload',
    location: 'Male',
    start_date: '2026-05-10',
    end_date: '2026-05-01',
    price: 300,
  };

  const badDateResponse = await fetch(`${baseUrl}/workations`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-workation-admin-care-validation-${Date.now()}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify(badDatePayload),
  });

  assert.equal(badDateResponse.status, 400);
  const badDateBody = await badDateResponse.json();
  assert.equal(typeof badDateBody.message, 'string');

  const missingTitlePayload = {
    description: 'Missing title payload',
    location: 'Male',
    start_date: '2026-06-01',
    end_date: '2026-06-05',
    price: 450,
  };

  const missingTitleResponse = await fetch(`${baseUrl}/workations`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-workation-admin-care-validation-${Date.now()}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify(missingTitlePayload),
  });

  assert.equal(missingTitleResponse.status, 400);
  const missingTitleBody = await missingTitleResponse.json();
  assert.equal(typeof missingTitleBody.message, 'string');

  const nonNumericPricePayload = {
    title: `Contract Invalid Price ${Date.now()}`,
    description: 'Non numeric price payload',
    location: 'Male',
    start_date: '2026-06-10',
    end_date: '2026-06-12',
    price: 'not-a-number',
  };

  const nonNumericPriceResponse = await fetch(`${baseUrl}/workations`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-workation-admin-care-validation-${Date.now()}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify(nonNumericPricePayload),
  });

  assert.equal(nonNumericPriceResponse.status, 400);
  const nonNumericPriceBody = await nonNumericPriceResponse.json();
  assert.equal(typeof nonNumericPriceBody.message, 'string');

  const negativePricePayload = {
    title: `Contract Negative Price ${Date.now()}`,
    description: 'Negative price payload',
    location: 'Male',
    start_date: '2026-06-15',
    end_date: '2026-06-20',
    price: -1,
  };

  const negativePriceResponse = await fetch(`${baseUrl}/workations`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-workation-admin-care-validation-${Date.now()}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify(negativePricePayload),
  });

  assert.equal(negativePriceResponse.status, 400);
  const negativePriceBody = await negativePriceResponse.json();
  assert.equal(typeof negativePriceBody.message, 'string');
});

test('POST /api/v1/workations enforces RBAC for non-property roles', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/workations`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-workation-user-rbac-${Date.now()}`, 'USER'),
    },
    body: JSON.stringify({
      title: `Contract RBAC ${Date.now()}`,
      description: 'RBAC validation payload',
      location: 'Male',
      start_date: '2026-07-01',
      end_date: '2026-07-05',
      price: 120,
    }),
  });

  assert.equal(response.status, 403);
});
