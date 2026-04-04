param(
    [string]$EntryName = "LukratoImportacoesWorker",
    [switch]$StopRunning
)

$ErrorActionPreference = "Stop"

$startupDir = Join-Path $env:APPDATA "Microsoft\Windows\Start Menu\Programs\Startup"
$entryFile = Join-Path $startupDir ($EntryName + ".cmd")

if (Test-Path -LiteralPath $entryFile) {
    Remove-Item -LiteralPath $entryFile -Force
    Write-Host "Startup entry removida: $entryFile" -ForegroundColor Green
}
else {
    Write-Host "Startup entry nao encontrada: $entryFile"
}

if ($StopRunning) {
    $running = Get-CimInstance Win32_Process -Filter "Name = 'powershell.exe'" |
    Where-Object { $_.CommandLine -like "*run-importacoes-worker.ps1*" }

    $stopped = 0
    foreach ($process in $running) {
        try {
            Stop-Process -Id $process.ProcessId -Force -ErrorAction Stop
            $stopped++
        }
        catch {
            Write-Warning "Falha ao encerrar PID $($process.ProcessId): $($_.Exception.Message)"
        }
    }

    Write-Host "Processos do worker encerrados: $stopped"
}
