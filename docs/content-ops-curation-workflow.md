# Content Ops Workflow: Island and Service Quality Curation

This workflow defines how content operations curate island and service listings before and after launch.

## Objectives
- Keep destination and service content accurate, current, and trustworthy.
- Ensure customer-facing quality consistency across accommodations, transport, activities, dining, resort day visits, remote work, and vehicle rentals.
- Reduce booking friction caused by stale or incomplete content.

## Workflow Stages
1. Intake and triage
2. Metadata completeness review
3. Quality curation and editorial normalization
4. Trust and safety review
5. Publish decision and QA signoff
6. Ongoing monitoring and recertification

## 1) Intake and Triage
- Source intake from vendor onboarding, vendor updates, support escalations, or quality audits.
- Assign a curation priority:
  - `P0`: critical customer-impacting inaccuracies
  - `P1`: launch-blocking completeness gaps
  - `P2`: enhancement-level improvements
- Exit criteria:
  - Owner assigned
  - SLA target assigned based on priority

## 2) Metadata Completeness Review
- Validate required listing fields:
  - title/name, location, category/type, pricing baseline, availability windows
  - policy fields (cancellation/refund/no-show where applicable)
  - media minimums and summary descriptions
- Validate island metadata quality:
  - facilities, connectivity, emergency-services notes
- Exit criteria:
  - Required fields complete
  - Missing-field remediation queue resolved

## 3) Quality Curation and Editorial Normalization
- Normalize naming, formatting, and taxonomy usage.
- Remove duplicate/low-signal descriptions.
- Enforce media quality baseline and ordering.
- Apply consistency checks for route and dependency-aware planning contexts.
- Exit criteria:
  - Editorial standards pass
  - Taxonomy/category alignment pass

## 4) Trust and Safety Review
- Verify policy claims and prohibited content guardrails.
- Validate social/UGC linked assets against existing trust controls.
- Route suspicious or high-risk entries to moderation queue.
- Exit criteria:
  - Trust/safety status set
  - Escalations resolved or explicitly tracked

## 5) Publish Decision and QA Signoff
- Publish states:
  - `APPROVED`: visible to customers
  - `NEEDS_CHANGES`: vendor revision required
  - `HOLD`: blocked pending trust/safety or policy issues
- Perform final QA spot checks on customer-facing views.
- Exit criteria:
  - Publish state recorded
  - QA signoff recorded with reviewer and timestamp

## 6) Ongoing Monitoring and Recertification
- Run periodic recertification (recommended: every 30 days for high-volume listings, 90 days otherwise).
- Trigger fast-path recertification after major incidents, repeated complaints, or high cancellation anomalies.
- Track drift metrics and remediation throughput.

## Curation SLA Targets
- `P0` critical correction: 4 hours
- `P1` launch-blocking correction: 1 business day
- `P2` enhancement correction: 3 business days

## Operational Roles
- Content Ops Lead: owns policy and final QA signoff
- Curation Specialist: executes review and remediation tasks
- Trust/Safety Reviewer: validates risk and moderation outcomes
- Vendor Manager: coordinates vendor-side fixes and evidence

## Success Metrics
- Listing completeness rate by domain
- Curation cycle time by priority
- Post-publish complaint rate
- Rework rate after first QA pass
- Recertification compliance rate
