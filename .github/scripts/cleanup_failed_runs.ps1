<#
Cleanup failed GitHub Actions runs and their artifacts for a repository.

Prerequisites:
- `gh` CLI must be installed and authenticated (`gh auth login`).
- The account must have permission to delete workflow runs/artifacts.

Usage examples:
# Preview only (no deletions):
pwsh -NoProfile -ExecutionPolicy Bypass -File .github/scripts/cleanup_failed_runs.ps1 -Repo "ibazzam/workation-cms" -Days 7

# Delete all failed runs older than 7 days (prompt for each):
pwsh -NoProfile -ExecutionPolicy Bypass -File .github/scripts/cleanup_failed_runs.ps1 -Repo "ibazzam/workation-cms" -Days 7 -ConfirmDeletion

# Delete all failed runs immediately without prompts:
pwsh -NoProfile -ExecutionPolicy Bypass -File .github/scripts/cleanup_failed_runs.ps1 -Repo "ibazzam/workation-cms" -Days 0 -Force

# Notes:
# - Set -Days to 0 to target all failed runs.
# - This script permanently deletes runs and artifacts. Use with care.
#>

param(
    [string]$Repo = "ibazzam/workation-cms",
    [int]$Days = 7,
    [switch]$ConfirmDeletion,
    [switch]$Force
)

function IsoToDateTime($iso) {
    return [DateTime]::Parse($iso)
}

Write-Host "Repository: $Repo"
if ($Days -le 0) { Write-Host "Targeting: all failed runs" } else { Write-Host "Targeting: failed runs older than $Days days" }

# Fetch failed workflow runs
$json = gh run list --repo $Repo --limit 500 --json databaseId,conclusion,createdAt,name,status 2>$null
if (-not $json) {
    Write-Host "No runs returned by gh. Ensure you are authenticated and have access." -ForegroundColor Yellow
    exit 1
}

$runs = $json | ConvertFrom-Json

$now = Get-Date
$candidates = @()
foreach ($r in $runs) {
    if ($r.conclusion -ne 'failure' -and $r.conclusion -ne 'cancelled' -and $r.conclusion -ne 'timed_out') { continue }
    $created = IsoToDateTime($r.createdAt)
    if ($Days -gt 0) {
        if ((($now - $created).TotalDays) -ge $Days) { $candidates += $r }
    } else {
        $candidates += $r
    }
}

if ($candidates.Count -eq 0) {
    Write-Host "No matching failed runs found for deletion." -ForegroundColor Green
    exit 0
}

Write-Host "Found $($candidates.Count) matching failed runs:" -ForegroundColor Cyan
foreach ($r in $candidates) { Write-Host "- ID: $($r.databaseId)  Name: $($r.name)  Created: $($r.createdAt)  Status: $($r.status)  Conclusion: $($r.conclusion)" }

if (-not $Force) {
    if ($ConfirmDeletion) {
        $answer = Read-Host "Proceed to DELETE these runs and their artifacts? (y/N)"
        if ($answer -ne 'y' -and $answer -ne 'Y') { Write-Host 'Aborting.'; exit 0 }
    } else {
        Write-Host "Run the script with -ConfirmDeletion to be prompted, or -Force to delete without prompts." -ForegroundColor Yellow
        exit 0
    }
}

# Proceed with deletion
foreach ($r in $candidates) {
    $id = $r.databaseId
    Write-Host "Deleting run $id..." -ForegroundColor Yellow
    $delRun = gh api --method DELETE "/repos/$Repo/actions/runs/$id" 2>&1
    if ($?) { Write-Host "Deleted run $id" -ForegroundColor Green } else { Write-Host "Failed to delete run ${id}: $delRun" -ForegroundColor Red }

    # Delete artifacts associated with this run
    $artJson = gh api "/repos/$Repo/actions/runs/$id/artifacts" 2>$null
    if ($artJson) {
        try {
            $arts = ($artJson | ConvertFrom-Json).artifacts
            foreach ($a in $arts) {
                Write-Host "  Deleting artifact id $($a.id) name $($a.name)..."
                gh api --method DELETE "/repos/$Repo/actions/artifacts/$($a.id)" 2>&1 | Out-Null
            }
        } catch {
            Write-Host "  No artifacts to delete or failed to parse artifact list." -ForegroundColor Yellow
        }
    }
}

Write-Host "Cleanup complete." -ForegroundColor Green
