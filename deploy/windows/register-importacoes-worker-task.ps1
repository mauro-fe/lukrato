param(
    [string]$TaskName = "Lukrato Importacoes Worker",
    [string]$AppDir = "C:\xampp\htdocs\lukrato",
    [string]$PhpBin = "C:\xampp\php\php.exe",
    [int]$QueueSleepSeconds = 2,
    [int]$QueueMaxAttempts = 3,
    [int]$QueueStaleTtlSeconds = 900,
    [int]$RestartDelaySeconds = 5,
    [string]$LogFile = "",
    [switch]$RunAsCurrentUser
)

$ErrorActionPreference = "Stop"

$RunnerScript = Join-Path $AppDir "deploy\windows\run-importacoes-worker.ps1"
if (-not (Test-Path -LiteralPath $RunnerScript)) {
    throw "Runner script nao encontrado em: $RunnerScript"
}

if (-not (Test-Path -LiteralPath $PhpBin)) {
    throw "PHP nao encontrado em: $PhpBin"
}

$arguments = @(
    "-NoProfile",
    "-ExecutionPolicy", "Bypass",
    "-File", ('"{0}"' -f $RunnerScript),
    "-AppDir", ('"{0}"' -f $AppDir),
    "-PhpBin", ('"{0}"' -f $PhpBin),
    "-QueueSleepSeconds", $QueueSleepSeconds,
    "-QueueMaxAttempts", $QueueMaxAttempts,
    "-QueueStaleTtlSeconds", $QueueStaleTtlSeconds,
    "-RestartDelaySeconds", $RestartDelaySeconds
)

if (-not [string]::IsNullOrWhiteSpace($LogFile)) {
    $arguments += @("-LogFile", ('"{0}"' -f $LogFile))
}

$action = New-ScheduledTaskAction `
    -Execute "powershell.exe" `
    -Argument ($arguments -join " ") `
    -WorkingDirectory $AppDir

$startupTrigger = New-ScheduledTaskTrigger -AtStartup
$triggers = @($startupTrigger)

if ($RunAsCurrentUser) {
    $currentUser = "{0}\{1}" -f $env:USERDOMAIN, $env:USERNAME
    $principal = New-ScheduledTaskPrincipal `
        -UserId $currentUser `
        -LogonType Interactive `
        -RunLevel Highest
    $triggers += New-ScheduledTaskTrigger -AtLogOn -User $currentUser
} else {
    $principal = New-ScheduledTaskPrincipal `
        -UserId "SYSTEM" `
        -LogonType ServiceAccount `
        -RunLevel Highest
}

$settings = New-ScheduledTaskSettingsSet `
    -StartWhenAvailable `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -MultipleInstances IgnoreNew `
    -RestartCount 999 `
    -RestartInterval (New-TimeSpan -Minutes 1) `
    -ExecutionTimeLimit (New-TimeSpan -Days 3650)

$task = New-ScheduledTask `
    -Action $action `
    -Trigger $triggers `
    -Principal $principal `
    -Settings $settings `
    -Description "Worker continuo da fila de importacoes OFX/CSV do Lukrato"

Register-ScheduledTask -TaskName $TaskName -InputObject $task -Force | Out-Null
Start-ScheduledTask -TaskName $TaskName

Write-Host "Task Scheduler registrado e iniciado com sucesso." -ForegroundColor Green
Write-Host "TaskName: $TaskName"
Write-Host "AppDir:   $AppDir"
Write-Host "PhpBin:   $PhpBin"
