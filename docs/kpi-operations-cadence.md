# KPI Operations Cadence (Launch)

This runbook operationalizes KPI review and action ownership for launch.

## Scope
- Conversion funnel by atoll
- Route completion rate
- Failed checkout reasons

## Production Topology Context
- Frontend web: Laravel Cloud (`www.workation.mv`)
- API: Render (`api.workation.mv`)
- Database: Neon PostgreSQL
- Media: S3 (+ Cloudflare edge)
- Blog: same web domain path (`/blog`) unless organization or security boundaries require separation

## Core SLO Targets (Start Here)
- Availability SLO:
  - Web and API monthly uptime target: `99.9%`
- API latency SLO:
  - Read-heavy endpoints (`GET` search/list/detail): `p95 < 350ms`
  - Write-heavy endpoints (`POST/PUT/PATCH/DELETE`): `p95 < 700ms`
- Error budget:
  - `5xx < 0.5%` over any 5-minute rolling window
- Database responsiveness:
  - Top 20 critical query patterns `p95 < 120ms`
  - Slow query triage threshold: `>= 300ms`
- Queue health:
  - Critical queue delay `< 30s`
  - Standard queue delay `< 2m`
- Cache efficiency:
  - Cloudflare edge hit ratio for static/media `> 85%`
  - Application cache hit ratio for read-heavy pages `> 70%`

## Dashboard Ownership
- Dashboard artifact: `infra/observability/grafana/workation-launch-kpi-dashboard.json`
- Primary owner: Product Analytics Lead
- Secondary owner: SRE / Platform Lead
- Incident liaison: Operations Lead

## Daily KPI Ritual
- Cadence: daily at 09:30 local time (launch window)
- Duration: 20 minutes
- Participants:
  - Product Analytics Lead
  - Operations Lead
  - Checkout Engineer
  - SRE / Platform representative
- Agenda:
  1. Review prior 24h KPI trend deltas
  2. Identify top funnel drop-off by atoll
  3. Review route completion variance and disruption correlation
  4. Review failed checkout reason distribution and top regression reason
  5. Assign actions and target completion dates

## KPI Alert Thresholds (Launch)
- Search-to-confirm conversion regression trigger:
  - >10% relative drop day-over-day in top atolls
- Route completion trigger:
  - below 95% daily
- Failed checkout trigger:
  - above 5% daily
- Unknown reason-code trigger:
  - above 1% of failed checkouts

## Weekly Action Loop
- Cadence: weekly on Monday 10:00 local time
- Inputs:
  - daily KPI review notes
  - incident records from prior week
  - top failed-checkout reasons by frequency and impact
- Required outputs:
  - prioritized remediation list (top 3)
  - owner + ETA for each remediation
  - risk/impact note for launch command thread

## Weekly and Monthly Ops Checklist
### Weekly (mandatory)
- Review top 20 slow SQL statements from Neon query insights.
- Review API `p95` latency and `5xx` spikes by endpoint family.
- Review queue depth, retry rate, and oldest-job age.
- Review Cloudflare edge hit ratio and origin egress trend.
- Review S3 growth trend and candidate orphaned media list.
- Confirm cache invalidation behavior after content publish/update.

### Monthly (mandatory)
- Run SQL index audit against top filter/sort/search endpoints.
- Archive old high-volume operational rows from hot tables.
- Run restore drill on Neon branch/PITR and verify application health.
- Run pre-peak load test profile and compare against baseline SLOs.
- Validate Cloudflare WAF/rate-limit thresholds against current traffic.
- Review retention windows (session/token/temp/log/audit) with product + compliance owners.

## SQL Index Review Template
Use this template for each high-traffic endpoint query path.

| Endpoint | Query Pattern | Filters | Sort | Current Index | p95(ms) | Rows Scanned | Rows Returned | Candidate Index | Owner | ETA |
|---|---|---|---|---|---:|---:|---:|---|---|---|
| `/api/v1/...` | `SELECT ...` | `status, atoll_id` | `created_at DESC` | `idx_old` | 0 | 0 | 0 | `(status, atoll_id, created_at DESC)` | TBD | TBD |

Index review rules:
- Every high-volume `WHERE + ORDER BY` path must have a measured index strategy.
- Avoid unbounded scans; enforce pagination and practical limits.
- Re-check execution plans after major data growth milestones.
- Prioritize indexes based on measured impact, not assumptions.

## Action Ownership Matrix
- Funnel conversion anomalies: Product Analytics Lead
- Route completion degradation: Operations Lead + Transport/Checkout owners
- Failed checkout regressions: Checkout Engineer
- Unknown reason code spikes: Backend Platform Lead

## Evidence and Logging
- Daily notes location: `docs/kpi-daily-review-log.md`
- Weekly summary location: `docs/kpi-weekly-action-log.md`
- Supporting KPI definitions: `docs/kpi-instrumentation-framework.md`

## Definition of Active
KPI operations are considered active when:
- Daily ritual has named role attendance and notes logged.
- Weekly action loop has at least one completed cycle with owners and ETAs.
- Any threshold breach is reflected in an assigned remediation item.
