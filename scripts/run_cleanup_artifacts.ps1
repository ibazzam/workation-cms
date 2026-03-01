$repoRoot = (Resolve-Path "$PSScriptRoot/..").Path
Set-Location $repoRoot
node scripts/cleanup_workspace_artifacts.cjs --execute
