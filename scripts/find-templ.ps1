param(
  [string]$path
)
if (-not (Test-Path $path)) { Write-Error "File not found: $path"; exit 2 }
$lines = Get-Content $path
for ($i=0; $i -lt $lines.Count; $i++) {
  $ln = $lines[$i]
  if ($ln -match '\$\{\{' -or $ln -match '\}\}') {
    Write-Host ("Line " + ($i+1) + ": " + $ln.Trim())
  }
}
