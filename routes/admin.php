<?php

declare(strict_types=1);

use Application\Core\Router;
use Application\Core\Response;

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

// SysAdmin - Blog / Aprenda
Router::add('GET', '/sysadmin/blog', 'SysAdmin\\BlogViewController@index', ['auth', 'sysadmin']);
// SysAdmin - Assistente IA
Router::add('GET', '/sysadmin/ai', 'SysAdmin\AiViewController@index', ['auth', 'sysadmin']);
Router::add('GET', '/sysadmin/ai/logs', 'SysAdmin\AiLogsViewController@index', ['auth', 'sysadmin']);


// Redirects legados
Router::add('GET', '/admin', function () {
    return Response::redirectResponse(BASE_URL . 'login');
});

Router::add('GET', '/admin/login', function () {
    return Response::redirectResponse(BASE_URL . 'login');
});

Router::add('GET', '/admin/home', function () {
    if (isset($_SESSION['user_id'])) {
        return Response::redirectResponse(BASE_URL . 'dashboard');
    }

    session_destroy();

    return Response::redirectResponse(BASE_URL . 'login');
});
