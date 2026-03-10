import { Injectable } from '@nestjs/common';

type Sample = {
  ts: number;
  method: string;
  path: string;
  statusCode: number;
  durationMs: number;
};

type Alerts = {
  status: 'OK' | 'WARN';
  activeAlerts: Array<{ key: string; severity: 'WARN'; message: string }>;
  routing: {
    pager: { enabled: boolean; target: string | null; matches: string[] };
    slack: { enabled: boolean; target: string | null; matches: string[] };
    email: { enabled: boolean; target: string | null; matches: string[] };
  };
  queueSlo: {
    sampleSize: number;
    minSampleSize: number;
    errorRate: number;
    latencyMs: { p95: number; p99: number };
  };
  generatedAt: string;
};

@Injectable()
export class ObservabilityService {
  private readonly samples: Sample[] = [];
  private readonly maxSamples = Number(process.env.OPS_SAMPLE_CAPACITY ?? 5000);

  record(sample: Sample): void {
    this.samples.push(sample);
    if (this.samples.length > this.maxSamples) {
      this.samples.splice(0, this.samples.length - this.maxSamples);
    }
  }

  getSloSummary() {
    const windowMinutes = Number(process.env.OPS_SLO_WINDOW_MINUTES ?? 15);
    const cutoff = Date.now() - windowMinutes * 60_000;
    const recent = this.samples.filter((sample) => sample.ts >= cutoff);

    const durations = recent.map((sample) => sample.durationMs).sort((a, b) => a - b);
    const totalRequests = recent.length;
    const errors = recent.filter((sample) => sample.statusCode >= 500).length;
    const errorRate = totalRequests === 0 ? 0 : errors / totalRequests;

    const payments = recent.filter((sample) => sample.path.includes('/payments'));
    const bookings = recent.filter((sample) => sample.path.includes('/bookings') || sample.path.includes('/checkout'));

    return {
      windowMinutes,
      totalRequests,
      errorRate,
      latencyMs: {
        p50: percentile(durations, 0.5),
        p95: percentile(durations, 0.95),
        p99: percentile(durations, 0.99),
      },
      domains: {
        payments: this.domainSnapshot(payments),
        bookings: this.domainSnapshot(bookings),
      },
      generatedAt: new Date().toISOString(),
    };
  }

  getPrometheusMetrics(): string {
    const summary = this.getSloSummary();
    const queue = this.getQueueSloSnapshot();
    const lines = [
      '# HELP workation_http_requests_total Total requests in SLO window',
      '# TYPE workation_http_requests_total gauge',
      `workation_http_requests_total ${summary.totalRequests}`,
      '# HELP workation_http_error_rate Error rate for status >= 500 in SLO window',
      '# TYPE workation_http_error_rate gauge',
      `workation_http_error_rate ${summary.errorRate}`,
      '# HELP workation_http_latency_p95_ms p95 latency in milliseconds',
      '# TYPE workation_http_latency_p95_ms gauge',
      `workation_http_latency_p95_ms ${summary.latencyMs.p95}`,
      '# HELP workation_http_latency_p99_ms p99 latency in milliseconds',
      '# TYPE workation_http_latency_p99_ms gauge',
      `workation_http_latency_p99_ms ${summary.latencyMs.p99}`,
      '# HELP workation_http_domain_requests_total Domain request totals in SLO window',
      '# TYPE workation_http_domain_requests_total gauge',
      `workation_http_domain_requests_total{domain="payments"} ${summary.domains.payments.totalRequests}`,
      `workation_http_domain_requests_total{domain="bookings"} ${summary.domains.bookings.totalRequests}`,
      '# HELP workation_http_domain_error_rate Domain error rates for status >= 500',
      '# TYPE workation_http_domain_error_rate gauge',
      `workation_http_domain_error_rate{domain="payments"} ${summary.domains.payments.errorRate}`,
      `workation_http_domain_error_rate{domain="bookings"} ${summary.domains.bookings.errorRate}`,
      '# HELP workation_queue_requests_total Queue-related admin request totals in SLO window',
      '# TYPE workation_queue_requests_total gauge',
      `workation_queue_requests_total ${queue.sampleSize}`,
      '# HELP workation_queue_error_rate Queue-related error rate for status >= 500 in SLO window',
      '# TYPE workation_queue_error_rate gauge',
      `workation_queue_error_rate ${queue.errorRate}`,
      '# HELP workation_queue_latency_p95_ms Queue-related p95 latency in milliseconds',
      '# TYPE workation_queue_latency_p95_ms gauge',
      `workation_queue_latency_p95_ms ${queue.latencyMs.p95}`,
      '# HELP workation_queue_latency_p99_ms Queue-related p99 latency in milliseconds',
      '# TYPE workation_queue_latency_p99_ms gauge',
      `workation_queue_latency_p99_ms ${queue.latencyMs.p99}`,
    ];

    return `${lines.join('\n')}\n`;
  }

  getOperationalAlerts(): Alerts {
    const summary = this.getSloSummary();
    const queue = this.getQueueSloSnapshot();
    const maxErrorRate = Number(process.env.OPS_ALERT_MAX_ERROR_RATE ?? 0.05);
    const maxP95 = Number(process.env.OPS_ALERT_MAX_P95_MS ?? 1200);
    const minSamples = Number(process.env.OPS_ALERT_MIN_SAMPLE_SIZE ?? 50);
    const maxQueueErrorRate = Number(process.env.OPS_ALERT_QUEUE_MAX_ERROR_RATE ?? 0.08);
    const maxQueueP95 = Number(process.env.OPS_ALERT_QUEUE_MAX_P95_MS ?? 1500);

    const activeAlerts: Alerts['activeAlerts'] = [];

    if (summary.totalRequests >= minSamples && summary.errorRate > maxErrorRate) {
      activeAlerts.push({
        key: 'OPS_ERROR_RATE_HIGH',
        severity: 'WARN',
        message: `Error rate ${summary.errorRate.toFixed(4)} exceeds threshold ${maxErrorRate.toFixed(4)}`,
      });
    }

    if (summary.totalRequests >= minSamples && summary.latencyMs.p95 > maxP95) {
      activeAlerts.push({
        key: 'OPS_P95_HIGH',
        severity: 'WARN',
        message: `p95 ${summary.latencyMs.p95.toFixed(2)}ms exceeds threshold ${maxP95.toFixed(2)}ms`,
      });
    }

    if (queue.sampleSize >= queue.minSampleSize && queue.errorRate > maxQueueErrorRate) {
      activeAlerts.push({
        key: 'OPS_QUEUE_ERROR_RATE_HIGH',
        severity: 'WARN',
        message: `Queue error rate ${queue.errorRate.toFixed(4)} exceeds threshold ${maxQueueErrorRate.toFixed(4)}`,
      });
    }

    if (queue.sampleSize >= queue.minSampleSize && queue.latencyMs.p95 > maxQueueP95) {
      activeAlerts.push({
        key: 'OPS_QUEUE_P95_HIGH',
        severity: 'WARN',
        message: `Queue p95 ${queue.latencyMs.p95.toFixed(2)}ms exceeds threshold ${maxQueueP95.toFixed(2)}ms`,
      });
    }

    const activeKeys = activeAlerts.map((alert) => alert.key);
    const routing = this.buildAlertRouting(activeKeys);

    return {
      status: activeAlerts.length === 0 ? 'OK' : 'WARN',
      activeAlerts,
      routing,
      queueSlo: queue,
      generatedAt: new Date().toISOString(),
    };
  }

  getRunbookLinks() {
    return {
      onCall: process.env.OPS_RUNBOOK_ONCALL_URL ?? null,
      incident: process.env.OPS_RUNBOOK_INCIDENT_URL ?? null,
      payments: process.env.OPS_RUNBOOK_PAYMENTS_URL ?? null,
      weatherDisruptions: process.env.OPS_RUNBOOK_WEATHER_URL ?? null,
      providerOutages: process.env.OPS_RUNBOOK_PROVIDER_OUTAGE_URL ?? null,
      updatedAt: new Date().toISOString(),
    };
  }

  private domainSnapshot(samples: Sample[]) {
    const durations = samples.map((sample) => sample.durationMs).sort((a, b) => a - b);
    const totalRequests = samples.length;
    const errors = samples.filter((sample) => sample.statusCode >= 500).length;

    return {
      totalRequests,
      errorRate: totalRequests === 0 ? 0 : errors / totalRequests,
      latencyMs: {
        p95: percentile(durations, 0.95),
        p99: percentile(durations, 0.99),
      },
    };
  }

  private getQueueSloSnapshot() {
    const windowMinutes = Number(process.env.OPS_SLO_WINDOW_MINUTES ?? 15);
    const cutoff = Date.now() - windowMinutes * 60_000;
    const minSampleSize = Number(process.env.OPS_ALERT_QUEUE_MIN_SAMPLE_SIZE ?? 20);
    const queueSamples = this.samples.filter((sample) => {
      if (sample.ts < cutoff) {
        return false;
      }

      return sample.path.includes('/payments/admin/jobs')
        || sample.path.includes('/payments/admin/reconcile')
        || sample.path.includes('/ops/alerts');
    });

    const durations = queueSamples.map((sample) => sample.durationMs).sort((a, b) => a - b);
    const errors = queueSamples.filter((sample) => sample.statusCode >= 500).length;
    const sampleSize = queueSamples.length;

    return {
      sampleSize,
      minSampleSize,
      errorRate: sampleSize === 0 ? 0 : errors / sampleSize,
      latencyMs: {
        p95: percentile(durations, 0.95),
        p99: percentile(durations, 0.99),
      },
    };
  }

  private buildAlertRouting(activeKeys: string[]) {
    const pager = this.channelRouting(
      process.env.OPS_ALERT_ROUTE_PAGER_ENABLED,
      process.env.OPS_ALERT_ROUTE_PAGER_TARGET,
      process.env.OPS_ALERT_ROUTE_PAGER_KEYS,
      activeKeys,
      ['OPS_ERROR_RATE_HIGH', 'OPS_QUEUE_ERROR_RATE_HIGH'],
    );
    const slack = this.channelRouting(
      process.env.OPS_ALERT_ROUTE_SLACK_ENABLED,
      process.env.OPS_ALERT_ROUTE_SLACK_TARGET,
      process.env.OPS_ALERT_ROUTE_SLACK_KEYS,
      activeKeys,
      ['OPS_ERROR_RATE_HIGH', 'OPS_P95_HIGH', 'OPS_QUEUE_ERROR_RATE_HIGH', 'OPS_QUEUE_P95_HIGH'],
    );
    const email = this.channelRouting(
      process.env.OPS_ALERT_ROUTE_EMAIL_ENABLED,
      process.env.OPS_ALERT_ROUTE_EMAIL_TARGET,
      process.env.OPS_ALERT_ROUTE_EMAIL_KEYS,
      activeKeys,
      ['OPS_P95_HIGH', 'OPS_QUEUE_P95_HIGH'],
    );

    return { pager, slack, email };
  }

  private channelRouting(
    enabledValue: string | undefined,
    targetValue: string | undefined,
    configuredKeys: string | undefined,
    activeKeys: string[],
    fallbackKeys: string[],
  ) {
    const enabled = this.readBool(enabledValue, true);
    const target = this.readText(targetValue);
    const keys = this.readKeyList(configuredKeys, fallbackKeys);
    const matches = enabled ? activeKeys.filter((key) => keys.includes(key)) : [];

    return {
      enabled,
      target,
      matches,
    };
  }

  private readBool(value: string | undefined, fallback: boolean): boolean {
    if (!value) {
      return fallback;
    }

    const normalized = value.trim().toLowerCase();
    if (['1', 'true', 'yes', 'on'].includes(normalized)) {
      return true;
    }
    if (['0', 'false', 'no', 'off'].includes(normalized)) {
      return false;
    }
    return fallback;
  }

  private readText(value: string | undefined): string | null {
    if (!value) {
      return null;
    }

    const trimmed = value.trim();
    return trimmed.length > 0 ? trimmed : null;
  }

  private readKeyList(value: string | undefined, fallback: string[]): string[] {
    if (!value) {
      return fallback;
    }

    const parsed = value
      .split(',')
      .map((item) => item.trim())
      .filter((item) => item.length > 0);

    return parsed.length > 0 ? parsed : fallback;
  }
}

function percentile(sortedValues: number[], p: number): number {
  if (sortedValues.length === 0) {
    return 0;
  }

  const index = Math.min(sortedValues.length - 1, Math.max(0, Math.ceil(sortedValues.length * p) - 1));
  return Number(sortedValues[index].toFixed(2));
}
