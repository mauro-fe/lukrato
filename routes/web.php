web.php

<?php



use Application\Core\Router;



/**

 * Registro de rotas

 */

registerAuthRoutes();

registerRedirectRoutes();

registerAppRoutes();

registerApiRoutes();

registerBillingRoutes();



/* =========================

 * AUTH

 * =======================*/

function registerAuthRoutes(): void

{

    Router::add('GET',  '/login',          'Auth\\LoginController@login');

    Router::add('POST', '/login/entrar',   'Auth\\LoginController@processLogin');

    Router::add('GET',  '/logout',         'Auth\\LoginController@logout');



    Router::add('POST', '/register/criar', 'Auth\\RegistroController@store');

   

    // Google OAuth

    Router::add('GET',  '/auth/google/register', 'Auth\\GoogleLoginController@login');

    Router::add('GET',  '/auth/google/callback', 'Auth\\GoogleCallbackController@callback');

}



/* =========================

 * REDIRECTS / LANDING

 * =======================*/

function registerRedirectRoutes(): void

{

    Router::add('GET',  '', function () {

        redirectToUserDashboard();

    });

    Router::add('GET',  '/', function () {

        redirectToUserDashboard();

    });



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

}



/* =========================

 * API

 * =======================*/

function registerApiRoutes(): void

{

    // Perfil

    Router::add('GET',  '/api/perfil',  'Api\\PerfilController@show',   ['auth']);

    Router::add('POST', '/api/perfil',  'Api\\PerfilController@update', ['auth', 'csrf']);



    // Dashboard / Opções

    Router::add('GET', '/api/dashboard/metrics', 'Api\\FinanceiroController@metrics', ['auth']);

    Router::add('GET', '/api/options',           'Api\\FinanceiroController@options', ['auth']);



    // Relatórios

    Router::add('GET', '/api/reports/overview',   'Api\\RelatoriosController@overview',  ['auth']);

    Router::add('GET', '/api/reports/table',      'Api\\RelatoriosController@table',     ['auth']);

    Router::add('GET', '/api/reports/timeseries', 'Api\\RelatoriosController@timeseries', ['auth']);

    Router::add('GET', '/api/reports',            'Api\\RelatoriosController@index',     ['auth']);



    // Lançamentos (REST-like)

    Router::add('GET',    '/api/lancamentos',      'Api\\LancamentosController@index',   ['auth']);

    Router::add('PUT',    '/api/lancamentos/{id}', 'Api\\LancamentosController@update',  ['auth', 'csrf']);

    Router::add('DELETE', '/api/lancamentos/{id}', 'Api\\LancamentosController@destroy', ['auth', 'csrf']);



    // Transações / Transferências

    Router::add('POST', '/api/transactions',               'Api\\FinanceiroController@store',   ['auth', 'csrf']);

    Router::add('PUT', '/api/transactions/{id}',          'Api\\FinanceiroController@update',  ['auth', 'csrf']);

    Router::add('POST', '/api/transactions/{id}/update',   'Api\\FinanceiroController@update',  ['auth', 'csrf']); // compat

    Router::add('POST', '/api/transfers',                  'Api\\FinanceiroController@transfer', ['auth', 'csrf']);



    // Contas

    Router::add('GET',   '/api/accounts',               'Api\\ContasController@index',   ['auth']);

    Router::add('POST',  '/api/accounts',               'Api\\ContasController@store',   ['auth', 'csrf']);

    Router::add('PUT',   '/api/accounts/{id}',          'Api\\ContasController@update',  ['auth', 'csrf']);

    Router::add('DELETE', '/api/accounts/{id}',          'Api\\ContasController@delete',  ['auth', 'csrf']);

    Router::add('POST',  '/api/accounts/archive',       'Api\\ContasController@archive', ['auth', 'csrf']);

    Router::add('POST',  '/api/accounts/unarchive',     'Api\\ContasController@unarchive', ['auth', 'csrf']);



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

    Router::add('POST', '/api/agendamentos/{id}/status',     'Api\\AgendamentoController@updateStatus', ['auth', 'csrf']);

    Router::add('POST', '/api/agendamentos/{id}/cancelar',   'Api\\AgendamentoController@cancel',       ['auth', 'csrf']);



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