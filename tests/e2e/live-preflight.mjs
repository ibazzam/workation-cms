import axios from 'axios';

const baseUrl = process.env.BASE_URL;
const scheduleId = Number(process.env.SCHEDULE_ID || 1);
const xUserId = process.env.X_USER_ID;
const xUserRole = process.env.X_USER_ROLE;

if (!baseUrl) {
  console.error('BASE_URL is required. Example: BASE_URL=https://api.workation.mv');
  process.exit(2);
}

const client = axios.create({
  baseURL: baseUrl,
  timeout: 10000,
  headers: {
    ...(xUserId ? { 'x-user-id': String(xUserId) } : {}),
    ...(xUserRole ? { 'x-user-role': String(xUserRole) } : {}),
  },
});

async function requestWithFallbacks(method, paths, body) {
  let lastError = null;

  for (const path of paths) {
    try {
      const res = await client.request({ method, url: path, data: body });
      return { res, path };
    } catch (err) {
      lastError = err;
    }
  }

  throw lastError;
}

async function checkHealth() {
  const candidates = ['/api/v1/health', '/health'];
  let lastError = null;

  for (const path of candidates) {
    try {
      const health = await client.get(path);
      if (health.status === 200) {
        console.log(`Health endpoint OK at ${path}`);
        return;
      }
    } catch (err) {
      lastError = err;
    }
  }

  if (lastError?.response) {
    throw new Error(`health failed: HTTP ${lastError.response.status}`);
  }

  throw new Error(`health failed: ${lastError?.message || 'no healthy endpoint found'}`);
}

async function checkOpsSlo() {
  try {
    const slo = await client.get('/api/v1/ops/slo-summary');
    if (slo.status !== 200) {
      throw new Error(`ops slo failed: ${slo.status}`);
    }
  } catch (err) {
    console.warn('ops/slo-summary not available yet, continuing');
  }
}

async function checkWorkationCrud() {
  const createPayload = {
    title: `preflight-${Date.now()}`,
    description: 'Render+Neon preflight check',
    location: 'Male',
    start_date: new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString(),
    end_date: new Date(Date.now() + 48 * 60 * 60 * 1000).toISOString(),
    price: 199.99,
  };

  const { res: created, path: createPath } = await requestWithFallbacks('post', ['/api/workations', '/api/v1/workations'], createPayload);
  const basePath = createPath.replace(/\/workations$/, '/workations');

  if (created.status !== 201) {
    throw new Error(`workation create failed: ${created.status}`);
  }

  const id = created.data?.id;
  if (!id) {
    throw new Error('workation create did not return id');
  }

  const fetched = await client.get(`${basePath}/${id}`);
  if (fetched.status !== 200) {
    throw new Error(`workation get failed: ${fetched.status}`);
  }

  const updated = await client.put(`${basePath}/${id}`, { location: 'Hulhumale' });
  if (updated.status !== 200) {
    throw new Error(`workation update failed: ${updated.status}`);
  }

  const deleted = await client.delete(`${basePath}/${id}`);
  if (deleted.status !== 204) {
    throw new Error(`workation delete failed: ${deleted.status}`);
  }
}

async function checkTransportHoldFlow() {
  const holdPayload = {
    schedule_id: scheduleId,
    seat_class: 'standard',
    seats: 1,
    idempotency_key: `live-preflight-${Date.now()}`,
    ttl_seconds: 120,
  };

  const { res: hold, path: holdPath } = await requestWithFallbacks('post', ['/api/transport/holds', '/api/v1/transport/holds'], holdPayload);
  const holdsBase = holdPath.replace(/\/holds$/, '/holds');

  if (hold.status !== 201) {
    throw new Error(`hold create failed: ${hold.status}`);
  }

  const holdId = hold.data?.hold?.id;
  if (!holdId) {
    throw new Error('hold create did not return hold id');
  }

  const confirmed = await client.post(`${holdsBase}/${holdId}/confirm`);
  if (confirmed.status !== 200) {
    throw new Error(`hold confirm failed: ${confirmed.status}`);
  }

  const released = await client.post(`${holdsBase}/${holdId}/release`);
  if (released.status !== 200) {
    throw new Error(`hold release failed: ${released.status}`);
  }
}

(async () => {
  try {
    console.log(`Running live preflight against ${baseUrl} (schedule ${scheduleId})`);
    if (xUserId) {
      console.log(`Using auth header x-user-id=${xUserId}`);
    }
    if (xUserRole) {
      console.log(`Using auth header x-user-role=${xUserRole}`);
    }
    await checkHealth();
    await checkOpsSlo();
    await checkWorkationCrud();
    await checkTransportHoldFlow();
    console.log('Live preflight passed');
    process.exit(0);
  } catch (err) {
    const msg = err?.response
      ? `HTTP ${err.response.status}: ${JSON.stringify(err.response.data)}`
      : (err?.message || String(err));
    console.error(`Live preflight failed: ${msg}`);
    process.exit(1);
  }
})();
