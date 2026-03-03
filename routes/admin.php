<?php

declare(strict_types=1);

use Application\Core\Router;

/**
 * ============================================
 * ROTAS DO PAINEL ADMINISTRATIVO
 * ============================================
 * Todas as rotas exigem autenticação
 * Rotas com 'onboarding' middleware exigem onboarding completo
 */

// Onboarding (SEM middleware de onboarding - senão dá loop)
Router::add('GET', '/onboarding', 'Admin\\OnboardingController@index', ['auth']);

// Dashboard (definido em web.php)

// Lançamentos
Router::add('GET', '/lancamentos', 'Admin\\LancamentoController@index', ['auth', 'onboarding']);

// Relatórios
Router::add('GET', '/relatorios', 'Admin\\RelatoriosController@view', ['auth', 'onboarding']);

// Configurações
Router::add('GET',  '/config',     'Admin\\ConfigController@index', ['auth', 'onboarding']);
Router::add('POST', '/api/config', 'Api\\Admin\\ConfigController@update',  ['auth', 'csrf', 'ratelimit']);

// Perfil
Router::add('GET', '/perfil', 'Admin\\PerfilController@index', ['auth', 'onboarding']);

// Contas
Router::add('GET', '/contas',            'Admin\\ContasController@index',    ['auth', 'onboarding']);
Router::add('GET', '/contas/arquivadas', 'Admin\\ContasController@archived', ['auth', 'onboarding']);

// Categorias
Router::add('GET', '/categorias', 'Admin\\CategoriaController@index', ['auth', 'onboarding']);


// Gamificação
Router::add('GET', '/gamification', 'GamificationController@index', ['auth', 'onboarding']);

// Billing / Planos
Router::add('GET', '/billing', 'Admin\\BillingController@index', ['auth', 'onboarding']);

// Super Admin
Router::add('GET', '/super_admin', 'SysAdmin\\SuperAdminController@index', ['auth', 'sysadmin']);
Router::add('GET', '/sysadmin', 'SysAdmin\\SuperAdminController@index', ['auth', 'sysadmin']);

// SysAdmin - Gerenciamento de cupons
Router::add('GET', '/sysadmin/cupons', 'SysAdmin\\CupomViewController@index', ['auth', 'sysadmin']);

// SysAdmin - Comunicações e campanhas
Router::add('GET', '/sysadmin/comunicacoes', 'SysAdmin\\CommunicationController@index', ['auth', 'sysadmin']);

// SysAdmin - Listagem de usuários com filtros
Router::add('GET', '/sysadmin/users', 'SysAdmin\\UserAdminController@list', ['auth', 'sysadmin']);

// SysAdmin - Listagem de usuários com filtros (rota alternativa)
Router::add('GET', '/super_admin/users', 'SysAdmin\UserAdminController@list', ['auth', 'sysadmin']);

// SysAdmin - Listagem de usuários com filtros (rota alternativa com hífen)
Router::add('GET', '/super-admin/users', 'SysAdmin\UserAdminController@list', ['auth', 'sysadmin']);



// Redirects legados
Router::add('GET', '/admin', function () {
    header('Location: ' . BASE_URL . 'login');
    exit;
});

Router::add('GET', '/admin/login', function () {
    header('Location: ' . BASE_URL . 'login');
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
