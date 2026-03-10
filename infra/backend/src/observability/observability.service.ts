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
    ];

    return `${lines.join('\n')}\n`;
  }

  getOperationalAlerts(): Alerts {
    const summary = this.getSloSummary();
    const maxErrorRate = Number(process.env.OPS_ALERT_MAX_ERROR_RATE ?? 0.05);
    const maxP95 = Number(process.env.OPS_ALERT_MAX_P95_MS ?? 1200);
    const minSamples = Number(process.env.OPS_ALERT_MIN_SAMPLE_SIZE ?? 50);

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

    return {
      status: activeAlerts.length === 0 ? 'OK' : 'WARN',
      activeAlerts,
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
}

function percentile(sortedValues: number[], p: number): number {
  if (sortedValues.length === 0) {
    return 0;
  }

  const index = Math.min(sortedValues.length - 1, Math.max(0, Math.ceil(sortedValues.length * p) - 1));
  return Number(sortedValues[index].toFixed(2));
}
