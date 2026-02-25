param(
  [string]$TaskName = 'WorkationCMS-ArtifactCleanup',
  [string]$RepoPath = (Resolve-Path "$PSScriptRoot/..").Path,
  [string]$StartTime = '02:30'
)

$runnerScriptPath = (Resolve-Path "$PSScriptRoot/run_cleanup_artifacts.ps1").Path
$command = 'powershell -NoProfile -ExecutionPolicy Bypass -File "{0}"' -f $runnerScriptPath

schtasks /Create /F /SC DAILY /ST $StartTime /TN $TaskName /TR $command | Out-Null
if ($LASTEXITCODE -ne 0) {
  throw "Failed to create scheduled task '$TaskName'."
}

Write-Host "Scheduled task '$TaskName' created."
Write-Host "Runs daily at $StartTime"
Write-Host "Command: $command"
