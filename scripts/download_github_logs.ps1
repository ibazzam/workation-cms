# Download GitHub Actions run logs for a workflow into a local folder.
# Requires: GitHub CLI (`gh`) authenticated with access to the repository.

param(
  [string]$Repo = 'ibazzam/workation-cms',
  [string]$Workflow = 'integration-smoke.yml',
  [string]$Branch = 'main',
  [int]$Limit = 20,
  [string]$OutDir = '.\ci-logs'
)

function Retry-Command {
  param(
    [scriptblock]$Script,
    [int]$Attempts = 3,
    [int]$DelaySeconds = 5
  )
  for ($i = 1; $i -le $Attempts; $i++) {
    try {
      & $Script
      return $true
    } catch {
      Write-Host "Attempt $i failed: $($_.Exception.Message)"
      if ($i -lt $Attempts) { Start-Sleep -Seconds $DelaySeconds }
    }
  }
  return $false
}

Write-Host "Downloading up to $Limit runs for workflow '$Workflow' on '$Repo' (branch: $Branch)"

New-Item -ItemType Directory -Force -Path $OutDir | Out-Null

# Get runs JSON
$json = gh run list --repo $Repo --workflow $Workflow --branch $Branch --limit $Limit --json databaseId,headSha,conclusion,createdAt 2>$null
if (-not $json) {
  Write-Host "No runs returned by gh run list. Ensure gh is installed and authenticated." -ForegroundColor Yellow
  exit 1
}

$runs = $json | ConvertFrom-Json
if (-not $runs) {
  Write-Host "No runs found for the specified filters." -ForegroundColor Yellow
  exit 0
}

$summary = @()
foreach ($r in $runs) {
  $id = $r.databaseId
  $created = $r.createdAt
  $conclusion = $r.conclusion
  $outFile = Join-Path $OutDir "ci-run-$id.log"

  if (Test-Path $outFile -PathType Leaf -ErrorAction SilentlyContinue) {
    $len = (Get-Item $outFile).Length
    if ($len -gt 0) { Write-Host "Skipping existing log $outFile (size $len bytes)"; $summary += @{id=$id;file=$outFile;status='skipped';size=$len;created=$created;conclusion=$conclusion}; continue }
  }

  Write-Host "Downloading run $id -> $outFile"
  $attempt = 0
  $success = $false
  while ($attempt -lt 4 -and -not $success) {
    $attempt++
    try {
      gh run view $id --repo $Repo --log > $outFile 2>$null
      if (Test-Path $outFile) {
        $size = (Get-Item $outFile).Length
        if ($size -gt 0) { $success = $true; Write-Host "Saved $outFile ($size bytes)"; break }
        else { Remove-Item $outFile -ErrorAction SilentlyContinue }
      }
    } catch {
      Write-Host "Download attempt $attempt failed for run $id: $($_.Exception.Message)"
    }
    Start-Sleep -Seconds (5 * $attempt)
  }

  if (-not $success) {
    Write-Host "Failed to download log for run $id after multiple attempts." -ForegroundColor Red
    $summary += @{id=$id;file=$outFile;status='failed';size=0;created=$created;conclusion=$conclusion}
  } else {
    $summary += @{id=$id;file=$outFile;status='ok';size=$size;created=$created;conclusion=$conclusion}
  }
}

Write-Host "`nSummary"
foreach ($s in $summary) {
  Write-Host ("{0} {1} {2} {3} bytes {4}" -f $s.id, $s.status, $s.conclusion, $s.size, $s.file)
}

Write-Host "Done. Logs saved to: $OutDir"
