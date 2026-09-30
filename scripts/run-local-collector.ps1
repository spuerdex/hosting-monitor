param(
    [string]$CacheDir = '.local-cache'
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$env:LOCAL_CS_API_TOKEN = 'local-only-cs-token'
$env:LOCAL_IT_API_TOKEN = 'local-only-it-token'

python -m ops.multi_host_entrypoint `
    --registry config/hosts.local.example.json `
    --cache-dir $CacheDir
