param([string]$path)
$s = Get-Content $path -Raw
$matches = [regex]::Matches($s,'\}\}')
for ($i=0;$i -lt $matches.Count;$i++){
  $m = $matches[$i]
  $idx = $m.Index
  $snippet = $s.Substring([Math]::Max(0,$idx-20), [Math]::Min(60, $s.Length-$idx+20))
  Write-Host "Match $i index=$idx snippet=...$snippet..."
}
