# KPI Instrumentation Framework

This document defines launch KPIs and instrumentation requirements for business performance tracking.

## Scope
The KPI baseline covers three launch-readiness metrics:
- Conversion funnel by atoll
- Route completion rate
- Failed checkout reason distribution

## KPI Definitions

### 1) Conversion Funnel by Atoll
Track customer progression through key booking stages grouped by atoll.

Canonical stages:
1. `search_viewed`
2. `availability_viewed`
3. `quote_requested`
4. `itinerary_hold_created`
5. `checkout_started`
6. `payment_authorized`
7. `booking_confirmed`

Primary KPI outputs:
- Stage-to-stage conversion percentage by atoll
- End-to-end search-to-confirm conversion by atoll
- Funnel drop-off point distribution by atoll

### 2) Route Completion Rate
Track successful completion of dependency-aware itinerary routes.

Definition:
- A route is complete when all required itinerary legs are confirmed within hold/checkout constraints.

Primary KPI outputs:
- Route completion rate: `completed_routes / attempted_routes`
- Partial-route failure rate
- Disruption-linked route failure rate

### 3) Failed Checkout Reasons
Track normalized failure reasons for checkout attempts.

Required reason taxonomy:
- `inventory_unavailable`
- `fare_changed`
- `hold_expired`
- `payment_declined`
- `payment_timeout`
- `dependency_mismatch`
- `validation_error`
- `provider_unavailable`
- `unknown_error`

Primary KPI outputs:
- Failed checkout count by reason
- Failed checkout rate by reason
- Failed checkout reasons split by atoll, service type, and payment provider

## Instrumentation Event Contract
All KPI events should include:
- `event_name`
- `event_time_utc`
- `request_id`
- `trace_id`
- `user_type` (`guest`, `authenticated`, `admin`)
- `atoll_code` (if known)
- `island_id` (if known)
- `service_domain` (`accommodation`, `transport`, `activity`, `restaurant`, `resort_day_visit`, `remote_work`, `vehicle_rental`)
- `booking_id` / `hold_id` / `checkout_id` where relevant

Failure events must include:
- `failure_reason_code`
- `failure_step`
- `provider_code` (if applicable)

## Data Freshness and Ownership
- Refresh target: every 15 minutes for launch dashboard views.
- Data quality checks:
  - event volume anomaly detection
  - required-field completeness checks
  - reason-code unknown/null threshold alerting
- Ownership:
  - Product Analytics: KPI definitions and dashboard governance
  - Backend Platform: event emission and schema compatibility
  - Operations: action playbooks for threshold breaches

## Launch Thresholds (Initial Targets)
- Search-to-confirm conversion by top atolls: non-degrading trend week-over-week
- Route completion rate: >= 95%
- Failed checkout rate: <= 5%
- Unknown failed-checkout reason share: <= 1%

## Review Cadence
- Daily launch standup: KPI trend review and incident correlation
- Weekly: funnel and failure root-cause prioritization
- Monthly: taxonomy and threshold recalibration
