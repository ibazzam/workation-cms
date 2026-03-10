# Vendor Onboarding Workflow and SLA Contracts

This document defines the launch-ready onboarding workflow for new vendors and the standard SLA contract expectations for Maldives operations.

## Onboarding Stages
1. Intake and qualification
2. Compliance and identity verification
3. Catalog and pricing setup
4. Technical integration and sandbox verification
5. Operational readiness review
6. Go-live approval and hypercare

## Stage Gates
### 1) Intake and Qualification
- Collect business profile, primary contacts, service categories, and island coverage.
- Confirm target vertical: accommodation, transport, excursions, restaurants, resort day visits, remote work, or vehicle rentals.
- Exit criteria:
  - Vendor profile exists in platform
  - Business scope accepted by category owner

### 2) Compliance and Identity Verification
- Validate legal entity details and authorized signatory.
- Verify payout details and finance ownership contacts.
- Confirm policy acceptance (cancellation/refund/no-show handling).
- Exit criteria:
  - Compliance checklist complete
  - Contract signatory approved

### 3) Catalog and Pricing Setup
- Configure core inventory and availability windows.
- Validate tax/fee policies and customer-facing terms.
- Confirm seasonal pricing and blackout windows where applicable.
- Exit criteria:
  - Inventory and pricing reviewed by content ops
  - Public listing quality baseline passed

### 4) Technical Integration and Sandbox Verification
- Verify admin/vendor API paths and auth scope.
- Validate critical workflows in staging:
  - listing and detail retrieval
  - quote flows and availability windows
  - booking/payment interactions for applicable domain
- Exit criteria:
  - Integration smoke pass
  - Incident/runbook links shared with vendor operations

### 5) Operational Readiness Review
- Confirm support contacts and escalation channels.
- Confirm service recovery expectations and disruption handling playbooks.
- Complete SLA review and signoff.
- Exit criteria:
  - SLA contract signed
  - Escalation matrix in place

### 6) Go-Live Approval and Hypercare
- Enable production visibility for approved listings.
- Monitor first-week KPIs and incidents daily.
- Run a 7-day hypercare checkpoint and close onboarding.
- Exit criteria:
  - No critical incidents in hypercare window
  - Vendor moved to steady-state operations

## SLA Contract Baseline

### Availability and Responsiveness
- API/system availability target: 99.9% monthly for critical booking/payment surfaces.
- Incident acknowledgement:
  - SEV-1: 15 minutes
  - SEV-2: 30 minutes
  - SEV-3: 4 hours
- Status update cadence:
  - SEV-1: every 30 minutes
  - SEV-2: every 60 minutes

### Booking and Operations
- Booking confirmation latency target: < 2 minutes for standard flow.
- Re-accommodation response for disruptions: first action within 30 minutes.
- Refund processing target: initiated within 2 business days when approved.

### Data and Reporting
- Daily settlement/reporting feed availability for finance operations.
- Reconciliation exception backlog reviewed every business day.
- Required retention of contract and operational records per legal/compliance policy.

### Quality and Escalation
- Listing/content correction SLA: 1 business day for critical inaccuracies.
- Escalation path:
  - Tier 1: vendor operations contact
  - Tier 2: platform operations lead
  - Tier 3: duty manager / executive escalation

## Required Onboarding Artifacts
- Signed SLA contract and commercial terms
- Verified support/escalation contacts
- Service policy mappings (cancellation, refund, no-show)
- Integration smoke evidence from staging
- Go-live approval checklist

## KPI Tracking for First 30 Days
- Booking success rate by vendor
- Cancellation/refund turnaround times
- Response times for incident escalations
- Customer complaint volume and resolution times
- Reconciliation exceptions and settlement lag
