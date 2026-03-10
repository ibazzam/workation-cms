# Incident Runbooks: Weather Disruptions and Provider Outages

This document defines response playbooks for two high-risk operational incident classes:
- weather and sea-state disruptions
- external provider outages (payments, transport operators, upstream APIs)

## Common Incident Flow
1. Detect and classify severity (`SEV-1`, `SEV-2`, `SEV-3`).
2. Open incident timeline and assign commander.
3. Stabilize user-facing impact first (safe failures, clear status updates).
4. Execute domain-specific mitigation checklist.
5. Confirm recovery and monitor for regression.
6. Publish incident summary and follow-up corrective actions.

## Severity Definitions
- `SEV-1`: Critical outage, high booking/payment failure rate, broad customer impact.
- `SEV-2`: Significant degradation with workarounds available.
- `SEV-3`: Localized issue with low customer impact.

## Runbook A: Weather/Service Disruption
### Trigger Signals
- Spike in transport disruption events (`CANCELLED`, `WEATHER_CANCELLED`).
- Elevated re-accommodation attempts for transport bookings.
- Partner weather advisories impacting routes/islands.

### Immediate Actions
1. Confirm disruption scope (`routes`, `islands`, `operator`, `time window`).
2. Record disruption via transport admin endpoint:
   - `POST /api/v1/transports/admin/:id/disruptions`
3. Attempt re-accommodation for affected bookings:
   - `POST /api/v1/transports/admin/:id/disruptions/:disruptionId/reaccommodate`
4. Publish internal status note for support and vendor operations.
5. If no replacement path exists, queue proactive customer communication and credits/refund guidance.

### Recovery Actions
1. Resolve disruption when service resumes:
   - `PATCH /api/v1/transports/admin/:id/disruptions/:disruptionId/resolve`
2. Verify booking state consistency (`HOLD`, `CONFIRMED`, `CANCELLED`).
3. Validate refunds/credits queues for impacted users.
4. Capture incident metrics: disruption duration, impacted bookings, re-accommodation success rate.

## Runbook B: Provider Outage
### Trigger Signals
- Payment provider health endpoint failures.
- Reconciliation/job queues rising (`RETRYABLE`, `DEAD`).
- Elevated provider-specific API timeout/error rates.

### Immediate Actions
1. Identify provider and blast radius (`BML`, `MIB`, transport vendor API, other upstreams).
2. Check provider health and queue health endpoints:
   - `GET /api/v1/payments/admin/bml/health`
   - `GET /api/v1/payments/admin/mib/health`
   - `GET /api/v1/payments/admin/jobs/health`
3. Enable degraded mode guidance:
   - keep checkout safe-failing instead of partial writes
   - pause non-essential retries if they amplify failure
4. Trigger targeted reconciliation when provider recovers:
   - `POST /api/v1/payments/admin/reconcile/pending`

### Recovery Actions
1. Requeue or complete stranded jobs as needed:
   - `POST /api/v1/payments/admin/jobs/:id/requeue`
2. Confirm alert status returns to normal:
   - `GET /api/v1/payments/admin/alerts`
   - `GET /api/v1/ops/alerts`
3. Validate settlement/reporting integrity for affected window.
4. Publish resolution summary with root cause and remediation items.

## Communication Checklist
- Initial incident message posted within 10 minutes for `SEV-1/2`.
- Customer-facing updates at defined intervals (30-60 minutes depending on severity).
- Final closure message with impact summary and compensation/refund notes when relevant.

## Operational Ownership
- Incident commander: SRE/Platform on-call.
- Domain owner: Payments or Transport lead depending on incident class.
- Support owner: Customer care lead for outbound communication and case management.

## Environment Linkage
Expose these links through `/api/v1/ops/runbooks` by setting:
- `OPS_RUNBOOK_WEATHER_URL`
- `OPS_RUNBOOK_PROVIDER_OUTAGE_URL`
- `OPS_RUNBOOK_ONCALL_URL`
- `OPS_RUNBOOK_INCIDENT_URL`
- `OPS_RUNBOOK_PAYMENTS_URL`# Incident Runbooks: Weather Disruptions and Provider Outages

This document defines response playbooks for the two high-risk Maldives operational incident classes:
- weather and sea-state disruptions
- external provider outages (payments, transport operators, upstream APIs)

## Common Incident Flow
1. Detect and classify severity (`SEV-1`, `SEV-2`, `SEV-3`).
2. Open incident timeline and assign commander.
3. Stabilize user-facing impact first (safe failures, clear status updates).
4. Execute domain-specific mitigation checklist.
5. Confirm recovery and monitor for regression.
6. Publish incident summary and follow-up corrective actions.

## Severity Definitions
- `SEV-1`: Critical outage, high booking/payment failure rate, broad customer impact.
- `SEV-2`: Significant degradation with workarounds available.
- `SEV-3`: Localized issue with low customer impact.

## Runbook A: Weather/Service Disruption
### Trigger Signals
- Spike in transport disruption events (`CANCELLED`, `WEATHER_CANCELLED`).
- Elevated re-accommodation attempts for transport bookings.
- Partner weather advisories impacting routes/islands.

### Immediate Actions
1. Confirm disruption scope (`routes`, `islands`, `operator`, `time window`).
2. Record disruption via transport admin endpoint:
   - `POST /api/v1/transports/admin/:id/disruptions`
3. Attempt re-accommodation for affected bookings:
   - `POST /api/v1/transports/admin/:id/disruptions/:disruptionId/reaccommodate`
4. Publish internal status note for support and vendor operations.
5. If no replacement path exists, queue proactive customer communication and credits/refund guidance.

### Recovery Actions
1. Resolve disruption when service resumes:
   - `PATCH /api/v1/transports/admin/:id/disruptions/:disruptionId/resolve`
2. Verify booking state consistency (`HOLD`, `CONFIRMED`, `CANCELLED`).
3. Validate refunds/credits queues for impacted users.
4. Capture incident metrics: disruption duration, impacted bookings, re-accommodation success rate.

## Runbook B: Provider Outage
### Trigger Signals
- Payment provider health endpoint failures.
- Reconciliation/job queues rising (`RETRYABLE`, `DEAD`).
- Elevated provider-specific API timeout/error rates.

### Immediate Actions
1. Identify provider and blast radius (`BML`, `MIB`, transport vendor API, other upstreams).
2. Check provider health and queue health endpoints:
   - `GET /api/v1/payments/admin/bml/health`
   - `GET /api/v1/payments/admin/mib/health`
   - `GET /api/v1/payments/admin/jobs/health`
3. Enable degraded mode guidance:
   - keep checkout safe-failing instead of partial writes
   - pause non-essential retries if they amplify failure
4. Trigger targeted reconciliation when provider recovers:
   - `POST /api/v1/payments/admin/reconcile/pending`

### Recovery Actions
1. Requeue or complete stranded jobs as needed:
   - `POST /api/v1/payments/admin/jobs/:id/requeue`
2. Confirm alert status returns to normal:
   - `GET /api/v1/payments/admin/alerts`
   - `GET /api/v1/ops/alerts`
3. Validate settlement/reporting integrity for affected window.
4. Publish resolution summary with root cause and remediation items.

## Communication Checklist
- Initial incident message posted within 10 minutes for `SEV-1/2`.
- Customer-facing updates at defined intervals (30-60 minutes depending on severity).
- Final closure message with impact summary and compensation/refund notes when relevant.

## Operational Ownership
- Incident commander: SRE/Platform on-call.
- Domain owner: Payments or Transport lead depending on incident class.
- Support owner: Customer care lead for outbound communication and case management.

## Environment Linkage
Expose these links through `/api/v1/ops/runbooks` by setting:
- `OPS_RUNBOOK_WEATHER_URL`
- `OPS_RUNBOOK_PROVIDER_OUTAGE_URL`
- `OPS_RUNBOOK_ONCALL_URL`
- `OPS_RUNBOOK_INCIDENT_URL`
- `OPS_RUNBOOK_PAYMENTS_URL`
