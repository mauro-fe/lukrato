<?php

declare(strict_types=1);

use Application\Core\Router;

/**
 * Additive v1 aliases for remaining finance management, dashboard overview,
 * and user AI routes still consumed by legacy frontend modules.
 */

// Dashboard overview
Router::add('GET', '/api/v1/dashboard/overview', 'Api\Dashboard\OverviewController@overview', ['auth']);
Router::add('POST', '/api/v1/csrf/refresh', 'Api\User\SecurityController@refreshCsrf', ['ratelimit']);

// Institutions / accounts management
Router::add('GET', '/api/v1/instituicoes', 'Api\Conta\ContasController@instituicoes', ['auth']);
Router::add('POST', '/api/v1/instituicoes', 'Api\Conta\ContasController@createInstituicao', ['auth', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/contas/instituicoes', 'Api\Conta\ContasController@instituicoes', ['auth']);
Router::add('POST', '/api/v1/contas', 'Api\Conta\ContasController@store', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT', '/api/v1/contas/{id}', 'Api\Conta\ContasController@update', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/contas/{id}/archive', 'Api\Conta\ContasController@archive', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/contas/{id}/restore', 'Api\Conta\ContasController@restore', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/contas/{id}/delete', 'Api\Conta\ContasController@hardDelete', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/v1/contas/{id}', 'Api\Conta\ContasController@destroy', ['auth', 'csrf', 'ratelimit']);

// Categories / subcategories management
Router::add('POST', '/api/v1/categorias', 'Api\Categoria\CategoriaController@store', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT', '/api/v1/categorias/reorder', 'Api\Categoria\CategoriaController@reorder', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT', '/api/v1/categorias/{id}', 'Api\Categoria\CategoriaController@update', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/v1/categorias/{id}', 'Api\Categoria\CategoriaController@delete', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/categorias/{id}/subcategorias', 'Api\Categoria\SubcategoriaController@store', ['auth', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/subcategorias/grouped', 'Api\Categoria\SubcategoriaController@grouped', ['auth']);
Router::add('PUT', '/api/v1/subcategorias/{id}', 'Api\Categoria\SubcategoriaController@update', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/v1/subcategorias/{id}', 'Api\Categoria\SubcategoriaController@delete', ['auth', 'csrf', 'ratelimit']);

// Cards / invoices management
Router::add('GET', '/api/v1/cartoes/resumo', 'Api\Cartao\CartoesController@summary', ['auth']);
Router::add('GET', '/api/v1/cartoes/alertas', 'Api\Cartao\CartoesController@alertas', ['auth']);
Router::add('GET', '/api/v1/cartoes/{id}', 'Api\Cartao\CartoesController@show', ['auth']);
Router::add('POST', '/api/v1/cartoes', 'Api\Cartao\CartoesController@store', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT', '/api/v1/cartoes/{id}', 'Api\Cartao\CartoesController@update', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/cartoes/{id}/archive', 'Api\Cartao\CartoesController@archive', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/cartoes/{id}/restore', 'Api\Cartao\CartoesController@restore', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/cartoes/{id}/delete', 'Api\Cartao\CartoesController@delete', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/v1/cartoes/{id}', 'Api\Cartao\CartoesController@destroy', ['auth', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/cartoes/{id}/fatura', 'Api\Cartao\CartoesController@fatura', ['auth']);
Router::add('GET', '/api/v1/cartoes/{id}/fatura/status', 'Api\Cartao\CartoesController@statusFatura', ['auth']);
Router::add('POST', '/api/v1/cartoes/{id}/parcelas/pagar', 'Api\Cartao\CartoesController@pagarParcelas', ['auth', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/cartoes/{id}/faturas-pendentes', 'Api\Cartao\CartoesController@faturasPendentes', ['auth']);
Router::add('GET', '/api/v1/cartoes/{id}/faturas-historico', 'Api\Cartao\CartoesController@faturasHistorico', ['auth']);
Router::add('GET', '/api/v1/cartoes/{id}/parcelamentos-resumo', 'Api\Cartao\CartoesController@parcelamentosResumo', ['auth']);

// User AI assistant
Router::add('POST', '/api/v1/ai/suggest-category', 'Api\AI\UserAiController@suggestCategory', ['auth', 'csrf', 'ai.ratelimit', 'ai.quota']);
Router::add('GET', '/api/v1/ai/quota', 'Api\AI\UserAiController@getQuota', ['auth']);
Router::add('GET', '/api/v1/ai/conversations', 'Api\AI\UserAiController@listConversations', ['auth']);
Router::add('POST', '/api/v1/ai/conversations', 'Api\AI\UserAiController@createConversation', ['auth', 'csrf', 'ai.ratelimit']);
Router::add('GET', '/api/v1/ai/conversations/{id}/messages', 'Api\AI\UserAiController@getMessages', ['auth']);
Router::add('POST', '/api/v1/ai/conversations/{id}/messages', 'Api\AI\UserAiController@sendMessage', ['auth', 'csrf', 'ai.ratelimit', 'ai.quota']);
Router::add('POST', '/api/v1/ai/actions/{id}/confirm', 'Api\AI\UserAiController@confirmAction', ['auth', 'csrf', 'ai.ratelimit']);
Router::add('POST', '/api/v1/ai/actions/{id}/reject', 'Api\AI\UserAiController@rejectAction', ['auth', 'csrf', 'ai.ratelimit']);
