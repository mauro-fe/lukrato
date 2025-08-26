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
}

/**
 * Rotas SIMPLES de app (sem username na URL)
 */
function registerSimpleRoutes(): void
{
    // Dashboard (view já existe)
    Router::add('GET', 'dashboard', 'Admin\DashboardController@dashboard', ['auth']);

    // Lançamentos (já existentes)
    Router::add('GET',  'lancamentos',       'Admin\LancamentoController@index',  ['auth']);


    // === API (dashboard) ===
    Router::add('GET',  'api/dashboard/metrics',       'Api\FinanceApiController@metrics',      ['auth']);
    Router::add('GET',  'api/dashboard/transactions',  'Api\FinanceApiController@transactions', ['auth']);
    Router::add('GET',  'api/options',                 'Api\FinanceApiController@options',      ['auth']);
    Router::add('POST', 'api/transactions',            'Api\FinanceApiController@store',        ['auth']); // se usar CSRF por header, ajuste o middleware

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
