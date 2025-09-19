<?php

use Application\Core\Router;


registerAuthRoutes();
registerSimpleRoutes();
registerFinanceRoutes();
registerRedirectRoutes();

function registerAuthRoutes(): void
{
    Router::add('GET',  'login',        'Auth\LoginController@login');
    Router::add('POST', 'login/entrar', 'Auth\LoginController@processLogin');
    Router::add('GET',  'logout',       'Auth\LoginController@logout');
    Router::add('POST', 'register/criar', 'Auth\RegisterController@store');
}

function registerSimpleRoutes(): void
{
    Router::add('GET', 'dashboard',    'Admin\DashboardController@dashboard', ['auth']);
    Router::add('GET', 'lancamentos',  'Admin\LancamentoController@index',    ['auth']);
    Router::add('GET', 'relatorios',   'Admin\RelatoriosController@view',           ['auth']);

    Router::add('GET',  'config',      'Admin\ConfigController@index', ['auth']);
    Router::add('POST', 'api/config',  'Api\ConfigController@update',  ['auth', 'csrf']);

    Router::add('GET',  'api/dashboard/metrics',       'Api\FinanceApiController@metrics',      ['auth']);
    Router::add('GET',  'api/options',                 'Api\FinanceApiController@options',      ['auth']);
    Router::add('POST', 'api/transactions',            'Api\FinanceApiController@store',        ['auth']);
    Router::add('GET',  'api/reports/overview',        'Api\RelatoriosController@overview',         ['auth']);
    Router::add('GET',  'api/reports/table',           'Api\RelatoriosController@table',            ['auth']);
    Router::add('GET',  'api/reports/timeseries',      'Api\RelatoriosController@timeseries',       ['auth']);
    Router::add('GET',  'api/reports',                 'Api\ReportController@index',            ['auth']); // compat
    Router::add('GET',  'api/lancamentos',             'Api\LancamentosController@index');
    Router::add('DELETE', 'api/lancamentos/{id}', 'Api\LancamentosController@destroy');
    Router::add('POST', 'api/transfers',  'Api\FinanceApiController@transfer');
    Router::add('POST', 'api/accounts/{id}/archive', 'Api\AccountController@archive');
    Router::add('POST', 'api/accounts/{id}/restore', 'Api\AccountController@restore');
    Router::add('GET', 'contas/arquivadas', 'Admin\AccountsController@archived');
    Router::add('POST', 'api/accounts/{id}/delete',  'Api\AccountController@hardDelete');
    Router::add('GET', 'contas', 'Admin\AccountsController@index', ['auth']);
    Router::add('GET',    'api/accounts',          'Api\AccountController@index',  ['auth']);
    Router::add('POST',   'api/accounts',          'Api\AccountController@store',  ['auth']);
    Router::add('PUT',    'api/accounts/{id}',     'Api\AccountController@update', ['auth']);
    Router::add('DELETE', 'api/accounts/{id}',     'Api\AccountController@destroy', ['auth']);
    Router::add('POST',    'api/accounts/{id:\d+}/update', 'Api\AccountController@update',  ['auth', 'csrf']);
    Router::add('POST',    'api/accounts/{id:\d+}/delete', 'Api\AccountController@destroy', ['auth', 'csrf']);

    Router::add('GET',  'perfil',      'Admin\ProfileController@index', ['auth']);
    Router::add('POST', 'api/profile', 'Api\ProfileApiController@update', ['auth', 'csrf']);

    Router::add('GET',  'categorias',                'Admin\CategoryController@index');

    Router::add('GET',  'api/categorias',                'Api\CategoryController@index');
    Router::add('POST', 'api/categorias',                'Api\CategoryController@store');
    Router::add('POST', 'api/categorias/{id:\d+}/delete', 'Api\CategoryController@delete');
    Router::add('POST', 'api/categorias/delete',         'Api\CategoryController@delete');

    Router::add('GET',  '/api/user/theme', 'Api\UserPreferenceController@show');
    Router::add('POST', '/api/user/theme', 'Api\UserPreferenceController@update');
}


function registerFinanceRoutes(): void
{
    Router::add('GET',  'admin/{username}/home',               'Admin\DashboardController@index', ['auth']);
    Router::add('GET',  'admin/{username}/dashboard-financas', 'Admin\DashboardController@index', ['auth']);

    Router::add('GET',  'admin/{username}/accounts',               'Admin\AccountController@index',   ['auth']);
    Router::add('POST', 'admin/{username}/accounts',               'Admin\AccountController@store',   ['auth', 'csrf']);
    Router::add('POST', 'admin/{username}/accounts/{id}/delete',   'Admin\AccountController@destroy', ['auth', 'csrf']);

    Router::add('GET',  'admin/{username}/categories',             'Admin\CategoryController@index',  ['auth']);
    Router::add('POST', 'admin/{username}/categories',             'Admin\CategoryController@store',  ['auth', 'csrf']);
    Router::add('POST', 'admin/{username}/categories/{id}/delete', 'Admin\CategoryController@destroy', ['auth', 'csrf']);

    Router::add('GET',  'admin/{username}/transactions',           'Admin\TransactionController@index', ['auth']);
    Router::add('POST', 'admin/{username}/transactions',           'Admin\TransactionController@store', ['auth', 'csrf']);
}

function registerRedirectRoutes(): void
{
    Router::add('GET', '', function () {
        redirectToUserDashboard();
    });
    Router::add('GET', '/', function () {
        redirectToUserDashboard();
    });

    Router::add('GET', 'admin', function () {
        redirectToLogin();
    });

    Router::add('GET', 'admin/login', function () {
        header('Location: ' . BASE_URL . 'login');
        exit;
    });
    Router::add('GET', 'admin/{username}/dashboard', function () {
        header('Location: ' . BASE_URL . 'dashboard');
        exit;
    });

    Router::add('GET', 'admin/home', function () {
        redirectToUserDashboard();
    });
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