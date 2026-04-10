# External Observability Stack

This document describes the external observability stack for Workation API runtime and how to verify it in staging/hosted environments.

## Stack Components
- Metrics source: `GET /api/v1/ops/metrics` (Prometheus text format)
- SLO API snapshot: `GET /api/v1/ops/slo-summary`
- Alerts API snapshot: `GET /api/v1/ops/alerts`
- Queue SLO snapshot in alerts payload (`queueSlo`) and queue metrics in `/api/v1/ops/metrics`
- Structured logs: JSON logs emitted by `ObservabilityMiddleware` with correlation fields (`requestId`, `traceId`)
- Dashboard template: `infra/observability/grafana/workation-slo-dashboard.json`
- Launch KPI dashboard template: `infra/observability/grafana/workation-launch-kpi-dashboard.json`
- Prometheus scrape template: `infra/observability/prometheus/workation-scrape.example.yml`
- KPI definition and event contract: `docs/kpi-instrumentation-framework.md`

## SLO Target Snapshot
Operational targets used for alert calibration and readiness gates:

- Availability:
   - web/API monthly target: `99.9%`
- API latency:
   - read endpoints (`GET`) `p95 < 350ms`
   - write endpoints `p95 < 700ms`
- Error rate:
   - `5xx < 0.5%` in any rolling 5-minute window
- Database performance:
   - top query families `p95 < 120ms`
   - slow-query triage threshold `>= 300ms`
- Queue delay:
   - critical `< 30s`, standard `< 2m`

## Tracing Correlation
The request middleware now propagates and emits trace context:
- Accepts inbound `traceparent` or `x-trace-id`
- Falls back to generated request id when trace id is absent
- Returns both `x-request-id` and `x-trace-id` headers
- Includes `traceId` in structured request logs for cross-system correlation

## Deployment Wiring
1. Configure Prometheus using `infra/observability/prometheus/workation-scrape.example.yml`.
2. Import Grafana dashboard JSON from `infra/observability/grafana/workation-slo-dashboard.json`.
3. Ensure backend runbook env vars are set if needed:
   - `OPS_RUNBOOK_ONCALL_URL`
   - `OPS_RUNBOOK_INCIDENT_URL`
   - `OPS_RUNBOOK_PAYMENTS_URL`
   - `OPS_RUNBOOK_WEATHER_URL`
   - `OPS_RUNBOOK_PROVIDER_OUTAGE_URL`
4. Verify metrics endpoint is reachable from monitoring infrastructure.

## Queue SLO and Alert Routing Configuration
Queue SLO checks are derived from queue-related admin traffic in the SLO window.

Queue thresholds:
- `OPS_ALERT_QUEUE_MIN_SAMPLE_SIZE` (default `20`)
- `OPS_ALERT_QUEUE_MAX_ERROR_RATE` (default `0.08`)
- `OPS_ALERT_QUEUE_MAX_P95_MS` (default `1500`)

Automated alert routing channels:
- Pager:
   - `OPS_ALERT_ROUTE_PAGER_ENABLED`
   - `OPS_ALERT_ROUTE_PAGER_TARGET`
   - `OPS_ALERT_ROUTE_PAGER_KEYS`
- Slack:
   - `OPS_ALERT_ROUTE_SLACK_ENABLED`
   - `OPS_ALERT_ROUTE_SLACK_TARGET`
   - `OPS_ALERT_ROUTE_SLACK_KEYS`
- Email:
   - `OPS_ALERT_ROUTE_EMAIL_ENABLED`
   - `OPS_ALERT_ROUTE_EMAIL_TARGET`
   - `OPS_ALERT_ROUTE_EMAIL_KEYS`

`*_KEYS` variables accept comma-separated alert keys. If unset, defaults are applied by channel.

## Verification Commands
From repository root:

```powershell
curl.exe -sS https://api.workation.mv/api/v1/ops/metrics
curl.exe -sS -H "Authorization: Bearer <jwt>" https://api.workation.mv/api/v1/ops/slo-summary
```

Optional trace-correlation probe:

```powershell
curl.exe -sS -D - -o NUL https://api.workation.mv/api/v1/health -H "traceparent: 00-0123456789abcdef0123456789abcdef-0123456789abcdef-01"
```

Expected:
- Response includes `x-request-id` and `x-trace-id`
- `x-trace-id` should match trace id from `traceparent`

## Peak Season Load Baseline
Use the performance harness with the `peak-season` profile to validate booking/payments critical paths under heavier concurrency.

PowerShell example:

```powershell
$env:BASE_URL = "https://api.workation.mv"
$env:AUTH_BEARER_TOKEN = "<jwt>"
$env:PERF_PROFILE = "peak-season"
npm run perf:peak-season
```

Output artifact:
- `artifacts/perf/booking-payments-peak-season-<timestamp>.json`

## Pre-Peak Load Test Checklist
Run 2-3 weeks before known high-demand windows.

1. Preparation
   - Freeze risky schema changes for test window.
   - Capture baseline (`p50/p95/p99`, error rate, queue delay, DB metrics).
   - Confirm representative traffic mix (search, listing, booking, checkout, blog/public pages).
2. Execution
   - Run at `1x`, `2x`, and `3x` expected peak.
   - Include write-heavy flows and webhook/background activity.
   - Record Cloudflare edge hit ratio and origin saturation signals.
3. Pass criteria
   - SLO targets hold at least at `2x` expected peak.
   - No sustained queue backlog growth.
   - No sustained DB connection saturation.
4. Recovery and closeout
   - Verify service returns to baseline within 15 minutes after load stop.
   - Log bottlenecks, owner, fix ETA, and re-test date.
