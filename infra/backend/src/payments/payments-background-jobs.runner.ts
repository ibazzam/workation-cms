import { Injectable, Logger, OnModuleDestroy, OnModuleInit } from '@nestjs/common';
import { PaymentsService } from './payments.service';

@Injectable()
export class PaymentsBackgroundJobsRunner implements OnModuleInit, OnModuleDestroy {
  private readonly logger = new Logger(PaymentsBackgroundJobsRunner.name);
  private intervalHandle: NodeJS.Timeout | null = null;
  private initialDelayHandle: NodeJS.Timeout | null = null;
  private enabled = true;
  private running = false;
  private intervalMs = 10000;
  private initialDelayMs = 3000;
  private batchSize = 25;
  private autoPruneEnabled = true;
  private retentionHours = 168;
  private pruneLimit = 200;
  private lastTickStartedAt: string | null = null;
  private lastTickFinishedAt: string | null = null;
  private lastTickDurationMs: number | null = null;
  private lastTickProcessed: number | null = null;
  private lastTickError: string | null = null;
  private lastPruneAt: string | null = null;
  private lastPruned: number | null = null;
  private lastPruneError: string | null = null;

  constructor(private readonly paymentsService: PaymentsService) {}

  onModuleInit() {
    this.enabled = (process.env.PAYMENTS_JOBS_ENABLED ?? 'true').toLowerCase() === 'true';
    this.intervalMs = this.parseNumber(process.env.PAYMENTS_JOBS_INTERVAL_MS, 10000, 1000, 300000);
    this.initialDelayMs = this.parseNumber(process.env.PAYMENTS_JOBS_INITIAL_DELAY_MS, 3000, 0, 60000);
    this.batchSize = this.parseNumber(process.env.PAYMENTS_JOBS_BATCH_SIZE, 25, 1, 100);
    this.autoPruneEnabled = (process.env.PAYMENTS_JOBS_AUTO_PRUNE_ENABLED ?? 'true').toLowerCase() === 'true';
    this.retentionHours = this.parseNumber(process.env.PAYMENTS_JOBS_RETENTION_HOURS, 168, 1, 8760);
    this.pruneLimit = this.parseNumber(process.env.PAYMENTS_JOBS_PRUNE_LIMIT, 200, 1, 2000);

    if (!this.enabled) {
      return;
    }

    this.initialDelayHandle = setTimeout(() => {
      void this.executeTick();
    }, this.initialDelayMs);

    this.intervalHandle = setInterval(() => {
      void this.executeTick();
    }, this.intervalMs);

    this.logger.log(
      `Payments background jobs runner enabled (intervalMs=${this.intervalMs}, initialDelayMs=${this.initialDelayMs}, batchSize=${this.batchSize}, autoPruneEnabled=${this.autoPruneEnabled}, retentionHours=${this.retentionHours}, pruneLimit=${this.pruneLimit})`,
    );
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

  getStatusSnapshot() {
    return {
      enabled: this.enabled,
      running: this.running,
      intervalMs: this.intervalMs,
      initialDelayMs: this.initialDelayMs,
      batchSize: this.batchSize,
      autoPruneEnabled: this.autoPruneEnabled,
      retentionHours: this.retentionHours,
      pruneLimit: this.pruneLimit,
      lastTickStartedAt: this.lastTickStartedAt,
      lastTickFinishedAt: this.lastTickFinishedAt,
      lastTickDurationMs: this.lastTickDurationMs,
      lastTickProcessed: this.lastTickProcessed,
      lastTickError: this.lastTickError,
      lastPruneAt: this.lastPruneAt,
      lastPruned: this.lastPruned,
      lastPruneError: this.lastPruneError,
    };
  }

  private async executeTick() {
    if (this.running) {
      this.logger.warn('Skipping jobs tick because previous tick is still running');
      return;
    }

    const startedAt = new Date();
    this.running = true;
    this.lastTickStartedAt = startedAt.toISOString();

    try {
      const result = await this.paymentsService.processDueBackgroundJobs(this.batchSize);
      this.lastTickProcessed = result.processed;
      this.lastTickError = null;

      if (this.autoPruneEnabled) {
        try {
          const prune = await this.paymentsService.pruneCompletedBackgroundJobs({
            olderThanHours: this.retentionHours,
            limit: this.pruneLimit,
          });
          this.lastPruneAt = new Date().toISOString();
          this.lastPruned = prune.pruned;
          this.lastPruneError = null;
        } catch (error) {
          this.lastPruneAt = new Date().toISOString();
          this.lastPruned = 0;
          this.lastPruneError = error instanceof Error ? error.message : 'unknown error';
          this.logger.error('Background jobs prune failed', error instanceof Error ? error.stack : undefined);
        }
      }
    } catch (error) {
      this.lastTickProcessed = 0;
      this.lastTickError = error instanceof Error ? error.message : 'unknown error';
      this.logger.error('Background jobs tick failed', error instanceof Error ? error.stack : undefined);
    } finally {
      this.lastTickFinishedAt = new Date().toISOString();
      this.lastTickDurationMs = Date.now() - startedAt.getTime();
      this.running = false;
    }
  }

  private parseNumber(value: string | undefined, fallback: number, min: number, max: number) {
    const parsed = Number(value ?? fallback);
    if (!Number.isInteger(parsed)) {
      return fallback;
    }

    if (parsed < min || parsed > max) {
      return fallback;
    }

    return parsed;
  }
}
