param(
  [Parameter(Mandatory = $true)]
  [string]$AppUrl,

  [Parameter(Mandatory = $true)]
  [string]$ApiUrl
)

$ErrorActionPreference = 'Stop'

function Get-CheckResult {
  param(
    [string]$Label,
    [string]$Url
  )

  Write-Host "[check] $Label => $Url"
  $response = Invoke-WebRequest -Uri $Url -Method GET -UseBasicParsing

  $server = $response.Headers['server']
  $cfRay = $response.Headers['cf-ray']
  $cfCacheStatus = $response.Headers['cf-cache-status']

  [PSCustomObject]@{
    Label = $Label
    Url = $Url
    StatusCode = $response.StatusCode
    Server = if ($server) { $server } else { '' }
    CfRay = if ($cfRay) { $cfRay } else { '' }
    CfCacheStatus = if ($cfCacheStatus) { $cfCacheStatus } else { '' }
  }
}

$checks = @()
$checks += Get-CheckResult -Label 'App' -Url $AppUrl
$checks += Get-CheckResult -Label 'API Health' -Url "$ApiUrl/health"

Write-Host ''
Write-Host '[result] Cloudflare preflight summary'
$checks | Format-Table -AutoSize

$app = $checks | Where-Object { $_.Label -eq 'App' }
$api = $checks | Where-Object { $_.Label -eq 'API Health' }

if ($app.StatusCode -ne 200 -or $api.StatusCode -ne 200) {
  throw 'Preflight failed: expected HTTP 200 for app and API health.'
}

if (-not $app.CfRay -and -not $api.CfRay) {
  Write-Warning 'Cloudflare edge headers were not detected. Verify proxied DNS (orange cloud) and routing.'
}

if ($api.CfCacheStatus -and $api.CfCacheStatus -ne 'DYNAMIC' -and $api.CfCacheStatus -ne 'BYPASS') {
  Write-Warning "API response cache status is '$($api.CfCacheStatus)'. Ensure /api/* is bypassed in Cloudflare cache rules."
}

Write-Host '[result] Preflight completed.'
