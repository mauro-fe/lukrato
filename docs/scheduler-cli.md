# Scheduler CLI

O scheduler interno do Lukrato nao roda mais por rotas HTTP publicas.

As rotas antigas de `scheduler/cron` foram removidas do [webhooks.php](C:/xampp/htdocs/lukrato/routes/webhooks.php) e qualquer acesso HTTP legado ao [SchedulerController.php](C:/xampp/htdocs/lukrato/Application/Controllers/Api/Billing/SchedulerController.php) agora responde `410 Gone`.

## Entrada oficial

Use o runner CLI:

```bash
php cli/run_scheduler.php help
php cli/run_scheduler.php list
php cli/run_scheduler.php health
php cli/run_scheduler.php run all
php cli/run_scheduler.php run dispatch-reminders
```

O runner central delega para [SchedulerTaskRunner.php](C:/xampp/htdocs/lukrato/Application/Services/Infrastructure/SchedulerTaskRunner.php).

## Tarefas disponiveis

- `dispatch-reminders`: lembretes de lancamentos. Recomendado: a cada 10 minutos.
- `dispatch-birthdays`: notificacoes de aniversario. Recomendado: diariamente as 08:00.
- `dispatch-fatura-reminders`: lembretes de vencimento de fatura. Recomendado: a cada 1 hora.
- `process-expired-subscriptions`: expiracao/bloqueio de assinaturas PRO. Recomendado: a cada 1 hora.
- `generate-recurring-lancamentos`: gera recorrencias vencidas. Recomendado: diariamente as 02:00.
- `process-recurring-card-items`: gera itens recorrentes de cartao. Recomendado: diariamente as 03:00.
- `dispatch-scheduled-campaigns`: envia campanhas agendadas. Recomendado: a cada 5 minutos.

## Lock global

Execucoes `run` usam [SchedulerExecutionLock.php](C:/xampp/htdocs/lukrato/Application/Services/Infrastructure/SchedulerExecutionLock.php) para impedir concorrencia entre processos.

Se outra execucao ja estiver rodando, o comando falha com exit code `1`.

## Exemplo de cron no Linux

Arquivo pronto no repositório:

```text
deploy/cron/lukrato-scheduler.cron.example
```

Depois de ajustar `APP_DIR` e `PHP_BIN`, aplique no servidor com:

```bash
crontab deploy/cron/lukrato-scheduler.cron.example
```

Conteudo:

```cron
*/5 * * * * cd /var/www/lukrato && php cli/run_scheduler.php run dispatch-scheduled-campaigns
*/10 * * * * cd /var/www/lukrato && php cli/run_scheduler.php run dispatch-reminders
0 * * * * cd /var/www/lukrato && php cli/run_scheduler.php run dispatch-fatura-reminders
0 * * * * cd /var/www/lukrato && php cli/run_scheduler.php run process-expired-subscriptions
0 2 * * * cd /var/www/lukrato && php cli/run_scheduler.php run generate-recurring-lancamentos
0 3 * * * cd /var/www/lukrato && php cli/run_scheduler.php run process-recurring-card-items
0 8 * * * cd /var/www/lukrato && php cli/run_scheduler.php run dispatch-birthdays
```

## Exemplo no Windows Task Scheduler

Programa/script:

```text
php
```

Argumentos:

```text
cli/run_scheduler.php run dispatch-reminders
```

Iniciar em:

```text
C:\xampp\htdocs\lukrato
```

## Observacoes de deploy

- atualize qualquer cron antigo que chamava `/api/scheduler/*` ou `/api/rota-do-cron`
- mantenha HTTP apenas para webhooks externos inevitaveis
- novas rotinas operacionais devem entrar primeiro no runner CLI, nao em rota publica
- o arquivo versionado para cron Linux fica em `deploy/cron/lukrato-scheduler.cron.example`
