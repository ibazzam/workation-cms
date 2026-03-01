$ErrorActionPreference = 'Stop'
$ArtifactsDir = 'artifacts\run_22486901985'
$Out = Join-Path $ArtifactsDir 'prisma_p1012_matches_all.txt'
if (-not (Test-Path $ArtifactsDir)) { Write-Output "Artifacts directory not found: $ArtifactsDir"; exit 0 }
Get-ChildItem $ArtifactsDir -Recurse -File | ForEach-Object {
    try {
        Select-String -Path $_.FullName -Pattern 'P1012' -AllMatches -Context 2,2 -ErrorAction SilentlyContinue
    } catch { }
} | Out-File -FilePath $Out -Encoding UTF8
if ((Get-Item $Out).Length -gt 0) { Write-Output "Matches saved to $Out" } else { Write-Output "No matches found in artifacts" }
