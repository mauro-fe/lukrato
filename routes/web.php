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
registerSysAdminRoutes();
registerAdminRoutes();
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
    // Dashboard financeiro simples
    // CORRETO
    Router::add('GET', 'dashboard', 'Admin\DashboardController@dashboard', ['auth']);

    // Lançamentos
    Router::add('GET',  'lancamentos',      'Application\Controllers\LancamentoController@index',  ['auth']);
    Router::add('GET',  'lancamentos/novo', 'Application\Controllers\LancamentoController@create', ['auth']);
    Router::add('POST', 'lancamentos',      'Application\Controllers\LancamentoController@store',  ['auth', 'csrf']);
}


/**
 * Rotas do sistema administrativo (SysAdmin) – legado
 */
function registerSysAdminRoutes(): void
{
    Router::add('GET', 'sysadmin/dashboard', 'SysAdmin\SysAdminController@index', ['sysadmin']);
    Router::add('GET', 'sysadmin/clientes',  'SysAdmin\SysAdminController@clientes', ['sysadmin']);

    Router::add('GET',  'sysadmin/admins',                 'SysAdmin\SysAdminController@admins',    ['sysadmin']);
    Router::add('GET',  'sysadmin/admins/autorizar/{id}',  'SysAdmin\SysAdminController@autorizar', ['sysadmin']);
    Router::add('GET',  'sysadmin/admins/bloquear/{id}',   'SysAdmin\SysAdminController@bloquear',  ['sysadmin']);
    Router::add('GET',  'sysadmin/admins/editar/{id}',     'SysAdmin\SysAdminController@editar',    ['sysadmin']);
    Router::add('POST', 'sysadmin/admins/salvar/{id}',     'SysAdmin\SysAdminController@salvar',    ['sysadmin']);

    Router::add('GET',  'admin/novo',        'Admin\RegisterController@showRegisterForm', ['sysadmin']);
    Router::add('POST', 'admin/novo/salvar', 'Admin\RegisterController@processRegister',  ['sysadmin']);
}

/**
 * Rotas ADMIN (legado)
 */
function registerAdminRoutes(): void
{
    registerDashboardRoutes();
    registerProfileRoutes();
}

/**
 * Dashboard ADMIN (legado)
 */
function registerDashboardRoutes(): void
{
    Router::add('GET',  'admin/{username}/dashboard',                 'Admin\DashboardController@dashboard', ['auth']);
    Router::add('GET',  'admin/pesquisa',                             'Admin\DashboardController@search',    ['auth']);
    Router::add('POST', 'admin/pesquisa',                             'Admin\DashboardController@search',    ['auth']);
    Router::add('POST', 'admin/{username}/ficha/{id}/lixeira',        'Admin\DashboardController@moverParaLixeira', ['auth', 'csrf']);
    Router::add('GET',  'admin/{username}/fichas-lixeira',            'Admin\DashboardController@viewLixeira', ['auth']);
    Router::add('POST', 'admin/{username}/ficha/{id}/excluir-definitivo', 'Admin\DashboardController@excluirDefinitivamente', ['auth', 'csrf']);
    Router::add('POST', 'admin/{username}/fichas/{id}/restaurar',     'Admin\DashboardController@restaurar', ['auth']);
}

/**
 * Perfil ADMIN (legado)
 */
function registerProfileRoutes(): void
{
    Router::add('GET',  'admin/{username}/perfil',          'Admin\ProfileController@view',            ['auth']);
    Router::add('GET',  'admin/{username}/perfil/editar',   'Admin\ProfileController@edit',            ['auth']);
    Router::add('POST', 'admin/{username}/perfil/salvar',   'Admin\ProfileController@update',          ['auth']);
    Router::add('POST', 'admin/perfil/atualizar-campo',     'Admin\ProfileController@updateField',     ['auth']);
    Router::add('GET',  'admin/{username}/alterar-senha',   'Admin\ProfileController@editCredentials', ['auth']);
    Router::add('POST', 'admin/alterar-senha',              'Admin\ProfileController@updateCredentials', ['auth']);
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

    // Compat específico
    Router::add('GET', 'admins/{username}/editCredentials', function ($username) {
        header('Location: ' . BASE_URL . 'admin/' . $username . '/alterar-senha');
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
