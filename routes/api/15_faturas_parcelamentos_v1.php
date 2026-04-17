<?php

declare(strict_types=1);

use Application\Core\Router;

/**
 * Additive v1 aliases for invoice/payment flows used by faturas and cartoes.
 */

Router::add('GET', '/api/v1/faturas', 'Api\Fatura\FaturasController@index', ['auth']);
Router::add('POST', '/api/v1/faturas', 'Api\Fatura\FaturasController@store', ['auth', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/faturas/{id}', 'Api\Fatura\FaturasController@show', ['auth']);
Router::add('DELETE', '/api/v1/faturas/{id}', 'Api\Fatura\FaturasController@destroy', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT', '/api/v1/faturas/{id}/itens/{itemId}', 'Api\Fatura\FaturasController@updateItem', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/faturas/{id}/itens/{itemId}/toggle', 'Api\Fatura\FaturasController@toggleItemPago', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/v1/faturas/{id}/itens/{itemId}', 'Api\Fatura\FaturasController@destroyItem', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/v1/faturas/{id}/itens/{itemId}/parcelamento', 'Api\Fatura\FaturasController@deleteParcelamento', ['auth', 'csrf', 'ratelimit']);

Router::add('POST', '/api/v1/cartoes/{id}/fatura/pagar', 'Api\Cartao\CartoesController@pagarFatura', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/cartoes/{id}/fatura/desfazer-pagamento', 'Api\Cartao\CartoesController@desfazerPagamentoFatura', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/cartoes/parcelas/{id}/desfazer-pagamento', 'Api\Cartao\CartoesController@desfazerPagamentoParcela', ['auth', 'csrf', 'ratelimit']);
