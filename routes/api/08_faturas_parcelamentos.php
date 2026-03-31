<?php

declare(strict_types=1);

use Application\Core\Router;

// Credit-card invoices
Router::add('GET',    '/api/faturas', 'Api\\Fatura\\FaturasController@index', ['auth']);
Router::add('POST',   '/api/faturas', 'Api\\Fatura\\FaturasController@store', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/faturas/{id}', 'Api\\Fatura\\FaturasController@show', ['auth']);
Router::add('DELETE', '/api/faturas/{id}', 'Api\\Fatura\\FaturasController@destroy', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/faturas/{id}/itens/{itemId}', 'Api\\Fatura\\FaturasController@updateItem', ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/faturas/{id}/itens/{itemId}/toggle', 'Api\\Fatura\\FaturasController@toggleItemPago', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/faturas/{id}/itens/{itemId}', 'Api\\Fatura\\FaturasController@destroyItem', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/faturas/{id}/itens/{itemId}/parcelamento', 'Api\\Fatura\\FaturasController@deleteParcelamento', ['auth', 'csrf', 'ratelimit']);

// Installments (non-card)
Router::add('GET',    '/api/parcelamentos', 'Api\\Fatura\\ParcelamentosController@index', ['auth']);
Router::add('POST',   '/api/parcelamentos', 'Api\\Fatura\\ParcelamentosController@store', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/parcelamentos/{id}', 'Api\\Fatura\\ParcelamentosController@show', ['auth']);
Router::add('DELETE', '/api/parcelamentos/{id}', 'Api\\Fatura\\ParcelamentosController@destroy', ['auth', 'csrf', 'ratelimit']);
