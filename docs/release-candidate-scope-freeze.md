# Release Candidate Scope Freeze

This document defines scope freeze controls for launch execution.

## Current Release Candidate
- Tag: `rc-2026-03-11-launch`
- Base branch: `main`
- Purpose: launch execution rehearsal and go/no-go readiness validation

## Freeze Rules
- Only `P0` launch blockers and `P1` verified regressions are allowed after RC tag creation.
- No new feature work enters release scope without explicit go/no-go owner approval.
- Any approved post-RC change must include:
  - linked incident or blocker reference
  - rollback plan
  - verification evidence in PR description

## Change Control
- Required approvers: Release Manager + Operations Lead.
- Required CI gates: full required checks green before merge.
- Required audit trail: PR must reference RC tag and reason for exception.

## Daily RC Review Checklist
- Confirm no unapproved scope additions merged since last review.
- Review open launch blockers and assign owner/ETA.
- Confirm rollback path remains valid for all RC-included changes.
- Update launch command thread with summary and risks.

## Exit Conditions
- All launch blocker items closed or explicitly accepted in go/no-go record.
- Rehearsal evidence set is complete and linked.
- Go/no-go decision captured with release owner signoff.
