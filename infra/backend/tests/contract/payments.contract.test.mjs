import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import test from 'node:test';
import { PrismaClient } from '@prisma/client';
import { authHeaders, baseUrl, canRun, registerBackendLifecycle, skipReason } from './contract-harness.mjs';

registerBackendLifecycle(test);

const prisma = new PrismaClient();

let fixtureUser;
let fixtureBooking;

async function cleanupFixtures() {
  await prisma.paymentWebhookEvent.deleteMany({ where: { provider: 'STRIPE', eventId: { startsWith: 'evt_contract_' } } });
  await prisma.paymentWebhookEvent.deleteMany({ where: { provider: 'BML', eventId: { startsWith: 'evt_bml_' } } });
  await prisma.paymentWebhookEvent.deleteMany({ where: { provider: 'MIB', eventId: { startsWith: 'evt_mib_' } } });
  await prisma.paymentBackgroundJob.deleteMany({});
  await prisma.payment.deleteMany({ where: { bookingId: { startsWith: 'contract-payment-booking-' } } });
  await prisma.booking.deleteMany({ where: { id: { startsWith: 'contract-payment-booking-' } } });
  await prisma.user.deleteMany({ where: { id: { startsWith: 'contract-payment-user-' } } });
}

test.before(async () => {
  if (!canRun) {
    return;
  }

  await prisma.$connect();
  await cleanupFixtures();

  const now = Date.now();
  fixtureUser = await prisma.user.create({
    data: {
      id: `contract-payment-user-${now}`,
      email: `contract-payment-user-${now}@example.test`,
      role: 'USER',
    },
  });

  fixtureBooking = await prisma.booking.create({
    data: {
      id: `contract-payment-booking-${now}`,
      userId: fixtureUser.id,
      guests: 1,
      totalPrice: 250,
      status: 'PENDING',
    },
  });
});

test.after(async () => {
  if (!canRun) {
    return;
  }

  await cleanupFixtures();
  await prisma.$disconnect();
});

test('GET /api/v1/payments/admin/bml/health enforces RBAC', { skip: !canRun ? skipReason : false }, async () => {
  const userResponse = await fetch(`${baseUrl}/payments/admin/bml/health`, {
    headers: authHeaders(`contract-payment-user-rbac-${Date.now()}`, 'USER'),
  });

  assert.equal(userResponse.status, 403);
});

test('GET /api/v1/payments/admin/bml/health returns probe details for ADMIN', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/payments/admin/bml/health`, {
    headers: authHeaders(`contract-payment-admin-${Date.now()}`, 'ADMIN'),
  });

  assert.equal(response.status, 200);
  const body = await response.json();
  assert.equal(body.provider, 'BML');
  assert.equal(body.mode, 'connect');
  assert.equal(typeof body.configured, 'boolean');
  assert.equal(typeof body.reachable, 'boolean');
  assert.equal(typeof body.authenticated, 'boolean');
  assert.equal(typeof body.responseTimeMs, 'number');
  assert.ok(body.responseTimeMs >= 0);
  assert.equal(typeof body.message, 'string');
});

test('GET /api/v1/payments/admin/mib/health enforces RBAC', { skip: !canRun ? skipReason : false }, async () => {
  const userResponse = await fetch(`${baseUrl}/payments/admin/mib/health`, {
    headers: authHeaders(`contract-payment-user-rbac-mib-${Date.now()}`, 'USER'),
  });

  assert.equal(userResponse.status, 403);
});

test('GET /api/v1/payments/admin/mib/health returns probe details for ADMIN', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/payments/admin/mib/health`, {
    headers: authHeaders(`contract-payment-admin-mib-${Date.now()}`, 'ADMIN'),
  });

  assert.equal(response.status, 200);
  const body = await response.json();
  assert.equal(body.provider, 'MIB');
  assert.equal(body.mode, 'legacy-or-api');
  assert.equal(typeof body.configured, 'boolean');
  assert.equal(typeof body.reachable, 'boolean');
  assert.equal(typeof body.authenticated, 'boolean');
  assert.equal(typeof body.responseTimeMs, 'number');
  assert.ok(body.responseTimeMs >= 0);
  assert.equal(typeof body.message, 'string');
});

test('GET /api/v1/payments/admin/reconcile/status enforces RBAC', { skip: !canRun ? skipReason : false }, async () => {
  const userResponse = await fetch(`${baseUrl}/payments/admin/reconcile/status`, {
    headers: authHeaders(`contract-payment-user-rbac-reconcile-status-${Date.now()}`, 'USER'),
  });

  assert.equal(userResponse.status, 403);
});

test('GET /api/v1/payments/admin/reconcile/status returns scheduler snapshot for ADMIN', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/payments/admin/reconcile/status`, {
    headers: authHeaders(`contract-payment-admin-reconcile-status-${Date.now()}`, 'ADMIN'),
  });

  assert.equal(response.status, 200);
  const body = await response.json();
  assert.equal(typeof body.enabled, 'boolean');
  assert.equal(typeof body.running, 'boolean');
  assert.equal(typeof body.dryRun, 'boolean');
  assert.equal(['ALL', 'STRIPE', 'BML', 'MIB'].includes(body.provider), true);
  assert.equal(body.intervalMs === null || typeof body.intervalMs === 'number', true);
  assert.equal(body.initialDelayMs === null || typeof body.initialDelayMs === 'number', true);
  assert.equal(body.limit === null || typeof body.limit === 'number', true);
  assert.equal(body.lastRunStartedAt === null || typeof body.lastRunStartedAt === 'string', true);
  assert.equal(body.lastRunFinishedAt === null || typeof body.lastRunFinishedAt === 'string', true);
  assert.equal(body.lastRunDurationMs === null || typeof body.lastRunDurationMs === 'number', true);
  assert.equal(body.lastRunOutcome === null || body.lastRunOutcome === 'success' || body.lastRunOutcome === 'error', true);
  assert.equal(body.lastRunSummary === null || typeof body.lastRunSummary === 'object', true);
  assert.equal(body.lastRunError === null || typeof body.lastRunError === 'string', true);
});

test('GET /api/v1/payments/admin/reconcile/history enforces RBAC', { skip: !canRun ? skipReason : false }, async () => {
  const userResponse = await fetch(`${baseUrl}/payments/admin/reconcile/history`, {
    headers: authHeaders(`contract-payment-user-rbac-reconcile-history-${Date.now()}`, 'USER'),
  });

  assert.equal(userResponse.status, 403);
});

test('GET /api/v1/payments/admin/reconcile/history returns recent runs for ADMIN', { skip: !canRun ? skipReason : false }, async () => {
  const reconcileResponse = await fetch(`${baseUrl}/payments/admin/reconcile/pending`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-payment-admin-reconcile-history-${Date.now()}`, 'ADMIN'),
    },
    body: JSON.stringify({ provider: 'BML', limit: 5, dryRun: true }),
  });

  assert.equal(reconcileResponse.status, 201);

  const response = await fetch(`${baseUrl}/payments/admin/reconcile/history?limit=5`, {
    headers: authHeaders(`contract-payment-admin-reconcile-history-read-${Date.now()}`, 'ADMIN'),
  });

  assert.equal(response.status, 200);
  const body = await response.json();
  assert.equal(Array.isArray(body), true);
  assert.equal(body.length > 0, true);

  const item = body[0];
  assert.equal(typeof item.id, 'string');
  assert.equal(item.source === 'ADMIN' || item.source === 'SCHEDULER', true);
  assert.equal(['ALL', 'STRIPE', 'BML', 'MIB'].includes(item.providerFilter), true);
  assert.equal(typeof item.limitUsed, 'number');
  assert.equal(typeof item.dryRun, 'boolean');
  assert.equal(item.status === 'SUCCESS' || item.status === 'ERROR', true);
  assert.equal(typeof item.scanned, 'number');
  assert.equal(typeof item.reconciled, 'number');
  assert.equal(typeof item.succeeded, 'number');
  assert.equal(typeof item.failed, 'number');
  assert.equal(typeof item.unchanged, 'number');
  assert.equal(typeof item.skipped, 'number');
  assert.equal(typeof item.errors, 'number');
  assert.equal(typeof item.startedAt, 'string');
  assert.equal(item.finishedAt === null || typeof item.finishedAt === 'string', true);
  assert.equal(item.durationMs === null || typeof item.durationMs === 'number', true);
  assert.equal(item.errorMessage === null || typeof item.errorMessage === 'string', true);
});

test('GET /api/v1/payments/admin/reconcile/alerts enforces RBAC', { skip: !canRun ? skipReason : false }, async () => {
  const userResponse = await fetch(`${baseUrl}/payments/admin/reconcile/alerts`, {
    headers: authHeaders(`contract-payment-user-rbac-reconcile-alerts-${Date.now()}`, 'USER'),
  });

  assert.equal(userResponse.status, 403);
});

test('GET /api/v1/payments/admin/reconcile/alerts returns alert summary for ADMIN', { skip: !canRun ? skipReason : false }, async () => {
  const reconcileResponse = await fetch(`${baseUrl}/payments/admin/reconcile/pending`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-payment-admin-reconcile-alerts-seed-${Date.now()}`, 'ADMIN'),
    },
    body: JSON.stringify({ provider: 'BML', limit: 5, dryRun: true }),
  });

  assert.equal(reconcileResponse.status, 201);

  const response = await fetch(`${baseUrl}/payments/admin/reconcile/alerts`, {
    headers: authHeaders(`contract-payment-admin-reconcile-alerts-${Date.now()}`, 'ADMIN'),
  });

  assert.equal(response.status, 200);
  const body = await response.json();
  assert.equal(body.status === 'OK' || body.status === 'WARN', true);
  assert.equal(typeof body.generatedAt, 'string');
  assert.equal(typeof body.config, 'object');
  assert.equal(typeof body.config.enabled, 'boolean');
  assert.equal(body.config.intervalMs === null || typeof body.config.intervalMs === 'number', true);
  assert.equal(typeof body.config.staleMultiplier, 'number');
  assert.equal(body.config.staleThresholdMs === null || typeof body.config.staleThresholdMs === 'number', true);
  assert.equal(typeof body.config.errorStreakThreshold, 'number');
  assert.equal(typeof body.config.errorCountThreshold, 'number');

  assert.equal(typeof body.checks, 'object');
  assert.equal(typeof body.checks.staleSuccess.active, 'boolean');
  assert.equal(body.checks.staleSuccess.lastSuccessAt === null || typeof body.checks.staleSuccess.lastSuccessAt === 'string', true);
  assert.equal(body.checks.staleSuccess.lastSuccessAgeMs === null || typeof body.checks.staleSuccess.lastSuccessAgeMs === 'number', true);
  assert.equal(typeof body.checks.errorStreak.active, 'boolean');
  assert.equal(typeof body.checks.errorStreak.consecutiveErrorRuns, 'number');
  assert.equal(typeof body.checks.highErrorsLastRun.active, 'boolean');
  assert.equal(body.checks.highErrorsLastRun.lastRunErrors === null || typeof body.checks.highErrorsLastRun.lastRunErrors === 'number', true);
  assert.equal(body.checks.highErrorsLastRun.lastRunAt === null || typeof body.checks.highErrorsLastRun.lastRunAt === 'string', true);
  assert.equal(body.checks.highErrorsLastRun.lastRunAgeMs === null || typeof body.checks.highErrorsLastRun.lastRunAgeMs === 'number', true);

  assert.equal(Array.isArray(body.activeAlerts), true);
});

test('GET /api/v1/payments/admin/alerts enforces RBAC', { skip: !canRun ? skipReason : false }, async () => {
  const userResponse = await fetch(`${baseUrl}/payments/admin/alerts`, {
    headers: authHeaders(`contract-payment-user-rbac-operational-alerts-${Date.now()}`, 'USER'),
  });

  assert.equal(userResponse.status, 403);
});

test('GET /api/v1/payments/admin/alerts returns combined dispatcher output for ADMIN', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  await prisma.paymentBackgroundJob.create({
    data: {
      type: 'WEBHOOK_PROCESS_RETRY',
      dedupeKey: `ops-alert-dead-${now}`,
      status: 'DEAD',
      attempts: 5,
      maxAttempts: 5,
      runAt: new Date(),
      processedAt: new Date(),
      payload: {
        provider: 'BML',
        eventRecordId: `ops-alert-dead-${now}`,
        event: { id: `ops-alert-dead-${now}`, type: 'payment_intent.succeeded' },
      },
    },
  });

  const response = await fetch(`${baseUrl}/payments/admin/alerts`, {
    headers: authHeaders(`contract-payment-admin-operational-alerts-${Date.now()}`, 'ADMIN'),
  });

  assert.equal(response.status, 200);
  const body = await response.json();
  assert.equal(body.status === 'OK' || body.status === 'WARN', true);
  assert.equal(typeof body.generatedAt, 'string');
  assert.equal(typeof body.config, 'object');
  assert.equal(typeof body.config.jobsPendingAgeThresholdMs, 'number');
  assert.equal(typeof body.config.jobsDeadCountThreshold, 'number');
  assert.equal(typeof body.config.jobsDeadLetterRateThreshold, 'number');
  assert.equal(typeof body.config.jobsStalledTickThresholdMs, 'number');

  assert.equal(typeof body.checks, 'object');
  assert.equal(typeof body.checks.reconciliation, 'object');
  assert.equal(typeof body.checks.jobs, 'object');
  assert.equal(typeof body.checks.jobs.pendingAgeHigh.active, 'boolean');
  assert.equal(typeof body.checks.jobs.deadCountHigh.active, 'boolean');
  assert.equal(typeof body.checks.jobs.deadLetterRateHigh.active, 'boolean');
  assert.equal(typeof body.checks.jobs.runnerStalled.active, 'boolean');
  assert.equal(typeof body.checks.jobs.runnerError.active, 'boolean');

  assert.equal(Array.isArray(body.activeAlerts), true);
  if (body.activeAlerts.length > 0) {
    const alert = body.activeAlerts[0];
    assert.equal(typeof alert.key, 'string');
    assert.equal(alert.source === 'RECONCILIATION' || alert.source === 'JOBS', true);
    assert.equal(alert.severity, 'WARN');
    assert.equal(typeof alert.message, 'string');
  }
});

test('GET /api/v1/payments/admin/jobs/health enforces RBAC', { skip: !canRun ? skipReason : false }, async () => {
  const userResponse = await fetch(`${baseUrl}/payments/admin/jobs/health`, {
    headers: authHeaders(`contract-payment-user-rbac-jobs-health-${Date.now()}`, 'USER'),
  });

  assert.equal(userResponse.status, 403);
});

test('GET /api/v1/payments/admin/jobs/health returns queue and runner snapshots for ADMIN', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/payments/admin/jobs/health?recentFailuresLimit=5`, {
    headers: authHeaders(`contract-payment-admin-jobs-health-${Date.now()}`, 'ADMIN'),
  });

  assert.equal(response.status, 200);
  const body = await response.json();

  assert.equal(typeof body.queue, 'object');
  assert.equal(typeof body.queue.generatedAt, 'string');
  assert.equal(typeof body.queue.counts, 'object');
  assert.equal(typeof body.queue.counts.pending, 'number');
  assert.equal(typeof body.queue.counts.retryable, 'number');
  assert.equal(typeof body.queue.counts.running, 'number');
  assert.equal(typeof body.queue.counts.completed, 'number');
  assert.equal(typeof body.queue.counts.dead, 'number');
  assert.equal(typeof body.queue.counts.total, 'number');
  assert.equal(typeof body.queue.metrics, 'object');
  assert.equal(body.queue.metrics.oldestPendingAgeMs === null || typeof body.queue.metrics.oldestPendingAgeMs === 'number', true);
  assert.equal(body.queue.metrics.oldestPendingAt === null || typeof body.queue.metrics.oldestPendingAt === 'string', true);
  assert.equal(body.queue.metrics.retrySuccessRate === null || typeof body.queue.metrics.retrySuccessRate === 'number', true);
  assert.equal(body.queue.metrics.deadLetterRate === null || typeof body.queue.metrics.deadLetterRate === 'number', true);
  assert.equal(body.queue.nextDueAt === null || typeof body.queue.nextDueAt === 'string', true);
  assert.equal(Array.isArray(body.queue.recentFailures), true);

  assert.equal(typeof body.runner, 'object');
  assert.equal(typeof body.runner.enabled, 'boolean');
  assert.equal(typeof body.runner.running, 'boolean');
  assert.equal(typeof body.runner.intervalMs, 'number');
  assert.equal(typeof body.runner.initialDelayMs, 'number');
  assert.equal(typeof body.runner.batchSize, 'number');
  assert.equal(body.runner.lastTickStartedAt === null || typeof body.runner.lastTickStartedAt === 'string', true);
  assert.equal(body.runner.lastTickFinishedAt === null || typeof body.runner.lastTickFinishedAt === 'string', true);
  assert.equal(body.runner.lastTickDurationMs === null || typeof body.runner.lastTickDurationMs === 'number', true);
  assert.equal(body.runner.lastTickProcessed === null || typeof body.runner.lastTickProcessed === 'number', true);
  assert.equal(body.runner.lastTickError === null || typeof body.runner.lastTickError === 'string', true);
  assert.equal(typeof body.runner.autoPruneEnabled, 'boolean');
  assert.equal(typeof body.runner.retentionHours, 'number');
  assert.equal(typeof body.runner.pruneLimit, 'number');
  assert.equal(body.runner.lastPruneAt === null || typeof body.runner.lastPruneAt === 'string', true);
  assert.equal(body.runner.lastPruned === null || typeof body.runner.lastPruned === 'number', true);
  assert.equal(body.runner.lastPruneError === null || typeof body.runner.lastPruneError === 'string', true);
});

test('GET /api/v1/payments/admin/jobs/health allows ADMIN_CARE read access', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/payments/admin/jobs/health`, {
    headers: authHeaders(`contract-payment-admin-care-jobs-health-${Date.now()}`, 'ADMIN_CARE'),
  });

  assert.equal(response.status, 200);
});

test('GET /api/v1/payments/admin/jobs enforces RBAC', { skip: !canRun ? skipReason : false }, async () => {
  const userResponse = await fetch(`${baseUrl}/payments/admin/jobs`, {
    headers: authHeaders(`contract-payment-user-rbac-jobs-list-${Date.now()}`, 'USER'),
  });

  assert.equal(userResponse.status, 403);
});

test('GET /api/v1/payments/admin/jobs lists jobs for ADMIN', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  await prisma.paymentBackgroundJob.create({
    data: {
      type: 'WEBHOOK_PROCESS_RETRY',
      dedupeKey: `jobs-list-${now}`,
      status: 'DEAD',
      attempts: 5,
      maxAttempts: 5,
      runAt: new Date(),
      processedAt: new Date(),
      payload: {
        provider: 'BML',
        eventRecordId: `jobs-list-${now}`,
        event: { id: `jobs-list-${now}`, type: 'payment_intent.succeeded' },
      },
    },
  });

  const response = await fetch(`${baseUrl}/payments/admin/jobs?status=DEAD&limit=10&offset=0`, {
    headers: authHeaders(`contract-payment-admin-jobs-list-${Date.now()}`, 'ADMIN'),
  });

  assert.equal(response.status, 200);
  const body = await response.json();
  assert.equal(typeof body.filters, 'object');
  assert.equal(body.filters.status, 'DEAD');
  assert.equal(typeof body.page, 'object');
  assert.equal(typeof body.page.total, 'number');
  assert.equal(Array.isArray(body.items), true);
  assert.equal(body.items.some((item) => item.dedupeKey === `jobs-list-${now}`), true);
});

test('POST /api/v1/payments/admin/jobs/:id/requeue enforces RBAC', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const job = await prisma.paymentBackgroundJob.create({
    data: {
      type: 'WEBHOOK_PROCESS_RETRY',
      dedupeKey: `requeue-rbac-${now}`,
      status: 'DEAD',
      attempts: 5,
      maxAttempts: 5,
      runAt: new Date(),
      processedAt: new Date(),
      payload: {
        provider: 'BML',
        eventRecordId: `requeue-rbac-${now}`,
        event: { id: `requeue-rbac-${now}`, type: 'payment_intent.succeeded' },
      },
    },
  });

  const userResponse = await fetch(`${baseUrl}/payments/admin/jobs/${job.id}/requeue`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-payment-user-rbac-jobs-requeue-${Date.now()}`, 'USER'),
    },
    body: JSON.stringify({ delaySeconds: 1 }),
  });

  assert.equal(userResponse.status, 403);
});

test('POST /api/v1/payments/admin/jobs/:id/requeue is idempotent for ADMIN', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const job = await prisma.paymentBackgroundJob.create({
    data: {
      type: 'WEBHOOK_PROCESS_RETRY',
      dedupeKey: `requeue-ok-${now}`,
      status: 'DEAD',
      attempts: 5,
      maxAttempts: 5,
      runAt: new Date(),
      processedAt: new Date(),
      payload: {
        provider: 'BML',
        eventRecordId: `requeue-ok-${now}`,
        event: { id: `requeue-ok-${now}`, type: 'payment_intent.succeeded' },
      },
    },
  });

  const first = await fetch(`${baseUrl}/payments/admin/jobs/${job.id}/requeue`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-payment-admin-jobs-requeue-${Date.now()}`, 'ADMIN'),
    },
    body: JSON.stringify({ delaySeconds: 0 }),
  });

  assert.equal(first.status, 201);
  const firstBody = await first.json();
  assert.equal(firstBody.changed, true);
  assert.equal(firstBody.status, 'PENDING');

  const second = await fetch(`${baseUrl}/payments/admin/jobs/${job.id}/requeue`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-payment-admin-jobs-requeue-second-${Date.now()}`, 'ADMIN'),
    },
    body: JSON.stringify({ delaySeconds: 0 }),
  });

  assert.equal(second.status, 201);
  const secondBody = await second.json();
  assert.equal(secondBody.changed, false);
});

test('POST /api/v1/payments/admin/jobs/:id/cancel and /complete support ADMIN actions idempotently', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();

  const cancellable = await prisma.paymentBackgroundJob.create({
    data: {
      type: 'WEBHOOK_PROCESS_RETRY',
      dedupeKey: `cancel-ok-${now}`,
      status: 'RETRYABLE',
      attempts: 2,
      maxAttempts: 5,
      runAt: new Date(),
      payload: {
        provider: 'BML',
        eventRecordId: `cancel-ok-${now}`,
        event: { id: `cancel-ok-${now}`, type: 'payment_intent.succeeded' },
      },
    },
  });

  const cancelFirst = await fetch(`${baseUrl}/payments/admin/jobs/${cancellable.id}/cancel`, {
    method: 'POST',
    headers: authHeaders(`contract-payment-admin-jobs-cancel-${Date.now()}`, 'ADMIN'),
  });
  assert.equal(cancelFirst.status, 201);
  const cancelFirstBody = await cancelFirst.json();
  assert.equal(cancelFirstBody.changed, true);
  assert.equal(cancelFirstBody.status, 'CANCELLED');

  const cancelSecond = await fetch(`${baseUrl}/payments/admin/jobs/${cancellable.id}/cancel`, {
    method: 'POST',
    headers: authHeaders(`contract-payment-admin-jobs-cancel-second-${Date.now()}`, 'ADMIN'),
  });
  assert.equal(cancelSecond.status, 201);
  const cancelSecondBody = await cancelSecond.json();
  assert.equal(cancelSecondBody.changed, false);

  const completable = await prisma.paymentBackgroundJob.create({
    data: {
      type: 'WEBHOOK_PROCESS_RETRY',
      dedupeKey: `complete-ok-${now}`,
      status: 'DEAD',
      attempts: 5,
      maxAttempts: 5,
      runAt: new Date(),
      payload: {
        provider: 'BML',
        eventRecordId: `complete-ok-${now}`,
        event: { id: `complete-ok-${now}`, type: 'payment_intent.succeeded' },
      },
    },
  });

  const completeFirst = await fetch(`${baseUrl}/payments/admin/jobs/${completable.id}/complete`, {
    method: 'POST',
    headers: authHeaders(`contract-payment-admin-jobs-complete-${Date.now()}`, 'ADMIN'),
  });
  assert.equal(completeFirst.status, 201);
  const completeFirstBody = await completeFirst.json();
  assert.equal(completeFirstBody.changed, true);
  assert.equal(completeFirstBody.status, 'COMPLETED');

  const completeSecond = await fetch(`${baseUrl}/payments/admin/jobs/${completable.id}/complete`, {
    method: 'POST',
    headers: authHeaders(`contract-payment-admin-jobs-complete-second-${Date.now()}`, 'ADMIN'),
  });
  assert.equal(completeSecond.status, 201);
  const completeSecondBody = await completeSecond.json();
  assert.equal(completeSecondBody.changed, false);
});

test('POST /api/v1/payments/admin/jobs/prune enforces RBAC', { skip: !canRun ? skipReason : false }, async () => {
  const userResponse = await fetch(`${baseUrl}/payments/admin/jobs/prune`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-payment-user-rbac-jobs-prune-${Date.now()}`, 'USER'),
    },
    body: JSON.stringify({ olderThanHours: 24, limit: 10 }),
  });

  assert.equal(userResponse.status, 403);
});

test('POST /api/v1/payments/admin/jobs/prune prunes old completed jobs for ADMIN', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const oldProcessedAt = new Date(now - 72 * 60 * 60 * 1000);
  const freshProcessedAt = new Date(now - 1 * 60 * 60 * 1000);

  const oldJob = await prisma.paymentBackgroundJob.create({
    data: {
      type: 'BOOKING_CONFIRMATION_NOTIFICATION',
      dedupeKey: `prune-old-${now}`,
      status: 'COMPLETED',
      attempts: 1,
      maxAttempts: 5,
      runAt: oldProcessedAt,
      processedAt: oldProcessedAt,
      payload: {
        bookingId: `contract-payment-booking-prune-old-${now}`,
        paymentId: `contract-payment-payment-prune-old-${now}`,
        source: 'WEBHOOK',
      },
    },
  });

  const freshJob = await prisma.paymentBackgroundJob.create({
    data: {
      type: 'BOOKING_CONFIRMATION_NOTIFICATION',
      dedupeKey: `prune-fresh-${now}`,
      status: 'COMPLETED',
      attempts: 1,
      maxAttempts: 5,
      runAt: freshProcessedAt,
      processedAt: freshProcessedAt,
      payload: {
        bookingId: `contract-payment-booking-prune-fresh-${now}`,
        paymentId: `contract-payment-payment-prune-fresh-${now}`,
        source: 'WEBHOOK',
      },
    },
  });

  const response = await fetch(`${baseUrl}/payments/admin/jobs/prune`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-payment-admin-jobs-prune-${Date.now()}`, 'ADMIN'),
    },
    body: JSON.stringify({ olderThanHours: 24, limit: 50 }),
  });

  assert.equal(response.status, 201);
  const body = await response.json();
  assert.equal(typeof body.pruned, 'number');
  assert.equal(body.pruned >= 1, true);
  assert.equal(body.olderThanHours, 24);

  const oldAfter = await prisma.paymentBackgroundJob.findUnique({ where: { id: oldJob.id } });
  const freshAfter = await prisma.paymentBackgroundJob.findUnique({ where: { id: freshJob.id } });

  assert.equal(oldAfter, null);
  assert.notEqual(freshAfter, null);
});

test('POST /api/v1/payments/admin/reconcile/run-now enforces RBAC', { skip: !canRun ? skipReason : false }, async () => {
  const userResponse = await fetch(`${baseUrl}/payments/admin/reconcile/run-now`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-payment-user-rbac-reconcile-run-now-${Date.now()}`, 'USER'),
    },
    body: JSON.stringify({ provider: 'BML', limit: 5, dryRun: true }),
  });

  assert.equal(userResponse.status, 403);

  const careResponse = await fetch(`${baseUrl}/payments/admin/reconcile/run-now`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-payment-admin-care-rbac-reconcile-run-now-${Date.now()}`, 'ADMIN_CARE'),
    },
    body: JSON.stringify({ provider: 'BML', limit: 5, dryRun: true }),
  });

  assert.equal(careResponse.status, 403);
});

test('POST /api/v1/payments/admin/reconcile/run-now allows ADMIN_FINANCE', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/payments/admin/reconcile/run-now`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-payment-admin-finance-reconcile-run-now-${Date.now()}`, 'ADMIN_FINANCE'),
    },
    body: JSON.stringify({ provider: 'BML', limit: 10, dryRun: true }),
  });

  assert.equal(response.status, 201);
  const body = await response.json();
  assert.equal(body.providerFilter, 'BML');
  assert.equal(body.dryRun, true);
});

test('POST /api/v1/payments/admin/reconcile/run-now returns reconciliation summary for ADMIN', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/payments/admin/reconcile/run-now`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-payment-admin-reconcile-run-now-${Date.now()}`, 'ADMIN'),
    },
    body: JSON.stringify({ provider: 'BML', limit: 10, dryRun: true }),
  });

  assert.equal(response.status, 201);
  const body = await response.json();
  assert.equal(body.providerFilter, 'BML');
  assert.equal(body.dryRun, true);
  assert.equal(typeof body.scanned, 'number');
  assert.equal(typeof body.reconciled, 'number');
  assert.equal(typeof body.succeeded, 'number');
  assert.equal(typeof body.failed, 'number');
  assert.equal(typeof body.unchanged, 'number');
  assert.equal(typeof body.skipped, 'number');
  assert.equal(typeof body.errors, 'number');
  assert.equal(Array.isArray(body.details), true);
});

test('POST /api/v1/payments/admin/reconcile/run-now handles concurrent requests safely', { skip: !canRun ? skipReason : false }, async () => {
  const overlapSeed = Array.from({ length: 120 }, (_, index) => index);
  const seedTimestamp = Date.now();
  for (const index of overlapSeed) {
    const bookingId = `contract-payment-booking-overlap-${seedTimestamp}-${index}`;
    await prisma.booking.create({
      data: {
        id: bookingId,
        userId: fixtureUser.id,
        guests: 1,
        totalPrice: 120 + index,
        status: 'PENDING',
      },
    });

    await prisma.payment.create({
      data: {
        bookingId,
        provider: 'BML',
        providerId: `bml_overlap_${seedTimestamp}_${index}`,
        amount: 120 + index,
        currency: 'MVR',
        status: 'PENDING',
      },
    });
  }

  const headers = {
    'Content-Type': 'application/json',
    ...authHeaders(`contract-payment-admin-reconcile-run-now-overlap-${Date.now()}`, 'ADMIN'),
  };

  const [first, second] = await Promise.all([
    fetch(`${baseUrl}/payments/admin/reconcile/run-now`, {
      method: 'POST',
      headers,
      body: JSON.stringify({ provider: 'BML', limit: 100, dryRun: true }),
    }),
    fetch(`${baseUrl}/payments/admin/reconcile/run-now`, {
      method: 'POST',
      headers,
      body: JSON.stringify({ provider: 'BML', limit: 100, dryRun: true }),
    }),
  ]);

  const statuses = [first.status, second.status].sort((a, b) => a - b);
  assert.equal(statuses[0], 201);
  assert.equal(statuses[1] === 201 || statuses[1] === 409, true);
});

test('POST /api/v1/payments/admin/reconcile/pending enforces RBAC', { skip: !canRun ? skipReason : false }, async () => {
  const userResponse = await fetch(`${baseUrl}/payments/admin/reconcile/pending`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-payment-user-reconcile-${Date.now()}`, 'USER'),
    },
    body: JSON.stringify({ provider: 'BML', limit: 10, dryRun: true }),
  });

  assert.equal(userResponse.status, 403);
});

test('POST /api/v1/payments/admin/reconcile/pending returns reconciliation summary for ADMIN', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/payments/admin/reconcile/pending`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(`contract-payment-admin-reconcile-${Date.now()}`, 'ADMIN'),
    },
    body: JSON.stringify({ provider: 'BML', limit: 25, dryRun: true }),
  });

  assert.equal(response.status, 201);
  const body = await response.json();
  assert.equal(body.providerFilter, 'BML');
  assert.equal(body.dryRun, true);
  assert.equal(typeof body.scanned, 'number');
  assert.equal(typeof body.reconciled, 'number');
  assert.equal(typeof body.succeeded, 'number');
  assert.equal(typeof body.failed, 'number');
  assert.equal(typeof body.unchanged, 'number');
  assert.equal(typeof body.skipped, 'number');
  assert.equal(typeof body.errors, 'number');
  assert.equal(Array.isArray(body.details), true);
});

test('POST /api/v1/payments/intents requires auth', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/payments/intents`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ bookingId: fixtureBooking.id }),
  });

  assert.equal(response.status, 401);
});

test('POST /api/v1/payments/intents rejects unsupported currency', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/payments/intents`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(fixtureUser.id, 'USER', fixtureUser.email),
    },
    body: JSON.stringify({ bookingId: fixtureBooking.id, currency: 'EUR' }),
  });

  assert.equal(response.status, 400);
});

test('POST /api/v1/payments/intents creates intent for booking owner', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/payments/intents`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(fixtureUser.id, 'USER', fixtureUser.email),
    },
    body: JSON.stringify({ bookingId: fixtureBooking.id }),
  });

  assert.equal(response.status, 201);
  const body = await response.json();
  assert.equal(body.payment.bookingId, fixtureBooking.id);
  assert.equal(body.payment.provider, 'STRIPE');
  assert.equal(typeof body.payment.providerId, 'string');
  assert.equal(typeof body.clientSecret, 'string');
});

test('POST /api/v1/payments/intents supports BML and MIB providers', { skip: !canRun ? skipReason : false }, async () => {
  const now = Date.now();
  const bmlBooking = await prisma.booking.create({
    data: {
      id: `contract-payment-booking-bml-${now}`,
      userId: fixtureUser.id,
      guests: 1,
      totalPrice: 300,
      status: 'PENDING',
    },
  });

  const mibBooking = await prisma.booking.create({
    data: {
      id: `contract-payment-booking-mib-${now}`,
      userId: fixtureUser.id,
      guests: 1,
      totalPrice: 325,
      status: 'PENDING',
    },
  });

  const bmlResponse = await fetch(`${baseUrl}/payments/intents`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(fixtureUser.id, 'USER', fixtureUser.email),
    },
    body: JSON.stringify({ bookingId: bmlBooking.id, provider: 'BML', currency: 'MVR' }),
  });

  assert.equal(bmlResponse.status, 201);
  const bmlBody = await bmlResponse.json();
  assert.equal(bmlBody.payment.provider, 'BML');
  assert.equal(bmlBody.payment.currency, 'MVR');
  assert.equal(typeof bmlBody.payment.providerId, 'string');

  const mibResponse = await fetch(`${baseUrl}/payments/intents`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(fixtureUser.id, 'USER', fixtureUser.email),
    },
    body: JSON.stringify({ bookingId: mibBooking.id, provider: 'MIB', currency: 'USD' }),
  });

  assert.equal(mibResponse.status, 201);
  const mibBody = await mibResponse.json();
  assert.equal(mibBody.payment.provider, 'MIB');
  assert.equal(mibBody.payment.currency, 'USD');
  assert.equal(typeof mibBody.payment.providerId, 'string');
});

test('POST /api/v1/payments/webhooks/stripe validates signature', { skip: !canRun ? skipReason : false }, async () => {
  const response = await fetch(`${baseUrl}/payments/webhooks/stripe`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'x-webhook-signature': 'wrong-secret' },
    body: JSON.stringify({ id: 'evt_contract_invalid_sig', type: 'payment_intent.succeeded', data: { object: {} } }),
  });

  assert.equal(response.status, 401);
});

test('POST /api/v1/payments/webhooks/stripe processes success and is idempotent', { skip: !canRun ? skipReason : false }, async () => {
  const payment = await prisma.payment.findUnique({ where: { bookingId: fixtureBooking.id } });
  assert.ok(payment?.providerId);

  const eventId = `evt_contract_${Date.now()}`;
  const payload = {
    id: eventId,
    type: 'payment_intent.succeeded',
    data: {
      object: {
        payment_intent: payment.providerId,
        bookingId: fixtureBooking.id,
      },
    },
  };

  const firstResponse = await fetch(`${baseUrl}/payments/webhooks/stripe`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'x-webhook-signature': 'dev-webhook-secret',
    },
    body: JSON.stringify(payload),
  });

  assert.equal(firstResponse.status, 200);
  const firstBody = await firstResponse.json();
  assert.equal(firstBody.received, true);
  assert.equal(firstBody.idempotent, false);

  const updatedPayment = await prisma.payment.findUnique({ where: { bookingId: fixtureBooking.id } });
  assert.equal(updatedPayment?.status, 'SUCCEEDED');

  const updatedBooking = await prisma.booking.findUnique({ where: { id: fixtureBooking.id } });
  assert.equal(updatedBooking?.status, 'CONFIRMED');

  const secondResponse = await fetch(`${baseUrl}/payments/webhooks/stripe`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'x-webhook-signature': 'dev-webhook-secret',
    },
    body: JSON.stringify(payload),
  });

  assert.equal(secondResponse.status, 200);
  const secondBody = await secondResponse.json();
  assert.equal(secondBody.received, true);
  assert.equal(secondBody.idempotent, true);
});

test('POST /api/v1/payments/webhooks/stripe deduplicates booking confirmation jobs per booking', { skip: !canRun ? skipReason : false }, async () => {
  const booking = await prisma.booking.create({
    data: {
      id: `contract-payment-booking-dedupe-${Date.now()}`,
      userId: fixtureUser.id,
      guests: 1,
      totalPrice: 280,
      status: 'PENDING',
    },
  });

  const intentResponse = await fetch(`${baseUrl}/payments/intents`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders(fixtureUser.id, 'USER', fixtureUser.email),
    },
    body: JSON.stringify({ bookingId: booking.id, provider: 'STRIPE', currency: 'USD' }),
  });

  assert.equal(intentResponse.status, 201);
  const intentBody = await intentResponse.json();
  const providerId = intentBody.payment?.providerId;
  assert.equal(typeof providerId, 'string');

  const firstEvent = await fetch(`${baseUrl}/payments/webhooks/stripe`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'x-webhook-signature': 'dev-webhook-secret',
    },
    body: JSON.stringify({
      id: `evt_contract_dedupe_a_${Date.now()}`,
      type: 'payment_intent.succeeded',
      data: { object: { payment_intent: providerId, bookingId: booking.id } },
    }),
  });

  assert.equal(firstEvent.status, 200);

  const secondEvent = await fetch(`${baseUrl}/payments/webhooks/stripe`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'x-webhook-signature': 'dev-webhook-secret',
    },
    body: JSON.stringify({
      id: `evt_contract_dedupe_b_${Date.now()}`,
      type: 'checkout.session.completed',
      data: { object: { payment_intent: providerId, bookingId: booking.id } },
    }),
  });

  assert.equal(secondEvent.status, 200);

  const confirmationJobs = await prisma.paymentBackgroundJob.findMany({
    where: {
      type: 'BOOKING_CONFIRMATION_NOTIFICATION',
      dedupeKey: booking.id,
    },
  });

  assert.equal(confirmationJobs.length, 1);
});

test('Payment background retry jobs enforce unique dedupe key per type', { skip: !canRun ? skipReason : false }, async () => {
  const dedupeKey = `BML:evt_bml_retry_dedupe_${Date.now()}`;

  await prisma.paymentBackgroundJob.create({
    data: {
      type: 'WEBHOOK_PROCESS_RETRY',
      dedupeKey,
      status: 'PENDING',
      attempts: 0,
      maxAttempts: 5,
      runAt: new Date(),
      payload: {
        provider: 'BML',
        eventRecordId: `evt_record_${Date.now()}`,
        event: {
          id: dedupeKey,
          type: 'payment_intent.succeeded',
        },
        errorMessage: 'contract dedupe test',
      },
    },
  });

  await assert.rejects(
    () =>
      prisma.paymentBackgroundJob.create({
        data: {
          type: 'WEBHOOK_PROCESS_RETRY',
          dedupeKey,
          status: 'PENDING',
          attempts: 0,
          maxAttempts: 5,
          runAt: new Date(),
          payload: {
            provider: 'BML',
            eventRecordId: `evt_record_dup_${Date.now()}`,
            event: {
              id: dedupeKey,
              type: 'payment_intent.succeeded',
            },
            errorMessage: 'contract duplicate',
          },
        },
      }),
  );
});

test('POST /api/v1/payments/webhooks/bml and /mib accept valid signatures', { skip: !canRun ? skipReason : false }, async () => {
  const nonce = `nonce_${Date.now()}`;
  const timestamp = String(Math.floor(Date.now() / 1000));
  const bmlSecret = process.env.BML_API_KEY ?? process.env.BML_WEBHOOK_SECRET ?? 'dev-bml-webhook-secret';
  const bmlSignature = createHash('sha256').update(`${nonce}${timestamp}${bmlSecret}`).digest('hex');

  const bmlResponse = await fetch(`${baseUrl}/payments/webhooks/bml`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'x-signature-nonce': nonce,
      'x-signature-timestamp': timestamp,
      'x-signature': bmlSignature,
      'x-originator': 'PomeloPay-Webhooks',
    },
    body: JSON.stringify({
      eventId: `evt_bml_${Date.now()}`,
      eventType: 'payment_intent.payment_failed',
      data: { transaction: {} },
    }),
  });

  assert.equal(bmlResponse.status, 200);
  const bmlBody = await bmlResponse.json();
  assert.equal(bmlBody.received, true);

  const mibResponse = await fetch(`${baseUrl}/payments/webhooks/mib`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'x-webhook-signature': 'dev-mib-webhook-secret',
    },
    body: JSON.stringify({
      id: `evt_mib_${Date.now()}`,
      kind: 'payment_intent.payment_failed',
      payment: {},
    }),
  });

  assert.equal(mibResponse.status, 200);
  const mibBody = await mibResponse.json();
  assert.equal(mibBody.received, true);
});
