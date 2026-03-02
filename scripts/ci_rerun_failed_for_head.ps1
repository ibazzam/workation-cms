# Rerun failed GitHub Actions workflow runs for the current branch HEAD
$ErrorActionPreference = 'Stop'
$head = (git rev-parse --short HEAD).Trim()
Write-Output "Current HEAD: $head"
$runsJson = gh run list --repo ibazzam/workation-cms --branch chore/backup-automation-hardening-2026-02-25 --limit 50 --json databaseId,headSha,conclusion,name,createdAt
$runs = $runsJson | ConvertFrom-Json
$failed = $runs | Where-Object { $_.conclusion -eq 'failure' -and $_.headSha -like ("$head*") }
if (-not $failed) {
    Write-Output "No failed runs found for current HEAD: $head"
    exit 0
}
foreach ($r in $failed) {
    Write-Output "Rerunning run: $($r.databaseId) - $($r.name) - $($r.headSha)"
    gh run rerun $($r.databaseId) --repo ibazzam/workation-cms
}
