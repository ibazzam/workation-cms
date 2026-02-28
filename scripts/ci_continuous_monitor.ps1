param(
    [string]$Repo = 'ibazzam/workation-cms',
    [string]$Branch = 'chore/backup-automation-hardening-2026-02-25',
    [int]$PollSeconds = 30,
    [string]$ProcessedFile = 'artifacts/processed_runs.txt'
)

if (-not (Test-Path 'artifacts')) { New-Item -ItemType Directory -Path 'artifacts' | Out-Null }
if (-not (Test-Path $ProcessedFile)) { New-Item -ItemType File -Path $ProcessedFile | Out-Null }

Write-Output "Starting continuous monitor for $Repo @$Branch (poll every ${PollSeconds}s). Processed file: $ProcessedFile"
$ErrorActionPreference = 'Continue'
while ($true) {
    try {
        $runs = gh run list --repo $Repo --branch $Branch --limit 20 --json databaseId,status,conclusion | ConvertFrom-Json
    } catch {
        Write-Output "gh run list failed: $_"
        Start-Sleep -Seconds $PollSeconds
        continue
    }

    foreach ($r in $runs) {
        $id = $r.databaseId
        if (-not $id) { continue }
        $already = Select-String -Path $ProcessedFile -Pattern "^$id$" -SimpleMatch -Quiet
        if ($already) { continue }
        # Only process completed runs with a conclusion
        if ($r.status -eq 'completed' -and $r.conclusion) {
            Write-Output "Processing run $id (conclusion: $($r.conclusion))"
            try {
                & .\scripts\ci_monitor_and_fetch.ps1 -RunId $id -Repo $Repo -PollSeconds 2
            } catch {
                Write-Output "Error processing run $id: $_"
            }
            Add-Content -Path $ProcessedFile -Value $id
        }
    }

    Start-Sleep -Seconds $PollSeconds
}
