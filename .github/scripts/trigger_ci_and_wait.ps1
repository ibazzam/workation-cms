param(
    [string]$repo = 'ibazzam/workation-cms',
    [string]$workflowName = 'Run tests',
    [string]$ref = 'main',
    [int]$timeoutMinutes = 30
)

$ErrorActionPreference = 'Continue'
Write-Output "Triggering workflow '$workflowName' on $repo@$ref"
$trigger = gh workflow run "$workflowName" --repo $repo --ref $ref 2>&1 | Out-String
Write-Output $trigger
Start-Sleep -Seconds 5

$deadline = (Get-Date).AddMinutes($timeoutMinutes)
while ((Get-Date) -lt $deadline) {
    try {
        $runsRaw = gh run list --repo $repo --branch $ref --limit 10 --json databaseId,name,headBranch,status,conclusion,url 2>&1 | Out-String
        if (-not $runsRaw) { Write-Output 'gh run list returned nothing'; Start-Sleep -Seconds 10; continue }
        try { $runs = $runsRaw | ConvertFrom-Json } catch { Write-Output 'Failed to parse runs JSON'; Start-Sleep -Seconds 10; continue }
        $run = $runs | Where-Object { $_.name -eq $workflowName } | Select-Object -First 1
        if (-not $run) { Write-Output 'No matching workflow run found yet'; Start-Sleep -Seconds 10; continue }
        Write-Output ("Found run id=$($run.databaseId) name=$($run.name) status=$($run.status) conclusion=$($run.conclusion) url=$($run.url)")
        if ($run.status -eq 'completed') {
            if ($run.conclusion -eq 'success') { Write-Output 'Workflow succeeded'; exit 0 } else { Write-Output ("Workflow completed with conclusion: $($run.conclusion)"); exit 2 }
        }
    } catch {
        Write-Output ("Error while checking runs: $($_.Exception.Message)")
    }
    Start-Sleep -Seconds 15
}
Write-Output 'Timeout waiting for workflow to complete'
exit 3
