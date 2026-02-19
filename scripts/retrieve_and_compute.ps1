<#
Retrieve coverage artifact from Actions and compute a resource-scoped summary.

Usage:
  $env:GITHUB_TOKEN = '<PAT>'
  .\scripts\retrieve_and_compute.ps1 -Owner ibazzam -Repo workation-cms

Options:
  -Owner (required) : GitHub owner/org
  -Repo  (required) : repository name
  -Workflow         : workflow filename (default: phpunit.yml)
  -Branch           : branch name (default: main)
  -ArtifactName     : artifact name to look for (default: coverage)
  -PollInterval     : seconds between polls (default: 10)
  -MaxAttempts      : max poll attempts (default: 60)
  -Out              : output summary path (default: coverage-summary-ci.json)
  -Commit           : switch to commit the summary to branch `coverage-reports`
  -CommitMessage    : commit message when committing (optional)
#>

param(
    [Parameter(Mandatory=$true)][string]$Owner,
    [Parameter(Mandatory=$true)][string]$Repo,
    [string]$Workflow = 'phpunit.yml',
    [string]$Branch = 'main',
    [string]$ArtifactName = 'coverage',
    [int]$PollInterval = 10,
    [int]$MaxAttempts = 60,
    [string]$Out = 'coverage-summary-ci.json',
    [switch]$Commit,
    [string]$CommitMessage = ''
)

if (-not $env:GITHUB_TOKEN) {
    Write-Error "GITHUB_TOKEN not set in environment. Export a PAT to GITHUB_TOKEN and retry."
    exit 2
}

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Definition

Write-Host "Running fetcher to download artifact..."
$fetchScript = Join-Path $scriptDir 'fetch_coverage_artifact.ps1'
# prefer pwsh when available, fallback to legacy powershell
$pwshCmd = (Get-Command pwsh -ErrorAction SilentlyContinue).Source
if (-not $pwshCmd) { $pwshCmd = (Get-Command powershell -ErrorAction SilentlyContinue).Source }
if (-not $pwshCmd) { Write-Error "No PowerShell executable found on PATH"; exit 2 }
& $pwshCmd -NoProfile -ExecutionPolicy Bypass -File $fetchScript -Owner $Owner -Repo $Repo -Workflow $Workflow -Branch $Branch -ArtifactName $ArtifactName -PollInterval $PollInterval -MaxAttempts $MaxAttempts
$fetchExit = $LASTEXITCODE
if ($fetchExit -ne 0) {
    Write-Error "Fetcher failed with exit code $fetchExit."
    exit $fetchExit
}

Write-Host "Locating downloaded coverage-final JSON..."
$found = Get-ChildItem -Path $PWD -Recurse -Filter 'coverage-final*.json' -File -ErrorAction SilentlyContinue | Sort-Object LastWriteTime -Descending | Select-Object -First 1
if (-not $found) {
    Write-Error "No coverage-final JSON found in workspace after fetch."
    exit 3
}

$inputPath = $found.FullName
Write-Host "Using coverage file: $inputPath"

# find node
$nodeCmd = Get-Command node -ErrorAction SilentlyContinue
if (-not $nodeCmd) { Write-Error '`node` command not found in PATH'; exit 4 }

$computeScript = Join-Path $scriptDir 'compute_coverage_summary.cjs'
if (-not (Test-Path $computeScript)) { Write-Error "Compute script not found: $computeScript"; exit 5 }

Write-Host "Computing coverage summary -> $Out"
 $nodeArgs = @($computeScript, $inputPath, $Out)
& $nodeCmd.Source @nodeArgs
if ($LASTEXITCODE -ne 0) { Write-Error "Node summary generator failed (exit $LASTEXITCODE)"; exit $LASTEXITCODE }

Write-Host "Wrote summary: $Out"

if ($Commit.IsPresent) {
    Write-Host "Committing $Out to branch coverage-reports"
    git checkout -B coverage-reports
    if (-not (Test-Path coverage-reports)) { New-Item -ItemType Directory -Path coverage-reports | Out-Null }
    Copy-Item -Path $Out -Destination (Join-Path 'coverage-reports' (Split-Path $Out -Leaf)) -Force
    git add coverage-reports/$(Split-Path $Out -Leaf)
    $msg = if ($CommitMessage) { $CommitMessage } else { "ci: add coverage summary (generated $(Get-Date -Format o))" }
    & git commit -m $msg
    if ($LASTEXITCODE -ne 0) { Write-Host "No changes to commit or commit failed (exit $LASTEXITCODE)" }
    git push -u origin coverage-reports
    Write-Host "Pushed coverage summary to origin/coverage-reports"
}

Write-Host "Done."
