# Guia de Configuração do Scheduler (Lembretes e Tarefas Agendadas)

Este guia explica como configurar o sistema de lembretes e tarefas agendadas no ambiente hospedado.

## Configuração do .env

Adicione a seguinte variável no seu arquivo `.env`:

```env
# Token secreto para autenticar requisições do scheduler
# Gere um token seguro com: php -r "echo bin2hex(random_bytes(32));"
SCHEDULER_TOKEN=seu_token_secreto_aqui
```

## Endpoints Disponíveis

### Health Check (Público)

```
GET /api/scheduler/health
```

Retorna status do scheduler. Não requer autenticação.

### Debug (Requer Token)

```
GET /api/scheduler/debug?token=SEU_TOKEN
```

Mostra informações de configuração e próximos lembretes.

### Dispatch Reminders (Requer Token)

```
GET /api/scheduler/dispatch-reminders?token=SEU_TOKEN
POST /api/scheduler/dispatch-reminders
    Header: X-Scheduler-Token: SEU_TOKEN
```

Processa e envia lembretes de agendamentos pendentes.

### Process Expired Subscriptions (Requer Token)

```
GET /api/scheduler/process-expired-subscriptions?token=SEU_TOKEN
```

Processa assinaturas expiradas.

## Opções de Configuração

### Opção 1: Cron Job Nativo (Recomendado)

Se seu servidor suporta cron jobs nativos, configure assim:

```bash
# Lembretes a cada 5 minutos
*/5 * * * * curl -s "https://seudominio.com.br/api/scheduler/dispatch-reminders?token=SEU_TOKEN" > /dev/null

# Assinaturas expiradas a cada hora
0 * * * * curl -s "https://seudominio.com.br/api/scheduler/process-expired-subscriptions?token=SEU_TOKEN" > /dev/null
```

### Opção 2: Cron URL (Hostinger, cPanel, etc)

A maioria dos painéis de hospedagem oferece "Cron Jobs" ou "Tarefas Agendadas":

1. Acesse o painel de controle (cPanel, hPanel, etc)
2. Procure por "Cron Jobs" ou "Tarefas Agendadas"
3. Adicione as URLs:
   - **Lembretes** (a cada 5 minutos):

     ```
     https://seudominio.com.br/api/scheduler/dispatch-reminders?token=SEU_TOKEN
     ```

   - **Assinaturas** (a cada hora):
     ```
     https://seudominio.com.br/api/scheduler/process-expired-subscriptions?token=SEU_TOKEN
     ```

### Opção 3: Serviço Externo (cron-job.org, EasyCron, etc)

Use um serviço de cron externo gratuito:

1. Crie uma conta em https://cron-job.org/ (gratuito)
2. Adicione as URLs acima
3. Configure o intervalo desejado

## Autenticação

O token pode ser enviado de duas formas:

1. **Query Parameter** (mais fácil para crons URL):

   ```
   ?token=SEU_TOKEN
   ```

2. **Header HTTP** (mais seguro para aplicações):
   ```
   X-Scheduler-Token: SEU_TOKEN
   ```

## Testando a Configuração

### 1. Verificar health check

```bash
curl https://seudominio.com.br/api/scheduler/health
```

### 2. Verificar configurações (com debug)

```bash
curl "https://seudominio.com.br/api/scheduler/debug?token=SEU_TOKEN"
```

### 3. Testar envio de lembretes

```bash
curl "https://seudominio.com.br/api/scheduler/dispatch-reminders?token=SEU_TOKEN"
```

## Resposta de Sucesso

```json
{
  "success": true,
  "message": "Lembretes processados com sucesso",
  "stats": {
    "processados": 5,
    "enviados_inapp": 3,
    "enviados_email": 2,
    "ignorados": 2,
    "erros": []
  }
}
```

## Logs

Os logs são salvos em:

- `storage/logs/app-YYYY-MM-DD.log`

Procure por `[Scheduler]` para ver os logs do scheduler.

## Requisitos para Emails

Para que os emails funcionem, configure também:

```env
MAIL_HOST=smtp.seuprovedor.com
MAIL_PORT=587
MAIL_USERNAME=seu@email.com
MAIL_PASSWORD=sua_senha
MAIL_ENCRYPTION=tls
MAIL_FROM=noreply@seudominio.com.br
MAIL_FROM_NAME=Lukrato
```

## Troubleshooting

### Lembretes não estão sendo enviados

1. Verifique se o token está configurado:

   ```bash
   curl "https://seudominio.com.br/api/scheduler/debug?token=SEU_TOKEN"
   ```

2. Verifique se há agendamentos pendentes com `canal_email` ou `canal_inapp` ativados

3. Verifique os logs em `storage/logs/`

### Erro 401 Unauthorized

O token está incorreto ou não configurado. Verifique:

- A variável `SCHEDULER_TOKEN` no `.env`
- Se você está passando o token corretamente na URL ou header

### Emails não estão chegando

1. Verifique configuração SMTP no debug:

   ```bash
   curl "https://seudominio.com.br/api/scheduler/debug?token=SEU_TOKEN"
   ```

2. Verifique se `mail_configured` é `true`
3. Teste o envio de email em outra parte do sistema (ex: redefinição de senha)
