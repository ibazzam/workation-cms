# API Contract & Versioning Policy

Date: 2026-02-21

## Scope

This policy applies to all product API endpoints served by NestJS under `/api/v1`.

## Compatibility Rules

1. Additive changes are allowed in `v1`:
   - adding optional response fields
   - adding new endpoints/resources
   - adding optional request fields
2. Breaking changes are not allowed in `v1`:
   - removing or renaming fields
   - changing field types
   - changing required/optional semantics
   - changing existing status code semantics
3. Any breaking change requires either:
   - a new versioned route (`/api/v2/...`), or
   - a temporary compatibility adapter with a deprecation window.

## PR Requirements

All API PRs must include:

- contract test updates or confirmation no contract changes occurred
- compatibility impact assessment (`none` / `additive` / `breaking`)
- migration/deprecation notes for client consumers

## CI Gate

The following checks are required before merge:

- `contract-tests`
- `smoke-tests`

These checks are defined in CI and enforced via branch protection.

## Error Shape Contract

Error payloads should remain stable and include:

- `code`
- `message`
- `details` (optional)

## Decommissioning Rule

Legacy Laravel product API routes under `/api/*` are decommissioned and must not be reintroduced. Product API changes must be implemented in NestJS (`infra/backend`) only.
