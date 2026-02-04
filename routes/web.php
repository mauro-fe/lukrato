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

    Router::add('GET', '/parcelamentos',     'Admin\FaturaController@index',  ['auth']);
    Router::add('GET', '/faturas',           'Admin\FaturaController@index',  ['auth']);

    Router::add('GET', '/relatorios',        'Admin\\RelatoriosController@view',     ['auth']);


    Router::add('GET', '/perfil',            'Admin\\PerfilController@index',        ['auth']);

    Router::add('GET', '/contas',            'Admin\\ContasController@index',        ['auth']);

    Router::add('GET', '/contas/arquivadas', 'Admin\\ContasController@archived',     ['auth']);

    Router::add('GET', '/cartoes',           'Admin\\CartoesController@index',       ['auth']);

    Router::add('GET', '/cartoes/arquivadas', 'Admin\\CartoesController@archived',    ['auth']);

    Router::add('GET', '/categorias',        'Admin\\CategoriaController@index',     ['auth']);

    Router::add('GET', '/agendamentos',      'Admin\\AgendamentoController@index',   ['auth']);

    Router::add('GET', '/investimentos',      'Admin\\InvestimentosController@index',   ['auth']);

    Router::add('POST', '/premium/checkout', 'PremiumController@checkout', ['auth']);
    Router::add('POST', '/premium/cancel', 'PremiumController@cancel', ['auth']);
    Router::add('GET', '/premium/check-payment/{paymentId}', 'PremiumController@checkPayment', ['auth']);
    Router::add('GET', '/premium/pending-pix', 'PremiumController@getPendingPix', ['auth']);
    Router::add('GET', '/premium/pending-payment', 'PremiumController@getPendingPayment', ['auth']);
    Router::add('POST', '/premium/cancel-pending', 'PremiumController@cancelPendingPayment', ['auth']);
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

    Router::add('GET', '/api/reports/card-details/{id}', 'Api\\RelatoriosController@cardDetails', ['auth']);

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
    Router::add('GET',    '/api/cartoes',                     'Api\\CartoesController@index',           ['auth']);
    Router::add('GET',    '/api/cartoes/resumo',              'Api\\CartoesController@summary',         ['auth']);
    Router::add('GET',    '/api/cartoes/alertas',             'Api\\CartoesController@alertas',         ['auth']);
    Router::add('GET',    '/api/cartoes/validar-integridade', 'Api\\CartoesController@validarIntegridade', ['auth']);
    Router::add('GET',    '/api/cartoes/{id}',                'Api\\CartoesController@show',            ['auth']);
    Router::add('POST',   '/api/cartoes',                     'Api\\CartoesController@store',           ['auth', 'csrf']);
    Router::add('PUT',    '/api/cartoes/{id}',                'Api\\CartoesController@update',          ['auth', 'csrf']);
    Router::add('POST',   '/api/cartoes/{id}/deactivate',     'Api\\CartoesController@deactivate',      ['auth', 'csrf']);
    Router::add('POST',   '/api/cartoes/{id}/reactivate',     'Api\\CartoesController@reactivate',      ['auth', 'csrf']);
    Router::add('POST',   '/api/cartoes/{id}/archive',        'Api\\CartoesController@archive',         ['auth', 'csrf']);
    Router::add('POST',   '/api/cartoes/{id}/restore',        'Api\\CartoesController@restore',         ['auth', 'csrf']);
    Router::add('POST',   '/api/cartoes/{id}/delete',         'Api\\CartoesController@delete',          ['auth', 'csrf']);
    Router::add('DELETE', '/api/cartoes/{id}',                'Api\\CartoesController@destroy',         ['auth', 'csrf']);
    Router::add('PUT',    '/api/cartoes/{id}/limite',         'Api\\CartoesController@updateLimit',     ['auth', 'csrf']);

    // Faturas de Cartão
    Router::add('GET',    '/api/cartoes/{id}/fatura',         'Api\\CartoesController@fatura',          ['auth']);
    Router::add('POST',   '/api/cartoes/{id}/fatura/pagar',   'Api\\CartoesController@pagarFatura',     ['auth', 'csrf']);
    Router::add('GET',    '/api/cartoes/{id}/fatura/status',  'Api\\CartoesController@statusFatura',    ['auth']);
    Router::add('POST',   '/api/cartoes/{id}/fatura/desfazer-pagamento', 'Api\\CartoesController@desfazerPagamentoFatura', ['auth', 'csrf']);
    Router::add('POST',   '/api/cartoes/{id}/parcelas/pagar', 'Api\\CartoesController@pagarParcelas',   ['auth', 'csrf']);
    Router::add('POST',   '/api/cartoes/parcelas/{id}/desfazer-pagamento', 'Api\\CartoesController@desfazerPagamentoParcela', ['auth', 'csrf']);
    Router::add('GET',    '/api/cartoes/{id}/faturas-pendentes', 'Api\\CartoesController@faturasPendentes', ['auth']);
    Router::add('GET',    '/api/cartoes/{id}/faturas-historico', 'Api\\CartoesController@faturasHistorico', ['auth']);
    Router::add('GET',    '/api/cartoes/{id}/parcelamentos-resumo', 'Api\\CartoesController@parcelamentosResumo', ['auth']);


    // Categorias

    Router::add('GET',   '/api/categorias',               'Api\\CategoriaController@index',   ['auth']);

    Router::add('POST',  '/api/categorias',               'Api\\CategoriaController@store',   ['auth', 'csrf']);

    Router::add('PUT',   '/api/categorias/{id}',          'Api\\CategoriaController@update',  ['auth', 'csrf']);

    Router::add('DELETE', '/api/categorias/{id}',          'Api\\CategoriaController@delete',  ['auth', 'csrf']);

    // Router::add('POST','/api/categorias/delete',        'Api\\CategoriaController@delete', ['auth','csrf']);


    // 🎮 GAMIFICAÇÃO
    Router::add('GET',  '/gamification',                    'GamificationController@index',                    ['auth']);
    Router::add('GET',  '/api/gamification/progress',      'Api\\GamificationController@getProgress',         ['auth']);
    Router::add('GET',  '/api/gamification/achievements',  'Api\\GamificationController@getAchievements',     ['auth']);
    Router::add('GET',  '/api/gamification/achievements/pending', 'Api\\GamificationController@getPendingAchievements', ['auth']);
    Router::add('GET',  '/api/gamification/stats',         'Api\\GamificationController@getStats',            ['auth']);
    Router::add('GET',  '/api/gamification/history',       'Api\\GamificationController@getHistory',          ['auth']);
    Router::add('POST', '/api/gamification/achievements/mark-seen', 'Api\\GamificationController@markAchievementsSeen', ['auth', 'csrf']);
    Router::add('GET',  '/api/gamification/leaderboard',   'Api\\GamificationController@getLeaderboard',      ['auth']);


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

    // Faturas de Cartão (antigo: Parcelamentos)
    Router::add('GET',    '/api/parcelamentos',                        'Api\\FaturasController@index',           ['auth']);
    Router::add('GET',    '/api/parcelamentos/{id}',                   'Api\\FaturasController@show',            ['auth']);
    Router::add('POST',   '/api/parcelamentos',                        'Api\\FaturasController@store',           ['auth', 'csrf']);
    Router::add('DELETE', '/api/parcelamentos/{id}',                   'Api\\FaturasController@destroy',         ['auth', 'csrf']);

    Router::add('GET',  '/api/notificacoes',           'Api\\NotificacaoController@index',         ['auth']);

    Router::add('GET',  '/api/notificacoes/unread',    'Api\\NotificacaoController@unreadCount',   ['auth']);

    Router::add('POST', '/api/notificacoes/marcar',    'Api\\NotificacaoController@marcarLida',    ['auth']);

    Router::add('POST', '/api/notificacoes/marcar-todas', 'Api\\NotificacaoController@marcarTodasLidas', ['auth']);

    // Rotas para recompensas de indicação
    Router::add('GET',  '/api/notificacoes/referral-rewards',      'Api\\NotificacaoController@getReferralRewards',     ['auth']);
    Router::add('POST', '/api/notificacoes/referral-rewards/seen', 'Api\\NotificacaoController@markReferralRewardsSeen', ['auth', 'csrf']);
}



/* =========================

 * BILLING / WEBHOOKS

 * =======================*/

function registerBillingRoutes(): void

{

    // Página do plano

    Router::add('GET',  '/billing',                      'Admin\\BillingController@index',        ['auth']);
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

    if (isset($_SESSION['user_id']) || isset($_SESSION['admin_username'])) {

        header('Location: ' . BASE_URL . 'dashboard');
    } else {

        session_destroy();

        redirectToLogin();
    }

    exit;
}

// Registrar todas as rotas
registerRedirectRoutes();
registerAppRoutes();
registerApiRoutes();
registerBillingRoutes();
