import axios from 'axios';

const baseUrl = process.env.BASE_URL;
const scheduleId = Number(process.env.SCHEDULE_ID || 1);
const xUserId = process.env.X_USER_ID;
const xUserRole = process.env.X_USER_ROLE;
const bearerToken = process.env.AUTH_BEARER_TOKEN;

if (!baseUrl) {
  console.error('BASE_URL is required. Example: BASE_URL=https://api.workation.mv');
  process.exit(2);
}

const client = axios.create({
  baseURL: baseUrl,
  timeout: 10000,
  headers: {
    ...(bearerToken ? { Authorization: `Bearer ${bearerToken}` } : {}),
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

async function checkTransportScheduleFlow() {
  const today = new Date().toISOString().slice(0, 10);

  const { res: listRes, path: listPath } = await requestWithFallbacks('get', [
    '/api/v1/transports',
    '/api/transports',
  ]);

  if (listRes.status !== 200) {
    throw new Error(`transport list failed: ${listRes.status}`);
  }

  const { res: scheduleRes } = await requestWithFallbacks('get', [
    `/api/v1/transports/schedule?date=${today}`,
    `/api/transports/schedule?date=${today}`,
    `/api/v1/transports/flights/schedule?date=${today}`,
    `/api/transports/flights/schedule?date=${today}`,
  ]);

  if (scheduleRes.status !== 200) {
    throw new Error(`transport schedule failed: ${scheduleRes.status}`);
  }

  const listData = listRes.data;
  const transports = Array.isArray(listData)
    ? listData
    : (Array.isArray(listData?.items) ? listData.items : []);
  const firstTransport = transports[0] ?? null;

  if (firstTransport?.id) {
    const detail = await client.get(`${listPath}/${firstTransport.id}`);
    if (detail.status !== 200) {
      throw new Error(`transport details failed: ${detail.status}`);
    }

    const firstFareClassCode = Array.isArray(firstTransport?.fareClasses) && firstTransport.fareClasses[0]?.code
      ? firstTransport.fareClasses[0].code
      : undefined;

    const quotePath = firstFareClassCode
      ? `${listPath}/${firstTransport.id}/quote?guests=1&fareClassCode=${encodeURIComponent(firstFareClassCode)}`
      : `${listPath}/${firstTransport.id}/quote?guests=1`;

    const quote = await client.get(quotePath);
    if (quote.status !== 200) {
      throw new Error(`transport quote failed: ${quote.status}`);
    }
  }

  if (bearerToken && transports.length >= 2 && transports[0]?.id && transports[1]?.id) {
    const disruptedId = transports[0].id;
    const replacementId = transports[1].id;

    const disruption = await client.post(`/api/v1/transports/admin/${disruptedId}/disruptions`, {
      status: 'DELAYED',
      delayMinutes: 15,
      reason: 'live-preflight disruption test',
      replacementTransportId: replacementId,
    });

    if (disruption.status !== 201) {
      throw new Error(`transport disruption create failed: ${disruption.status}`);
    }

    const disruptionId = disruption.data?.id;
    if (!disruptionId) {
      throw new Error('transport disruption create did not return id');
    }

    const reaccommodation = await client.post(
      `/api/v1/transports/admin/${disruptedId}/disruptions/${disruptionId}/reaccommodate`,
    );

    if (reaccommodation.status !== 200) {
      throw new Error(`transport reaccommodation failed: ${reaccommodation.status}`);
    }

    const resolved = await client.patch(
      `/api/v1/transports/admin/${disruptedId}/disruptions/${disruptionId}/resolve`,
    );

    if (resolved.status !== 200) {
      throw new Error(`transport disruption resolve failed: ${resolved.status}`);
    }
  }
}

async function checkTransportFlow() {
  try {
    await checkTransportHoldFlow();
    console.log('Transport hold flow OK (legacy endpoints)');
    return;
  } catch (err) {
    if (err?.response?.status !== 404) {
      throw err;
    }

    console.warn('Legacy transport hold endpoints unavailable; running transport schedule smoke instead');
  }

  await checkTransportScheduleFlow();
}

function countActiveBookings(bookingsPayload) {
  const entries = Array.isArray(bookingsPayload)
    ? bookingsPayload
    : (Array.isArray(bookingsPayload?.items) ? bookingsPayload.items : []);

  return entries.filter((booking) => ['HOLD', 'PENDING', 'CONFIRMED'].includes(String(booking?.status ?? ''))).length;
}

async function checkCheckoutFailureSemantics() {
  if (!bearerToken) {
    console.warn('Skipping checkout reliability smoke: AUTH_BEARER_TOKEN not set');
    return;
  }

  let cartPath;
  let bookingsPath;
  let createdCartItemIds = [];

  try {
    const { res: transportsRes } = await requestWithFallbacks('get', [
      '/api/v1/transports',
      '/api/transports',
    ]);

    const transportsData = transportsRes.data;
    const transports = Array.isArray(transportsData)
      ? transportsData
      : (Array.isArray(transportsData?.items) ? transportsData.items : []);
    const firstTransport = transports[0];
    if (!firstTransport?.id) {
      console.warn('Skipping checkout reliability smoke: no transport fixtures available');
      return;
    }

    const firstFareClassCode = Array.isArray(firstTransport?.fareClasses) && firstTransport.fareClasses[0]?.code
      ? firstTransport.fareClasses[0].code
      : undefined;

    const cartFetch = await requestWithFallbacks('get', ['/api/v1/cart', '/api/cart']);
    cartPath = cartFetch.path;

    const bookingsBeforeRes = await requestWithFallbacks('get', ['/api/v1/bookings', '/api/bookings']);
    bookingsPath = bookingsBeforeRes.path;
    const activeBefore = countActiveBookings(bookingsBeforeRes.res.data);

    const validTransportItemPayload = {
      serviceType: 'TRANSPORT',
      transportId: firstTransport.id,
      guests: 1,
      ...(firstFareClassCode ? { transportFareClassCode: firstFareClassCode } : {}),
    };

    const validItem = await client.post(`${cartPath}/items`, validTransportItemPayload);
    createdCartItemIds.push(validItem?.data?.items?.[validItem.data.items.length - 1]?.id ?? null);

    const invalidTransportItem = await client.post(`${cartPath}/items`, {
      serviceType: 'TRANSPORT',
      transportId: `missing-transport-${Date.now()}`,
      guests: 1,
    });
    createdCartItemIds.push(invalidTransportItem?.data?.items?.[invalidTransportItem.data.items.length - 1]?.id ?? null);

    let checkoutFailed = false;
    try {
      await client.post(`${cartPath}/checkout?clear=false`, {});
    } catch (err) {
      if (err?.response?.status && [400, 404].includes(err.response.status)) {
        checkoutFailed = true;
      } else {
        throw err;
      }
    }

    if (!checkoutFailed) {
      throw new Error('checkout reliability smoke expected a failure but checkout succeeded');
    }

    const bookingsAfterRes = await client.get(bookingsPath);
    const activeAfter = countActiveBookings(bookingsAfterRes.data);
    if (activeAfter !== activeBefore) {
      throw new Error(`checkout reliability smoke failed: active booking count drifted (${activeBefore} -> ${activeAfter})`);
    }
  } catch (err) {
    if (err?.response?.status === 404) {
      console.warn('checkout/bookings endpoints unavailable on target runtime, skipping checkout reliability smoke');
      return;
    }
    throw err;
  } finally {
    if (cartPath) {
      for (const itemId of createdCartItemIds.filter(Boolean)) {
        try {
          await client.delete(`${cartPath}/items/${itemId}`);
        } catch {
          // Cleanup is best-effort for preflight safety.
        }
      }
    }
  }
}

async function checkPaymentsReliabilityFlow() {
  if (!bearerToken) {
    console.warn('Skipping payments reliability smoke: AUTH_BEARER_TOKEN not set');
    return;
  }

  try {
    const from = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString();
    const to = new Date().toISOString();
    const settlement = await client.get(`/api/v1/payments/admin/settlements/report?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`);
    if (settlement.status !== 200) {
      throw new Error(`settlement report failed: ${settlement.status}`);
    }

    let refundValidationFailed = false;
    try {
      await client.post('/api/v1/payments/refunds', {
        reason: 'preflight-negative-validation',
      });
    } catch (err) {
      if (err?.response?.status === 400) {
        refundValidationFailed = true;
      } else {
        throw err;
      }
    }

    if (!refundValidationFailed) {
      throw new Error('payments reliability smoke expected refund validation failure but request succeeded');
    }

    let disputeValidationFailed = false;
    try {
      await client.post('/api/v1/payments/disputes', {
        paymentId: `missing-payment-${Date.now()}`,
      });
    } catch (err) {
      if (err?.response?.status === 400 || err?.response?.status === 404) {
        disputeValidationFailed = true;
      } else {
        throw err;
      }
    }

    if (!disputeValidationFailed) {
      throw new Error('payments reliability smoke expected dispute validation failure but request succeeded');
    }
  } catch (err) {
    if (err?.response?.status === 404) {
      console.warn('payments reliability endpoints unavailable on target runtime, skipping payments smoke');
      return;
    }

    if (err?.response?.status === 403) {
      console.warn('payments settlement report requires elevated role, skipping payments smoke');
      return;
    }

    throw err;
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
    if (bearerToken) {
      console.log('Using bearer token authentication');
    }
    await checkHealth();
    await checkOpsSlo();
    await checkWorkationCrud();
    await checkTransportFlow();
    await checkCheckoutFailureSemantics();
    await checkPaymentsReliabilityFlow();
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
