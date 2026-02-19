<#
Fetch the `coverage` artifact from the latest completed workflow run and extract `coverage-final.json`.

Usage (PowerShell):
  $env:GITHUB_TOKEN = '<PAT>'
  .\scripts\fetch_coverage_artifact.ps1 -Owner my-org -Repo my-repo -Workflow phpunit.yml

Parameters:
  -Owner (required) : GitHub repo owner/org
  -Repo  (required) : GitHub repo name
  -Workflow         : workflow file name under .github/workflows (default: phpunit.yml)
  -Branch           : branch to filter runs (default: main)
  -ArtifactName     : artifact name to look for (default: coverage)
  -PollInterval     : seconds between polls (default: 10)
  -MaxAttempts      : max poll attempts (default: 60)

Requires: a PAT exported to $env:GITHUB_TOKEN with repo access (or at least actions:read and repo read access).
#>

param(
    [Parameter(Mandatory=$true)][string]$Owner,
    [Parameter(Mandatory=$true)][string]$Repo,
    [string]$Workflow = 'phpunit.yml',
    [string]$Branch = 'main',
    [string]$ArtifactName = 'coverage',
    [int]$PollInterval = 10,
    [int]$MaxAttempts = 60
)

if (-not $env:GITHUB_TOKEN) {
    Write-Error "GITHUB_TOKEN environment variable not set. Export a PAT to `GITHUB_TOKEN` and retry."
    exit 2
}

$headers = @{ Authorization = "Bearer $($env:GITHUB_TOKEN)"; Accept = 'application/vnd.github+json'; 'User-Agent' = 'fetch-coverage-script' }

function Get-WorkflowRuns() {
    $uri = "https://api.github.com/repos/$Owner/$Repo/actions/workflows/$Workflow/runs?branch=$Branch&per_page=10&status=completed"
    try { return (Invoke-RestMethod -Uri $uri -Headers $headers -Method Get) } catch { return $null }
}

function Get-RunArtifacts($runId) {
    $uri = "https://api.github.com/repos/$Owner/$Repo/actions/runs/$runId/artifacts"
    try { return (Invoke-RestMethod -Uri $uri -Headers $headers -Method Get) } catch { return $null }
}

function Download-ArtifactZip($artifactId, $outPath) {
    $uri = "https://api.github.com/repos/$Owner/$Repo/actions/artifacts/$artifactId/zip"
    try {
        Invoke-WebRequest -Uri $uri -Headers $headers -OutFile $outPath -UseBasicParsing -MaximumRedirection 10 -ErrorAction Stop
        return $true
    } catch {
        Write-Verbose "Failed to download artifact ${artifactId}: $_"
        return $false
    }
}

$attempt = 0
Write-Host "Polling workflow runs for '$Workflow' on $Owner/$Repo (branch=$Branch) looking for artifact '$ArtifactName'..."
while ($attempt -lt $MaxAttempts) {
    $attempt++
    $runsResp = Get-WorkflowRuns
    if ($null -eq $runsResp) {
        Write-Host "Attempt ${attempt}: failed to retrieve runs; retrying in ${PollInterval} s..."
        Start-Sleep -Seconds $PollInterval
        continue
    }

    $runs = $runsResp.workflow_runs | Sort-Object -Property created_at -Descending
    foreach ($run in $runs) {
        $runId = $run.id
        Write-Host "Checking run $runId (created: $($run.created_at))..."
        $artsResp = Get-RunArtifacts -runId $runId
        if ($null -eq $artsResp) { continue }
        foreach ($art in $artsResp.artifacts) {
            if ($art.name -eq $ArtifactName) {
                Write-Host "Found artifact '$ArtifactName' (id=$($art.id)) in run $runId; downloading..."
                $outDir = Join-Path -Path "$PWD" -ChildPath "artifacts"
                if (-not (Test-Path $outDir)) { New-Item -Path $outDir -ItemType Directory | Out-Null }
                $zipPath = Join-Path $outDir "artifact_${runId}_$($art.id).zip"
                if (Download-ArtifactZip -artifactId $art.id -outPath $zipPath) {
                    $extractDir = Join-Path $outDir "artifact_${runId}_$($art.id)"
                    if (Test-Path $extractDir) { Remove-Item -Recurse -Force $extractDir }
                    Expand-Archive -Path $zipPath -DestinationPath $extractDir -Force
                    Write-Host "Extracted artifact to $extractDir"
                    $found = Get-ChildItem -Path $extractDir -Recurse -Filter 'coverage-final.json' -ErrorAction SilentlyContinue | Select-Object -First 1
                    if ($found) {
                        $dest = Join-Path $PWD "coverage-final-run${runId}-artifact${($art.id)}.json"
                        Copy-Item -Path $found.FullName -Destination $dest -Force
                        Write-Host "Found coverage-final.json -> $dest"
                        exit 0
                    } else {
                        Write-Host "coverage-final.json not present in artifact; continuing search."
                    }
                }
            }
        }
    }

    Write-Host "Attempt ${attempt}: no suitable artifact found; sleeping ${PollInterval} s..."
    Start-Sleep -Seconds $PollInterval
}

Write-Error "Timed out after $MaxAttempts attempts; no artifact with coverage-final.json found."
exit 1
