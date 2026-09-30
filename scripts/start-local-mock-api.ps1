param(
    [int]$ComputerSciencePort = 9001,
    [int]$InformationTechnologyPort = 9002
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot

$processes = @(
    Start-Process -FilePath 'python' -WorkingDirectory $root -PassThru `
        -ArgumentList @(
            'ops/mock_api_server.py',
            '--code', 'cs',
            '--fixture', 'fixtures/hosts/cs/status.json',
            '--port', $ComputerSciencePort
        )
    Start-Process -FilePath 'python' -WorkingDirectory $root -PassThru `
        -ArgumentList @(
            'ops/mock_api_server.py',
            '--code', 'it',
            '--fixture', 'fixtures/hosts/it/status.json',
            '--port', $InformationTechnologyPort
        )
)

$processes | Select-Object Id, ProcessName
Write-Output 'Local mock APIs started. Stop them with: Stop-Process -Id <id>'
