# Branch Protection (GitHub)

Use this checklist to enforce CI quality gates before merges to `main`.

## Branch rule

1. Go to GitHub repository settings.
2. Open **Settings → Branches → Add rule**.
3. Branch name pattern: `main`.

## Required settings

Enable:

- **Require a pull request before merging**
- **Require status checks to pass before merging**
- **Require branches to be up to date before merging**
- **Do not allow bypassing the above settings** (recommended for strict enforcement)

## Required status checks

From workflow `.github/workflows/phpunit.yml`, require these checks:

- `Contract (workations)`
- `Contract (auth)`
- `Contract (auth-strict)`
- `Contract (countries)`
- `Contract (islands)`
- `Contract (service-categories)`
- `Contract (vendors)`
- `Contract (accommodations)`
- `Contract (transports)`
- `Contract (bookings)`
- `Contract (payments)`
- `Contract (admin-settings)`
- `Contract (permissions-matrix)`
- `Contract (feature-flags)`
- `Smoke (payments)`

Optionally also require:

- `PHPUnit + Build`
- `JS Coverage`

## Notes

- Contract checks run in matrix mode; all listed suites should be required to prevent partial regressions.
- Smoke check validates seeded payment-intent flow (`201`) with real DB-backed execution.
- If job names change in workflow YAML, update this list accordingly.
