param(
    [string]$AppDir = "C:\xampp\htdocs\lukrato",
    [string]$PhpBin = "C:\xampp\php\php.exe",
    [int]$QueueSleepSeconds = 2,
    [int]$QueueMaxAttempts = 3,
    [int]$QueueStaleTtlSeconds = 900,
    [int]$RestartDelaySeconds = 5,
    [string]$LogFile = ""
)

$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($LogFile)) {
    $LogFile = Join-Path $AppDir "storage\logs\importacoes-worker.log"
}

$WorkerScript = Join-Path $AppDir "cli\process_importacoes_queue.php"
$LogDir = Split-Path -Parent $LogFile

if (-not (Test-Path -LiteralPath $PhpBin)) {
    throw "PHP nao encontrado em: $PhpBin"
}

if (-not (Test-Path -LiteralPath $WorkerScript)) {
    throw "Worker script nao encontrado em: $WorkerScript"
}

if (-not (Test-Path -LiteralPath $LogDir)) {
    New-Item -Path $LogDir -ItemType Directory -Force | Out-Null
}

function Write-WorkerLog {
    param([string]$Message)

    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    Add-Content -Path $LogFile -Value "[$timestamp] $Message"
}

Write-WorkerLog "Wrapper iniciado. app_dir=$AppDir php_bin=$PhpBin"

while ($true) {
    try {
        $env:IMPORTACOES_QUEUE_SLEEP = [string]$QueueSleepSeconds
        $env:IMPORTACOES_QUEUE_MAX_ATTEMPTS = [string]$QueueMaxAttempts
        $env:IMPORTACOES_QUEUE_STALE_TTL = [string]$QueueStaleTtlSeconds

        Write-WorkerLog "Iniciando worker PHP (sleep=$QueueSleepSeconds max_attempts=$QueueMaxAttempts stale_ttl=$QueueStaleTtlSeconds)"

        & $PhpBin $WorkerScript 2>&1 | ForEach-Object {
            Write-WorkerLog ($_.ToString())
        }

        $exitCode = $LASTEXITCODE
        Write-WorkerLog "Worker finalizado com codigo $exitCode. Reiniciando em $RestartDelaySeconds s."
    }
    catch {
        Write-WorkerLog "Erro no wrapper: $($_.Exception.Message). Reiniciando em $RestartDelaySeconds s."
    }

    Start-Sleep -Seconds $RestartDelaySeconds
}
