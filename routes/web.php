<?php

use Application\Core\Router;

registerAuthRoutes();
registerRedirectRoutes();
registerAppRoutes();
registerApiRoutes();
planos();


function registerAuthRoutes(): void
{
    Router::add('GET',  'login',            'Auth\LoginController@login');
    Router::add('POST', 'login/entrar',     'Auth\LoginController@processLogin');
    Router::add('GET',  'logout',           'Auth\LoginController@logout');

    Router::add('POST', 'register/criar',   'Auth\RegistroController@store');
}


function registerRedirectRoutes(): void
{
    Router::add('GET',  '',          function () {
        redirectToUserDashboard();
    });
    Router::add('GET',  '/',         function () {
        redirectToUserDashboard();
    });

    Router::add('GET',  'admin',            function () {
        redirectToLogin();
    });
    Router::add('GET',  'admin/login',      function () {
        header('Location: ' . BASE_URL . 'login');
        exit;
    });

    Router::add('GET',  'admin/dashboard', function () {
        header('Location: ' . BASE_URL . 'dashboard');
        exit;
    });

    Router::add('GET',  'admin/home', function () {
        redirectToUserDashboard();
    });
}



function registerAppRoutes(): void
{
    Router::add('GET',  'dashboard',          'Admin\DashboardController@dashboard', ['auth']);
    Router::add('GET',  'lancamentos',        'Admin\LancamentoController@index',    ['auth']);
    Router::add('GET',  'relatorios',         'Admin\RelatoriosController@view',     ['auth']);
    Router::add('GET',  'config',             'Admin\ConfigController@index',        ['auth']);
    Router::add('POST', 'api/config',         'Api\ConfigController@update',         ['auth', 'csrf']);
    Router::add('GET',  'perfil',             'Admin\PerfilController@index',        ['auth']);
    Router::add('GET',  'contas',             'Admin\ContasController@index',        ['auth']);
    Router::add('GET',  'contas/arquivadas',  'Admin\ContasController@archived',     ['auth']);
    Router::add('GET',  'categorias',         'Admin\CategoriaController@index',     ['auth']);

    Router::add('GET',  'admin/home',               'Admin\DashboardController@index',   ['auth']);
    Router::add('GET',  'admin/dashboard-financas', 'Admin\DashboardController@index',   ['auth']);
    Router::add('GET',  'admin/accounts',           'Admin\ContasController@index',      ['auth']);
    Router::add('GET',  'admin/categories',         'Admin\CategoriaController@index',   ['auth']);
    Router::add('GET',  'admin/transactions',       'Admin\TransactionController@index', ['auth']);



    function registerApiRoutes(): void
    {
        Router::add('GET',  'api/perfil',             'Api\PerfilController@show',   ['auth']);
        Router::add('POST', 'api/perfil',             'Api\PerfilController@update', ['auth', 'csrf']);

        Router::add('GET',  'api/dashboard/metrics',   'Api\FinanceiroController@metrics',   ['auth']);
        Router::add('GET',  'api/options',             'Api\FinanceiroController@options',   ['auth']);

        Router::add('GET',  'api/reports/overview',    'Api\RelatoriosController@overview',  ['auth']);
        Router::add('GET',  'api/reports/table',       'Api\RelatoriosController@table',     ['auth']);
        Router::add('GET',  'api/reports/timeseries',  'Api\RelatoriosController@timeseries', ['auth']);
        Router::add('GET',  'api/reports',             'Api\RelatoriosController@index',     ['auth']);

        Router::add('GET',     'api/lancamentos',          'Api\LancamentosController@index',   ['auth']);
        Router::add('DELETE',  'api/lancamentos/{id}', 'Api\LancamentosController@destroy', ['auth', 'csrf']);

        Router::add('POST', 'api/transactions',  'Api\FinanceiroController@store',    ['auth', 'csrf']);
        Router::add('POST', 'api/transfers',     'Api\FinanceiroController@transfer', ['auth', 'csrf']);

        Router::add('GET',     'api/accounts',                 'Api\ContasController@index',   ['auth']);
        Router::add('POST',    'api/accounts',                 'Api\ContasController@store',   ['auth', 'csrf']);
        Router::add('PUT',     'api/accounts/{id}',        'Api\ContasController@update',  ['auth', 'csrf']);
        Router::add('DELETE',  'api/accounts/{id}',        'Api\ContasController@destroy', ['auth', 'csrf']);

        Router::add('POST',    'api/accounts/{id}/archive', 'Api\ContasController@archive', ['auth', 'csrf']);
        Router::add('POST',    'api/accounts/{id}/restore', 'Api\ContasController@restore', ['auth', 'csrf']);
        Router::add('POST',    'api/accounts/{id}/delete', 'Api\ContasController@hardDelete', ['auth', 'csrf']);

        Router::add('POST',    '/api/accounts/{id}/update', 'Api\ContasController@update',  ['auth', 'csrf']);
        Router::add('POST',    '/api/accounts/{id}/destroy', 'Api\ContasController@destroy', ['auth', 'csrf']); // (se quiser)

        Router::add('GET',  '/api/categorias',                   'Api\CategoriaController@index',  ['auth']);
        Router::add('POST', '/api/categorias',                   'Api\CategoriaController@store',  ['auth', 'csrf']);

        Router::add('POST', '/api/categorias/{id}/delete',   'Api\CategoriaController@delete', ['auth', 'csrf']);
        Router::add('POST', '/api/categorias/delete',            'Api\CategoriaController@delete', ['auth', 'csrf']);

        Router::add('GET',  '/api/user/theme', 'Api\PreferenciaUsuarioController@show',   ['auth']);
        Router::add('POST', '/api/user/theme', 'Api\PreferenciaUsuarioController@update', ['auth', 'csrf']);
    }

    function planos()
    {
        Router::add('GET',  '/billing',                         'Admin\\BillingController@index');
        Router::add('POST', '/api/billing/pagarme/checkout',    'Api\\BillingController@createCheckout');   // cria link/checkout da assinatura
        Router::add('POST', '/api/webhooks/pagarme',            'Api\\WebhookController@pagarme');          // público

    }


    function redirectToLogin(): void
    {
        header('Location: ' . BASE_URL . 'login');
        exit;
    }

    function redirectToUserDashboard(): void
    {
        if (isset($_SESSION['usuario_id']) || isset($_SESSION['admin_username'])) {
            header('Location: ' . BASE_URL . 'dashboard');
        } else {
            session_destroy();
            redirectToLogin();
        }
        exit;
    }
}
