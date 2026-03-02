param(
    [string]$LogPath = 'artifacts\run_22486901985\full_run_log.txt',
    [string]$OutPath = 'artifacts\run_22486901985\prisma_p1012_matches.txt'
)
if (-not (Test-Path $LogPath)) {
    Write-Output "Log file not found: $LogPath"
    exit 0
}
Select-String -Path $LogPath -Pattern 'P1012' -AllMatches -Context 2,2 | Out-File -FilePath $OutPath -Encoding UTF8
if ((Get-Item $OutPath).Length -gt 0) {
    Write-Output "Matches saved to $OutPath"
} else {
    Write-Output "No matches found"
}
