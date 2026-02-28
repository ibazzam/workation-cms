# Monitor a GitHub Actions run until completion, download logs/artifacts if it fails,
# and search logs for Prisma P1012 errors.
param(
    [long]$RunId,
    [string]$Repo,
    [int]$PollSeconds
)

if (-not $Repo) { $Repo = 'ibazzam/workation-cms' }
if (-not $PollSeconds) { $PollSeconds = 10 }
if (-not $RunId) {
    Write-Error "RunId must be passed via -RunId. Example: .\scripts\ci_monitor_and_fetch.ps1 -RunId 22519074023"
    exit 2
}

$ErrorActionPreference = 'Stop'
Write-Output "Monitoring run $RunId in $Repo"
while ($true) {
    $json = gh run view $RunId --repo $Repo --json status,conclusion | ConvertFrom-Json
    $status = $json.status
    $conclusion = $json.conclusion
    Write-Output "Status: $status    Conclusion: $conclusion"
    if ($conclusion -ne $null -and $conclusion -ne '') { break }
    Start-Sleep -Seconds $PollSeconds
}
Write-Output "Run $RunId completed with conclusion: $conclusion"
$outDir = Join-Path -Path "artifacts" -ChildPath "run_$RunId"
if (-not (Test-Path $outDir)) { New-Item -ItemType Directory -Path $outDir | Out-Null }
if ($conclusion -ne 'success') {
    Write-Output "Downloading run artifacts and full logs to $outDir"
    gh run download $RunId --repo $Repo --dir $outDir
    gh run view $RunId --repo $Repo --log > (Join-Path $outDir "run_${RunId}_logs.txt")
    Write-Output "Searching logs for Prisma P1012 errors"
    $matches = Select-String -Path (Join-Path $outDir "**\*.*") -Pattern 'P1012' -SimpleMatch -AllMatches -ErrorAction SilentlyContinue
    if ($matches) {
        $matches | ForEach-Object { $_.ToString() } | Out-File -FilePath (Join-Path $outDir "prisma_p1012_matches.txt") -Encoding UTF8
        Write-Output "Found P1012 occurrences; saved to prisma_p1012_matches.txt"
    } else {
        Write-Output "No P1012 occurrences found in run logs/artifacts."
    }
} else {
    Write-Output "Run succeeded; no logs downloaded."
}
Write-Output "Done."
