import { BadRequestException, ConflictException, Injectable, Logger, OnModuleDestroy, OnModuleInit } from '@nestjs/common';
import { PaymentsService } from './payments.service';

type ProviderName = 'STRIPE' | 'BML' | 'MIB';

type ReconcileRunSummary = {
  scanned: number;
  reconciled: number;
  succeeded: number;
  failed: number;
  unchanged: number;
  skipped: number;
  errors: number;
};

type ManualRunPayload = {
  provider?: unknown;
  limit?: unknown;
  dryRun?: unknown;
};

@Injectable()
export class PaymentsReconciliationRunner implements OnModuleInit, OnModuleDestroy {
  private readonly logger = new Logger(PaymentsReconciliationRunner.name);
  private intervalHandle: NodeJS.Timeout | null = null;
  private initialDelayHandle: NodeJS.Timeout | null = null;
  private running = false;
  private enabled = false;
  private intervalMs: number | null = null;
  private initialDelayMs: number | null = null;
  private provider: ProviderName | undefined;
  private limit: number | null = null;
  private dryRun = false;
  private lastRunStartedAt: string | null = null;
  private lastRunFinishedAt: string | null = null;
  private lastRunDurationMs: number | null = null;
  private lastRunOutcome: 'success' | 'error' | null = null;
  private lastRunSummary: ReconcileRunSummary | null = null;
  private lastRunError: string | null = null;

  constructor(private readonly paymentsService: PaymentsService) {}

  onModuleInit() {
    this.enabled = (process.env.PAYMENTS_RECONCILE_ENABLED ?? 'false').toLowerCase() === 'true';
    if (!this.enabled) {
      return;
    }

    this.intervalMs = this.parseNumber(process.env.PAYMENTS_RECONCILE_INTERVAL_MS, 300000, 10000, 3600000);
    this.initialDelayMs = this.parseNumber(process.env.PAYMENTS_RECONCILE_INITIAL_DELAY_MS, 5000, 0, 600000);
    this.limit = this.parseNumber(process.env.PAYMENTS_RECONCILE_LIMIT, 100, 1, 500);
    this.provider = this.parseProvider(process.env.PAYMENTS_RECONCILE_PROVIDER);
    this.dryRun = (process.env.PAYMENTS_RECONCILE_DRY_RUN ?? 'false').toLowerCase() === 'true';

    this.initialDelayHandle = setTimeout(() => {
      void this.executeReconciliation({
        source: 'SCHEDULER',
        provider: this.provider,
        limit: this.limit,
        dryRun: this.dryRun,
      });
    }, this.initialDelayMs);

    this.intervalHandle = setInterval(() => {
      void this.executeReconciliation({
        source: 'SCHEDULER',
        provider: this.provider,
        limit: this.limit,
        dryRun: this.dryRun,
      });
    }, this.intervalMs);

    this.logger.log(
      `Payments reconciliation scheduler enabled (intervalMs=${this.intervalMs}, initialDelayMs=${this.initialDelayMs}, provider=${this.provider ?? 'ALL'}, limit=${this.limit}, dryRun=${this.dryRun})`,
    );
  }

  async runNow(payload: ManualRunPayload = {}) {
    const provider = this.parseProviderPayload(payload.provider);
    if (payload.provider !== undefined && provider === null) {
      throw new BadRequestException('Unsupported provider filter. Allowed: STRIPE, BML, MIB');
    }

    const limit = this.parseLimitPayload(payload.limit);
    if (payload.limit !== undefined && limit === null) {
      throw new BadRequestException('limit must be an integer between 1 and 500');
    }

    const normalizedProvider = provider === undefined ? this.provider : provider;
    const normalizedLimit = limit === undefined ? this.limit : limit;
    const normalizedDryRun = payload.dryRun === undefined ? this.dryRun : Boolean(payload.dryRun);

    return this.executeReconciliation({
      source: 'ADMIN',
      provider: normalizedProvider,
      limit: normalizedLimit,
      dryRun: normalizedDryRun,
      throwOnOverlap: true,
    });
  }

  getStatusSnapshot() {
    return {
      enabled: this.enabled,
      running: this.running,
      intervalMs: this.intervalMs,
      initialDelayMs: this.initialDelayMs,
      provider: this.provider ?? 'ALL',
      limit: this.limit,
      dryRun: this.dryRun,
      lastRunStartedAt: this.lastRunStartedAt,
      lastRunFinishedAt: this.lastRunFinishedAt,
      lastRunDurationMs: this.lastRunDurationMs,
      lastRunOutcome: this.lastRunOutcome,
      lastRunSummary: this.lastRunSummary,
      lastRunError: this.lastRunError,
    };
  }

  onModuleDestroy() {
    if (this.initialDelayHandle) {
      clearTimeout(this.initialDelayHandle);
      this.initialDelayHandle = null;
    }

    if (this.intervalHandle) {
      clearInterval(this.intervalHandle);
      this.intervalHandle = null;
    }
  }

  private parseNumber(value: string | undefined, fallback: number, min: number, max: number): number {
    const parsed = Number(value ?? fallback);
    if (!Number.isFinite(parsed)) {
      return fallback;
    }

    const normalized = Math.trunc(parsed);
    if (normalized < min || normalized > max) {
      return fallback;
    }

    return normalized;
  }

  private parseProvider(value: string | undefined): 'STRIPE' | 'BML' | 'MIB' | undefined {
    if (!value) {
      return undefined;
    }

    const normalized = value.toUpperCase();
    if (normalized === 'STRIPE' || normalized === 'BML' || normalized === 'MIB') {
      return normalized;
    }

    return undefined;
  }

  private async executeReconciliation(options: {
    source: 'SCHEDULER' | 'ADMIN';
    provider: ProviderName | undefined;
    limit: number | null;
    dryRun: boolean;
    throwOnOverlap?: boolean;
  }) {
    if (this.running) {
      if (options.throwOnOverlap) {
        throw new ConflictException('A reconciliation run is already in progress');
      }

      this.logger.warn('Skipping reconciliation tick because previous run is still in progress');
      return null;
    }

    const startedAt = new Date();
    this.running = true;
    this.lastRunStartedAt = startedAt.toISOString();
    try {
      const summary = await this.paymentsService.reconcilePendingPayments(
        {
          provider: options.provider,
          limit: options.limit,
          dryRun: options.dryRun,
        },
        options.source,
      );

      this.lastRunOutcome = 'success';
      this.lastRunSummary = {
        scanned: summary.scanned,
        reconciled: summary.reconciled,
        succeeded: summary.succeeded,
        failed: summary.failed,
        unchanged: summary.unchanged,
        skipped: summary.skipped,
        errors: summary.errors,
      };
      this.lastRunError = null;

      this.logger.log(
        `Reconcile ${options.source.toLowerCase()} run complete: scanned=${summary.scanned}, reconciled=${summary.reconciled}, succeeded=${summary.succeeded}, failed=${summary.failed}, unchanged=${summary.unchanged}, skipped=${summary.skipped}, errors=${summary.errors}, dryRun=${summary.dryRun}`,
      );

      return summary;
    } catch (error) {
      this.lastRunOutcome = 'error';
      this.lastRunSummary = null;
      this.lastRunError = error instanceof Error ? error.message : 'unknown error';
      this.logger.error('Reconcile run failed', error instanceof Error ? error.stack : undefined);
      throw error;
    } finally {
      this.lastRunFinishedAt = new Date().toISOString();
      this.lastRunDurationMs = Date.now() - startedAt.getTime();
      this.running = false;
    }
  }

  private parseProviderPayload(value: unknown): ProviderName | undefined | null {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string') {
      return null;
    }

    const normalized = value.toUpperCase();
    if (normalized === 'STRIPE' || normalized === 'BML' || normalized === 'MIB') {
      return normalized;
    }

    return null;
  }

  private parseLimitPayload(value: unknown): number | undefined | null {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'number' || !Number.isInteger(value)) {
      return null;
    }

    if (value < 1 || value > 500) {
      return null;
    }

    return value;
  }
}
