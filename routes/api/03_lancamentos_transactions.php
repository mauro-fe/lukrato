<?php

declare(strict_types=1);

use Application\Core\Router;

// Lancamentos
Router::add('GET',    '/api/lancamentos',       'Api\\Lancamentos\\IndexController@__invoke', ['auth']);
Router::add('POST',   '/api/lancamentos',       'Api\\Lancamentos\\StoreController@__invoke', ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/lancamentos/delete', 'Api\\Lancamentos\\DestroyController@bulkDelete', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/lancamentos/{id}',  'Api\\Lancamentos\\UpdateController@__invoke', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/lancamentos/{id}',  'Api\\Lancamentos\\DestroyController@__invoke', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/lancamentos/usage', 'Api\\Lancamentos\\UsageController@__invoke', ['auth']);
Router::add('GET',    '/api/lancamentos/export', 'Api\\Lancamentos\\ExportController@__invoke', ['auth', 'ratelimit']);
Router::add('POST',   '/api/lancamentos/{id}/cancelar-recorrencia', 'Api\\Lancamentos\\CancelarRecorrenciaController@__invoke', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/lancamentos/{id}/pagar', 'Api\\Lancamentos\\MarcarPagoController@__invoke', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/lancamentos/{id}/despagar', 'Api\\Lancamentos\\MarcarPagoController@desmarcar', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/lancamentos/{id}/fatura-detalhes', 'Api\\Lancamentos\\FaturaDetalhesController@__invoke', ['auth']);

// Alias for recent account history
Router::add('GET', '/api/contas/{id}/lancamentos', 'Api\\Lancamentos\\IndexController@__invoke', ['auth']);

// Transactions and transfers
Router::add('POST', '/api/transactions', 'Api\\Lancamentos\\TransactionsController@store', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',  '/api/transactions/{id}', 'Api\\Lancamentos\\TransactionsController@update', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/transactions/{id}/update', 'Api\\Lancamentos\\TransactionsController@update', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/transfers', 'Api\\Lancamentos\\TransactionsController@transfer', ['auth', 'csrf', 'ratelimit_strict']);
