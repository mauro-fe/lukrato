<?php

/**
 * Definição de Rotas da Aplicação
 *
 * Este arquivo centraliza todas as rotas HTTP da aplicação,
 * organizadas por categoria e funcionalidade.
 */

use Application\Core\Router;

// ============================================================================
// ROTAS DO SISTEMA ADMINISTRATIVO (SYSADMIN)
// ============================================================================

registerSysAdminRoutes();

// ============================================================================
// ROTAS PÚBLICAS (USUÁRIOS FINAIS)
// ============================================================================

registerPublicRoutes();

// ============================================================================
// ROTAS DE AUTENTICAÇÃO
// ============================================================================

registerAuthRoutes();

// ============================================================================
// ROTAS ADMINISTRATIVAS (ÁREA DO ADMIN)
// ============================================================================

registerAdminRoutes();

// ============================================================================
// ROTAS DE REDIRECIONAMENTO E FALLBACKS
// ============================================================================

registerRedirectRoutes();

// ============================================================================
// FUNÇÕES DE REGISTRO DE ROTAS
// ============================================================================

/**
 * Registra rotas do sistema administrativo (SysAdmin)
 */
function registerSysAdminRoutes(): void
{
    // Dashboard e visão geral
    Router::add('GET', 'sysadmin/dashboard', 'SysAdmin\SysAdminController@index', ['sysadmin']);
    Router::add('GET', 'sysadmin/clientes', 'SysAdmin\SysAdminController@clientes', ['sysadmin']);

    // Gestão de administradores
    Router::add('GET', 'sysadmin/admins', 'SysAdmin\SysAdminController@admins', ['sysadmin']);
    Router::add('GET', 'sysadmin/admins/autorizar/{id}', 'SysAdmin\SysAdminController@autorizar', ['sysadmin']);
    Router::add('GET', 'sysadmin/admins/bloquear/{id}', 'SysAdmin\SysAdminController@bloquear', ['sysadmin']);
    Router::add('GET', 'sysadmin/admins/editar/{id}', 'SysAdmin\SysAdminController@editar', ['sysadmin']);
    Router::add('POST', 'sysadmin/admins/salvar/{id}', 'SysAdmin\SysAdminController@salvar', ['sysadmin']);

    // Registro de novos administradores
    Router::add('GET', 'admin/novo', 'Admin\RegisterController@showRegisterForm', ['sysadmin']);
    Router::add('POST', 'admin/novo/salvar', 'Admin\RegisterController@processRegister', ['sysadmin']);
}

/**
 * Registra rotas públicas (usuários finais)
 */
function registerPublicRoutes(): void
{
    // Páginas informativas
    Router::add('GET', 'sobre', 'Public\ResponderController@about');
    Router::add('GET', 'sobre/{slug}', 'Public\ResponderController@about');

    // Formulários de resposta
    Router::add('GET', 'formulario/{slug}', 'Public\ResponderController@responder');
    Router::add('GET', 'user/responder/{slugClinica}', 'Public\ResponderController@responder');

    // Processamento de respostas
    Router::add('POST', 'user/salvar-respostas', 'Public\RespostaController@salvar');
    Router::add('GET', 'obrigado', 'Public\RespostaController@obrigado');
}

/**
 * Registra rotas de autenticação
 */
function registerAuthRoutes(): void
{
    // Router::add('GET', 'admin/login', 'Auth\LoginController@login');
    // Router::add('POST', 'admin/login/entrar', 'Auth\LoginController@processLogin');
    // Router::add('GET', 'admin/logout', 'Auth\LoginController@logout');

    Router::add('GET',  'login',        'Auth\LoginController@login');
    Router::add('POST', 'login/entrar', 'Auth\LoginController@processLogin');
    Router::add('GET',  'logout',       'Auth\LoginController@logout');
}

function registerFinanceRoutes(): void
{
    // Dashboard financeiro (reaproveita seu DashboardController adaptado)
    Router::add('GET', 'admin/{username}/home', 'Admin\DashboardController@index', ['auth']);
    Router::add('GET', 'admin/{username}/dashboard-financas', 'Admin\DashboardController@index', ['auth']);

    // Contas
    Router::add('GET',  'admin/{username}/accounts',             'Admin\AccountController@index',  ['auth']);
    Router::add('POST', 'admin/{username}/accounts',             'Admin\AccountController@store',  ['auth', 'csrf']);
    Router::add('POST', 'admin/{username}/accounts/{id}/delete', 'Admin\AccountController@destroy', ['auth', 'csrf']);

    // Categorias
    Router::add('GET',  'admin/{username}/categories',             'Admin\CategoryController@index',  ['auth']);
    Router::add('POST', 'admin/{username}/categories',             'Admin\CategoryController@store',  ['auth', 'csrf']);
    Router::add('POST', 'admin/{username}/categories/{id}/delete', 'Admin\CategoryController@destroy', ['auth', 'csrf']);

    // Transações
    Router::add('GET',  'admin/{username}/transactions', 'Admin\TransactionController@index', ['auth']);
    Router::add('POST', 'admin/{username}/transactions', 'Admin\TransactionController@store', ['auth', 'csrf']);
}


/**
 * Registra rotas administrativas (área do admin)
 */
function registerAdminRoutes(): void
{
    registerDashboardRoutes();
    // registerQuestionRoutes();
    // registerTemplateRoutes();
    // registerResponseRoutes();
    registerProfileRoutes();
}

/**
 * Registra rotas do dashboard administrativo
 */
function registerDashboardRoutes(): void
{
    Router::add('GET', 'admin/{username}/dashboard', 'Admin\DashboardController@dashboard', ['auth']);
    Router::add('GET', 'admin/pesquisa', 'Admin\DashboardController@search', ['auth']);
    Router::add('POST', 'admin/pesquisa', 'Admin\DashboardController@search', ['auth']);

    Router::add('POST', 'admin/{username}/ficha/{id}/lixeira', 'Admin\DashboardController@moverParaLixeira', ['auth', 'csrf']);
    Router::add('GET', 'admin/{username}/fichas-lixeira', 'Admin\DashboardController@viewLixeira', ['auth']);
    Router::add(
        'POST',
        'admin/{username}/ficha/{id}/excluir-definitivo',
        'Admin\DashboardController@excluirDefinitivamente',
        ['auth', 'csrf']
    );
    Router::add('POST', 'admin/{username}/fichas/{id}/restaurar', 'Admin\DashboardController@restaurar', ['auth']);
}

/**
 * Registra rotas de gestão de perguntas
 */
function registerQuestionRoutes(): void
{
    // Banco de perguntas
    Router::add('GET', 'admin/{username}/banco-perguntas', 'Admin\PerguntaController@index', ['auth']);
    Router::add('POST', 'admin/perguntas/criar', 'Admin\PerguntaController@salvar', ['auth']);

    // Exclusão de perguntas
    Router::add('GET', 'admin/perguntas/excluir/{id}', 'Admin\PerguntaController@excluir', ['auth']);
    Router::add('POST', 'admin/perguntas/excluir/{id}', 'Admin\PerguntaController@excluir', ['auth']);
    Router::add('POST', 'admin/perguntas/excluir-multiplas', 'Admin\PerguntaController@excluirMultiplas', ['auth']);

    // Importação de modelos
    Router::add('POST', 'admin/perguntas/importar-modelo', 'Admin\PerguntaController@usarModelo', ['auth']);
}

/**
 * Registra rotas de fichas modelo
 */
function registerTemplateRoutes(): void
{
    // Listagem e visualização
    Router::add('GET', 'admin/{username}/fichas-modelo', 'Admin\FichaModeloController@index', ['auth']);
    Router::add('GET', 'admin/{username}/fichas-modelo/{id}/perguntas', 'Admin\FichaModeloController@perguntas');

    // Criação de fichas modelo
    Router::add('GET', 'admin/fichas-modelo/novo', 'Admin\FichaModeloController@criar');
    Router::add('POST', 'admin/fichas-modelo/novo/salvar', 'Admin\FichaModeloController@salvar');
    Router::add('POST', 'admin/fichas-modelo/novo-rapido', 'Admin\FichaModeloController@criarViaAjax', ['auth']);

    // Edição e atualização
    Router::add('GET', 'admin/fichas-modelo/editar/{id}', 'Admin\FichaModeloController@editar', ['auth']);
    Router::add('POST', 'admin/fichas-modelo/{id}/salvar', 'Admin\FichaModeloController@atualizar', ['auth']);

    // Exclusão
    Router::add('POST', 'admin/fichas-modelo/excluir/{id}', 'Admin\FichaModeloController@excluir', ['auth']);
}

/**
 * Registra rotas de fichas respondidas e respostas
 */
function registerResponseRoutes(): void
{
    // Visualização de fichas
    Router::add('GET', 'admin/{username}/fichas', 'Admin\DashboardController@dashboard', ['auth']);
    Router::add('GET', 'admin/fichas/{id}', 'Admin\DashboardController@verFicha', ['auth']);
    Router::add('GET', 'admin/{username}/respostas/visualizar/{id}', 'Admin\RespostaAdminController@visualizar');

    // Gestão de fichas
    Router::add('POST', 'admin/{username}/excluir/{id}', 'Admin\DashboardController@excluir', ['auth']);
    Router::add('GET', 'admin/{username}/pdf/{id}', 'Admin\DashboardController@gerarPdf', ['auth']);
    Router::add('POST', 'admin/fichas/observacao/{id}', 'Admin\DashboardController@adicionarObservacao', ['auth']);

    // Busca e lixeira
    Router::add('GET', 'admin/fichas/buscar', 'Admin\DashboardController@buscar', ['auth']);
    Router::add('POST', 'admin/fichas/buscar', 'Admin\DashboardController@buscar', ['auth']);
    Router::add('GET', 'admin/fichas/lixeira', 'Admin\DashboardController@lixeira', ['auth']);
}

/**
 * Registra rotas de perfil e configurações do admin
 */
function registerProfileRoutes(): void
{
    // Visualização e edição do perfil
    Router::add('GET', 'admin/{username}/perfil', 'Admin\ProfileController@view', ['auth']);
    Router::add('GET', 'admin/{username}/perfil/editar', 'Admin\ProfileController@edit', ['auth']);
    Router::add('POST', 'admin/{username}/perfil/salvar', 'Admin\ProfileController@update', ['auth']);
    Router::add('POST', 'admin/perfil/atualizar-campo', 'Admin\ProfileController@updateField', ['auth']);

    // Alteração de credenciais
    Router::add('GET', 'admin/{username}/alterar-senha', 'Admin\ProfileController@editCredentials', ['auth']);
    Router::add('POST', 'admin/alterar-senha', 'Admin\ProfileController@updateCredentials', ['auth']);
}

/**
 * Registra rotas de redirecionamento e fallbacks
 */
function registerRedirectRoutes(): void
{
    // Redirecionamentos para login
    Router::add('GET', '', function () {
        redirectToLogin();
    });

    Router::add('GET', '/', function () {
        redirectToLogin();
    });

    Router::add('GET', 'admin', function () {
        redirectToLogin();
    });

    // Compatibilidade com rotas antigas
    Router::add('GET', 'admins/{username}/editCredentials', function ($username) {
        header('Location: ' . BASE_URL . 'admin/' . $username . '/alterar-senha');
        exit;
    });

    Router::add('GET', 'admin/home', function () {
        redirectToUserDashboard();
    });
}

// ============================================================================
// FUNÇÕES AUXILIARES DE REDIRECIONAMENTO
// ============================================================================

/**
 * Redireciona para a página de login
 */
function redirectToLogin(): void
{
    header('Location: ' . BASE_URL . 'admin/login');
    exit;
}

/**
 * Redireciona para o dashboard do usuário ou login se não autenticado
 */
function redirectToUserDashboard(): void
{
    if (isset($_SESSION['admin_username'])) {
        $username = $_SESSION['admin_username'];
        header('Location: ' . BASE_URL . 'admin/' . $username . '/dashboard');
    } else {
        session_destroy();
        redirectToLogin();
    }
    exit;
}