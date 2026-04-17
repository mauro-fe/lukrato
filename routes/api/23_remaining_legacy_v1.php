<?php

declare(strict_types=1);

use Application\Core\Router;

/**
 * Additive v1 aliases for legacy endpoints that still lacked a versioned path.
 * This preserves existing contracts while completing the backend v1 surface.
 */

// Onboarding
Router::add('POST', '/api/v1/tour/complete', 'Api\User\TourController@complete', ['auth', 'csrf', 'ratelimit']);

// Dashboard helpers not yet covered by previous v1 slices
Router::add('GET', '/api/v1/dashboard/metrics', 'Api\Financas\MetricsController@metrics', ['auth']);
Router::add('GET', '/api/v1/dashboard/transactions', 'Api\Dashboard\TransactionsController@transactions', ['auth']);
Router::add('GET', '/api/v1/dashboard/comparativo-competencia', 'Api\Dashboard\OverviewController@comparativoCompetenciaCaixa', ['auth']);
Router::add('GET', '/api/v1/dashboard/provisao', 'Api\Dashboard\OverviewController@provisao', ['auth']);
Router::add('GET', '/api/v1/dashboard/health-score', 'Api\Dashboard\HealthController@healthScore', ['auth']);
Router::add('GET', '/api/v1/dashboard/health-score/insights', 'Api\Dashboard\HealthController@healthScoreInsights', ['auth']);
Router::add('GET', '/api/v1/dashboard/greeting-insight', 'Api\Dashboard\HealthController@greetingInsight', ['auth']);
Router::add('GET', '/api/v1/options', 'Api\Financas\MetricsController@options', ['auth']);

// English account compatibility aliases
Router::add('GET', '/api/v1/accounts', 'Api\Conta\ContasController@index', ['auth']);
Router::add('POST', '/api/v1/accounts', 'Api\Conta\ContasController@store', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT', '/api/v1/accounts/{id}', 'Api\Conta\ContasController@update', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/v1/accounts/{id}', 'Api\Conta\ContasController@destroy', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/accounts/{id}/archive', 'Api\Conta\ContasController@archive', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/accounts/{id}/restore', 'Api\Conta\ContasController@restore', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/accounts/{id}/delete', 'Api\Conta\ContasController@hardDelete', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/accounts/archive', 'Api\Conta\ContasController@archive', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/accounts/unarchive', 'Api\Conta\ContasController@restore', ['auth', 'csrf', 'ratelimit']);

// Additional card operations not yet available under v1
Router::add('GET', '/api/v1/cartoes/validar-integridade', 'Api\Cartao\CartoesController@validarIntegridade', ['auth']);
Router::add('POST', '/api/v1/cartoes/{id}/deactivate', 'Api\Cartao\CartoesController@deactivate', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/cartoes/{id}/reactivate', 'Api\Cartao\CartoesController@reactivate', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT', '/api/v1/cartoes/{id}/limite', 'Api\Cartao\CartoesController@updateLimit', ['auth', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/cartoes/recorrencias', 'Api\Cartao\CartoesController@recorrencias', ['auth']);
Router::add('GET', '/api/v1/cartoes/{id}/recorrencias', 'Api\Cartao\CartoesController@recorrenciasCartao', ['auth']);
Router::add('POST', '/api/v1/cartoes/recorrencias/{id}/cancelar', 'Api\Cartao\CartoesController@cancelarRecorrencia', ['auth', 'csrf', 'ratelimit']);

// Additional AI endpoints not yet available under v1
Router::add('POST', '/api/v1/ai/chat', 'Api\AI\UserAiController@chat', ['auth', 'csrf', 'ai.ratelimit', 'ai.quota']);
Router::add('POST', '/api/v1/ai/analyze', 'Api\AI\UserAiController@analyze', ['auth', 'csrf', 'ai.ratelimit', 'ai.quota']);
Router::add('POST', '/api/v1/ai/extract-transaction', 'Api\AI\UserAiController@extractTransaction', ['auth', 'csrf', 'ai.ratelimit', 'ai.quota']);
Router::add('DELETE', '/api/v1/ai/conversations/{id}', 'Api\AI\UserAiController@deleteConversation', ['auth', 'csrf', 'ai.ratelimit']);

// English notification compatibility aliases
Router::add('GET', '/api/v1/notifications', 'Api\Notification\NotificationController@index', ['auth']);
Router::add('GET', '/api/v1/notifications/count', 'Api\Notification\NotificationController@count', ['auth']);
Router::add('POST', '/api/v1/notifications/{id}/read', 'Api\Notification\NotificationController@markAsRead', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/notifications/read-all', 'Api\Notification\NotificationController@markAllAsRead', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/v1/notifications/{id}', 'Api\Notification\NotificationController@destroy', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/v1/notifications/read', 'Api\Notification\NotificationController@deleteRead', ['auth', 'csrf', 'ratelimit']);
