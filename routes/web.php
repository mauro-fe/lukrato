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

// BLOG / APRENDA (100% público, SEO)
Router::add('GET', '/aprenda', 'Site\\AprendaController@index');
Router::add('GET', '/aprenda/categoria/{slug}', 'Site\\AprendaController@categoria');
Router::add('GET', '/aprenda/{slug}', 'Site\\AprendaController@show');

// SITEMAP DINÂMICO
Router::add('GET', '/sitemap.xml', 'Site\\SitemapController@index');



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

    Router::add('GET', '/dashboard',         'Admin\\DashboardController@dashboard', ['auth', 'onboarding']);

    Router::add('GET', '/lancamentos',       'Admin\\LancamentoController@index',    ['auth', 'onboarding']);

    Router::add('GET', '/faturas',           'Admin\FaturaController@index',  ['auth', 'onboarding']);

    Router::add('GET', '/relatorios',        'Admin\\RelatoriosController@view',     ['auth', 'onboarding']);


    Router::add('GET', '/perfil',            'Admin\\PerfilController@index',        ['auth', 'onboarding']);

    Router::add('GET', '/contas',            'Admin\\ContasController@index',        ['auth', 'onboarding']);

    Router::add('GET', '/contas/arquivadas', 'Admin\\ContasController@archived',     ['auth', 'onboarding']);

    Router::add('GET', '/cartoes',           'Admin\\CartoesController@index',       ['auth', 'onboarding']);

    Router::add('GET', '/cartoes/arquivadas', 'Admin\\CartoesController@archived',    ['auth', 'onboarding']);


    // Agendamentos removido - unificado em lançamentos


    Router::add('GET', '/financas',           'Admin\\FinancasController@index',       ['auth', 'onboarding']);

    // Premium routes e webhook ficam em api.php e webhooks.php com middleware adequado
}


/* =========================

 * API — todas as rotas da API ficam em routes/api.php

 * =======================*/

function registerApiRoutes(): void

{
    // Removido: todas as rotas de API foram consolidadas em routes/api.php
    // com middleware adequado (auth, csrf, ratelimit).
    // Anteriormente este bloco duplicava rotas de api.php sem middleware,
    // o que anulava as proteções pois web.php carrega primeiro.
}



/* =========================

 * BILLING (Página de planos)
 * Rota registrada em routes/admin.php

 * =======================*/



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
