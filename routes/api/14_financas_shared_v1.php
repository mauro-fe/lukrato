<?php

declare(strict_types=1);

use Application\Core\Router;

/**
 * Additive v1 aliases for shared finance/account/category/card endpoints.
 * Existing routes remain canonical for legacy clients while new consumers
 * can move incrementally to versioned contracts.
 */

// Accounts, categories and cards
Router::add('GET', '/api/v1/contas', 'Api\Conta\ContasController@index', ['auth']);
Router::add('GET', '/api/v1/categorias', 'Api\Categoria\CategoriaController@index', ['auth']);
Router::add('GET', '/api/v1/categorias/{id}/subcategorias', 'Api\Categoria\SubcategoriaController@index', ['auth']);
Router::add('GET', '/api/v1/cartoes', 'Api\Cartao\CartoesController@index', ['auth']);

// Shared finance reads/writes used by multiple admin screens
Router::add('GET', '/api/v1/financas/resumo', 'Api\Financas\ResumoController@resumo', ['auth']);
Router::add('GET', '/api/v1/financas/metas', 'Api\Metas\MetasController@index', ['auth']);
Router::add('POST', '/api/v1/financas/metas', 'Api\Metas\MetasController@store', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT', '/api/v1/financas/metas/{id}', 'Api\Metas\MetasController@update', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/financas/metas/{id}/aporte', 'Api\Metas\MetasController@aporte', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/v1/financas/metas/{id}', 'Api\Metas\MetasController@destroy', ['auth', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/financas/metas/templates', 'Api\Metas\MetasController@templates', ['auth']);
Router::add('GET', '/api/v1/financas/orcamentos', 'Api\Orcamentos\OrcamentosController@index', ['auth']);
Router::add('POST', '/api/v1/financas/orcamentos', 'Api\Orcamentos\OrcamentosController@store', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/financas/orcamentos/bulk', 'Api\Orcamentos\OrcamentosController@bulk', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/v1/financas/orcamentos/{id}', 'Api\Orcamentos\OrcamentosController@destroy', ['auth', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/financas/orcamentos/sugestoes', 'Api\Orcamentos\OrcamentosController@sugestoes', ['auth']);
Router::add('POST', '/api/v1/financas/orcamentos/aplicar-sugestoes', 'Api\Orcamentos\OrcamentosController@aplicarSugestoes', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/financas/orcamentos/copiar-mes', 'Api\Orcamentos\OrcamentosController@copiarMes', ['auth', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/financas/insights', 'Api\Financas\ResumoController@insights', ['auth']);
