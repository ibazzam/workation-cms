# Launch Week On-Call Schedule

This schedule defines role coverage and backup routing for launch week.

## Coverage Window
- Start: D0 00:00 local time
- End: D7 23:59 local time

## Role-Based Coverage
- Incident Commander On-Call
  - Primary: Operations Lead
  - Backup: Engineering Manager
- Platform/SRE On-Call
  - Primary: SRE / Platform Lead
  - Backup: Senior Backend Engineer
- Checkout/Payments On-Call
  - Primary: Checkout Engineer
  - Backup: Payments Engineer
- Customer Support On-Call
  - Primary: Support Lead
  - Backup: Senior Support Specialist

## Shift Cadence
- Standard shifts:
  - Shift A: 00:00-08:00
  - Shift B: 08:00-16:00
  - Shift C: 16:00-00:00
- Handoff requirements:
  - Open incidents and risk summary
  - Current KPI threshold breaches
  - Pending rollback-sensitive changes

## Escalation Rules
- Any SEV-1 incident pages Incident Commander + Platform/SRE immediately.
- Checkout/payments incident pages Checkout/Payments owner and Incident Commander.
- Customer-impact incidents require Support On-Call and Communications Owner in bridge.

## Contact Confirmation Checklist
- [x] Role-based primary/backup map published
- [ ] Named individuals and active handles confirmed in launch command channel
- [ ] Pager/slack/email target subscriptions confirmed for each role

## References
- `docs/launch-support-escalation-roster.md`
- `docs/go-no-go-rehearsal-2026-03-18.md`
- `docs/alert-routing-verification-2026-03-18.md`
