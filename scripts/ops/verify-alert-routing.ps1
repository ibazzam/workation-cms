param(
  [string]$BaseUrl = "https://api.workation.mv",
  [switch]$RunWorkflow
)

$ErrorActionPreference = "Stop"

if (-not $env:AUTH_BEARER_TOKEN) {
  Write-Error "AUTH_BEARER_TOKEN is required. Set it before running this script."
}

$headers = @{ Authorization = "Bearer $($env:AUTH_BEARER_TOKEN)" }

Write-Output "Probing authenticated ops endpoints..."
$alertsStatus = (curl.exe -sS -o NUL -w "%{http_code}" -H "Authorization: Bearer $($env:AUTH_BEARER_TOKEN)" "$BaseUrl/api/v1/ops/alerts")
$runbooksStatus = (curl.exe -sS -o NUL -w "%{http_code}" -H "Authorization: Bearer $($env:AUTH_BEARER_TOKEN)" "$BaseUrl/api/v1/ops/runbooks")

Write-Output "ops/alerts status: $alertsStatus"
Write-Output "ops/runbooks status: $runbooksStatus"

if ($RunWorkflow) {
  Write-Output "Triggering strict live-preflight workflow..."
  gh workflow run live-preflight-gate.yml -f require_ops_slo=true -f require_scheduler_health=true -f require_checkout_reliability=true -f require_payments_reliability=true -f require_moderation_paths=true -f require_new_verticals=true | Out-Null

  $run = gh run list --workflow "Live preflight gate" --limit 1 --json databaseId,url,status,conclusion | ConvertFrom-Json | Select-Object -First 1
  if (-not $run) {
    Write-Error "Unable to locate workflow run after dispatch."
  }

  Write-Output "Watching run: $($run.url)"
  gh run watch $run.databaseId --exit-status
}

Write-Output "Done. Capture pager/slack/email receipts and update docs/alert-routing-verification-2026-03-18.md."
