const { spawn } = require('node:child_process');
const { spawnSync } = require('node:child_process');
const path = require('node:path');
const assert = require('node:assert/strict');
const { PrismaClient } = require('@prisma/client');

const port = Number(process.env.CONTRACT_TEST_PORT || 3310);
const baseUrl = `http://127.0.0.1:${port}`;

function buildContractDatabaseUrl() {
  const raw = process.env.CONTRACT_TEST_DATABASE_URL || process.env.DATABASE_URL;
  if (!raw) return null;

  if (process.env.CONTRACT_TEST_DATABASE_URL) {
    return process.env.CONTRACT_TEST_DATABASE_URL;
  }

  const url = new URL(raw);
  const schema = process.env.CONTRACT_TEST_SCHEMA || 'wb201_contract';
  url.searchParams.set('schema', schema);
  return url.toString();
}

const contractDatabaseUrl = buildContractDatabaseUrl();
const prisma = new PrismaClient({
  datasources: {
    db: {
      url: contractDatabaseUrl || undefined,
    },
  },
});

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function waitForHealth(timeoutMs = 20000) {
  const start = Date.now();
  while (Date.now() - start < timeoutMs) {
    try {
      const res = await fetch(`${baseUrl}/health`);
      if (res.ok) return;
    } catch (_) {
      // continue polling
    }
    await sleep(250);
  }

  throw new Error('Backend did not become healthy in time');
}

async function jsonRequest(method, url, body) {
  const res = await fetch(`${baseUrl}${url}`, {
    method,
    headers: {
      'Content-Type': 'application/json',
    },
    body: body ? JSON.stringify(body) : undefined,
  });

  const text = await res.text();
  const data = text ? JSON.parse(text) : null;
  return { status: res.status, data };
}

async function ensureTestSchedule() {
  const schedule = await prisma.transportSchedule.create({
    data: {
      originIslandId: 101,
      destinationIslandId: 202,
      operator: 'contract-test-operator',
      departureAt: new Date(Date.now() + 3600 * 1000),
      arrivalAt: new Date(Date.now() + 7200 * 1000),
    },
  });

  await prisma.transportInventory.create({
    data: {
      scheduleId: schedule.id,
      seatClass: 'standard',
      totalSeats: 10,
      reservedSeats: 0,
    },
  });

  return schedule;
}

async function run() {
  if (!contractDatabaseUrl) {
    throw new Error('DATABASE_URL or CONTRACT_TEST_DATABASE_URL is required for contract tests');
  }

  const dbPush = spawnSync(
    'npx.cmd',
    ['prisma', 'db', 'push', '--schema=..\\prisma\\schema.prisma', '--accept-data-loss'],
    {
      cwd: path.resolve(__dirname, '..'),
      env: {
        ...process.env,
        DATABASE_URL: contractDatabaseUrl,
      },
      stdio: 'inherit',
      shell: true,
    },
  );

  if (dbPush.status !== 0) {
    throw new Error('Failed to provision contract-test schema via prisma db push');
  }

  const child = spawn(process.execPath, ['dist/main.js'], {
    cwd: path.resolve(__dirname, '..'),
    env: {
      ...process.env,
      PORT: String(port),
      DATABASE_URL: contractDatabaseUrl,
    },
    stdio: ['ignore', 'pipe', 'pipe'],
  });

  child.stdout.on('data', (buf) => process.stdout.write(buf));
  child.stderr.on('data', (buf) => process.stderr.write(buf));

  let createdWorkationId = null;
  let createdHoldId = null;
  let scheduleId = null;

  try {
    await waitForHealth();

    const schedule = await ensureTestSchedule();
    scheduleId = schedule.id;

    // Workation parity contract
    const createWorkation = await jsonRequest('POST', '/api/workations', {
      title: 'Contract Test Workation',
      description: 'Parity test record',
      location: 'Male',
      start_date: '2026-04-01T00:00:00.000Z',
      end_date: '2026-04-05T00:00:00.000Z',
      price: 999.5,
    });

    assert.equal(createWorkation.status, 201);
    assert.equal(createWorkation.data.title, 'Contract Test Workation');
    createdWorkationId = createWorkation.data.id;

    const listWorkations = await jsonRequest('GET', '/api/workations');
    assert.equal(listWorkations.status, 200);
    assert.ok(Array.isArray(listWorkations.data));

    const showWorkation = await jsonRequest('GET', `/api/workations/${createdWorkationId}`);
    assert.equal(showWorkation.status, 200);
    assert.equal(showWorkation.data.id, createdWorkationId);

    const updateWorkation = await jsonRequest('PUT', `/api/workations/${createdWorkationId}`, {
      location: 'Hulhumale',
    });
    assert.equal(updateWorkation.status, 200);
    assert.equal(updateWorkation.data.location, 'Hulhumale');

    // Hold parity contract
    const createHold = await jsonRequest('POST', '/api/transport/holds', {
      schedule_id: schedule.id,
      seat_class: 'standard',
      seats: 2,
      idempotency_key: `contract-test-${Date.now()}`,
      ttl_seconds: 300,
    });

    assert.equal(createHold.status, 201);
    assert.ok(createHold.data.hold.id);
    createdHoldId = createHold.data.hold.id;

    const confirmHold = await jsonRequest('POST', `/api/transport/holds/${createdHoldId}/confirm`);
    assert.equal(confirmHold.status, 200);
    assert.equal(confirmHold.data.hold.status, 'confirmed');

    const releaseHold = await jsonRequest('POST', `/api/transport/holds/${createdHoldId}/release`);
    assert.equal(releaseHold.status, 200);
    assert.equal(releaseHold.data.hold.status, 'released');

    const deleteWorkation = await fetch(`${baseUrl}/api/workations/${createdWorkationId}`, { method: 'DELETE' });
    assert.equal(deleteWorkation.status, 204);

    console.log('WB-201 contract parity tests passed');
  } finally {
    try {
      if (createdHoldId) {
        await prisma.transportHold.deleteMany({ where: { id: createdHoldId } });
      }
      if (scheduleId) {
        await prisma.transportInventory.deleteMany({ where: { scheduleId } });
        await prisma.transportDisruption.deleteMany({ where: { scheduleId } });
        await prisma.transportSchedule.deleteMany({ where: { id: scheduleId } });
      }
      if (createdWorkationId) {
        await prisma.workation.deleteMany({ where: { id: createdWorkationId } });
      }
    } catch (_) {
      // best-effort cleanup
    }

    child.kill('SIGTERM');
    await prisma.$disconnect();
  }
}

run().catch(async (error) => {
  console.error(error);
  await prisma.$disconnect();
  process.exit(1);
});
