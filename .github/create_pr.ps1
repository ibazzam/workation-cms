$hosts = Join-Path $env:USERPROFILE ".config\gh\hosts.yml"
if (-not (Test-Path $hosts)) { Write-Error "hosts file not found: $hosts"; exit 1 }
$content = Get-Content $hosts
$token = $null
foreach ($line in $content) {
    if ($line -match 'oauth_token:\s*(\S+)') {
        $token = $matches[1]
        break
    }
}
if (-not $token) { Write-Error "token not found"; exit 1 }
$body = @{
    title = 'chore: add npm test script'
    head  = 'ci-add-test-script'
    base  = 'main'
    body  = 'Make npm test run the frontend build so CI has a meaningful npm test step.'
} | ConvertTo-Json -Depth 4
$headers = @{ Authorization = "token $token"; Accept = 'application/vnd.github+json' }
$resp = Invoke-RestMethod -Uri 'https://api.github.com/repos/ibazzam/workation-cms/pulls' -Method Post -Headers $headers -Body $body -ContentType 'application/json'
$resp.html_url
