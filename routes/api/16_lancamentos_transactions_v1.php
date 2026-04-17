<?php

declare(strict_types=1);

use Application\Core\Router;

/**
 * Additive v1 aliases for lancamentos, importacoes, parcelamentos and
 * transaction flows still consumed across admin list and finance screens.
 */

Router::add('GET', '/api/v1/lancamentos', 'Api\Lancamentos\IndexController@__invoke', ['auth']);
Router::add('POST', '/api/v1/lancamentos', 'Api\Lancamentos\StoreController@__invoke', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/lancamentos/delete', 'Api\Lancamentos\DestroyController@bulkDelete', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT', '/api/v1/lancamentos/{id}', 'Api\Lancamentos\UpdateController@__invoke', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/v1/lancamentos/{id}', 'Api\Lancamentos\DestroyController@__invoke', ['auth', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/lancamentos/usage', 'Api\Lancamentos\UsageController@__invoke', ['auth']);
Router::add('GET', '/api/v1/lancamentos/export', 'Api\Lancamentos\ExportController@__invoke', ['auth', 'ratelimit']);
Router::add('POST', '/api/v1/lancamentos/{id}/cancelar-recorrencia', 'Api\Lancamentos\CancelarRecorrenciaController@__invoke', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT', '/api/v1/lancamentos/{id}/pagar', 'Api\Lancamentos\MarcarPagoController@__invoke', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT', '/api/v1/lancamentos/{id}/despagar', 'Api\Lancamentos\MarcarPagoController@desmarcar', ['auth', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/lancamentos/{id}/fatura-detalhes', 'Api\Lancamentos\FaturaDetalhesController@__invoke', ['auth']);

Router::add('GET', '/api/v1/contas/{id}/lancamentos', 'Api\Lancamentos\IndexController@__invoke', ['auth']);

Router::add('GET', '/api/v1/importacoes/page-init', 'Api\Importacoes\PageInitController@__invoke', ['auth']);
Router::add('GET', '/api/v1/importacoes/configuracoes/page-init', 'Api\Importacoes\ConfiguracoesPageInitController@__invoke', ['auth']);
Router::add('GET', '/api/v1/importacoes/configuracoes', 'Api\Importacoes\ConfiguracoesController@__invoke', ['auth']);
Router::add('POST', '/api/v1/importacoes/configuracoes', 'Api\Importacoes\ConfiguracoesController@save', ['auth', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/importacoes/modelos/csv', 'Api\Importacoes\CsvTemplateController@__invoke', ['auth']);
Router::add('GET', '/api/v1/importacoes/historico/page-init', 'Api\Importacoes\HistoricoPageInitController@__invoke', ['auth']);
Router::add('GET', '/api/v1/importacoes/historico', 'Api\Importacoes\HistoricoController@__invoke', ['auth']);
Router::add('DELETE', '/api/v1/importacoes/historico/{id}', 'Api\Importacoes\DeleteController@__invoke', ['auth', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/importacoes/jobs/{id}', 'Api\Importacoes\JobStatusController@__invoke', ['auth']);
Router::add('POST', '/api/v1/importacoes/preview', 'Api\Importacoes\PreviewController@__invoke', ['auth', 'csrf', 'ratelimit_strict']);
Router::add('POST', '/api/v1/importacoes/confirm', 'Api\Importacoes\ConfirmController@__invoke', ['auth', 'csrf', 'ratelimit_strict']);

Router::add('GET', '/api/v1/parcelamentos', 'Api\Fatura\ParcelamentosController@index', ['auth']);
Router::add('POST', '/api/v1/parcelamentos', 'Api\Fatura\ParcelamentosController@store', ['auth', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/parcelamentos/{id}', 'Api\Fatura\ParcelamentosController@show', ['auth']);
Router::add('DELETE', '/api/v1/parcelamentos/{id}', 'Api\Fatura\ParcelamentosController@destroy', ['auth', 'csrf', 'ratelimit']);

Router::add('POST', '/api/v1/transactions', 'Api\Lancamentos\TransactionsController@store', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT', '/api/v1/transactions/{id}', 'Api\Lancamentos\TransactionsController@update', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/transactions/{id}/update', 'Api\Lancamentos\TransactionsController@update', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/transfers', 'Api\Lancamentos\TransactionsController@transfer', ['auth', 'csrf', 'ratelimit_strict']);
