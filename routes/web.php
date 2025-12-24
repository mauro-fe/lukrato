<?php


use Application\Core\Router;

/**
 * ============================================
 * ROTAS PÚBLICAS (LANDING PAGE)
 * ============================================
 */

// Landing principal
Router::add('GET', '/', 'Site\\LandingController@index');

// Seções da landing
Router::add('GET', '/funcionalidades', 'Site\\LandingController@index');
Router::add('GET', '/beneficios',      'Site\\LandingController@index');
Router::add('GET', '/planos',          'Site\\LandingController@index');
Router::add('GET', '/contato',         'Site\\LandingController@index');

// Cartão Digital (Bio do Instagram)
Router::add('GET', '/card', 'Site\\CardController@index');
Router::add('GET', '/links', 'Site\\CardController@index'); // Alias alternativo

// PÁGINAS LEGAIS DO SITE / TERMOS
Router::add('GET', '/termos', 'Site\\LegalController@terms');
Router::add('GET', '/privacidade', 'Site\\LegalController@privacy');
Router::add('GET', '/lgpd', 'Site\\LegalController@lgpd');

Router::add('GET', '/api/lancamentos/usage', 'Api\\LancamentosController@usage');


/* =========================

 * REDIRECTS / LANDING

 * =======================*/

function registerRedirectRoutes(): void

{

    /* Router::add('GET',  '', function () {

        redirectToUserDashboard();
    });

    Router::add('GET',  '/', function () {

        redirectToUserDashboard();
    });*/



    Router::add('GET',  '/admin',       function () {

        redirectToLogin();
    });

    Router::add('GET',  '/admin/login', function () {

        header('Location: ' . BASE_URL . 'login');

        exit;
    });

    Router::add('GET',  '/admin/dashboard', function () {

        header('Location: ' . BASE_URL . 'dashboard');

        exit;
    });

    Router::add('GET',  '/admin/home', function () {

        redirectToUserDashboard();
    });
}



/* =========================

 * APP (Views protegidas)

 * =======================*/

function registerAppRoutes(): void

{

    Router::add('GET', '/dashboard',         'Admin\\DashboardController@dashboard', ['auth']);

    Router::add('GET', '/lancamentos',       'Admin\\LancamentoController@index',    ['auth']);

    Router::add('GET', '/relatorios',        'Admin\\RelatoriosController@view',     ['auth']);

    Router::add('GET', '/config',            'Admin\\ConfigController@index',        ['auth']);

    Router::add('POST', '/api/config',        'Api\\ConfigController@update',         ['auth', 'csrf']);

    Router::add('GET', '/perfil',            'Admin\\PerfilController@index',        ['auth']);

    Router::add('GET', '/contas',            'Admin\\ContasController@index',        ['auth']);

    Router::add('GET', '/contas/arquivadas', 'Admin\\ContasController@archived',     ['auth']);

    Router::add('GET', '/categorias',        'Admin\\CategoriaController@index',     ['auth']);

    Router::add('GET', '/agendamentos',      'Admin\\AgendamentoController@index',   ['auth']);

    Router::add('GET', '/investimentos',      'Admin\\InvestimentosController@index',   ['auth']);

    Router::add('POST', '/premium/checkout', 'PremiumController@checkout');
    Router::add('POST', '/premium/cancel', 'PremiumController@cancel');
    Router::add('POST', '/api/webhook/asaas', 'Api\\AsaasWebhookController@receive');
    Router::add('GET', '/api/webhook/asaas', 'Api\AsaasWebhookController@test');
}

Router::add('POST', '/api/suporte/enviar', 'Api\\SupportController@send');


/* =========================

 * API

 * =======================*/

function registerApiRoutes(): void

{
    // Segurança / utilidades
    Router::add('POST', '/api/csrf/refresh', 'Api\\SecurityController@refreshCsrf');

    // Contato
    Router::add('POST', '/api/contato/enviar', 'Api\\ContactController@send');


    // Perfil

    Router::add('GET',  '/api/perfil',  'Api\\PerfilController@show',   ['auth']);

    Router::add('POST', '/api/perfil',  'Api\\PerfilController@update', ['auth', 'csrf']);



    // Dashboard / Opções

    Router::add('GET', '/api/dashboard/metrics', 'Api\\FinanceiroController@metrics', ['auth']);

    Router::add('GET', '/api/dashboard/transactions', 'Api\\DashboardController@transactions', ['auth']);
    Router::add('GET', '/api/options',           'Api\\FinanceiroController@options', ['auth']);



    // Relatórios

    Router::add('GET', '/api/reports/overview',   'Api\\RelatoriosController@overview',  ['auth']);

    Router::add('GET', '/api/reports/table',      'Api\\RelatoriosController@table',     ['auth']);

    Router::add('GET', '/api/reports/timeseries', 'Api\\RelatoriosController@timeseries', ['auth']);

    Router::add('GET', '/api/reports',            'Api\\RelatoriosController@index',     ['auth']);
    Router::add('GET', '/api/reports/export',     'Api\\RelatoriosController@export',    ['auth']);



    // Lançamentos (REST-like)

    Router::add('GET',    '/api/lancamentos',      'Api\\LancamentosController@index',   ['auth']);

    Router::add('POST',   '/api/lancamentos',      'Api\\LancamentosController@store',   ['auth', 'csrf']);
    Router::add('GET',    '/api/lancamentos',      'Api\\LancamentosController@index',   ['auth']);
    Router::add('GET',    '/api/lancamentos/export', 'Api\\LancamentosController@export', ['auth']);
    Router::add('PUT',    '/api/lancamentos/{id}', 'Api\\LancamentosController@update',  ['auth', 'csrf']);

    Router::add('DELETE', '/api/lancamentos/{id}', 'Api\\LancamentosController@destroy', ['auth', 'csrf']);



    // Transações / Transferências

    Router::add('POST', '/api/transactions',               'Api\\FinanceiroController@store',   ['auth', 'csrf']);

    Router::add('PUT', '/api/transactions/{id}',          'Api\\FinanceiroController@update',  ['auth', 'csrf']);

    Router::add('POST', '/api/transactions/{id}/update',   'Api\\FinanceiroController@update',  ['auth', 'csrf']); // compat

    Router::add('POST', '/api/transfers',                  'Api\\FinanceiroController@transfer', ['auth', 'csrf']);



    // Contas (versão unificada - antiga API V2)
    Router::add('GET',    '/api/contas/instituicoes',     'Api\\ContasController@instituicoes', ['auth']);
    Router::add('GET',    '/api/contas',                  'Api\\ContasController@index',        ['auth']);
    Router::add('POST',   '/api/contas',                  'Api\\ContasController@store',        ['auth', 'csrf']);
    Router::add('PUT',    '/api/contas/{id}',             'Api\\ContasController@update',       ['auth', 'csrf']);
    Router::add('POST',   '/api/contas/{id}/archive',     'Api\\ContasController@archive',      ['auth', 'csrf']);
    Router::add('POST',   '/api/contas/{id}/restore',     'Api\\ContasController@restore',      ['auth', 'csrf']);
    Router::add('DELETE', '/api/contas/{id}',             'Api\\ContasController@destroy',      ['auth', 'csrf']);

    // Cartões de Crédito
    Router::add('GET',    '/api/cartoes',                 'Api\\CartoesController@index',         ['auth']);
    Router::add('GET',    '/api/cartoes/{id}',            'Api\\CartoesController@show',          ['auth']);
    Router::add('POST',   '/api/cartoes',                 'Api\\CartoesController@store',         ['auth', 'csrf']);
    Router::add('PUT',    '/api/cartoes/{id}',            'Api\\CartoesController@update',        ['auth', 'csrf']);
    Router::add('POST',   '/api/cartoes/{id}/deactivate', 'Api\\CartoesController@deactivate',   ['auth', 'csrf']);
    Router::add('POST',   '/api/cartoes/{id}/reactivate', 'Api\\CartoesController@reactivate',   ['auth', 'csrf']);
    Router::add('DELETE', '/api/cartoes/{id}',            'Api\\CartoesController@destroy',       ['auth', 'csrf']);
    Router::add('PUT',    '/api/cartoes/{id}/limite',     'Api\\CartoesController@updateLimit',   ['auth', 'csrf']);
    Router::add('GET',    '/api/cartoes/resumo',          'Api\\CartoesController@summary',       ['auth']);


    // Categorias

    Router::add('GET',   '/api/categorias',               'Api\\CategoriaController@index',   ['auth']);

    Router::add('POST',  '/api/categorias',               'Api\\CategoriaController@store',   ['auth', 'csrf']);

    Router::add('PUT',   '/api/categorias/{id}',          'Api\\CategoriaController@update',  ['auth', 'csrf']);

    Router::add('DELETE', '/api/categorias/{id}',          'Api\\CategoriaController@delete',  ['auth', 'csrf']);

    // Router::add('POST','/api/categorias/delete',        'Api\\CategoriaController@delete', ['auth','csrf']);



    // Investimentos

    Router::add('GET',    '/api/investimentos',                       'Api\\InvestimentosController@index');

    // Rotas literais devem vir antes da dinâmica {id}

    Router::add('GET',    '/api/investimentos/stats',                 'Api\\InvestimentosController@stats');

    Router::add('GET',    '/api/investimentos/categorias',            'Api\\InvestimentosController@categorias');

    Router::add('GET',    '/api/investimentos/{id}',                  'Api\\InvestimentosController@show');

    Router::add('POST',   '/api/investimentos',                       'Api\\InvestimentosController@store');

    Router::add('POST',   '/api/investimentos/{id}/update',           'Api\\InvestimentosController@update');

    Router::add('POST',   '/api/investimentos/{id}/delete',           'Api\\InvestimentosController@destroy');

    Router::add('POST',   '/api/investimentos/{id}/preco',            'Api\\InvestimentosController@atualizarPreco');



    // Transações

    Router::add('GET',    '/api/investimentos/{id}/transacoes',       'Api\\InvestimentosController@transacoes');

    Router::add('POST',   '/api/investimentos/{id}/transacoes',       'Api\\InvestimentosController@criarTransacao');



    // Proventos

    Router::add('GET',    '/api/investimentos/{id}/proventos',        'Api\\InvestimentosController@proventos');

    Router::add('POST',   '/api/investimentos/{id}/proventos',        'Api\\InvestimentosController@criarProvento');



    // Estatísticas (cards do dashboard)



    // Preferência de tema do usuário

    Router::add('GET',  '/api/user/theme', 'Api\\PreferenciaUsuarioController@show',   ['auth']);

    Router::add('POST', '/api/user/theme', 'Api\\PreferenciaUsuarioController@update', ['auth', 'csrf']);



    // Agendamentos

    Router::add('POST', '/api/agendamentos',                 'Api\\AgendamentoController@store',        ['auth', 'csrf']);

    Router::add('GET',  '/api/agendamentos',                 'Api\\AgendamentoController@index',        ['auth']);

    Router::add('POST', '/api/agendamentos/{id}',            'Api\\AgendamentoController@update',       ['auth', 'csrf']);

    Router::add('POST', '/api/agendamentos/{id}/status',     'Api\\AgendamentoController@updateStatus', ['auth', 'csrf']);

    Router::add('POST', '/api/agendamentos/{id}/cancelar',   'Api\\AgendamentoController@cancel',       ['auth', 'csrf']);

    Router::add('POST', '/api/agendamentos/{id}/reativar',   'Api\\AgendamentoController@restore',      ['auth', 'csrf']);



    Router::add('GET',  '/api/notificacoes',           'Api\\NotificacaoController@index');

    Router::add('GET',  '/api/notificacoes/unread',    'Api\\NotificacaoController@unreadCount');

    Router::add('POST', '/api/notificacoes/marcar',    'Api\\NotificacaoController@marcarLida');

    Router::add('POST', '/api/notificacoes/marcar-todas', 'Api\\NotificacaoController@marcarTodasLidas');
}



/* =========================

 * BILLING / WEBHOOKS

 * =======================*/

function registerBillingRoutes(): void

{

    // Página do plano

    Router::add('GET',  '/billing',                      'Admin\\BillingController@index',        ['auth']);

    Router::add('POST', '/api/mercadopago/checkout', 'Api\\MercadoPagoController@createCheckout');

    Router::add('POST', '/api/webhooks/mercadopago', 'Api\\WebhookMercadoPagoController@handle');
    Router::add('POST', '/api/mercadopago/pay', 'Api\\MercadoPagoController@pay');
}



/* =========================

 * Helpers

 * =======================*/

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
