<?php

declare(strict_types=1);

use Application\Core\Router;

/**
 * ============================================
 * WEBHOOKS DE TERCEIROS
 * ============================================
 * Estas rotas NÃO têm autenticação de sessão.
 * A validação é feita via assinatura/token específico.
 */

// Asaas (Gateway de pagamento)
Router::add('POST', '/api/webhook/asaas', 'Api\\AsaasWebhookController@receive');
Router::add('GET',  '/api/webhook/asaas', 'Api\\AsaasWebhookController@test');

/**
 * ============================================
 * SCHEDULER / CRON JOBS VIA HTTP
 * ============================================
 * Rotas para executar tarefas agendadas via HTTP.
 * Ideal para ambientes hospedados sem suporte a cron nativo.
 * Autenticação via header X-Scheduler-Token ou query param ?token=
 */

// Health check (público)
Router::add('GET', '/api/scheduler/health', 'Api\\SchedulerController@health');

// Tarefas agendadas (requerem SCHEDULER_TOKEN)
Router::add('GET',  '/api/scheduler/tasks', 'Api\\SchedulerController@tasks');
Router::add('GET',  '/api/scheduler/debug', 'Api\\SchedulerController@debug');
Router::add('GET',  '/api/scheduler/dispatch-reminders', 'Api\\SchedulerController@dispatchReminders');
Router::add('POST', '/api/scheduler/dispatch-reminders', 'Api\\SchedulerController@dispatchReminders');
Router::add('GET',  '/api/scheduler/process-expired-subscriptions', 'Api\\SchedulerController@processExpiredSubscriptions');
Router::add('POST', '/api/scheduler/process-expired-subscriptions', 'Api\\SchedulerController@processExpiredSubscriptions');
