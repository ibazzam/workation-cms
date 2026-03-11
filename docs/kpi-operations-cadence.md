# KPI Operations Cadence (Launch)

This runbook operationalizes KPI review and action ownership for launch.

## Scope
- Conversion funnel by atoll
- Route completion rate
- Failed checkout reasons

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
