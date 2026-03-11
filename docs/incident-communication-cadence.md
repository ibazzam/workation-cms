# Incident Communication Cadence (Launch)

This runbook defines communication timing and ownership by severity during launch and hypercare.

## Communication Owners
- Incident Commander: operational updates and decision calls
- Communications Owner: stakeholder/customer-facing updates
- Support Lead: customer support queue updates and macro alignment

## Severity Cadence
- SEV-1
  - First internal update: within 10 minutes
  - Internal cadence: every 15 minutes
  - External/status-page cadence: every 30 minutes or major state change
- SEV-2
  - First internal update: within 20 minutes
  - Internal cadence: every 30 minutes
  - External/status-page cadence: every 60 minutes
- SEV-3
  - First internal update: within 60 minutes
  - Internal cadence: every 2 hours
  - External/status-page cadence: as needed

## Required Update Fields
- Incident status (`investigating`, `identified`, `monitoring`, `resolved`)
- Customer impact scope
- Current mitigation actions
- Next checkpoint timestamp
- Owner/accountability for next action

## Launch-Day Channels
- Internal command bridge: realtime incident coordination
- Ops channel: technical updates and mitigation progress
- Support channel: customer impact and ticket guidance
- External status channel/page: customer-facing updates

## Closure Standard
- Publish final resolution summary with:
  - timeline
  - impact
  - remediation
  - follow-up items and owners
