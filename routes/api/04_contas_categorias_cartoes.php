<?php

declare(strict_types=1);

use Application\Core\Router;

// Accounts (REST)
Router::add('GET',    '/api/accounts',              'Api\\Conta\\ContasController@index', ['auth']);
Router::add('POST',   '/api/accounts',              'Api\\Conta\\ContasController@store', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/accounts/{id}',         'Api\\Conta\\ContasController@update', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/accounts/{id}',         'Api\\Conta\\ContasController@destroy', ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/accounts/{id}/archive', 'Api\\Conta\\ContasController@archive', ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/accounts/{id}/restore', 'Api\\Conta\\ContasController@restore', ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/accounts/{id}/delete',  'Api\\Conta\\ContasController@hardDelete', ['auth', 'csrf', 'ratelimit']);

// Legacy account routes
Router::add('POST', '/api/accounts/archive',   'Api\\Conta\\ContasController@archive', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/accounts/unarchive', 'Api\\Conta\\ContasController@restore', ['auth', 'csrf', 'ratelimit']);

// Portuguese compatibility routes
Router::add('GET',    '/api/instituicoes',        'Api\\Conta\\ContasController@instituicoes', ['auth']);
Router::add('POST',   '/api/instituicoes',        'Api\\Conta\\ContasController@createInstituicao', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/contas/instituicoes', 'Api\\Conta\\ContasController@instituicoes', ['auth']);
Router::add('GET',    '/api/contas',              'Api\\Conta\\ContasController@index', ['auth']);
Router::add('POST',   '/api/contas',              'Api\\Conta\\ContasController@store', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/contas/{id}',         'Api\\Conta\\ContasController@update', ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/contas/{id}/archive', 'Api\\Conta\\ContasController@archive', ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/contas/{id}/restore', 'Api\\Conta\\ContasController@restore', ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/contas/{id}/delete',  'Api\\Conta\\ContasController@hardDelete', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/contas/{id}',         'Api\\Conta\\ContasController@destroy', ['auth', 'csrf', 'ratelimit']);

// Categories
Router::add('GET',    '/api/categorias',         'Api\\Categoria\\CategoriaController@index', ['auth']);
Router::add('POST',   '/api/categorias',         'Api\\Categoria\\CategoriaController@store', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/categorias/reorder', 'Api\\Categoria\\CategoriaController@reorder', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/categorias/{id}',    'Api\\Categoria\\CategoriaController@update', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/categorias/{id}',    'Api\\Categoria\\CategoriaController@delete', ['auth', 'csrf', 'ratelimit']);

// Subcategories
Router::add('GET',    '/api/categorias/{id}/subcategorias', 'Api\\Categoria\\SubcategoriaController@index', ['auth']);
Router::add('POST',   '/api/categorias/{id}/subcategorias', 'Api\\Categoria\\SubcategoriaController@store', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/subcategorias/grouped',          'Api\\Categoria\\SubcategoriaController@grouped', ['auth']);
Router::add('PUT',    '/api/subcategorias/{id}',             'Api\\Categoria\\SubcategoriaController@update', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/subcategorias/{id}',             'Api\\Categoria\\SubcategoriaController@delete', ['auth', 'csrf', 'ratelimit']);

// Credit cards
Router::add('GET',    '/api/cartoes',                     'Api\\Cartao\\CartoesController@index', ['auth']);
Router::add('GET',    '/api/cartoes/resumo',              'Api\\Cartao\\CartoesController@summary', ['auth']);
Router::add('GET',    '/api/cartoes/alertas',             'Api\\Cartao\\CartoesController@alertas', ['auth']);
Router::add('GET',    '/api/cartoes/validar-integridade', 'Api\\Cartao\\CartoesController@validarIntegridade', ['auth']);
Router::add('GET',    '/api/cartoes/{id}',                'Api\\Cartao\\CartoesController@show', ['auth']);
Router::add('POST',   '/api/cartoes',                     'Api\\Cartao\\CartoesController@store', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/cartoes/{id}',                'Api\\Cartao\\CartoesController@update', ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/cartoes/{id}/deactivate',     'Api\\Cartao\\CartoesController@deactivate', ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/cartoes/{id}/reactivate',     'Api\\Cartao\\CartoesController@reactivate', ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/cartoes/{id}/archive',        'Api\\Cartao\\CartoesController@archive', ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/cartoes/{id}/restore',        'Api\\Cartao\\CartoesController@restore', ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/cartoes/{id}/delete',         'Api\\Cartao\\CartoesController@delete', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/cartoes/{id}',                'Api\\Cartao\\CartoesController@destroy', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/cartoes/{id}/limite',         'Api\\Cartao\\CartoesController@updateLimit', ['auth', 'csrf', 'ratelimit']);

// Card invoice routes
Router::add('GET',  '/api/cartoes/{id}/fatura', 'Api\\Cartao\\CartoesController@fatura', ['auth']);
Router::add('POST', '/api/cartoes/{id}/fatura/pagar', 'Api\\Cartao\\CartoesController@pagarFatura', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',  '/api/cartoes/{id}/fatura/status', 'Api\\Cartao\\CartoesController@statusFatura', ['auth']);
Router::add('POST', '/api/cartoes/{id}/fatura/desfazer-pagamento', 'Api\\Cartao\\CartoesController@desfazerPagamentoFatura', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/cartoes/{id}/parcelas/pagar', 'Api\\Cartao\\CartoesController@pagarParcelas', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/cartoes/parcelas/{id}/desfazer-pagamento', 'Api\\Cartao\\CartoesController@desfazerPagamentoParcela', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',  '/api/cartoes/{id}/faturas-pendentes', 'Api\\Cartao\\CartoesController@faturasPendentes', ['auth']);
Router::add('GET',  '/api/cartoes/{id}/faturas-historico', 'Api\\Cartao\\CartoesController@faturasHistorico', ['auth']);
Router::add('GET',  '/api/cartoes/{id}/parcelamentos-resumo', 'Api\\Cartao\\CartoesController@parcelamentosResumo', ['auth']);

// Card recurrences
Router::add('GET',  '/api/cartoes/recorrencias', 'Api\\Cartao\\CartoesController@recorrencias', ['auth']);
Router::add('GET',  '/api/cartoes/{id}/recorrencias', 'Api\\Cartao\\CartoesController@recorrenciasCartao', ['auth']);
Router::add('POST', '/api/cartoes/recorrencias/{id}/cancelar', 'Api\\Cartao\\CartoesController@cancelarRecorrencia', ['auth', 'csrf', 'ratelimit']);
