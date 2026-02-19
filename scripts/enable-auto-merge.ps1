param(
  [string]$owner = 'ibazzam',
  [string]$repo  = 'workation-cms',
  [int]$pr       = 2
)
$pat = $env:COVERAGE_FETCH_PAT
if (-not $pat) { Write-Error 'COVERAGE_FETCH_PAT not set'; exit 2 }
$headers = @{ Authorization = 'token ' + $pat; 'User-Agent' = 'gh-auto-merge-file' }
$prUrl = "https://api.github.com/repos/$owner/$repo/pulls/$pr"
try {
  $prObj = Invoke-RestMethod -Uri $prUrl -Headers $headers -UseBasicParsing -ErrorAction Stop
} catch {
  Write-Host "Failed to fetch PR: $($_.Exception.Message)"
  exit 3
}
Write-Host "PR #$($prObj.number) state=$($prObj.state) mergeable=$($prObj.mergeable) mergeable_state=$($prObj.mergeable_state)"
$node = $prObj.node_id
$graphql = 'mutation { enablePullRequestAutoMerge(input: { pullRequestId: "' + $node + '", mergeMethod: SQUASH }) { pullRequest { number } clientMutationId } }'
$body = @{ query = $graphql } | ConvertTo-Json -Depth 10
try {
  $gql = Invoke-RestMethod -Uri 'https://api.github.com/graphql' -Method Post -Headers $headers -Body $body -ContentType 'application/json' -ErrorAction Stop
  Write-Host 'GraphQL response:'
  $gql | ConvertTo-Json -Depth 5 | Write-Host
  if ($gql.data -and $gql.data.enablePullRequestAutoMerge) { Write-Host 'Auto-merge enabled.'; exit 0 } else { Write-Host 'GraphQL call did not return expected data.'; exit 4 }
} catch {
  Write-Host 'GraphQL error:'
  if ($_.Exception.Response -ne $null) {
    $sr = New-Object IO.StreamReader($_.Exception.Response.GetResponseStream())
    $bodyText = $sr.ReadToEnd()
    Write-Host $bodyText
  } else {
    Write-Host $_.Exception.Message
  }
  exit 5
}
