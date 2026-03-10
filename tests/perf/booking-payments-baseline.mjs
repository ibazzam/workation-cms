import axios from 'axios';
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';

const baseUrl = process.env.BASE_URL;
const bearerToken = process.env.AUTH_BEARER_TOKEN;
const xUserId = process.env.X_USER_ID;
const xUserRole = process.env.X_USER_ROLE;

const profile = normalizeProfile(process.env.PERF_PROFILE);
const defaults = profileDefaults(profile);

const iterations = Number(process.env.PERF_ITERATIONS ?? defaults.iterations);
const concurrency = Number(process.env.PERF_CONCURRENCY ?? defaults.concurrency);
const timeoutMs = Number(process.env.PERF_TIMEOUT_MS ?? defaults.timeoutMs);

const bookingP95BudgetMs = Number(process.env.SLO_BOOKING_P95_MS ?? defaults.bookingP95BudgetMs);
const paymentsP95BudgetMs = Number(process.env.SLO_PAYMENTS_P95_MS ?? defaults.paymentsP95BudgetMs);
const failOnBreach = (process.env.PERF_FAIL_ON_BREACH ?? 'false').toLowerCase() === 'true';

if (!baseUrl) {
  console.error('BASE_URL is required, for example https://api.workation.mv');
  process.exit(2);
}

if (!bearerToken) {
  console.error('AUTH_BEARER_TOKEN is required to run booking/payments baseline.');
  process.exit(2);
}

const client = axios.create({
  baseURL: baseUrl,
  timeout: timeoutMs,
  headers: {
    Authorization: `Bearer ${bearerToken}`,
    ...(xUserId ? { 'x-user-id': String(xUserId) } : {}),
    ...(xUserRole ? { 'x-user-role': String(xUserRole) } : {}),
  },
  validateStatus: () => true,
});

const scenarios = [
  {
    key: 'booking_list',
    domain: 'booking',
    method: 'get',
    paths: ['/api/v1/bookings'],
    expectedStatuses: [200],
  },
  {
    key: 'booking_cart',
    domain: 'booking',
    method: 'get',
    paths: ['/api/v1/cart'],
    expectedStatuses: [200],
  },
  {
    key: 'payments_refund_validation',
    domain: 'payments',
    method: 'post',
    paths: ['/api/v1/payments/refunds'],
    body: {
      reason: 'perf-baseline-validation',
    },
    expectedStatuses: [400],
  },
  {
    key: 'payments_dispute_validation',
    domain: 'payments',
    method: 'post',
    paths: ['/api/v1/payments/disputes'],
    body: {
      paymentId: `missing-payment-${Date.now()}`,
    },
    expectedStatuses: [400, 404],
  },
];

async function requestWithFallbacks(scenario) {
  let lastResponse = null;

  for (const scenarioPath of scenario.paths) {
    const started = process.hrtime.bigint();
    try {
      const response = await client.request({
        method: scenario.method,
        url: scenarioPath,
        data: scenario.body,
      });
      const durationMs = Number(process.hrtime.bigint() - started) / 1_000_000;

      if (response.status !== 404) {
        return {
          scenario: scenario.key,
          domain: scenario.domain,
          path: scenarioPath,
          status: response.status,
          ok: scenario.expectedStatuses.includes(response.status),
          durationMs,
        };
      }

      lastResponse = {
        scenario: scenario.key,
        domain: scenario.domain,
        path: scenarioPath,
        status: response.status,
        ok: false,
        durationMs,
      };
    } catch (error) {
      const durationMs = Number(process.hrtime.bigint() - started) / 1_000_000;
      const code = typeof error?.code === 'string' ? error.code : 'REQUEST_ERROR';
      const status = code === 'ECONNABORTED' ? 408 : 599;

      return {
        scenario: scenario.key,
        domain: scenario.domain,
        path: scenarioPath,
        status,
        ok: false,
        durationMs,
      };
    }
  }

  return lastResponse;
}

async function runScenario(scenario) {
  const totalRequests = iterations * concurrency;
  const results = [];

  for (let i = 0; i < iterations; i += 1) {
    const batch = [];
    for (let c = 0; c < concurrency; c += 1) {
      batch.push(requestWithFallbacks(scenario));
    }

    const batchResults = await Promise.all(batch);
    results.push(...batchResults);
  }

  const durations = results.map((result) => result.durationMs).sort((a, b) => a - b);
  const successful = results.filter((result) => result.ok).length;
  const errorRate = totalRequests === 0 ? 0 : (totalRequests - successful) / totalRequests;

  return {
    key: scenario.key,
    domain: scenario.domain,
    requests: totalRequests,
    durationsMs: durations,
    successRate: totalRequests === 0 ? 0 : successful / totalRequests,
    errorRate,
    statusCounts: summarizeStatusCounts(results),
    latencyMs: {
      p50: percentile(durations, 0.5),
      p95: percentile(durations, 0.95),
      p99: percentile(durations, 0.99),
      max: durations.length === 0 ? 0 : Number(durations[durations.length - 1].toFixed(2)),
    },
  };
}

function summarizeStatusCounts(results) {
  const counts = {};
  for (const result of results) {
    const key = String(result.status);
    counts[key] = (counts[key] ?? 0) + 1;
  }
  return counts;
}

function percentile(sortedValues, p) {
  if (sortedValues.length === 0) {
    return 0;
  }

  const index = Math.min(sortedValues.length - 1, Math.max(0, Math.ceil(sortedValues.length * p) - 1));
  return Number(sortedValues[index].toFixed(2));
}

function summarizeDomain(domainKey, scenarioSummaries) {
  const scoped = scenarioSummaries.filter((summary) => summary.domain === domainKey);
  const durations = scoped
    .flatMap((summary) => summary.durationsMs)
    .sort((a, b) => a - b);

  const totalRequests = scoped.reduce((acc, summary) => acc + summary.requests, 0);
  const weightedErrors = scoped.reduce((acc, summary) => acc + summary.errorRate * summary.requests, 0);

  return {
    requests: totalRequests,
    errorRate: totalRequests === 0 ? 0 : Number((weightedErrors / totalRequests).toFixed(4)),
    latencyMs: {
      p95: percentile(durations, 0.95),
      p99: percentile(durations, 0.99),
    },
  };
}

function normalizeProfile(value) {
  const normalized = String(value ?? 'baseline').trim().toLowerCase();
  if (normalized === 'peak' || normalized === 'peak-season') {
    return 'peak-season';
  }
  return 'baseline';
}

function profileDefaults(profileKey) {
  if (profileKey === 'peak-season') {
    return {
      iterations: 40,
      concurrency: 10,
      timeoutMs: 45000,
      bookingP95BudgetMs: 1200,
      paymentsP95BudgetMs: 1800,
    };
  }

  return {
    iterations: 20,
    concurrency: 4,
    timeoutMs: 30000,
    bookingP95BudgetMs: 800,
    paymentsP95BudgetMs: 1200,
  };
}

(async () => {
  console.log(`Running booking/payments performance profile=${profile} against ${baseUrl}`);
  console.log(`Config: iterations=${iterations}, concurrency=${concurrency}, timeoutMs=${timeoutMs}`);

  const scenarioSummaries = [];
  for (const scenario of scenarios) {
    const summary = await runScenario(scenario);
    scenarioSummaries.push(summary);
    console.log(
      `${summary.key}: requests=${summary.requests}, successRate=${summary.successRate.toFixed(4)}, p95=${summary.latencyMs.p95}ms, p99=${summary.latencyMs.p99}ms`,
    );
  }

  const booking = summarizeDomain('booking', scenarioSummaries);
  const payments = summarizeDomain('payments', scenarioSummaries);

  const breaches = [];
  if (booking.latencyMs.p95 > bookingP95BudgetMs) {
    breaches.push(`booking p95 ${booking.latencyMs.p95}ms > budget ${bookingP95BudgetMs}ms`);
  }
  if (payments.latencyMs.p95 > paymentsP95BudgetMs) {
    breaches.push(`payments p95 ${payments.latencyMs.p95}ms > budget ${paymentsP95BudgetMs}ms`);
  }

  const report = {
    generatedAt: new Date().toISOString(),
    profile,
    baseUrl,
    config: {
      iterations,
      concurrency,
      timeoutMs,
      bookingP95BudgetMs,
      paymentsP95BudgetMs,
    },
    scenarios: scenarioSummaries.map(({ durationsMs, ...summary }) => summary),
    domains: {
      booking,
      payments,
    },
    slo: {
      pass: breaches.length === 0,
      breaches,
    },
  };

  const outputDir = path.resolve('artifacts', 'perf');
  await mkdir(outputDir, { recursive: true });
  const outputPath = path.join(outputDir, `booking-payments-${profile}-${Date.now()}.json`);
  await writeFile(outputPath, `${JSON.stringify(report, null, 2)}\n`, 'utf8');

  console.log(`Wrote baseline report to ${outputPath}`);
  if (breaches.length > 0) {
    console.warn(`SLO breaches: ${breaches.join('; ')}`);
    if (failOnBreach) {
      process.exit(1);
    }
  } else {
    console.log('SLO budgets met for this baseline run.');
  }
})();
