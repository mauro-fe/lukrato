<?php

declare(strict_types=1);

use Application\Core\Router;

/**
 * ============================================
 * WEBHOOKS DE TERCEIROS
 * ============================================
 * Estas rotas nao tem autenticacao de sessao.
 * A validacao e feita via assinatura/token especifico.
 */

// Asaas (Gateway de pagamento)
Router::add('POST', '/api/webhook/asaas', 'Api\\Billing\\AsaasWebhookController@receive');

// GET apenas para desenvolvimento (retorna 404 em producao)
if (!defined('APP_ENV') || APP_ENV !== 'production') {
    Router::add('GET', '/api/webhook/asaas', 'Api\\Billing\\AsaasWebhookController@test');
}

// WhatsApp (Meta Cloud API)
Router::add('GET', '/api/webhook/whatsapp', 'Api\\AI\\WhatsAppWebhookController@verify');
Router::add('POST', '/api/webhook/whatsapp', 'Api\\AI\\WhatsAppWebhookController@receive');

// Telegram (Bot API)
Router::add('POST', '/api/webhook/telegram', 'Api\\AI\\TelegramWebhookController@receive');

/**
 * ============================================
 * OPERACOES INTERNAS
 * ============================================
 * Tarefas operacionais/scheduler nao ficam mais expostas por HTTP.
 * A entrada oficial e o runner CLI interno:
 *
 *   php cli/run_scheduler.php list
 *   php cli/run_scheduler.php run all
 *   php cli/run_scheduler.php run dispatch-reminders
 */
