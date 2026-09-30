param(
    [string]$CacheDir = '.local-cache'
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$env:LOCAL_CS01_API_TOKEN = 'local-only-cs01-token'
$env:LOCAL_CS02_API_TOKEN = 'local-only-cs02-token'
$env:LOCAL_IT01_API_TOKEN = 'local-only-it01-token'
$env:LOCAL_ENG01_API_TOKEN = 'local-only-eng01-token'
$env:LOCAL_SCI01_API_TOKEN = 'local-only-sci01-token'

python -m ops.multi_host_entrypoint `
    --registry config/hosts.local.example.json `
    --cache-dir $CacheDir
