# Scripts usage

This folder contains helper scripts to fetch the GitHub Actions `coverage` artifact, extract the v8 `coverage-final.json`, compute a resource-scoped coverage summary for `resources/js/**`, and (optionally) commit the summary to the `coverage-reports` branch.

Files
- `fetch_coverage_artifact.ps1` — PowerShell script that polls the Actions API, downloads the `coverage` artifact, and extracts `coverage-final.json` into the repository root.
- `compute_coverage_summary.cjs` — Node (CommonJS) script that reads a v8 `coverage-final.json` and writes a compact `coverage-summary.json` containing totals and percent for statements/functions/branches across `resources/js`.
- `compute_coverage_summary.js` — alternate (ESM) version (kept for convenience).
- `retrieve_and_compute.ps1` — wrapper that runs the fetcher, locates the downloaded coverage JSON, runs the Node summary generator, and can commit the result to `coverage-reports`.

Quick examples

1) Fetch artifact only (PowerShell):
```powershell
$env:GITHUB_TOKEN = '<PAT>'
.\scripts\fetch_coverage_artifact.ps1 -Owner ibazzam -Repo workation-cms -Workflow phpunit.yml -Branch main
```

2) Compute summary from an existing coverage file (Node):
```powershell
# use the CommonJS script if package.json has "type": "module"
node .\scripts\compute_coverage_summary.cjs coverage-final.json coverage-summary.json
```

3) Full retrieval + compute (and optionally commit):
```powershell
$env:GITHUB_TOKEN = '<PAT>'
.\scripts\retrieve_and_compute.ps1 -Owner ibazzam -Repo workation-cms
# to also commit to branch 'coverage-reports':
.\scripts\retrieve_and_compute.ps1 -Owner ibazzam -Repo workation-cms -Commit
```

Notes
- The scripts require a GitHub PAT stored in the current session as `GITHUB_TOKEN`. The token needs appropriate scopes to read Actions and repo contents (typically `repo` and `actions:read`).
- On Windows you may need to temporarily bypass execution policy: `Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass -Force`.
- The computed summary is written by default to `coverage-summary-ci.json`. When using `-Commit` the file is copied to `coverage-reports/` and pushed to `origin/coverage-reports`.
- Keep PATs secret — revoke any tokens accidentally exposed.