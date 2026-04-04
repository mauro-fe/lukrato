param(
    [string]$EntryName = "LukratoImportacoesWorker",
    [string]$AppDir = "C:\xampp\htdocs\lukrato",
    [string]$PhpBin = "C:\xampp\php\php.exe",
    [int]$QueueSleepSeconds = 2,
    [int]$QueueMaxAttempts = 3,
    [int]$QueueStaleTtlSeconds = 900,
    [int]$RestartDelaySeconds = 5,
    [string]$LogFile = "",
    [switch]$StartNow
)

$ErrorActionPreference = "Stop"

$runnerScript = Join-Path $AppDir "deploy\windows\run-importacoes-worker.ps1"
if (-not (Test-Path -LiteralPath $runnerScript)) {
    throw "Runner script nao encontrado em: $runnerScript"
}

if (-not (Test-Path -LiteralPath $PhpBin)) {
    throw "PHP nao encontrado em: $PhpBin"
}

if ([string]::IsNullOrWhiteSpace($LogFile)) {
    $LogFile = Join-Path $AppDir "storage\logs\importacoes-worker.log"
}

$startupDir = Join-Path $env:APPDATA "Microsoft\Windows\Start Menu\Programs\Startup"
if (-not (Test-Path -LiteralPath $startupDir)) {
    throw "Pasta Startup nao encontrada em: $startupDir"
}

$entryFile = Join-Path $startupDir ($EntryName + ".cmd")

$arguments = @(
    "-NoProfile",
    "-ExecutionPolicy", "Bypass",
    "-WindowStyle", "Hidden",
    "-File", ('"{0}"' -f $runnerScript),
    "-AppDir", ('"{0}"' -f $AppDir),
    "-PhpBin", ('"{0}"' -f $PhpBin),
    "-QueueSleepSeconds", $QueueSleepSeconds,
    "-QueueMaxAttempts", $QueueMaxAttempts,
    "-QueueStaleTtlSeconds", $QueueStaleTtlSeconds,
    "-RestartDelaySeconds", $RestartDelaySeconds,
    "-LogFile", ('"{0}"' -f $LogFile)
)

$cmdContent = @(
    "@echo off",
    ('start "" powershell.exe {0}' -f ($arguments -join ' '))
) -join "`r`n"

Set-Content -LiteralPath $entryFile -Value $cmdContent -Encoding Ascii

if ($StartNow) {
    Start-Process -FilePath "powershell.exe" -ArgumentList ($arguments -join " ") -WindowStyle Hidden
}

Write-Host "Startup entry criada com sucesso." -ForegroundColor Green
Write-Host "Arquivo:  $entryFile"
Write-Host "Runner:   $runnerScript"
Write-Host "Log:      $LogFile"
if ($StartNow) {
    Write-Host "Worker iniciado em background para a sessao atual."
}
