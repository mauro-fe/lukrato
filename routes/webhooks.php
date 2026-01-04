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
