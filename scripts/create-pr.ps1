param(
  [string]$owner = 'ibazzam',
  [string]$repo = 'workation-cms',
  [string]$head = 'chore/auto-merge-retry',
  [string]$base = 'main',
  [string]$title = 'chore: add auto-merge retry workflow',
  [string]$body = 'Add a workflow to automatically enable auto-merge when PR checks become clean.'
)
$pat = $env:COVERAGE_FETCH_PAT
if (-not $pat) { Write-Error 'COVERAGE_FETCH_PAT not set'; exit 2 }
$headers = @{ Authorization = 'token ' + $pat; 'User-Agent' = 'create-pr-script' }
$payload = @{ title = $title; head = $head; base = $base; body = $body }
$payloadJson = $payload | ConvertTo-Json -Depth 10
try {
  $resp = Invoke-RestMethod -Uri "https://api.github.com/repos/$owner/$repo/pulls" -Method Post -Headers $headers -Body $payloadJson -ContentType 'application/json' -ErrorAction Stop
  Write-Host 'PR created:' $resp.html_url
} catch {
  Write-Host 'Request failed.'
  if ($_.Exception.Response -ne $null) {
    $sr = New-Object IO.StreamReader($_.Exception.Response.GetResponseStream())
    $bodyText = $sr.ReadToEnd()
    Write-Host $bodyText
  } else {
    Write-Host $_.Exception.Message
  }
  exit 3
}
