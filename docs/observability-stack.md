# External Observability Stack

This document describes the external observability stack for Workation API runtime and how to verify it in staging/hosted environments.

## Stack Components
- Metrics source: `GET /api/v1/ops/metrics` (Prometheus text format)
- SLO API snapshot: `GET /api/v1/ops/slo-summary`
- Alerts API snapshot: `GET /api/v1/ops/alerts`
- Structured logs: JSON logs emitted by `ObservabilityMiddleware` with correlation fields (`requestId`, `traceId`)
- Dashboard template: `infra/observability/grafana/workation-slo-dashboard.json`
- Prometheus scrape template: `infra/observability/prometheus/workation-scrape.example.yml`

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
