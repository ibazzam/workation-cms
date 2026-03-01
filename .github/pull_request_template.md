## Summary

- What changed:
- Why it changed:

## API Compatibility Impact

- [ ] No API contract changes
- [ ] Additive API changes only (backward compatible)
- [ ] Breaking API changes (requires `/api/v2` plan or compatibility adapter + deprecation timeline)
- [ ] Reviewed policy: `docs/api-contract-versioning-policy.md`

## Validation

- [ ] Ran relevant local tests
- [ ] No unrelated files changed
- [ ] Docs updated (if behavior/config changed)

## Required CI Checks

Before requesting review, confirm these checks are green:

- [ ] `contract-tests` workflow job(s)
- [ ] `smoke-tests` workflow job(s)

## Payments-specific (when touched)

- [ ] Provider + currency combinations affected are covered (`USD`/`MVR`, `BML`/`MIB`/`STRIPE`)
- [ ] Webhook signature behavior unchanged or documented
- [ ] Any new env vars added to examples/docs

## Notes for Reviewers

- Risk areas:
- Rollback plan:
