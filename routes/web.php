<?php

/**
 * Definição de Rotas da Aplicação
 */

use Application\Core\Router;

// ============================================================================
// ROTAS PÚBLICAS / AUTENTICAÇÃO (simples)
// ============================================================================
registerAuthRoutes();

// ============================================================================
// ROTAS SIMPLES (sem /admin/{username})
// ============================================================================
registerSimpleRoutes();

// ============================================================================
// ROTAS ADMINISTRATIVAS LEGACY (mantidas por compatibilidade)
// ============================================================================
registerFinanceRoutes();

// ============================================================================
// REDIRECIONAMENTOS E FALLBACKS
// ============================================================================
registerRedirectRoutes();


// ============================================================================
// FUNÇÕES DE REGISTRO
// ============================================================================

/**
 * Rotas de autenticação (simples)
 */
function registerAuthRoutes(): void
{
    Router::add('GET',  'login',        'Auth\LoginController@login');
    Router::add('POST', 'login/entrar', 'Auth\LoginController@processLogin');
    Router::add('GET',  'logout',       'Auth\LoginController@logout');
    Router::add('POST', 'register/criar', 'Auth\RegisterController@store');
}

/**
 * Rotas SIMPLES de app (sem username na URL)
 */
function registerSimpleRoutes(): void
{
    // Dashboard / páginas simples
    Router::add('GET', 'dashboard',    'Admin\DashboardController@dashboard', ['auth']);
    Router::add('GET', 'lancamentos',  'Admin\LancamentoController@index',    ['auth']);
    Router::add('GET', 'relatorios',   'RelatoriosController@view',           ['auth']);

    Router::add('GET',  'config',      'Admin\ConfigController@index', ['auth']);
    Router::add('POST', 'api/config',  'Api\ConfigController@update',  ['auth', 'csrf']);

    // API Dashboard/Reports já existentes
    Router::add('GET',  'api/dashboard/metrics',       'Api\FinanceApiController@metrics',      ['auth']);
    Router::add('GET',  'api/dashboard/transactions',  'Api\FinanceApiController@transactions', ['auth']);
    Router::add('GET',  'api/options',                 'Api\FinanceApiController@options',      ['auth']);
    Router::add('POST', 'api/transactions',            'Api\FinanceApiController@store',        ['auth']);
    Router::add('GET',  'api/reports/overview',        'RelatoriosController@overview',         ['auth']);
    Router::add('GET',  'api/reports/table',           'RelatoriosController@table',            ['auth']);
    Router::add('GET',  'api/reports/timeseries',      'RelatoriosController@timeseries',       ['auth']);
    Router::add('GET',  'api/reports',                 'Api\ReportController@index',            ['auth']); // compat

    // Página Contas
    Router::add('GET', 'contas', 'Admin\AccountsController@index', ['auth']);

    // ===== API de Contas (NOVAS) =====
    // REST "bonito"
    // registerSimpleRoutes()
    Router::add('GET',    'api/accounts',          'Api\AccountController@index',  ['auth']);
    Router::add('POST',   'api/accounts',          'Api\AccountController@store',  ['auth']);   // sem 'csrf'
    Router::add('PUT',    'api/accounts/{id}',     'Api\AccountController@update', ['auth']);   // sem 'csrf'
    Router::add('DELETE', 'api/accounts/{id}',     'Api\AccountController@destroy', ['auth']);   // sem 'csrf'

    // Fallbacks para ambientes sem PUT/DELETE (úteis se seu Router/Apache não aceitarem)
    Router::add('POST',    'api/accounts/{id:\d+}/update', 'Api\AccountController@update',  ['auth', 'csrf']);
    Router::add('POST',    'api/accounts/{id:\d+}/delete', 'Api\AccountController@destroy', ['auth', 'csrf']);

    // Perfil
    Router::add('GET',  'perfil',      'Admin\ProfileController@index', ['auth']);
    Router::add('POST', 'api/profile', 'Api\ProfileApiController@update', ['auth', 'csrf']);
}


/**
 * Finance (legado com username na URL)
 */
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

/**
 * Fallbacks + redirecionamentos
 */
function registerRedirectRoutes(): void
{
    // Home -> se logado vai para dashboard simples; senão login
    Router::add('GET', '', function () {
        redirectToUserDashboard();
    });
    Router::add('GET', '/', function () {
        redirectToUserDashboard();
    });

    // Compat antigo: /admin -> login simples
    Router::add('GET', 'admin', function () {
        redirectToLogin();
    });

    // Compat de rotas antigas:
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


// ============================================================================
// HELPERS DE REDIRECIONAMENTO
// ============================================================================

/** Login simples */
function redirectToLogin(): void
{
    header('Location: ' . BASE_URL . 'login'); // ← simples (antes era admin/login)
    exit;
}

/** Para o dashboard simples, se logado */
function redirectToUserDashboard(): void
{
    if (isset($_SESSION['usuario_id']) || isset($_SESSION['admin_username'])) {
        header('Location: ' . BASE_URL . 'dashboard'); // ← simples
    } else {
        session_destroy();
        redirectToLogin();
    }
    exit;
}
