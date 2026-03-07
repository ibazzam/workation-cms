import { BadRequestException, ForbiddenException, Inject, Injectable, NotFoundException, UnauthorizedException } from '@nestjs/common';
import { Prisma } from '@prisma/client';
import { LoyaltyService } from '../loyalty/loyalty.service';
import { PrismaService } from '../prisma.service';
import { PaymentProviderAdapter, ProviderTransactionStatus } from './adapters/payment-provider.interface';
import { PAYMENT_PROVIDER_BML, PAYMENT_PROVIDER_MIB, PAYMENT_PROVIDER_STRIPE } from './adapters/payment-provider.tokens';

type CreateIntentPayload = {
  bookingId?: unknown;
  provider?: unknown;
  currency?: unknown;
};

type ReconcilePayload = {
  provider?: unknown;
  limit?: unknown;
  dryRun?: unknown;
};

type ReconcileSource = 'ADMIN' | 'SCHEDULER';

type ProviderName = 'STRIPE' | 'BML' | 'MIB';
type BackgroundJobType = 'BOOKING_CONFIRMATION_NOTIFICATION' | 'WEBHOOK_PROCESS_RETRY';

type BackgroundJobsRunnerSnapshot = {
  enabled: boolean;
  running: boolean;
  intervalMs: number;
  lastTickFinishedAt: string | null;
  lastTickError: string | null;
  lastPruneError: string | null;
};

@Injectable()
export class PaymentsService {
  constructor(
    private readonly prisma: PrismaService,
    private readonly loyaltyService: LoyaltyService,
    @Inject(PAYMENT_PROVIDER_STRIPE) private readonly stripeAdapter: PaymentProviderAdapter,
    @Inject(PAYMENT_PROVIDER_BML) private readonly bmlAdapter: PaymentProviderAdapter,
    @Inject(PAYMENT_PROVIDER_MIB) private readonly mibAdapter: PaymentProviderAdapter,
  ) {}

  async createIntentForUser(userId: string, payload: CreateIntentPayload) {
    const bookingId = typeof payload.bookingId === 'string' ? payload.bookingId : undefined;
    if (!bookingId) {
      throw new BadRequestException('bookingId is required');
    }

    const provider = this.parseProvider(payload.provider);
    if (!provider) {
      throw new BadRequestException('Unsupported provider. Allowed providers: STRIPE, BML, MIB');
    }

    const currency = this.parseCurrency(payload.currency);
    if (!currency) {
      throw new BadRequestException('Unsupported currency. Allowed currencies: USD, MVR');
    }

    const booking = await this.prisma.booking.findUnique({ where: { id: bookingId } });
    if (!booking) {
      throw new NotFoundException('Booking not found');
    }

    if (booking.userId !== userId) {
      throw new ForbiddenException('Booking does not belong to authenticated user');
    }

    if (booking.status === 'DRAFT') {
      throw new BadRequestException('Booking must be on HOLD before payment intent creation');
    }

    if (booking.status === 'CANCELLED' || booking.status === 'REFUNDED') {
      throw new BadRequestException(`Cannot create payment intent for booking status ${booking.status}`);
    }

    if (booking.status === 'HOLD' && booking.holdExpiresAt && booking.holdExpiresAt.getTime() < Date.now()) {
      await this.prisma.booking.update({
        where: { id: booking.id },
        data: {
          status: 'CANCELLED',
          holdExpiresAt: null,
        },
      });
      throw new BadRequestException('Booking hold has expired and was cancelled');
    }

    const existingPayment = await this.prisma.payment.findUnique({ where: { bookingId } });
    if (existingPayment && existingPayment.provider === provider) {
      return {
        created: false,
        payment: existingPayment,
      };
    }

    const adapter = this.getAdapter(provider);
    const intent = await adapter.createIntent({
      bookingId,
      amount: Number(booking.totalPrice),
      currency,
      metadata: { bookingId },
    });

    const payment = await this.prisma.payment.upsert({
      where: { bookingId },
      update: {
        provider,
        providerId: intent.providerIntentId,
        amount: booking.totalPrice,
        currency,
        status: intent.status,
      },
      create: {
        bookingId,
        provider,
        providerId: intent.providerIntentId,
        amount: booking.totalPrice,
        currency,
        status: intent.status,
      },
    });

    return {
      created: true,
      payment,
      clientSecret: intent.clientSecret,
    };
  }

  async processStripeWebhook(signature: string | undefined, payload: unknown) {
    return this.processWebhook('STRIPE', signature, payload);
  }

  async processBmlWebhook(signature: string | undefined, payload: unknown) {
    return this.processWebhook('BML', signature, payload);
  }

  async processMibWebhook(signature: string | undefined, payload: unknown) {
    return this.processWebhook('MIB', signature, payload);
  }

  async getBmlHealthReport() {
    if (!this.bmlAdapter.probeHealth) {
      return {
        provider: 'BML',
        mode: 'connect',
        configured: false,
        reachable: false,
        authenticated: false,
        message: 'BML health probe is not implemented for current adapter',
      };
    }

    const result = await this.bmlAdapter.probeHealth();
    return {
      provider: 'BML',
      mode: 'connect',
      ...result,
    };
  }

  async getMibHealthReport() {
    if (!this.mibAdapter.probeHealth) {
      return {
        provider: 'MIB',
        mode: 'legacy-or-api',
        configured: false,
        reachable: false,
        authenticated: false,
        responseTimeMs: 0,
        message: 'MIB health probe is not implemented for current adapter',
      };
    }

    const result = await this.mibAdapter.probeHealth();
    return {
      provider: 'MIB',
      mode: 'legacy-or-api',
      ...result,
    };
  }

  async reconcilePendingPayments(payload: ReconcilePayload, source: ReconcileSource = 'ADMIN') {
    const providerFilter = this.parseProviderFilter(payload.provider);
    if (payload.provider !== undefined && !providerFilter) {
      throw new BadRequestException('Unsupported provider filter. Allowed: STRIPE, BML, MIB');
    }

    const limit = this.parseLimit(payload.limit);
    if (payload.limit !== undefined && limit === null) {
      throw new BadRequestException('limit must be an integer between 1 and 500');
    }

    const dryRun = Boolean(payload.dryRun);
    const startedAt = new Date();

    const pendingStatuses = ['PENDING', 'REQUIRES_ACTION'];
    const payments = await this.prisma.payment.findMany({
      where: {
        ...(providerFilter ? { provider: providerFilter } : {}),
        status: { in: pendingStatuses },
      },
      orderBy: { createdAt: 'asc' },
      take: limit ?? 100,
    });

    const summary = {
      scanned: payments.length,
      providerFilter: providerFilter ?? 'ALL',
      dryRun,
      reconciled: 0,
      succeeded: 0,
      failed: 0,
      unchanged: 0,
      skipped: 0,
      errors: 0,
      details: [] as Array<{ paymentId: string; provider: string; result: string; note?: string }>,
    };

    try {
      for (const payment of payments) {
        const provider = this.parseProviderFilter(payment.provider);
        if (!provider) {
          summary.skipped += 1;
          summary.details.push({ paymentId: payment.id, provider: payment.provider, result: 'skipped', note: 'unknown provider' });
          continue;
        }

        const adapter = this.getAdapter(provider);
        if (!adapter.fetchTransactionStatus) {
          summary.skipped += 1;
          summary.details.push({ paymentId: payment.id, provider, result: 'skipped', note: 'provider status fetch not supported' });
          continue;
        }

        if (!payment.providerId) {
          summary.skipped += 1;
          summary.details.push({ paymentId: payment.id, provider, result: 'skipped', note: 'missing provider reference' });
          continue;
        }

        try {
          const providerStatus = await this.fetchWithRetry(() => adapter.fetchTransactionStatus!(payment.providerId!), 2);
          if (!providerStatus || providerStatus.state === 'PENDING') {
            summary.unchanged += 1;
            summary.details.push({ paymentId: payment.id, provider, result: 'unchanged' });
            continue;
          }

          if (!dryRun) {
            if (providerStatus.state === 'SUCCEEDED') {
              await this.prisma.payment.update({ where: { id: payment.id }, data: { status: 'SUCCEEDED' } });
              await this.prisma.booking.update({ where: { id: payment.bookingId }, data: { status: 'CONFIRMED' } });
              await this.loyaltyService.awardPointsForConfirmedBooking(payment.bookingId, 'PAYMENT_RECONCILE');
              await this.enqueueBookingConfirmationJob(payment.bookingId, payment.id, 'RECONCILE');
            }

            if (providerStatus.state === 'FAILED') {
              await this.prisma.payment.update({ where: { id: payment.id }, data: { status: 'FAILED' } });
            }
          }

          summary.reconciled += 1;
          if (providerStatus.state === 'SUCCEEDED') {
            summary.succeeded += 1;
          }
          if (providerStatus.state === 'FAILED') {
            summary.failed += 1;
          }
          summary.details.push({ paymentId: payment.id, provider, result: providerStatus.state.toLowerCase() });
        } catch (error) {
          summary.errors += 1;
          summary.details.push({
            paymentId: payment.id,
            provider,
            result: 'error',
            note: error instanceof Error ? error.message : 'unknown error',
          });
        }
      }

      await this.prisma.paymentReconciliationRun.create({
        data: {
          source,
          providerFilter: summary.providerFilter,
          limitUsed: limit ?? 100,
          dryRun: summary.dryRun,
          status: 'SUCCESS',
          scanned: summary.scanned,
          reconciled: summary.reconciled,
          succeeded: summary.succeeded,
          failed: summary.failed,
          unchanged: summary.unchanged,
          skipped: summary.skipped,
          errors: summary.errors,
          startedAt,
          finishedAt: new Date(),
          durationMs: Date.now() - startedAt.getTime(),
        },
      });

      return summary;
    } catch (error) {
      await this.prisma.paymentReconciliationRun.create({
        data: {
          source,
          providerFilter: providerFilter ?? 'ALL',
          limitUsed: limit ?? 100,
          dryRun,
          status: 'ERROR',
          scanned: summary.scanned,
          reconciled: summary.reconciled,
          succeeded: summary.succeeded,
          failed: summary.failed,
          unchanged: summary.unchanged,
          skipped: summary.skipped,
          errors: summary.errors,
          errorMessage: error instanceof Error ? error.message : 'unknown error',
          startedAt,
          finishedAt: new Date(),
          durationMs: Date.now() - startedAt.getTime(),
        },
      });
      throw error;
    }
  }

  async getReconciliationRunHistory(payload: { limit?: unknown }) {
    const limit = this.parseHistoryLimit(payload.limit);
    if (payload.limit !== undefined && limit === null) {
      throw new BadRequestException('limit must be an integer between 1 and 100');
    }

    return this.prisma.paymentReconciliationRun.findMany({
      orderBy: { createdAt: 'desc' },
      take: limit ?? 20,
    });
  }

  async getReconciliationAlerts(payload: { enabled: boolean; intervalMs: number | null }) {
    const staleMultiplier = this.readPositiveIntEnv('PAYMENTS_RECONCILE_ALERT_STALE_MULTIPLIER', 2);
    const errorStreakThreshold = this.readPositiveIntEnv('PAYMENTS_RECONCILE_ALERT_ERROR_STREAK', 3);
    const errorCountThreshold = this.readPositiveIntEnv('PAYMENTS_RECONCILE_ALERT_ERRORS_THRESHOLD', 5);

    const [lastRun, lastSuccessRun, recentRuns] = await Promise.all([
      this.prisma.paymentReconciliationRun.findFirst({
        orderBy: { createdAt: 'desc' },
      }),
      this.prisma.paymentReconciliationRun.findFirst({
        where: { status: 'SUCCESS' },
        orderBy: { createdAt: 'desc' },
      }),
      this.prisma.paymentReconciliationRun.findMany({
        orderBy: { createdAt: 'desc' },
        take: errorStreakThreshold,
      }),
    ]);

    const nowMs = Date.now();
    const lastRunCompletedAt = lastRun?.finishedAt ?? lastRun?.createdAt ?? null;
    const lastSuccessCompletedAt = lastSuccessRun?.finishedAt ?? lastSuccessRun?.createdAt ?? null;

    const lastRunAgeMs = lastRunCompletedAt ? nowMs - lastRunCompletedAt.getTime() : null;
    const lastSuccessAgeMs = lastSuccessCompletedAt ? nowMs - lastSuccessCompletedAt.getTime() : null;

    const staleThresholdMs = payload.enabled && payload.intervalMs ? payload.intervalMs * staleMultiplier : null;
    const staleSuccess = staleThresholdMs !== null && (lastSuccessAgeMs === null || lastSuccessAgeMs > staleThresholdMs);

    let consecutiveErrorRuns = 0;
    for (const run of recentRuns) {
      if (run.status !== 'ERROR') {
        break;
      }
      consecutiveErrorRuns += 1;
    }

    const errorStreak = consecutiveErrorRuns >= errorStreakThreshold;
    const highErrorsLastRun = Boolean(lastRun && lastRun.errors >= errorCountThreshold);

    const activeAlertKeys = [
      staleSuccess ? 'staleSuccess' : null,
      errorStreak ? 'errorStreak' : null,
      highErrorsLastRun ? 'highErrorsLastRun' : null,
    ].filter((value): value is string => value !== null);

    return {
      status: activeAlertKeys.length === 0 ? 'OK' : 'WARN',
      generatedAt: new Date().toISOString(),
      config: {
        enabled: payload.enabled,
        intervalMs: payload.intervalMs,
        staleMultiplier,
        staleThresholdMs,
        errorStreakThreshold,
        errorCountThreshold,
      },
      checks: {
        staleSuccess: {
          active: staleSuccess,
          lastSuccessAt: lastSuccessCompletedAt ? lastSuccessCompletedAt.toISOString() : null,
          lastSuccessAgeMs,
        },
        errorStreak: {
          active: errorStreak,
          consecutiveErrorRuns,
        },
        highErrorsLastRun: {
          active: highErrorsLastRun,
          lastRunErrors: lastRun?.errors ?? null,
          lastRunAt: lastRunCompletedAt ? lastRunCompletedAt.toISOString() : null,
          lastRunAgeMs,
        },
      },
      activeAlerts: activeAlertKeys,
    };
  }

  async getBackgroundJobsHealth(payload: { recentFailuresLimit?: unknown } = {}) {
    const recentFailuresLimit = this.parseRecentFailuresLimit(payload.recentFailuresLimit);
    if (payload.recentFailuresLimit !== undefined && recentFailuresLimit === null) {
      throw new BadRequestException('recentFailuresLimit must be an integer between 1 and 50');
    }

    const [groupedCounts, nextDueJob, oldestPendingJob, recentFailures] = await Promise.all([
      this.prisma.paymentBackgroundJob.groupBy({
        by: ['status'],
        _count: { _all: true },
      }),
      this.prisma.paymentBackgroundJob.findFirst({
        where: { status: { in: ['PENDING', 'RETRYABLE'] } },
        orderBy: { runAt: 'asc' },
      }),
      this.prisma.paymentBackgroundJob.findFirst({
        where: { status: { in: ['PENDING', 'RETRYABLE'] } },
        orderBy: { createdAt: 'asc' },
      }),
      this.prisma.paymentBackgroundJob.findMany({
        where: { status: 'DEAD' },
        orderBy: { updatedAt: 'desc' },
        take: recentFailuresLimit ?? 10,
      }),
    ]);

    const counts = {
      pending: 0,
      retryable: 0,
      running: 0,
      completed: 0,
      dead: 0,
      total: 0,
    };

    for (const item of groupedCounts) {
      const count = item._count._all;
      counts.total += count;
      if (item.status === 'PENDING') {
        counts.pending = count;
      }
      if (item.status === 'RETRYABLE') {
        counts.retryable = count;
      }
      if (item.status === 'RUNNING') {
        counts.running = count;
      }
      if (item.status === 'COMPLETED') {
        counts.completed = count;
      }
      if (item.status === 'DEAD') {
        counts.dead = count;
      }
    }

    const terminalTotal = counts.completed + counts.dead;
    const nowMs = Date.now();
    const oldestPendingAgeMs = oldestPendingJob ? Math.max(0, nowMs - oldestPendingJob.createdAt.getTime()) : null;
    const retrySuccessRate = terminalTotal > 0 ? counts.completed / terminalTotal : null;
    const deadLetterRate = terminalTotal > 0 ? counts.dead / terminalTotal : null;

    return {
      generatedAt: new Date().toISOString(),
      counts,
      metrics: {
        oldestPendingAgeMs,
        oldestPendingAt: oldestPendingJob?.createdAt?.toISOString() ?? null,
        retrySuccessRate,
        deadLetterRate,
      },
      nextDueAt: nextDueJob?.runAt?.toISOString() ?? null,
      recentFailures: recentFailures.map((job) => ({
        id: job.id,
        type: job.type,
        attempts: job.attempts,
        maxAttempts: job.maxAttempts,
        lastError: job.lastError,
        updatedAt: job.updatedAt.toISOString(),
      })),
    };
  }

  async dispatchOperationalAlerts(payload: {
    reconcileEnabled: boolean;
    reconcileIntervalMs: number | null;
    jobsRunner: BackgroundJobsRunnerSnapshot;
  }) {
    const [reconciliation, jobsHealth] = await Promise.all([
      this.getReconciliationAlerts({
        enabled: payload.reconcileEnabled,
        intervalMs: payload.reconcileIntervalMs,
      }),
      this.getBackgroundJobsHealth({ recentFailuresLimit: 10 }),
    ]);

    const jobsPendingAgeThresholdMs = this.readPositiveIntEnv('PAYMENTS_JOBS_ALERT_PENDING_AGE_MS', 900000);
    const jobsDeadCountThreshold = this.readPositiveIntEnv('PAYMENTS_JOBS_ALERT_DEAD_COUNT', 5);
    const jobsDeadLetterRateThreshold = this.readRateEnv('PAYMENTS_JOBS_ALERT_DEAD_LETTER_RATE', 0.2);
    const jobsStalledTickThresholdMs = this.readPositiveIntEnv('PAYMENTS_JOBS_ALERT_STALLED_TICK_MS', Math.max(payload.jobsRunner.intervalMs * 3, 30000));

    const oldestPendingAgeMs = jobsHealth.metrics.oldestPendingAgeMs;
    const deadCount = jobsHealth.counts.dead;
    const deadLetterRate = jobsHealth.metrics.deadLetterRate;

    const pendingAgeHigh = oldestPendingAgeMs !== null && oldestPendingAgeMs >= jobsPendingAgeThresholdMs;
    const deadCountHigh = deadCount >= jobsDeadCountThreshold;
    const deadLetterRateHigh = deadLetterRate !== null && deadLetterRate >= jobsDeadLetterRateThreshold;

    const lastTickAgeMs = payload.jobsRunner.lastTickFinishedAt
      ? Math.max(0, Date.now() - new Date(payload.jobsRunner.lastTickFinishedAt).getTime())
      : null;
    const runnerStalled = Boolean(
      payload.jobsRunner.enabled
      && !payload.jobsRunner.running
      && lastTickAgeMs !== null
      && lastTickAgeMs >= jobsStalledTickThresholdMs,
    );
    const runnerError = Boolean(payload.jobsRunner.lastTickError || payload.jobsRunner.lastPruneError);

    const activeAlerts: Array<{ key: string; source: 'RECONCILIATION' | 'JOBS'; severity: 'WARN'; message: string }> = [];

    for (const key of reconciliation.activeAlerts) {
      activeAlerts.push({
        key: `reconcile.${key}`,
        source: 'RECONCILIATION',
        severity: 'WARN',
        message: `Reconciliation check triggered: ${key}`,
      });
    }

    if (pendingAgeHigh) {
      activeAlerts.push({
        key: 'jobs.pendingAgeHigh',
        source: 'JOBS',
        severity: 'WARN',
        message: `Oldest pending job age ${oldestPendingAgeMs}ms exceeds threshold ${jobsPendingAgeThresholdMs}ms`,
      });
    }

    if (deadCountHigh) {
      activeAlerts.push({
        key: 'jobs.deadCountHigh',
        source: 'JOBS',
        severity: 'WARN',
        message: `Dead jobs count ${deadCount} exceeds threshold ${jobsDeadCountThreshold}`,
      });
    }

    if (deadLetterRateHigh) {
      activeAlerts.push({
        key: 'jobs.deadLetterRateHigh',
        source: 'JOBS',
        severity: 'WARN',
        message: `Dead-letter rate ${deadLetterRate} exceeds threshold ${jobsDeadLetterRateThreshold}`,
      });
    }

    if (runnerStalled) {
      activeAlerts.push({
        key: 'jobs.runnerStalled',
        source: 'JOBS',
        severity: 'WARN',
        message: `Jobs runner last tick age ${lastTickAgeMs}ms exceeds threshold ${jobsStalledTickThresholdMs}ms`,
      });
    }

    if (runnerError) {
      activeAlerts.push({
        key: 'jobs.runnerError',
        source: 'JOBS',
        severity: 'WARN',
        message: payload.jobsRunner.lastTickError ?? payload.jobsRunner.lastPruneError ?? 'Jobs runner error detected',
      });
    }

    return {
      status: activeAlerts.length === 0 ? 'OK' : 'WARN',
      generatedAt: new Date().toISOString(),
      config: {
        jobsPendingAgeThresholdMs,
        jobsDeadCountThreshold,
        jobsDeadLetterRateThreshold,
        jobsStalledTickThresholdMs,
      },
      checks: {
        reconciliation,
        jobs: {
          pendingAgeHigh: {
            active: pendingAgeHigh,
            oldestPendingAgeMs,
            thresholdMs: jobsPendingAgeThresholdMs,
          },
          deadCountHigh: {
            active: deadCountHigh,
            deadCount,
            threshold: jobsDeadCountThreshold,
          },
          deadLetterRateHigh: {
            active: deadLetterRateHigh,
            deadLetterRate,
            threshold: jobsDeadLetterRateThreshold,
          },
          runnerStalled: {
            active: runnerStalled,
            lastTickFinishedAt: payload.jobsRunner.lastTickFinishedAt,
            lastTickAgeMs,
            thresholdMs: jobsStalledTickThresholdMs,
          },
          runnerError: {
            active: runnerError,
            lastTickError: payload.jobsRunner.lastTickError,
            lastPruneError: payload.jobsRunner.lastPruneError,
          },
        },
      },
      activeAlerts,
    };
  }

  async listBackgroundJobs(payload: { status?: unknown; type?: unknown; limit?: unknown; offset?: unknown } = {}) {
    const status = this.parseBackgroundJobStatus(payload.status);
    if (payload.status !== undefined && status === null) {
      throw new BadRequestException('status must be one of PENDING, RETRYABLE, RUNNING, COMPLETED, DEAD, CANCELLED');
    }

    const type = this.parseBackgroundJobType(payload.type);
    if (payload.type !== undefined && type === null) {
      throw new BadRequestException('type must be a non-empty string up to 64 chars');
    }

    const limit = this.parseBackgroundJobListLimit(payload.limit);
    if (payload.limit !== undefined && limit === null) {
      throw new BadRequestException('limit must be an integer between 1 and 200');
    }

    const offset = this.parseBackgroundJobListOffset(payload.offset);
    if (payload.offset !== undefined && offset === null) {
      throw new BadRequestException('offset must be an integer between 0 and 10000');
    }

    const normalizedLimit = limit ?? 50;
    const normalizedOffset = offset ?? 0;

    const where = {
      ...(status ? { status } : {}),
      ...(type ? { type } : {}),
    };

    const [items, total] = await Promise.all([
      this.prisma.paymentBackgroundJob.findMany({
        where,
        orderBy: { createdAt: 'desc' },
        take: normalizedLimit,
        skip: normalizedOffset,
      }),
      this.prisma.paymentBackgroundJob.count({ where }),
    ]);

    return {
      filters: {
        status: status ?? null,
        type: type ?? null,
      },
      page: {
        limit: normalizedLimit,
        offset: normalizedOffset,
        total,
      },
      items,
    };
  }

  async requeueBackgroundJob(jobId: string, payload: { delaySeconds?: unknown } = {}) {
    const delaySeconds = this.parseBackgroundJobDelaySeconds(payload.delaySeconds);
    if (payload.delaySeconds !== undefined && delaySeconds === null) {
      throw new BadRequestException('delaySeconds must be an integer between 0 and 3600');
    }

    const job = await this.prisma.paymentBackgroundJob.findUnique({ where: { id: jobId } });
    if (!job) {
      throw new NotFoundException('Background job not found');
    }

    if (job.status === 'PENDING' || job.status === 'RETRYABLE') {
      return {
        changed: false,
        id: job.id,
        status: job.status,
        runAt: job.runAt.toISOString(),
      };
    }

    if (job.status === 'RUNNING') {
      throw new BadRequestException('Cannot requeue a RUNNING job');
    }

    const runAt = new Date(Date.now() + (delaySeconds ?? 0) * 1000);
    const updated = await this.prisma.paymentBackgroundJob.update({
      where: { id: job.id },
      data: {
        status: 'PENDING',
        runAt,
        processedAt: null,
        lastError: null,
        attempts: 0,
      },
    });

    return {
      changed: true,
      id: updated.id,
      status: updated.status,
      runAt: updated.runAt.toISOString(),
    };
  }

  async cancelBackgroundJob(jobId: string) {
    const job = await this.prisma.paymentBackgroundJob.findUnique({ where: { id: jobId } });
    if (!job) {
      throw new NotFoundException('Background job not found');
    }

    if (job.status === 'CANCELLED') {
      return {
        changed: false,
        id: job.id,
        status: job.status,
      };
    }

    if (job.status === 'COMPLETED') {
      return {
        changed: false,
        id: job.id,
        status: job.status,
      };
    }

    if (job.status === 'RUNNING') {
      throw new BadRequestException('Cannot cancel a RUNNING job');
    }

    const updated = await this.prisma.paymentBackgroundJob.update({
      where: { id: job.id },
      data: {
        status: 'CANCELLED',
        processedAt: new Date(),
      },
    });

    return {
      changed: true,
      id: updated.id,
      status: updated.status,
    };
  }

  async completeBackgroundJob(jobId: string) {
    const job = await this.prisma.paymentBackgroundJob.findUnique({ where: { id: jobId } });
    if (!job) {
      throw new NotFoundException('Background job not found');
    }

    if (job.status === 'COMPLETED') {
      return {
        changed: false,
        id: job.id,
        status: job.status,
      };
    }

    if (job.status === 'RUNNING') {
      throw new BadRequestException('Cannot force-complete a RUNNING job');
    }

    const updated = await this.prisma.paymentBackgroundJob.update({
      where: { id: job.id },
      data: {
        status: 'COMPLETED',
        processedAt: new Date(),
        lastError: null,
      },
    });

    return {
      changed: true,
      id: updated.id,
      status: updated.status,
    };
  }

  async processDueBackgroundJobs(limit: number) {
    const normalizedLimit = Number.isInteger(limit) && limit > 0 ? Math.min(limit, 100) : 25;
    let processed = 0;

    for (let index = 0; index < normalizedLimit; index += 1) {
      const job = await this.claimNextDueBackgroundJob();
      if (!job) {
        break;
      }

      try {
        await this.executeBackgroundJob(job);
        await this.prisma.paymentBackgroundJob.update({
          where: { id: job.id },
          data: {
            status: 'COMPLETED',
            processedAt: new Date(),
            lastError: null,
          },
        });
      } catch (error) {
        const attempt = job.attempts;
        const maxAttempts = job.maxAttempts;
        const willRetry = attempt < maxAttempts;
        const retryDelayMs = this.computeJobRetryDelayMs(attempt);

        await this.prisma.paymentBackgroundJob.update({
          where: { id: job.id },
          data: {
            status: willRetry ? 'RETRYABLE' : 'DEAD',
            runAt: willRetry ? new Date(Date.now() + retryDelayMs) : job.runAt,
            processedAt: willRetry ? null : new Date(),
            lastError: error instanceof Error ? error.message : 'unknown error',
          },
        });
      }

      processed += 1;
    }

    return { processed };
  }

  async pruneCompletedBackgroundJobs(payload: { olderThanHours?: unknown; limit?: unknown } = {}) {
    const olderThanHours = this.parsePruneOlderThanHours(payload.olderThanHours);
    if (payload.olderThanHours !== undefined && olderThanHours === null) {
      throw new BadRequestException('olderThanHours must be an integer between 1 and 8760');
    }

    const limit = this.parsePruneLimit(payload.limit);
    if (payload.limit !== undefined && limit === null) {
      throw new BadRequestException('limit must be an integer between 1 and 2000');
    }

    const normalizedOlderThanHours = olderThanHours ?? 168;
    const normalizedLimit = limit ?? 200;
    const cutoff = new Date(Date.now() - normalizedOlderThanHours * 60 * 60 * 1000);

    const candidateIds = await this.prisma.paymentBackgroundJob.findMany({
      where: {
        status: 'COMPLETED',
        processedAt: { lte: cutoff },
      },
      orderBy: { processedAt: 'asc' },
      take: normalizedLimit,
      select: { id: true },
    });

    if (candidateIds.length === 0) {
      return {
        olderThanHours: normalizedOlderThanHours,
        limit: normalizedLimit,
        cutoffAt: cutoff.toISOString(),
        candidates: 0,
        pruned: 0,
      };
    }

    const deleted = await this.prisma.paymentBackgroundJob.deleteMany({
      where: {
        id: { in: candidateIds.map((item) => item.id) },
      },
    });

    return {
      olderThanHours: normalizedOlderThanHours,
      limit: normalizedLimit,
      cutoffAt: cutoff.toISOString(),
      candidates: candidateIds.length,
      pruned: deleted.count,
    };
  }

  async enqueueBookingConfirmationJob(bookingId: string, paymentId: string, source: 'WEBHOOK' | 'RECONCILE') {
    return this.enqueueBackgroundJob('BOOKING_CONFIRMATION_NOTIFICATION', {
      bookingId,
      paymentId,
      source,
    }, undefined, 5, bookingId);
  }

  private async processWebhook(
    provider: ProviderName,
    signature: string | undefined,
    payload: unknown,
  ) {
    const adapter = this.getAdapter(provider);
    const webhookSecret = this.getWebhookSecret(provider);
    const event = adapter.verifyAndParseWebhook(payload, signature, webhookSecret);

    if (!event.id || !event.type) {
      throw new UnauthorizedException('Invalid webhook signature or payload');
    }

    let eventRecordId: string;
    try {
      const createdEvent = await this.prisma.paymentWebhookEvent.create({
        data: {
          provider,
          eventId: event.id,
          eventType: event.type,
          payload: event.payload as Prisma.JsonObject,
          status: 'PROCESSED',
        },
      });
      eventRecordId = createdEvent.id;
    } catch (error) {
      if (error instanceof Prisma.PrismaClientKnownRequestError && error.code === 'P2002') {
        return {
          received: true,
          idempotent: true,
        };
      }
      throw error;
    }

    try {
      await this.applyWebhookEvent(provider, adapter, event);
    } catch (error) {
      await this.prisma.paymentWebhookEvent.update({
        where: { id: eventRecordId },
        data: { status: 'FAILED' },
      });

      await this.enqueueWebhookRetryJob({
        provider,
        eventRecordId,
        event,
        errorMessage: error instanceof Error ? error.message : 'unknown error',
      });

      return {
        received: true,
        idempotent: false,
        queuedRetry: true,
      };
    }

    return {
      received: true,
      idempotent: false,
    };
  }

  private async applyWebhookEvent(
    provider: ProviderName,
    adapter: PaymentProviderAdapter,
    event: { id: string; type: string; paymentIntentId?: string; bookingId?: string },
  ) {
    const payment = await this.resolvePaymentFromEvent(provider, event.paymentIntentId, event.bookingId);
    if (!payment) {
      return;
    }

    const providerStatus = await this.resolveProviderStatus(provider, adapter, event.paymentIntentId, payment.providerId ?? undefined);

    if (this.isSucceededEvent(event.type, providerStatus)) {
      await this.prisma.payment.update({
        where: { id: payment.id },
        data: { status: 'SUCCEEDED' },
      });

      await this.prisma.booking.update({
        where: { id: payment.bookingId },
        data: { status: 'CONFIRMED' },
      });

      await this.loyaltyService.awardPointsForConfirmedBooking(payment.bookingId, 'PAYMENT_WEBHOOK');

      await this.enqueueBookingConfirmationJob(payment.bookingId, payment.id, 'WEBHOOK');
    }

    if (this.isFailedEvent(event.type, providerStatus)) {
      await this.prisma.payment.update({
        where: { id: payment.id },
        data: { status: 'FAILED' },
      });
    }
  }

  private async enqueueWebhookRetryJob(payload: {
    provider: ProviderName;
    eventRecordId: string;
    event: { id: string; type: string; paymentIntentId?: string; bookingId?: string };
    errorMessage: string;
  }) {
    const dedupeKey = `${payload.provider}:${payload.event.id}`;
    return this.enqueueBackgroundJob('WEBHOOK_PROCESS_RETRY', payload, undefined, 5, dedupeKey);
  }

  private async enqueueBackgroundJob(
    type: BackgroundJobType,
    payload: Prisma.JsonObject,
    runAt?: Date,
    maxAttempts = 5,
    dedupeKey?: string,
  ) {
    if (!dedupeKey) {
      return this.prisma.paymentBackgroundJob.create({
        data: {
          type,
          status: 'PENDING',
          attempts: 0,
          maxAttempts,
          runAt: runAt ?? new Date(),
          payload,
        },
      });
    }

    const existing = await this.prisma.paymentBackgroundJob.findFirst({
      where: {
        type,
        dedupeKey,
      },
    });
    if (existing) {
      return existing;
    }

    try {
      return await this.prisma.paymentBackgroundJob.create({
        data: {
          type,
          dedupeKey,
          status: 'PENDING',
          attempts: 0,
          maxAttempts,
          runAt: runAt ?? new Date(),
          payload,
        },
      });
    } catch (error) {
      if (error instanceof Prisma.PrismaClientKnownRequestError && error.code === 'P2002') {
        const raced = await this.prisma.paymentBackgroundJob.findFirst({
          where: {
            type,
            dedupeKey,
          },
        });
        if (raced) {
          return raced;
        }
      }

      throw error;
    }
  }

  private async claimNextDueBackgroundJob() {
    const now = new Date();
    return this.prisma.$transaction(async (transaction) => {
      const candidate = await transaction.paymentBackgroundJob.findFirst({
        where: {
          status: { in: ['PENDING', 'RETRYABLE'] },
          runAt: { lte: now },
        },
        orderBy: { createdAt: 'asc' },
      });

      if (!candidate) {
        return null;
      }

      const claimed = await transaction.paymentBackgroundJob.updateMany({
        where: {
          id: candidate.id,
          status: { in: ['PENDING', 'RETRYABLE'] },
          runAt: { lte: now },
        },
        data: {
          status: 'RUNNING',
          attempts: { increment: 1 },
          lastError: null,
        },
      });

      if (claimed.count === 0) {
        return null;
      }

      return transaction.paymentBackgroundJob.findUnique({ where: { id: candidate.id } });
    });
  }

  private async executeBackgroundJob(job: {
    id: string;
    type: string;
    payload: Prisma.JsonValue;
  }) {
    if (job.type === 'BOOKING_CONFIRMATION_NOTIFICATION') {
      const payload = this.parseBookingConfirmationPayload(job.payload);
      if (!payload) {
        throw new Error('invalid booking confirmation job payload');
      }

      await this.executeBookingConfirmationJob(payload);
      return;
    }

    if (job.type === 'WEBHOOK_PROCESS_RETRY') {
      const payload = this.parseWebhookRetryPayload(job.payload);
      if (!payload) {
        throw new Error('invalid webhook retry job payload');
      }

      await this.executeWebhookRetryJob(payload);
      return;
    }

    throw new Error(`unsupported job type: ${job.type}`);
  }

  private async executeBookingConfirmationJob(payload: { bookingId: string; paymentId: string; source: string }) {
    const [booking, payment] = await Promise.all([
      this.prisma.booking.findUnique({ where: { id: payload.bookingId } }),
      this.prisma.payment.findUnique({ where: { id: payload.paymentId } }),
    ]);

    if (!booking) {
      throw new Error('booking not found');
    }

    if (!payment) {
      throw new Error('payment not found');
    }

    if (booking.status !== 'CONFIRMED' || payment.status !== 'SUCCEEDED') {
      throw new Error('booking confirmation prerequisites are not met');
    }
  }

  private async executeWebhookRetryJob(payload: {
    provider: ProviderName;
    eventRecordId: string;
    event: { id: string; type: string; paymentIntentId?: string; bookingId?: string };
  }) {
    const adapter = this.getAdapter(payload.provider);
    await this.applyWebhookEvent(payload.provider, adapter, payload.event);
    await this.prisma.paymentWebhookEvent.update({
      where: { id: payload.eventRecordId },
      data: { status: 'PROCESSED' },
    });
  }

  private parseBookingConfirmationPayload(payload: Prisma.JsonValue): { bookingId: string; paymentId: string; source: string } | null {
    if (!payload || typeof payload !== 'object' || Array.isArray(payload)) {
      return null;
    }

    const value = payload as Record<string, unknown>;
    if (typeof value.bookingId !== 'string' || typeof value.paymentId !== 'string' || typeof value.source !== 'string') {
      return null;
    }

    return {
      bookingId: value.bookingId,
      paymentId: value.paymentId,
      source: value.source,
    };
  }

  private parseWebhookRetryPayload(payload: Prisma.JsonValue): {
    provider: ProviderName;
    eventRecordId: string;
    event: { id: string; type: string; paymentIntentId?: string; bookingId?: string };
  } | null {
    if (!payload || typeof payload !== 'object' || Array.isArray(payload)) {
      return null;
    }

    const value = payload as Record<string, unknown>;
    const event = value.event;
    if (!event || typeof event !== 'object' || Array.isArray(event)) {
      return null;
    }

    const provider = this.parseProviderFilter(value.provider);
    if (!provider || typeof value.eventRecordId !== 'string') {
      return null;
    }

    const eventValue = event as Record<string, unknown>;
    if (typeof eventValue.id !== 'string' || typeof eventValue.type !== 'string') {
      return null;
    }

    return {
      provider,
      eventRecordId: value.eventRecordId,
      event: {
        id: eventValue.id,
        type: eventValue.type,
        paymentIntentId: typeof eventValue.paymentIntentId === 'string' ? eventValue.paymentIntentId : undefined,
        bookingId: typeof eventValue.bookingId === 'string' ? eventValue.bookingId : undefined,
      },
    };
  }

  private computeJobRetryDelayMs(attempt: number) {
    const maxDelayMs = 10 * 60 * 1000;
    const baseDelayMs = 30 * 1000;
    const delayMs = baseDelayMs * 2 ** Math.max(0, attempt - 1);
    return Math.min(delayMs, maxDelayMs);
  }

  private async resolveProviderStatus(
    provider: ProviderName,
    adapter: PaymentProviderAdapter,
    eventReferenceId?: string,
    paymentReferenceId?: string,
  ): Promise<ProviderTransactionStatus | null> {
    if (provider !== 'BML' || !adapter.fetchTransactionStatus) {
      return null;
    }

    const referenceId = eventReferenceId ?? paymentReferenceId;
    if (!referenceId) {
      return null;
    }

    return this.fetchWithRetry(() => adapter.fetchTransactionStatus!(referenceId), 2);
  }

  private async fetchWithRetry<T>(factory: () => Promise<T>, retries: number): Promise<T> {
    let attempt = 0;
    let delayMs = 250;

    while (true) {
      try {
        return await factory();
      } catch (error) {
        if (attempt >= retries) {
          throw error;
        }

        await new Promise((resolve) => setTimeout(resolve, delayMs));
        delayMs *= 2;
        attempt += 1;
      }
    }
  }

  private isSucceededEvent(eventType: string, providerStatus: ProviderTransactionStatus | null): boolean {
    if (providerStatus?.state === 'SUCCEEDED') {
      return true;
    }

    if (providerStatus?.state === 'FAILED') {
      return false;
    }

    return eventType === 'payment_intent.succeeded' || eventType === 'checkout.session.completed';
  }

  private isFailedEvent(eventType: string, providerStatus: ProviderTransactionStatus | null): boolean {
    if (providerStatus?.state === 'FAILED') {
      return true;
    }

    if (providerStatus?.state === 'SUCCEEDED') {
      return false;
    }

    return eventType === 'payment_intent.payment_failed';
  }

  private async resolvePaymentFromEvent(provider: ProviderName, paymentIntentId?: string, bookingId?: string) {
    if (paymentIntentId) {
      const paymentByIntent = await this.prisma.payment.findFirst({
        where: {
          provider,
          providerId: paymentIntentId,
        },
      });

      if (paymentByIntent) {
        return paymentByIntent;
      }
    }

    if (bookingId) {
      return this.prisma.payment.findUnique({ where: { bookingId } });
    }

    return null;
  }

  private getAdapter(provider: ProviderName): PaymentProviderAdapter {
    if (provider === 'BML') {
      return this.bmlAdapter;
    }

    if (provider === 'MIB') {
      return this.mibAdapter;
    }

    return this.stripeAdapter;
  }

  private getWebhookSecret(provider: ProviderName): string {
    if (provider === 'BML') {
      return process.env.BML_API_KEY ?? process.env.BML_WEBHOOK_SECRET ?? 'dev-bml-webhook-secret';
    }

    if (provider === 'MIB') {
      return process.env.MIB_WEBHOOK_SECRET ?? 'dev-mib-webhook-secret';
    }

    return process.env.STRIPE_WEBHOOK_SECRET ?? 'dev-webhook-secret';
  }

  private parseProvider(value: unknown): ProviderName | null {
    if (typeof value !== 'string') {
      return 'STRIPE';
    }

    const normalized = value.toUpperCase();
    if (normalized === 'STRIPE' || normalized === 'BML' || normalized === 'MIB') {
      return normalized;
    }

    return null;
  }

  private parseCurrency(value: unknown): 'USD' | 'MVR' | null {
    if (typeof value !== 'string') {
      return 'USD';
    }

    const normalized = value.toUpperCase();
    if (normalized === 'USD' || normalized === 'MVR') {
      return normalized;
    }

    return null;
  }

  private parseProviderFilter(value: unknown): ProviderName | null {
    if (typeof value !== 'string') {
      return null;
    }

    const normalized = value.toUpperCase();
    if (normalized === 'STRIPE' || normalized === 'BML' || normalized === 'MIB') {
      return normalized;
    }

    return null;
  }

  private parseLimit(value: unknown): number | null {
    if (value === undefined) {
      return null;
    }

    if (typeof value !== 'number' || !Number.isInteger(value)) {
      return null;
    }

    if (value < 1 || value > 500) {
      return null;
    }

    return value;
  }

  private parseHistoryLimit(value: unknown): number | null {
    if (value === undefined) {
      return null;
    }

    const parsed = typeof value === 'string' ? Number(value) : value;
    if (typeof parsed !== 'number' || !Number.isInteger(parsed)) {
      return null;
    }

    if (parsed < 1 || parsed > 100) {
      return null;
    }

    return parsed;
  }

  private parseRecentFailuresLimit(value: unknown): number | null {
    if (value === undefined) {
      return null;
    }

    const parsed = typeof value === 'string' ? Number(value) : value;
    if (typeof parsed !== 'number' || !Number.isInteger(parsed)) {
      return null;
    }

    if (parsed < 1 || parsed > 50) {
      return null;
    }

    return parsed;
  }

  private parsePruneOlderThanHours(value: unknown): number | null {
    if (value === undefined) {
      return null;
    }

    const parsed = typeof value === 'string' ? Number(value) : value;
    if (typeof parsed !== 'number' || !Number.isInteger(parsed)) {
      return null;
    }

    if (parsed < 1 || parsed > 8760) {
      return null;
    }

    return parsed;
  }

  private parsePruneLimit(value: unknown): number | null {
    if (value === undefined) {
      return null;
    }

    const parsed = typeof value === 'string' ? Number(value) : value;
    if (typeof parsed !== 'number' || !Number.isInteger(parsed)) {
      return null;
    }

    if (parsed < 1 || parsed > 2000) {
      return null;
    }

    return parsed;
  }

  private parseBackgroundJobStatus(value: unknown): string | null {
    if (value === undefined) {
      return null;
    }

    if (typeof value !== 'string') {
      return null;
    }

    const normalized = value.toUpperCase();
    if (['PENDING', 'RETRYABLE', 'RUNNING', 'COMPLETED', 'DEAD', 'CANCELLED'].includes(normalized)) {
      return normalized;
    }

    return null;
  }

  private parseBackgroundJobType(value: unknown): string | null {
    if (value === undefined) {
      return null;
    }

    if (typeof value !== 'string') {
      return null;
    }

    const normalized = value.trim();
    if (normalized.length === 0 || normalized.length > 64) {
      return null;
    }

    return normalized;
  }

  private parseBackgroundJobListLimit(value: unknown): number | null {
    if (value === undefined) {
      return null;
    }

    const parsed = typeof value === 'string' ? Number(value) : value;
    if (typeof parsed !== 'number' || !Number.isInteger(parsed)) {
      return null;
    }

    if (parsed < 1 || parsed > 200) {
      return null;
    }

    return parsed;
  }

  private parseBackgroundJobListOffset(value: unknown): number | null {
    if (value === undefined) {
      return null;
    }

    const parsed = typeof value === 'string' ? Number(value) : value;
    if (typeof parsed !== 'number' || !Number.isInteger(parsed)) {
      return null;
    }

    if (parsed < 0 || parsed > 10000) {
      return null;
    }

    return parsed;
  }

  private parseBackgroundJobDelaySeconds(value: unknown): number | null {
    if (value === undefined) {
      return null;
    }

    const parsed = typeof value === 'string' ? Number(value) : value;
    if (typeof parsed !== 'number' || !Number.isInteger(parsed)) {
      return null;
    }

    if (parsed < 0 || parsed > 3600) {
      return null;
    }

    return parsed;
  }

  private readPositiveIntEnv(key: string, fallback: number): number {
    const parsed = Number(process.env[key] ?? fallback);
    if (!Number.isInteger(parsed) || parsed <= 0) {
      return fallback;
    }

    return parsed;
  }

  private readRateEnv(key: string, fallback: number): number {
    const parsed = Number(process.env[key] ?? fallback);
    if (!Number.isFinite(parsed) || parsed < 0 || parsed > 1) {
      return fallback;
    }

    return parsed;
  }
}
