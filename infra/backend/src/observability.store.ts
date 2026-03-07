type RequestSample = {
  route: string;
  method: string;
  status: number;
  durationMs: number;
  at: string;
};

class ObservabilityStore {
  private samples: RequestSample[] = [];
  private readonly maxSamples = 5000;

  record(sample: RequestSample): void {
    this.samples.push(sample);
    if (this.samples.length > this.maxSamples) {
      this.samples.splice(0, this.samples.length - this.maxSamples);
    }
  }

  getSummary(): {
    total_requests: number;
    error_rate: number;
    p95_ms: number;
    p99_ms: number;
    generated_at: string;
  } {
    const total = this.samples.length;
    if (total === 0) {
      return {
        total_requests: 0,
        error_rate: 0,
        p95_ms: 0,
        p99_ms: 0,
        generated_at: new Date().toISOString(),
      };
    }

    const sorted = [...this.samples].map((s) => s.durationMs).sort((a, b) => a - b);
    const p95 = sorted[Math.min(sorted.length - 1, Math.floor(sorted.length * 0.95))];
    const p99 = sorted[Math.min(sorted.length - 1, Math.floor(sorted.length * 0.99))];
    const errors = this.samples.filter((s) => s.status >= 500).length;

    return {
      total_requests: total,
      error_rate: Number((errors / total).toFixed(4)),
      p95_ms: Number(p95.toFixed(2)),
      p99_ms: Number(p99.toFixed(2)),
      generated_at: new Date().toISOString(),
    };
  }

  toPrometheus(): string {
    const summary = this.getSummary();
    return [
      '# HELP workation_requests_total Total HTTP requests observed',
      '# TYPE workation_requests_total counter',
      `workation_requests_total ${summary.total_requests}`,
      '# HELP workation_error_rate Current 5xx error rate',
      '# TYPE workation_error_rate gauge',
      `workation_error_rate ${summary.error_rate}`,
      '# HELP workation_http_latency_p95_ms Approximate p95 latency in milliseconds',
      '# TYPE workation_http_latency_p95_ms gauge',
      `workation_http_latency_p95_ms ${summary.p95_ms}`,
      '# HELP workation_http_latency_p99_ms Approximate p99 latency in milliseconds',
      '# TYPE workation_http_latency_p99_ms gauge',
      `workation_http_latency_p99_ms ${summary.p99_ms}`,
    ].join('\n');
  }
}

export const observabilityStore = new ObservabilityStore();
