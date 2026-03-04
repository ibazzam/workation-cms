param(
    [int[]]$prs,
    [string]$repo = 'ibazzam/workation-cms'
)

$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '..\..')

foreach ($pr in $prs) {
    Write-Output "\n=== Creating fix branch for PR #$pr ==="
    try {
        $infoRaw = gh pr view $pr --repo $repo --json headRefName 2>&1 | Out-String
        $info = $infoRaw | ConvertFrom-Json
        $head = $info.headRefName
        Write-Output "PR #$pr headRefName = $head"

        Write-Output "Fetching origin/main"
        git fetch origin main 2>&1 | Write-Output

        $branchName = "merge/pr-$pr-fix"
        Write-Output "Creating branch $branchName from origin/main"
        git switch --create $branchName origin/main 2>&1 | Write-Output

        Write-Output "Pushing branch $branchName"
        git push -u origin $branchName 2>&1 | Write-Output

        Write-Output "Opening PR for branch $branchName"
        gh pr create --title "Conflict-fix: PR #$pr (branch created)" --body "Created branch $branchName from main to stage conflict resolution for PR #$pr (head: $head). Please review and rebase/merge as appropriate." --base main --head $branchName --repo $repo 2>&1 | Write-Output
    } catch {
        Write-Output ("Error creating fix branch for PR #{0}: {1}" -f $pr, $_.Exception.Message)
    }
}
