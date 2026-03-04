$token = $env:GITHUB_TOKEN
# NOTE: Do NOT hardcode personal access tokens in source. Set the token via
# environment variable before running, e.g.:
# $env:GITHUB_TOKEN = 'ghp_...'
# The script will use `GITHUB_TOKEN` from the environment instead of an in-repo secret.
$owner = 'ibazzam'
$repo = 'workation-cms'
$workflow = 'diag-servicecategory.yml'
$branch = 'replace-servicecategory-migration'
$headers = @{ Authorization = "Bearer $token"; Accept = 'application/vnd.github+json' }

Write-Output 'Querying workflow runs...'
$runs = Invoke-RestMethod -Headers $headers -Uri "https://api.github.com/repos/$owner/$repo/actions/workflows/$workflow/runs?branch=$branch&per_page=5"
$run = $runs.workflow_runs | Select-Object -First 1
if (-not $run) {
    Write-Output 'No runs found'; exit 2
}
$id = $run.id
Write-Output "Found run id: $id, status: $($run.status), conclusion: $($run.conclusion)"

while ($run.status -ne 'completed') {
    Start-Sleep -Seconds 5
    $runs = Invoke-RestMethod -Headers $headers -Uri "https://api.github.com/repos/$owner/$repo/actions/workflows/$workflow/runs?branch=$branch&per_page=5"
    $run = $runs.workflow_runs | Select-Object -First 1
    Write-Output "status=$($run.status), conclusion=$($run.conclusion)"
}

Write-Output "Run completed with conclusion: $($run.conclusion)"
$artifacts = Invoke-RestMethod -Headers $headers -Uri "https://api.github.com/repos/$owner/$repo/actions/runs/$id/artifacts"
if ($artifacts.total_count -eq 0) {
    Write-Output 'No artifacts found'; exit 0
}
$art = $artifacts.artifacts | Where-Object { $_.name -eq 'servicecategory-id-check' } | Select-Object -First 1
if (-not $art) {
    $art = $artifacts.artifacts | Select-Object -First 1
}
$dl = $art.archive_download_url
$out = 'infra/backend/artifacts/diagnostics/servicecategory-id-check.zip'
Invoke-WebRequest -Headers $headers -OutFile $out -Uri $dl
Write-Output "Downloaded artifact to $out"

if (-Not (Test-Path 'infra/backend/artifacts/diagnostics')) { New-Item -ItemType Directory -Path 'infra/backend/artifacts/diagnostics' -Force | Out-Null }
Expand-Archive -LiteralPath $out -DestinationPath 'infra/backend/artifacts/diagnostics' -Force
Write-Output 'Extracted artifact to infra/backend/artifacts/diagnostics'