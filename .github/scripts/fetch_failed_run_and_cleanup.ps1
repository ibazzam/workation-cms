param(
    [string]$repo = 'ibazzam/workation-cms'
)

$ErrorActionPreference = 'Continue'

Write-Output "Listing recent runs for $repo/main"
$runsRaw = gh run list --repo $repo --branch main --limit 30 --json databaseId,name,status,conclusion,url 2>$null | Out-String
try {
    $runs = $runsRaw | ConvertFrom-Json
} catch {
    Write-Output 'Failed to parse runs JSON'
    Write-Output $runsRaw
    exit 2
}

$failed = $runs | Where-Object { $_.name -eq 'Run tests' -and ($_.conclusion -ne 'success' -or $_.status -ne 'completed') } | Select-Object -First 1
if (-not $failed) {
    Write-Output 'No failed or incomplete "Run tests" runs found'
    exit 3
}

$id = $failed.databaseId
$url = $failed.url
Write-Output "Found run id=$id url=$url status=$($failed.status) conclusion=$($failed.conclusion)"

$outdir = Join-Path $PSScriptRoot ("failed_run_" + $id)
New-Item -ItemType Directory -Path $outdir -Force | Out-Null
Write-Output "Downloading logs to $outdir"
gh run download $id --repo $repo --dir $outdir 2>&1 | Out-String | Write-Output
Write-Output 'Download complete. Listing files:'
Get-ChildItem -Recurse -File $outdir | ForEach-Object { Write-Output $_.FullName }

Write-Output 'Deleting remote refs named origin/merge/pr-* if any'
$remoteMatches = git ls-remote --heads origin 'merge/pr-*' 2>$null | ForEach-Object { ($_ -split '\s+')[1] }
$remoteCount = @($remoteMatches | ForEach-Object {$_}).Count
if ($remoteCount -gt 0) {
    foreach ($ref in $remoteMatches) {
        $branch = $ref -replace '^refs/heads/',''
        Write-Output "Deleting remote branch: $branch"
        git push origin --delete $branch 2>&1 | Write-Output
    }
} else {
    Write-Output 'No remote merge/pr-* branches found'
}

Write-Output 'Deleting local branches merge/pr-* if present'
$local = git for-each-ref --format='%(refname:short)' refs/heads | Where-Object { $_ -like 'merge/pr-*' }
$localCount = @($local | ForEach-Object {$_}).Count
if ($localCount -gt 0) {
    foreach ($b in $local) {
        Write-Output "Deleting local branch: $b"
        git branch -D $b 2>&1 | Write-Output
    }
} else {
    Write-Output 'No local merge/pr-* branches found'
}

Write-Output 'Pruning remotes'
git fetch --prune 2>&1 | Write-Output
Write-Output 'Done'
Write-Output "Failed run URL: $url"
