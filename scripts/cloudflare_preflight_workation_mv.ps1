param(
  [string]$AppUrl = 'https://workation.mv',
  [string]$ApiUrl = 'https://api.workation.mv/api/v1'
)

$scriptPath = Join-Path $PSScriptRoot 'cloudflare_preflight_check.ps1'

powershell -ExecutionPolicy Bypass -File $scriptPath -AppUrl $AppUrl -ApiUrl $ApiUrl
if ($LASTEXITCODE -ne 0) {
  throw "Cloudflare preflight failed with exit code $LASTEXITCODE"
}

Write-Host "Cloudflare preflight completed for app=$AppUrl api=$ApiUrl"
