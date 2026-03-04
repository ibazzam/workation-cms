param(
  [string]$repo = 'ibazzam/workation-cms',
  [int]$pr = 13,
  [int]$timeoutSeconds = 7200,
  [string]$logFile = '.\\auto_merge_pr13.log'
)

function Log { param($m) $t = Get-Date -Format o; $s = "[$t] $m"; Add-Content -Path $logFile -Value $s; Write-Output $s }

Log "Starting auto-merge monitor for PR #$pr in $repo"
$start = Get-Date
while ((Get-Date) -lt $start.AddSeconds($timeoutSeconds)) {
  try {
    $prInfoJson = gh pr view $pr --repo $repo --json headRefName,headOid,mergeable,mergeStateStatus 2>$null
    if (-not $prInfoJson) { Log 'gh returned no PR info yet'; Start-Sleep -Seconds 10; continue }
    $prInfo = $prInfoJson | ConvertFrom-Json
    $sha = $prInfo.headOid
    $branch = $prInfo.headRefName
    Log "PR#$pr branch=$branch mergeable=$($prInfo.mergeable) state=$($prInfo.mergeStateStatus)"

    if (-not $sha) { Log 'No head OID yet'; Start-Sleep -Seconds 10; continue }

    $checksJson = gh api repos/$repo/commits/$sha/check-runs --jq '.check_runs' 2>$null
    if (-not $checksJson) { Log 'No check runs found yet'; Start-Sleep -Seconds 12; continue }
    $checks = $checksJson | ConvertFrom-Json

    $allCompleted = $true; $allSuccess = $true
    foreach ($c in $checks) {
      Log ("{0} - status={1} conclusion={2}" -f $c.name,$c.status,$c.conclusion)
      if ($c.status -ne 'completed') { $allCompleted = $false }
      if ($c.conclusion -ne 'success') { $allSuccess = $false }
    }

    if ($allCompleted -and $allSuccess) {
      Log 'All checks passed — attempting to merge PR'
      $merge = gh pr merge $pr --repo $repo --merge --delete-branch --admin 2>&1
      Log "Merge output: $merge"
      if ($LASTEXITCODE -eq 0) { Log 'Merged PR successfully'; exit 0 } else { Log 'Merge command failed'; exit 2 }
    }

    Log 'Checks not all passed yet — sleeping 30s'
    Start-Sleep -Seconds 30
  } catch {
    Log "Error during polling: $_"
    Start-Sleep -Seconds 30
  }
}

Log 'Timeout waiting for checks to pass'
exit 1
