<?php

declare(strict_types=1);

use Application\Core\Router;

// Main pages
Router::add('GET', '/dashboard', 'Admin\\DashboardController@dashboard', ['auth']);
Router::add('GET', '/lancamentos', 'Admin\\LancamentoController@index', ['auth']);
Router::add('GET', '/faturas', 'Admin\\FaturaController@index', ['auth']);
Router::add('GET', '/faturas/{id}', 'Admin\\FaturaController@show', ['auth']);
Router::add('GET', '/relatorios', 'Admin\\RelatoriosController@view', ['auth']);
