param(
    [string]$Repo = 'ibazzam/workation-cms',
    [string]$Workflow = 'integration-smoke.yml',
    [string]$Branch = 'main',
    [int]$PollSeconds = 15,
    [int]$TimeoutMinutes = 30,
    [switch]$Watch,
    [int]$PollNewRunSeconds = 30,
    [string]$LogsDir = 'ci-logs',
    [string]$ArchiveDir = 'ci-logs/archive',
    [int]$SinceRunId = 0
)

New-Item -Path $LogsDir -ItemType Directory -Force -ErrorAction SilentlyContinue | Out-Null
New-Item -Path $ArchiveDir -ItemType Directory -Force -ErrorAction SilentlyContinue | Out-Null

function Download-And-Archive($runId) {
    $logFile = "$LogsDir/ci-run-$runId.log"
    Write-Host "Downloading logs for run $runId to $logFile"
    gh run view $runId --repo $Repo --log > $logFile 2>$null
    if (-not (Test-Path $logFile)) { Write-Host "Failed to download logs for $runId."; return 1 }

    Write-Host "--- Error summary (first matches) ---"
    Select-String -Path $logFile -Pattern "ERROR|Exception|no such table|MissingAppKey|failed with exit code|Database file" -CaseSensitive:$false | Select-Object -First 200 | ForEach-Object { $_.Line }

    Write-Host "--- Tail (last 200 lines) ---"
    Get-Content $logFile -Tail 200

    $timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
    $archiveName = "$ArchiveDir/ci-run-$runId-$timestamp.log"
    Move-Item -Path $logFile -Destination $archiveName -Force
    Write-Host "Archived log to $archiveName"
    return 0
}

if ($Watch) {
    Write-Host "Watch mode: waiting for a new run of $Workflow on $Branch"
    # Determine current latest run id if not supplied
    if ($SinceRunId -le 0) {
        $latest = gh run list --repo $Repo --workflow $Workflow --branch $Branch --limit 1 --json databaseId --jq '.[0].databaseId' 2>$null
        if ($latest) { $SinceRunId = [int]$latest }
        Write-Host "Starting from run id: $SinceRunId"
    }

    while ($true) {
        $top = gh run list --repo $Repo --workflow $Workflow --branch $Branch --limit 1 --json databaseId,createdAt --jq '.[0]' 2>$null
        if ($top) {
            $obj = $top | ConvertFrom-Json
            $candidate = [int]$obj.databaseId
            if ($candidate -gt $SinceRunId) {
                Write-Host "Detected new run: $candidate (created: $($obj.createdAt))"
                $runId = $candidate
                $start = Get-Date
                $timeout = $start.AddMinutes($TimeoutMinutes)
                while ((Get-Date) -lt $timeout) {
                    $conclusion = gh run view $runId --repo $Repo --json conclusion --jq '.conclusion' 2>$null
                    if ($conclusion -and $conclusion -ne 'null') { Write-Host "Run $runId concluded: $conclusion"; break }
                    Write-Host "Waiting for run $runId to finish... (sleep $PollSeconds s)"
                    Start-Sleep -Seconds $PollSeconds
                }
                if (-not $conclusion) { Write-Host "Timed out waiting for run $runId" }
                Download-And-Archive $runId | Out-Null
                $SinceRunId = $runId
            }
        }
        Start-Sleep -Seconds $PollNewRunSeconds
    }
} else {
    Write-Host "Querying latest run for $Workflow on $Branch..."
    $run = gh run list --repo $Repo --workflow $Workflow --branch $Branch --limit 1 --json databaseId,createdAt --jq '.[0]'
    if (-not $run) { Write-Host 'No run found.'; exit 1 }
    $runId = ($run | ConvertFrom-Json).databaseId
    Write-Host "Monitoring run id: $runId"

    $start = Get-Date
    $timeout = $start.AddMinutes($TimeoutMinutes)
    while ((Get-Date) -lt $timeout) {
        $conclusion = gh run view $runId --repo $Repo --json conclusion --jq '.conclusion' 2>$null
        if ($conclusion -and $conclusion -ne 'null') { Write-Host "Run concluded: $conclusion"; break }
        Write-Host "Waiting for run to finish... (sleep $PollSeconds s)"
        Start-Sleep -Seconds $PollSeconds
    }

    if (-not $conclusion) { Write-Host "Timed out waiting for run $runId"; exit 2 }

    Download-And-Archive $runId | Out-Null
    if ($conclusion -ne 'success') { exit 4 }
    exit 0
}
