<?php

declare(strict_types=1);

use Application\Core\Router;

/**
 * ============================================
 * ROTAS DO PAINEL ADMINISTRATIVO
 * ============================================
 * Todas as rotas exigem autenticação
 */

// Dashboard
Router::add('GET', '/dashboard', 'Admin\\DashboardController@dashboard', ['auth']);

// Lançamentos
Router::add('GET', '/lancamentos', 'Admin\\LancamentoController@index', ['auth']);

// Relatórios
Router::add('GET', '/relatorios', 'Admin\\RelatoriosController@view', ['auth']);

// Configurações
Router::add('GET',  '/config',     'Admin\\ConfigController@index', ['auth']);
Router::add('POST', '/api/config', 'Api\\ConfigController@update',  ['auth', 'csrf']);

// Perfil
Router::add('GET', '/perfil', 'Admin\\PerfilController@index', ['auth']);

// Contas
Router::add('GET', '/contas',            'Admin\\ContasController@index',    ['auth']);
Router::add('GET', '/contas/arquivadas', 'Admin\\ContasController@archived', ['auth']);

// Categorias
Router::add('GET', '/categorias', 'Admin\\CategoriaController@index', ['auth']);

// Agendamentos
Router::add('GET', '/agendamentos', 'Admin\\AgendamentoController@index', ['auth']);

// Investimentos
Router::add('GET', '/investimentos', 'Admin\\InvestimentosController@index', ['auth']);

// Billing / Planos
Router::add('GET', '/billing', 'Admin\\BillingController@index', ['auth']);

// Super Admin
Router::add('GET', '/super_admin', 'SysAdmin\\SuperAdminController@index', ['auth']);

// SysAdmin - Gerenciamento de cupons
Router::add('GET', '/sysadmin/cupons', function () {
    require_once __DIR__ . '/../views/sysAdmin/cupons.php';
}, ['auth']);

// SysAdmin - Listagem de usuários com filtros
Router::add('GET', '/sysadmin/users', 'SysAdmin\\UserAdminController@list', ['auth']);

// SysAdmin - Listagem de usuários com filtros (rota alternativa)
Router::add('GET', '/super_admin/users', 'SysAdmin\UserAdminController@list', ['auth']);

// SysAdmin - Listagem de usuários com filtros (rota alternativa com hífen)
Router::add('GET', '/super-admin/users', 'SysAdmin\UserAdminController@list', ['auth']);

// Redirects legados
Router::add('GET', '/admin', function () {
    header('Location: ' . BASE_URL . 'login');
    exit;
});

Router::add('GET', '/admin/login', function () {
    header('Location: ' . BASE_URL . 'login');
    exit;
});

Router::add('GET', '/admin/dashboard', function () {
    header('Location: ' . BASE_URL . 'dashboard');
    exit;
});

Router::add('GET', '/admin/home', function () {
    if (isset($_SESSION['user_id']) || isset($_SESSION['admin_username'])) {
        header('Location: ' . BASE_URL . 'dashboard');
    } else {
        session_destroy();
        header('Location: ' . BASE_URL . 'login');
    }
    exit;
});
