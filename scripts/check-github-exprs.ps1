param([string]$path)
$s = Get-Content $path -Raw
$opens = [regex]::Matches($s,'\$\{\{') | ForEach-Object { $_.Index }
$closes = [regex]::Matches($s,'\}\}') | ForEach-Object { $_.Index }
# Print counts without interpolating ${{
Write-Host ("Found {0} openers and {1} closers." -f $opens.Count, $closes.Count)
if ($opens.Count -gt $closes.Count) { Write-Host 'Fewer closers than openers (possible unpaired)'; exit 1 }
# For each open check there's a closer after it
for ($i=0;$i -lt $opens.Count;$i++){
  $openIdx = $opens[$i]
  $match = $closes | Where-Object { $_ -gt $openIdx } | Select-Object -First 1
  if (-not $match) { Write-Host "No closer found for opener at index $openIdx"; exit 2 }
}
Write-Host 'All ${{ have a later }} closer.'
