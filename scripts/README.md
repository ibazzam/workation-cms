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

Artifact/log cleanup automation
------------------------------

To avoid stale local log dumps and run artifacts, use:

```powershell
# preview (no deletion)
npm run cleanup:artifacts:dry-run

# execute deletion
npm run cleanup:artifacts
```

Retention defaults in `scripts/cleanup_workspace_artifacts.cjs`:
- backend test logs: 7 days
- temporary run directories (`artifacts_run_*`, `run-*-logs`, `actions-logs-`, `tmp-job-log`): 3 days
- Laravel `storage/logs/*.log`: 14 days

You can schedule daily cleanup on Windows:

```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass -Force
.\scripts\register_cleanup_task.ps1 -StartTime '02:30'
```

Cloudflare preflight verification
--------------------------------

Use this script before/after staging cutover to confirm Cloudflare edge routing and API health:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\cloudflare_preflight_check.ps1 -AppUrl https://app.your-domain.com -ApiUrl https://api.your-domain.com/api/v1
```

The script checks:
- app HTTP status
- API health HTTP status
- Cloudflare edge headers (`cf-ray`, `server`)
- API cache behavior hints (`cf-cache-status`)

Workation.mv shortcut:

```powershell
npm run cloudflare:preflight:workation-mv
```

This runs preflight with:
- App: `https://workation.mv`
- API: `https://api.workation.mv/api/v1`