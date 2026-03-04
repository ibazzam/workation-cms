param(
    [int]$pr = 16,
    [string]$repo = 'ibazzam/workation-cms'
)

$ErrorActionPreference = 'Continue'
Write-Output "Starting resilient auto-merge monitor for PR #$pr in $repo"
while ($true) {
    try {
        $raw = gh pr view $pr --repo $repo --json headRefName,headRefOid,mergeable,mergeStateStatus 2>&1 | Out-String
        if (-not $raw) { Write-Output 'gh pr view returned nothing'; Start-Sleep -Seconds 15; continue }
        try { $prInfo = $raw | ConvertFrom-Json } catch { Write-Output 'Failed to parse prInfo JSON'; Start-Sleep -Seconds 15; continue }
        if (-not $prInfo) { Write-Output 'prInfo empty'; Start-Sleep -Seconds 15; continue }

        $sha = $prInfo.headRefOid
        $branch = $prInfo.headRefName
        Write-Output ("[$(Get-Date -Format o)] PR#$pr branch=$branch mergeable=$($prInfo.mergeable) state=$($prInfo.mergeStateStatus)")
        if (-not $sha) { Write-Output 'No head OID yet'; Start-Sleep -Seconds 15; continue }

        $rawChecks = gh api /repos/$repo/commits/$sha/check-runs --jq '.check_runs' 2>&1 | Out-String
        if (-not $rawChecks) { Write-Output 'No check runs returned'; Start-Sleep -Seconds 15; continue }
        try { $checks = $rawChecks | ConvertFrom-Json } catch { Write-Output 'Failed to parse checks JSON'; Start-Sleep -Seconds 15; continue }

        $allCompleted = $true; $allSuccess = $true
        foreach ($c in $checks) {
            Write-Output ("{0} - status={1} conclusion={2}" -f $c.name,$c.status,$c.conclusion)
            if ($c.status -ne 'completed') { $allCompleted = $false }
            if ($c.conclusion -ne 'success') { $allSuccess = $false }
        }

        if ($allCompleted -and $allSuccess) {
            Write-Output 'All checks passed - merging PR'
            $mergeResult = gh pr merge $pr --repo $repo --merge --delete-branch --admin 2>&1 | Out-String
            Write-Output $mergeResult
            Write-Output 'Merge attempted; exiting monitor'
            break
        }

        Write-Output 'Checks not all passed yet - sleeping 30s'
        Start-Sleep -Seconds 30
    } catch {
        Write-Output ("Unexpected error: $($_.Exception.Message)")
        Start-Sleep -Seconds 30
    }
}