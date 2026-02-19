$files = Get-ChildItem -Path .github/workflows -Include *.yml,*.yaml -Recurse -ErrorAction SilentlyContinue
if (-not $files) { Write-Host 'No workflow files found.'; exit 0 }
$result = @()
foreach ($f in $files) {
  $text = Get-Content $f.FullName -Raw
  $single = ($text -split "'" | Measure-Object).Count - 1
  $double = ($text -split '"' | Measure-Object).Count - 1
  $open = ([regex]::Matches($text,'\$\{\{').Count)
  $close = ([regex]::Matches($text,'\}\}').Count)
  $tabs = ($text -match "`t")
  $result += [PSCustomObject]@{
    path = $f.FullName
    singleQuotes = $single
    doubleQuotes = $double
    openTempl = $open
    closeTempl = $close
    tabs = $tabs
  }
}
$result | ConvertTo-Json -Depth 5
