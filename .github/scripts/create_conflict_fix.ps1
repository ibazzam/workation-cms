param(
    [int[]]$prs,
    [string]$repo = 'ibazzam/workation-cms'
)

$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '..\\..')

foreach ($pr in $prs) {
    Write-Output "\n=== Processing PR #$pr ==="
    try {
        $raw = gh pr view $pr --repo $repo --json headRefName,headRefOid,mergeable,mergeStateStatus 2>&1 | Out-String
        $info = $raw | ConvertFrom-Json
        $head = $info.headRefName
        Write-Output "Found head: $head"

        Write-Output "Fetching origin/main and origin/$head"
        git fetch origin main 2>&1 | Write-Output
        git fetch origin $head 2>&1 | Write-Output

        $branchName = "merge/pr-$pr-fix"
        Write-Output "Creating branch $branchName from origin/main"
        git switch --create $branchName origin/main 2>&1 | Write-Output

        Write-Output "Merging origin/$head into $branchName"
        $mergeOut = git merge --no-edit origin/$head 2>&1
        $mergeExit = $LASTEXITCODE
        Write-Output $mergeOut
        if ($mergeExit -ne 0) {
            Write-Output "Merge returned exit code $mergeExit -- resolving conflicts by taking PR version"
            $conflicts = git diff --name-only --diff-filter=U
            if ($conflicts) {
                foreach ($f in $conflicts) {
                    Write-Output "Resolving $f by taking PR version"
                    git checkout --theirs -- $f
                    git add $f
                }
                git commit -m "Resolve conflicts by taking PR version (automated) for PR #$pr"
            } else {
                Write-Output "No conflicted files listed, aborting merge"
                git merge --abort 2>&1 | Write-Output
                continue
            }
        }

        Write-Output "Pushing branch $branchName"
        git push -u origin $branchName 2>&1 | Write-Output

        Write-Output "Opening conflict-fix PR for #$pr"
        gh pr create --title "Conflict-fix: PR #$pr (automated)" --body "Merged PR head into a conflict-fix branch and resolved conflicts by taking the PR version. Please review before merging into main." --base main --head $branchName --repo $repo 2>&1 | Write-Output
    } catch {
        Write-Output ("Error processing PR #{0}: {1}" -f $pr, $_.Exception.Message)
    }
}
