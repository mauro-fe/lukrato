# Worker continuo de importacoes

Este guia configura execucao continua do worker da fila de importacoes.

- Script do worker: [cli/process_importacoes_queue.php](../cli/process_importacoes_queue.php)
- Setup Windows: [deploy/windows](../deploy/windows)
- Setup Linux (Supervisor): [deploy/supervisor/lukrato-importacoes-worker.supervisor.conf.example](../deploy/supervisor/lukrato-importacoes-worker.supervisor.conf.example)

## O que este setup resolve

- Processa jobs `queued` sem acao manual.
- Reinicia automaticamente em caso de falha.
- Mantem logs em arquivo para observabilidade.

## Pre-requisito de banco

Antes de iniciar o worker, confirme que a migration `2026_07_01_create_importacao_jobs_table` foi aplicada.

Sem essa tabela, o worker entra em loop de erro com `Table 'importacao_jobs' doesn't exist`.

## Parametros operacionais

As variaveis abaixo podem ser ajustadas no processo do worker:

- `IMPORTACOES_QUEUE_SLEEP` (default `2`): pausa entre ciclos ociosos.
- `IMPORTACOES_QUEUE_MAX_ATTEMPTS` (default `3`): tentativas maximas por job.
- `IMPORTACOES_QUEUE_STALE_TTL` (default `900`): segundos para considerar job `processing` como travado.

## Windows Task Scheduler (recomendado para este ambiente)

### 1) Registrar task (PowerShell Admin)

```powershell
Set-Location C:\xampp\htdocs\lukrato
powershell -NoProfile -ExecutionPolicy Bypass -File .\deploy\windows\register-importacoes-worker-task.ps1 \
  -TaskName "Lukrato Importacoes Worker" \
  -AppDir "C:\xampp\htdocs\lukrato" \
  -PhpBin "C:\xampp\php\php.exe" \
  -QueueSleepSeconds 2 \
  -QueueMaxAttempts 3 \
  -QueueStaleTtlSeconds 900 \
  -RestartDelaySeconds 5
```

### 2) Validar task registrada

```powershell
Get-ScheduledTask -TaskName "Lukrato Importacoes Worker"
Get-ScheduledTaskInfo -TaskName "Lukrato Importacoes Worker"
```

### 3) Acompanhar logs

```powershell
Get-Content .\storage\logs\importacoes-worker.log -Wait
```

### 4) Remover task (rollback)

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\deploy\windows\unregister-importacoes-worker-task.ps1 -TaskName "Lukrato Importacoes Worker"
```

## Windows sem Admin (fallback com Startup do usuario)

Se o Task Scheduler retornar `Acesso negado`, use o Startup do usuario atual.

### 1) Instalar entry no Startup e iniciar agora

```powershell
Set-Location C:\xampp\htdocs\lukrato
powershell -NoProfile -ExecutionPolicy Bypass -File .\deploy\windows\install-importacoes-worker-startup.ps1 -AppDir "C:\xampp\htdocs\lukrato" -PhpBin "C:\xampp\php\php.exe" -QueueSleepSeconds 2 -QueueMaxAttempts 3 -QueueStaleTtlSeconds 900 -RestartDelaySeconds 5 -StartNow
```

### 2) Validar runtime

```powershell
Get-Content .\storage\logs\importacoes-worker.log -Wait
```

### 3) Remover entry (rollback)

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\deploy\windows\uninstall-importacoes-worker-startup.ps1 -StopRunning
```

## Linux com Supervisor

1. Copie o arquivo de exemplo para `/etc/supervisor/conf.d/`.
2. Ajuste `directory`, `command` e `user`.
3. Recarregue o Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart lukrato-importacoes-worker
sudo supervisorctl status lukrato-importacoes-worker
```

## Health check rapido

Para verificar o runtime manualmente:

```bash
php cli/process_importacoes_queue.php --once
```

Saidas esperadas:

- `Nenhum job pendente na fila.` quando ocioso.
- `Job #X ... status completed` quando processa com sucesso.
- `status queued` para reprocessamento (retry) quando falha temporaria.
- `status failed` quando esgota tentativas.
