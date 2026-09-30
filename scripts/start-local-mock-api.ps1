param(
    [int]$ComputerScience01Port = 9001,
    [int]$ComputerScience02Port = 9002,
    [int]$InformationTechnology01Port = 9003,
    [int]$Engineering01Port = 9004,
    [int]$Science01Port = 9005
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot

$processes = @(
    Start-Process -FilePath 'python' -WorkingDirectory $root -PassThru `
        -ArgumentList @(
            'ops/mock_api_server.py',
            '--code', 'cs-01',
            '--fixture', 'fixtures/hosts/cs-01/status.json',
            '--port', $ComputerScience01Port
        )
    Start-Process -FilePath 'python' -WorkingDirectory $root -PassThru `
        -ArgumentList @(
            'ops/mock_api_server.py',
            '--code', 'cs-02',
            '--fixture', 'fixtures/hosts/cs-02/status.json',
            '--port', $ComputerScience02Port
        )
    Start-Process -FilePath 'python' -WorkingDirectory $root -PassThru `
        -ArgumentList @(
            'ops/mock_api_server.py',
            '--code', 'it-01',
            '--fixture', 'fixtures/hosts/it-01/status.json',
            '--port', $InformationTechnology01Port
        )
    Start-Process -FilePath 'python' -WorkingDirectory $root -PassThru `
        -ArgumentList @(
            'ops/mock_api_server.py',
            '--code', 'eng-01',
            '--fixture', 'fixtures/hosts/eng-01/status.json',
            '--port', $Engineering01Port
        )
    Start-Process -FilePath 'python' -WorkingDirectory $root -PassThru `
        -ArgumentList @(
            'ops/mock_api_server.py',
            '--code', 'sci-01',
            '--fixture', 'fixtures/hosts/sci-01/status.json',
            '--port', $Science01Port
        )
)

$processes | Select-Object Id, ProcessName
Write-Output 'Local mock APIs started. Stop them with: Stop-Process -Id <id>'
