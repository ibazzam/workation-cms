param(
    [Parameter(Mandatory=$true)][string]$Url
)
$ErrorActionPreference = 'Stop'
if(-not (Test-Path 'artifacts')) { New-Item -ItemType Directory -Path 'artifacts' | Out-Null }
$Out = 'artifacts\run_0844e74a_job_logs.txt'
try {
    Invoke-WebRequest -Uri $Url -OutFile $Out -UseBasicParsing
} catch {
    Write-Error "Download failed: $_"
    exit 2
}
$pattern = 'P1012|Prisma schema validation|Type .* is neither a built-in type'
$matches = Select-String -Path $Out -Pattern $pattern -AllMatches -Context 2,2 -ErrorAction SilentlyContinue
if ($matches) {
    $matches | ForEach-Object { $_.ToString() } | Out-File -FilePath 'artifacts\prisma_p1012_matches.txt' -Encoding UTF8
    Write-Output "Matches saved to artifacts\prisma_p1012_matches.txt"
} else {
    Write-Output "No matches found in downloaded log"
}
