This file was added to trigger GitHub Actions when pushed.

To rerun the CI with the DB-fix verification step applied, push this commit:

```bash
git add ci-rerun-trigger.md
git commit -m "ci: trigger tests (rerun with DB-fix verification)"
git push origin YOUR_BRANCH
```

Or create an empty commit instead of modifying files:

```bash
git commit --allow-empty -m "ci: trigger tests (rerun with DB-fix verification)"
git push origin YOUR_BRANCH
```

If you'd like, I can instead provide a workflow_dispatch manual trigger step — tell me if you prefer that.
