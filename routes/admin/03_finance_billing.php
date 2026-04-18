<?php

declare(strict_types=1);

use Application\Core\Router;

// Finance pages
Router::add('GET', '/contas', 'Admin\\ContasController@index', ['auth']);
Router::add('GET', '/contas/arquivadas', 'Admin\\ContasController@archived', ['auth']);
Router::add('GET', '/cartoes', 'Admin\\CartoesController@index', ['auth']);
Router::add('GET', '/cartoes/arquivadas', 'Admin\\CartoesController@archived', ['auth']);
Router::add('GET', '/cartoes/{id}', 'Admin\\CartoesController@show', ['auth']);
Router::add('GET', '/financas', 'Admin\\OrcamentoController@index', ['auth']);
Router::add('GET', '/orcamento', 'Admin\\OrcamentoController@index', ['auth']);
Router::add('GET', '/metas', 'Admin\\MetasController@index', ['auth']);
Router::add('GET', '/categorias', 'Admin\\CategoriaController@index', ['auth']);
Router::add('GET', '/gamification', 'GamificationController@index', ['auth']);
Router::add('GET', '/importacoes', 'Admin\\ImportacoesController@index', ['auth']);
Router::add('GET', '/importacoes/configuracoes', 'Admin\\ImportacoesConfiguracoesController@index', ['auth']);
Router::add('GET', '/importacoes/historico', 'Admin\\ImportacoesHistoricoController@index', ['auth']);

// Billing/plans
Router::add('GET', '/billing', 'Admin\\BillingController@index', ['auth']);
