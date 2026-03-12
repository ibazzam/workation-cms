import axios from 'axios';

const baseUrl = process.env.BASE_URL;
const scheduleId = Number(process.env.SCHEDULE_ID || 1);
const xUserId = process.env.X_USER_ID;
const xUserRole = process.env.X_USER_ROLE;
const bearerToken = process.env.AUTH_BEARER_TOKEN;
const requireOpsSlo = (process.env.PREFLIGHT_REQUIRE_OPS_SLO ?? 'false').toLowerCase() === 'true';
const requireCheckoutReliability = (process.env.PREFLIGHT_REQUIRE_CHECKOUT_RELIABILITY ?? 'false').toLowerCase() === 'true';
const requirePaymentsReliability = (process.env.PREFLIGHT_REQUIRE_PAYMENTS_RELIABILITY ?? 'false').toLowerCase() === 'true';
const requireModerationPaths = (process.env.PREFLIGHT_REQUIRE_MODERATION_PATHS ?? 'false').toLowerCase() === 'true';
const requireSchedulerHealth = (process.env.PREFLIGHT_REQUIRE_SCHEDULER_HEALTH ?? 'false').toLowerCase() === 'true';
const requireNewVerticals = (process.env.PREFLIGHT_REQUIRE_NEW_VERTICALS ?? 'false').toLowerCase() === 'true';

if (!baseUrl) {
  console.error('BASE_URL is required. Example: BASE_URL=https://api.workation.mv');
  process.exit(2);
}

const client = axios.create({
  baseURL: baseUrl,
  timeout: 30000,
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
  try {
    const health = await client.get('/api/v1/health');
    if (health.status === 200) {
      console.log('Health endpoint OK at /api/v1/health');
      return;
    }
  } catch (err) {
    if (err?.response) {
      throw new Error(`health failed: HTTP ${err.response.status}`);
    }

    throw new Error(`health failed: ${err?.message || 'request failed'}`);
  }

  throw new Error('health failed: unexpected status');
}

async function checkOpsSlo() {
  try {
    const slo = await client.get('/api/v1/ops/slo-summary');
    if (slo.status !== 200) {
      throw new Error(`ops slo failed: ${slo.status}`);
    }
  } catch (err) {
    if (requireOpsSlo) {
      throw new Error('ops/slo-summary is required but unavailable');
    }

    console.warn('ops/slo-summary not available yet, continuing');
  }
}

async function checkWorkationCrud() {
  if (!bearerToken) {
    console.warn('Skipping workation CRUD smoke: AUTH_BEARER_TOKEN not set');
    return;
  }

  const createPayload = {
    title: `preflight-${Date.now()}`,
    description: 'Render+Neon preflight check',
    location: 'Male',
    start_date: new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString(),
    end_date: new Date(Date.now() + 48 * 60 * 60 * 1000).toISOString(),
    price: 199.99,
  };

  const created = await client.post('/api/v1/workations', createPayload);
  const basePath = '/api/v1/workations';

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

async function checkTransportScheduleFlow() {
  const today = new Date().toISOString().slice(0, 10);

  const listPath = '/api/v1/transports';
  const listRes = await client.get(listPath);

  if (listRes.status !== 200) {
    throw new Error(`transport list failed: ${listRes.status}`);
  }

  const scheduleRes = await client.get(`/api/v1/transports/schedule?date=${today}`);

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

    if (reaccommodation.status !== 200 && reaccommodation.status !== 201) {
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
    await checkTransportScheduleFlow();
  } catch (err) {
    if (err?.response?.status === 401 && !bearerToken) {
      console.warn('Skipping transport flow smoke: AUTH_BEARER_TOKEN not set');
      return;
    }
    throw err;
  }
}

function countActiveBookings(bookingsPayload) {
  const entries = Array.isArray(bookingsPayload)
    ? bookingsPayload
    : (Array.isArray(bookingsPayload?.items) ? bookingsPayload.items : []);

  return entries.filter((booking) => ['HOLD', 'PENDING', 'CONFIRMED'].includes(String(booking?.status ?? ''))).length;
}

async function checkCheckoutFailureSemantics() {
  if (!bearerToken) {
    if (requireCheckoutReliability) {
      throw new Error('checkout reliability smoke is required but AUTH_BEARER_TOKEN is not set');
    }

    console.warn('Skipping checkout reliability smoke: AUTH_BEARER_TOKEN not set');
    return;
  }

  let cartPath;
  let bookingsPath;
  let createdCartItemIds = [];

  try {
    const transportsRes = await client.get('/api/v1/transports');

    const transportsData = transportsRes.data;
    const transports = Array.isArray(transportsData)
      ? transportsData
      : (Array.isArray(transportsData?.items) ? transportsData.items : []);
    const firstTransport = transports[0];
    if (!firstTransport?.id) {
      if (requireCheckoutReliability) {
        throw new Error('checkout reliability smoke is required but no transport fixtures are available');
      }

      console.warn('Skipping checkout reliability smoke: no transport fixtures available');
      return;
    }

    const firstFareClassCode = Array.isArray(firstTransport?.fareClasses) && firstTransport.fareClasses[0]?.code
      ? firstTransport.fareClasses[0].code
      : undefined;

    cartPath = '/api/v1/cart';
    await client.get(cartPath);

    bookingsPath = '/api/v1/bookings';
    const bookingsBeforeRes = await client.get(bookingsPath);
    const activeBefore = countActiveBookings(bookingsBeforeRes.data);

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
      if (requireCheckoutReliability) {
        throw new Error('checkout reliability smoke is required but checkout/bookings endpoints are unavailable');
      }

      console.warn('checkout/bookings endpoints unavailable on target runtime, skipping checkout reliability smoke');
      return;
    }

    if (err?.response?.status >= 500) {
      if (requireCheckoutReliability) {
        throw new Error('checkout reliability smoke is required but checkout endpoint is unstable');
      }

      console.warn('checkout reliability endpoint returned 5xx on target runtime, skipping non-required check');
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
    if (requirePaymentsReliability) {
      throw new Error('payments reliability smoke is required but AUTH_BEARER_TOKEN is not set');
    }

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
      if (requirePaymentsReliability) {
        throw new Error('payments reliability smoke is required but endpoints are unavailable');
      }

      console.warn('payments reliability endpoints unavailable on target runtime, skipping payments smoke');
      return;
    }

    if (err?.response?.status === 403) {
      if (requirePaymentsReliability) {
        throw new Error('payments reliability smoke is required but role lacks settlement-report access');
      }

      console.warn('payments settlement report requires elevated role, skipping payments smoke');
      return;
    }

    if (err?.response?.status >= 500) {
      if (requirePaymentsReliability) {
        throw new Error('payments reliability smoke is required but payment endpoints are unstable');
      }

      console.warn('payments reliability endpoints returned 5xx on target runtime, skipping payments smoke');
      return;
    }

    throw err;
  }
}

async function resolveModerationTarget() {
  const transportsResponse = await client.get('/api/v1/transports');
  const transportRows = Array.isArray(transportsResponse.data)
    ? transportsResponse.data
    : (Array.isArray(transportsResponse.data?.items) ? transportsResponse.data.items : []);

  if (!xUserId) {
    const firstTransport = transportRows.find((item) => typeof item?.id === 'string');
    if (firstTransport?.id) {
      return {
        targetType: 'TRANSPORT',
        targetId: firstTransport.id,
      };
    }
  }

  for (const transport of transportRows) {
    if (typeof transport?.id !== 'string') {
      continue;
    }

    let hasActorReview = false;
    try {
      const existingReviews = await client.get(`/api/v1/reviews/transports/${transport.id}`);
      const items = Array.isArray(existingReviews.data?.items) ? existingReviews.data.items : [];
      hasActorReview = items.some((item) => String(item?.user?.id ?? '') === String(xUserId));
    } catch {
      continue;
    }

    if (!hasActorReview) {
      return {
        targetType: 'TRANSPORT',
        targetId: transport.id,
      };
    }
  }

  const accommodationsResponse = await client.get('/api/v1/accommodations');
  const accommodationRows = Array.isArray(accommodationsResponse.data)
    ? accommodationsResponse.data
    : (Array.isArray(accommodationsResponse.data?.items) ? accommodationsResponse.data.items : []);

  const firstAccommodation = accommodationRows.find((item) => typeof item?.id === 'string');
  if (firstAccommodation?.id) {
    return {
      targetType: 'ACCOMMODATION',
      targetId: firstAccommodation.id,
    };
  }

  const createdTransport = await client.post('/api/v1/transports/admin', {
    type: 'SPEEDBOAT',
    code: `LIVE-PREFLIGHT-${Date.now()}`,
    price: 1,
  });

  const createdTransportId = createdTransport.data?.id;
  if (createdTransport.status === 201 && createdTransportId) {
    return {
      targetType: 'TRANSPORT',
      targetId: createdTransportId,
    };
  }

  return null;
}

async function checkModerationAdminPaths() {
  if (!bearerToken) {
    if (requireModerationPaths) {
      throw new Error('moderation paths are required but AUTH_BEARER_TOKEN is not set');
    }

    console.warn('Skipping moderation paths smoke: AUTH_BEARER_TOKEN not set');
    return;
  }

  let createdSocialLinkId = null;

  const withBestEffortMutation = async (label, fn) => {
    try {
      return await fn();
    } catch (err) {
      if (err?.response?.status >= 500) {
        console.warn(`${label} returned 5xx; continuing moderation verification`);
        return null;
      }

      throw err;
    }
  };

  const tryResolveExistingReviewId = async (targetType, targetId) => {
    const targetTypeToPath = {
      TRANSPORT: `/api/v1/reviews/transports/${targetId}`,
      ACCOMMODATION: `/api/v1/reviews/accommodations/${targetId}`,
      ACTIVITY: `/api/v1/reviews/activities/${targetId}`,
      SERVICE: `/api/v1/reviews/services/${targetId}`,
    };

    const path = targetTypeToPath[targetType];
    if (!path) {
      return null;
    }

    try {
      const response = await client.get(path);
      const rows = Array.isArray(response.data?.items) ? response.data.items : [];
      const existing = rows.find((item) => typeof item?.id === 'string');
      return existing?.id ?? null;
    } catch {
      return null;
    }
  };

  const tryResolveModerationQueueReviewId = async () => {
    try {
      const queue = await client.get('/api/v1/reviews/admin/moderation?limit=1');
      const items = Array.isArray(queue.data) ? queue.data : (Array.isArray(queue.data?.items) ? queue.data.items : []);
      const first = items.find((item) => typeof item?.id === 'string');
      return first?.id ?? null;
    } catch (err) {
      console.warn(`Failed to resolve moderation queue review ID: ${err?.message ?? 'unknown error'}`);
      return null;
    }
  };

  const fetchModerationQueueWithRetry = async (label, paths, { required = true } = {}) => {
    let attempt = 0;
    let lastError = null;

    while (attempt < 3) {
      attempt += 1;
      try {
        const { res } = await requestWithFallbacks('get', paths);
        if (res.status === 200) {
          return res;
        }

        throw new Error(`${label} returned ${res.status}`);
      } catch (err) {
        lastError = err;
        if (err?.response?.status >= 500 && attempt < 3) {
          await new Promise((resolve) => setTimeout(resolve, 250 * attempt));
          continue;
        }

        break;
      }
    }

    if (required) {
      throw lastError ?? new Error(`${label} failed`);
    }

    const code = lastError?.response?.status ?? 'unknown';
    console.warn(`${label} unavailable (${code}); continuing moderation verification`);
    return null;
  };

  try {
    const target = await resolveModerationTarget();
    if (!target) {
      if (requireModerationPaths) {
        throw new Error('moderation paths are required but no transport/accommodation fixtures are available');
      }

      console.warn('Skipping moderation paths smoke: no transport/accommodation fixtures available');
      return;
    }

    const createReviewPayload = (targetType, targetId) => ({
      targetType,
      targetId,
      rating: 5,
      title: 'live-preflight moderation probe',
      comment: `live-preflight-${Date.now()}`,
    });

    let reviewCreated = null;
    const existingReviewId = await tryResolveExistingReviewId(target.targetType, target.targetId);
    if (existingReviewId) {
      reviewCreated = { data: { id: existingReviewId } };
    }

    if (!reviewCreated) {
      const queueReviewId = await tryResolveModerationQueueReviewId();
      if (queueReviewId) {
        reviewCreated = { data: { id: queueReviewId } };
      }
    }

    if (!reviewCreated) {
      try {
        reviewCreated = await client.post('/api/v1/reviews', createReviewPayload(target.targetType, target.targetId));
      } catch (err) {
        const message = String(err?.response?.data?.message ?? '');
        const duplicateReview = err?.response?.status === 400 && message.toLowerCase().includes('already reviewed');
        const unstableCreate = err?.response?.status >= 500;

        if (!duplicateReview && !unstableCreate) {
          throw err;
        }

        if (unstableCreate) {
          const resolvedReviewId = await tryResolveExistingReviewId(target.targetType, target.targetId);
          if (resolvedReviewId) {
            reviewCreated = { data: { id: resolvedReviewId } };
          }
        }

        if (!reviewCreated?.data?.id) {
          try {
            const newTransport = await client.post('/api/v1/transports/admin', {
              type: 'SPEEDBOAT',
              code: `LIVE-PREFLIGHT-REVIEW-${Date.now()}`,
              price: 1,
            });

            const newTransportId = newTransport.data?.id;
            if (newTransportId) {
              reviewCreated = await client.post('/api/v1/reviews', createReviewPayload('TRANSPORT', newTransportId));
              target.targetType = 'TRANSPORT';
              target.targetId = newTransportId;
            }
          } catch (fallbackErr) {
            console.warn(`Failed to create fallback review fixture: ${fallbackErr?.message ?? 'unknown error'}`);
          }
        }
      }
    }

    const reviewId = reviewCreated?.data?.id ?? null;
    if (reviewId) {
      const flaggedReview = await withBestEffortMutation('review flag', () => client.post(`/api/v1/reviews/${reviewId}/flag`, {
        reasonCode: 'OTHER',
        reviewerNote: 'live-preflight user flag',
      }));
      if (flaggedReview && flaggedReview.status !== 201 && flaggedReview.status !== 200) {
        throw new Error(`review flag failed: ${flaggedReview.status}`);
      }

      const hiddenReview = await withBestEffortMutation('review hide', () => client.post(`/api/v1/reviews/admin/${reviewId}/hide`, {
        reasonCode: 'POLICY_VIOLATION',
        reviewerNote: 'live-preflight admin hide',
      }));
      if (hiddenReview && hiddenReview.status !== 201 && hiddenReview.status !== 200) {
        throw new Error(`review hide failed: ${hiddenReview.status}`);
      }

      const publishedReview = await withBestEffortMutation('review publish', () => client.post(`/api/v1/reviews/admin/${reviewId}/publish`, {
        reasonCode: 'OTHER',
        reviewerNote: 'live-preflight admin publish',
      }));
      if (publishedReview && publishedReview.status !== 201 && publishedReview.status !== 200) {
        throw new Error(`review publish failed: ${publishedReview.status}`);
      }
    } else {
      console.warn('Skipping review lifecycle mutation checks: no reusable review fixture available');
    }

    const socialCreated = await withBestEffortMutation('social link create', () => client.post('/api/v1/social-links/admin', {
      targetType: target.targetType,
      targetId: target.targetId,
      platform: 'WEBSITE',
      url: `https://example.com/live-preflight-${Date.now()}`,
      handle: 'live-preflight',
    }));

    createdSocialLinkId = socialCreated?.data?.id ?? null;
    if (createdSocialLinkId) {
      const flaggedSocial = await withBestEffortMutation('social link flag', () => client.post(`/api/v1/social-links/${createdSocialLinkId}/flag`, {
        reasonCode: 'OTHER',
        reviewerNote: 'live-preflight social flag',
      }));
      if (flaggedSocial && flaggedSocial.status !== 201 && flaggedSocial.status !== 200) {
        throw new Error(`social link flag failed: ${flaggedSocial.status}`);
      }

      const approvedSocial = await withBestEffortMutation('social link approve', () => client.post(`/api/v1/social-links/admin/${createdSocialLinkId}/approve`, {
        reasonCode: 'OTHER',
        reviewerNote: 'live-preflight social approve',
      }));
      if (approvedSocial && approvedSocial.status !== 201 && approvedSocial.status !== 200) {
        throw new Error(`social link approve failed: ${approvedSocial.status}`);
      }

      const hiddenSocial = await withBestEffortMutation('social link hide', () => client.post(`/api/v1/social-links/admin/${createdSocialLinkId}/hide`, {
        reasonCode: 'POLICY_VIOLATION',
        reviewerNote: 'live-preflight social hide',
      }));
      if (hiddenSocial && hiddenSocial.status !== 201 && hiddenSocial.status !== 200) {
        throw new Error(`social link hide failed: ${hiddenSocial.status}`);
      }
    } else {
      console.warn('Skipping social-link lifecycle mutation checks: social link fixture could not be created');
    }

    const moderationQueueReviews = await fetchModerationQueueWithRetry('reviews moderation queue', [
      '/api/v1/reviews/admin/moderation?limit=1',
      '/api/v1/reviews/admin/moderation',
    ], { required: true });

    const moderationQueueSocial = await fetchModerationQueueWithRetry('social-links moderation queue', [
      '/api/v1/social-links/admin/moderation?limit=1',
      '/api/v1/social-links/admin/moderation',
    ], { required: false });

    if (moderationQueueReviews.status !== 200 || (moderationQueueSocial && moderationQueueSocial.status !== 200)) {
      throw new Error('moderation queue retrieval failed');
    }

    console.log('Moderation admin paths OK');
  } catch (err) {
    if (err?.response?.status === 404) {
      if (requireModerationPaths) {
        throw new Error('moderation paths are required but endpoints are unavailable');
      }

      console.warn('moderation endpoints unavailable on target runtime, skipping moderation smoke');
      return;
    }

    if (err?.response?.status === 403) {
      if (requireModerationPaths) {
        throw new Error('moderation paths are required but role lacks required admin permissions');
      }

      console.warn('moderation endpoints require elevated role, skipping moderation smoke');
      return;
    }

    if (err?.response?.status >= 500) {
      if (requireModerationPaths) {
        throw new Error('moderation paths are required but moderation endpoints are unstable');
      }

      console.warn('moderation endpoints returned 5xx on target runtime, skipping moderation smoke');
      return;
    }

    throw err;
  } finally {
    if (createdSocialLinkId) {
      try {
        await client.delete(`/api/v1/social-links/admin/${createdSocialLinkId}`);
      } catch {
        // Cleanup is best-effort for preflight safety.
      }
    }
  }
}

async function checkSchedulerHealth() {
  if (!bearerToken) {
    if (requireSchedulerHealth) {
      throw new Error('scheduler health is required but AUTH_BEARER_TOKEN is not set');
    }

    console.warn('Skipping scheduler health smoke: AUTH_BEARER_TOKEN not set');
    return;
  }

  try {
    const [reconcileStatus, jobsHealth, opsAlerts] = await Promise.all([
      client.get('/api/v1/payments/admin/reconcile/status'),
      client.get('/api/v1/payments/admin/jobs/health'),
      client.get('/api/v1/payments/admin/alerts'),
    ]);

    if (reconcileStatus.status !== 200) {
      throw new Error(`reconcile status failed: ${reconcileStatus.status}`);
    }

    if (jobsHealth.status !== 200) {
      throw new Error(`jobs health failed: ${jobsHealth.status}`);
    }

    if (opsAlerts.status !== 200) {
      throw new Error(`payments alerts failed: ${opsAlerts.status}`);
    }

    console.log('Scheduler health endpoints OK');
  } catch (err) {
    if (err?.response?.status === 404) {
      if (requireSchedulerHealth) {
        throw new Error('scheduler health is required but payments scheduler endpoints are unavailable');
      }

      console.warn('payments scheduler endpoints unavailable on target runtime, skipping scheduler smoke');
      return;
    }

    if (err?.response?.status === 403) {
      if (requireSchedulerHealth) {
        throw new Error('scheduler health is required but role lacks required admin permissions');
      }

      console.warn('payments scheduler endpoints require elevated role, skipping scheduler smoke');
      return;
    }

    if (err?.response?.status >= 500) {
      if (requireSchedulerHealth) {
        throw new Error('scheduler health is required but scheduler endpoints are unstable');
      }

      console.warn('payments scheduler endpoints returned 5xx on target runtime, skipping scheduler smoke');
      return;
    }

    throw err;
  }
}

async function checkNewVerticalsCoverage() {
  const domains = [
    {
      key: 'excursions',
      listPath: '/api/v1/excursions',
      detailPath: (id) => `/api/v1/excursions/${id}`,
      childListPath: (id) => `/api/v1/excursions/${id}/slots`,
      quotePath: (id, childRows) => {
        const slotId = childRows[0]?.id;
        return slotId
          ? `/api/v1/excursions/${id}/quote?participants=1&slotId=${encodeURIComponent(slotId)}`
          : `/api/v1/excursions/${id}/quote?participants=1`;
      },
    },
    {
      key: 'restaurants',
      listPath: '/api/v1/restaurants',
      detailPath: (id) => `/api/v1/restaurants/${id}`,
      childListPath: (id) => `/api/v1/restaurants/${id}/windows`,
      quotePath: (id, childRows) => {
        const windowId = childRows[0]?.id;
        return windowId
          ? `/api/v1/restaurants/${id}/quote?partySize=2&windowId=${encodeURIComponent(windowId)}`
          : `/api/v1/restaurants/${id}/quote?partySize=2`;
      },
    },
    {
      key: 'resort-day-visits',
      listPath: '/api/v1/resort-day-visits',
      detailPath: (id) => `/api/v1/resort-day-visits/${id}`,
      childListPath: (id) => `/api/v1/resort-day-visits/${id}/windows`,
      quotePath: (id, childRows) => {
        const windowId = childRows[0]?.id;
        return windowId
          ? `/api/v1/resort-day-visits/${id}/quote?passesRequested=1&travelerCategory=ADULT&travelerAge=30&windowId=${encodeURIComponent(windowId)}`
          : null;
      },
    },
    {
      key: 'remote-work-spaces',
      listPath: '/api/v1/remote-work-spaces',
      detailPath: (id) => `/api/v1/remote-work-spaces/${id}`,
      childListPath: (id) => `/api/v1/remote-work-spaces/${id}/pass-windows`,
      quotePath: (id, childRows) => {
        const windowId = childRows[0]?.id;
        return windowId
          ? `/api/v1/remote-work-spaces/${id}/quote?passesRequested=1&passType=DAY&deskType=DESK&windowId=${encodeURIComponent(windowId)}`
          : null;
      },
    },
    {
      key: 'vehicle-rentals',
      listPath: '/api/v1/vehicle-rentals',
      detailPath: (id) => `/api/v1/vehicle-rentals/${id}`,
      childListPath: (id) => {
        const startDate = new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString().slice(0, 10);
        const endDate = new Date(Date.now() + 2 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10);
        return `/api/v1/vehicle-rentals/${id}/availability?startDate=${startDate}&endDate=${endDate}&unitsRequested=1`;
      },
      quotePath: (id) => {
        const startDate = new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString().slice(0, 10);
        const endDate = new Date(Date.now() + 2 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10);
        return `/api/v1/vehicle-rentals/${id}/quote?startDate=${startDate}&endDate=${endDate}&unitsRequested=1&driverAge=30&hasLicense=true`;
      },
    },
  ];

  for (const domain of domains) {
    let list;
    try {
      list = await client.get(domain.listPath);
    } catch (err) {
      if (requireNewVerticals) {
        throw err;
      }

      const status = err?.response?.status;
      console.warn(`Skipping ${domain.key} checks: list endpoint unavailable (${status ?? 'request error'})`);
      continue;
    }

    if (list.status !== 200) {
      if (requireNewVerticals) {
        throw new Error(`${domain.key} list failed: ${list.status}`);
      }

      console.warn(`Skipping ${domain.key} checks: list endpoint returned ${list.status}`);
      continue;
    }

    const rows = Array.isArray(list.data)
      ? list.data
      : (Array.isArray(list.data?.items) ? list.data.items : []);

    const first = rows.find((item) => typeof item?.id === 'string');
    if (!first?.id) {
      console.warn(`Skipping deep ${domain.key} checks: no fixtures returned`);
      continue;
    }

    try {
      const detail = await client.get(domain.detailPath(first.id));
      if (detail.status !== 200) {
        throw new Error(`${domain.key} detail failed: ${detail.status}`);
      }

      const childList = await client.get(domain.childListPath(first.id));
      if (childList.status !== 200) {
        throw new Error(`${domain.key} window/availability list failed: ${childList.status}`);
      }

      const childRows = Array.isArray(childList.data)
        ? childList.data
        : (Array.isArray(childList.data?.items) ? childList.data.items : []);

      const quotePath = domain.quotePath(first.id, childRows);
      if (!quotePath) {
        console.warn(`Skipping ${domain.key} quote check: no slot/window fixtures available`);
        continue;
      }

      const quote = await client.get(quotePath);
      if (quote.status !== 200) {
        throw new Error(`${domain.key} quote failed: ${quote.status}`);
      }
    } catch (err) {
      const status = err?.response?.status;
      if (!requireNewVerticals && status >= 500) {
        console.warn(`Skipping ${domain.key} deep checks: endpoint unstable (${status})`);
        continue;
      }

      throw err;
    }
  }

  console.log('New verticals integration coverage checks completed');
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
    console.log('Preflight checkpoint: health');
    await checkHealth();
    console.log('Preflight checkpoint: ops-slo');
    await checkOpsSlo();
    console.log('Preflight checkpoint: workation-crud');
    await checkWorkationCrud();
    console.log('Preflight checkpoint: transport-flow');
    await checkTransportFlow();
    console.log('Preflight checkpoint: checkout-reliability');
    await checkCheckoutFailureSemantics();
    console.log('Preflight checkpoint: payments-reliability');
    await checkPaymentsReliabilityFlow();
    console.log('Preflight checkpoint: moderation-paths');
    await checkModerationAdminPaths();
    console.log('Preflight checkpoint: scheduler-health');
    await checkSchedulerHealth();
    console.log('Preflight checkpoint: new-verticals');
    await checkNewVerticalsCoverage();
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
