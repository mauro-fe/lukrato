<?php

declare(strict_types=1);

use Application\Core\Router;

/**
 * ============================================
 * ROTAS DA API REST
 * ============================================
 * Padrão REST com autenticação e CSRF
 */

// ============================================
// SEGURANÇA / UTILIDADES
// ============================================

Router::add('POST', '/api/csrf/refresh', 'Api\\SecurityController@refreshCsrf');

// ============================================
// GERENCIAMENTO DE SESSÃO
// ============================================

// Status e Renew sem middleware auth para permitir verificação/renovação mesmo com sessão expirada
Router::add('GET',  '/api/session/status',    'Api\\SessionController@status');
Router::add('POST', '/api/session/renew',     'Api\\SessionController@renew');
Router::add('POST', '/api/session/heartbeat', 'Api\\SessionController@heartbeat', ['auth']);

// ============================================
// CONTATO / SUPORTE (Público)
// ============================================

Router::add('POST', '/api/contato/enviar', 'Api\\ContactController@send');
Router::add('POST', '/api/suporte/enviar', 'Api\\SupportController@send');

// ============================================
// PERFIL
// ============================================

Router::add('GET',  '/api/perfil', 'Api\\PerfilController@show',   ['auth']);
Router::add('POST', '/api/perfil', 'Api\\PerfilController@update', ['auth', 'csrf']);
Router::add('POST', '/api/perfil/tema', 'Api\\PerfilController@updateTheme', ['auth', 'csrf']);
Router::add('DELETE', '/api/perfil/delete', 'Api\\PerfilController@delete', ['auth', 'csrf']);

// ============================================
// ONBOARDING
// ============================================

Router::add('GET',  '/api/onboarding/status',    'Api\\OnboardingController@status',   ['auth']);
Router::add('POST', '/api/onboarding/complete',  'Api\\OnboardingController@complete', ['auth', 'csrf']);
Router::add('POST', '/api/onboarding/skip-tour', 'Api\\OnboardingController@skipTour', ['auth', 'csrf']);
Router::add('POST', '/api/onboarding/reset',     'Api\\OnboardingController@reset',    ['auth', 'csrf']);

// ============================================
// DASHBOARD
// ============================================

Router::add('GET', '/api/dashboard/metrics',      'Api\\FinanceiroController@metrics',      ['auth']);
Router::add('GET', '/api/dashboard/transactions', 'Api\\DashboardController@transactions',  ['auth']);
Router::add('GET', '/api/dashboard/comparativo-competencia', 'Api\\DashboardController@comparativoCompetenciaCaixa', ['auth']);
Router::add('GET', '/api/options',                'Api\\FinanceiroController@options',      ['auth']);

// ============================================
// RELATÓRIOS
// ============================================

Router::add('GET', '/api/reports',             'Api\\RelatoriosController@index',        ['auth']);
Router::add('GET', '/api/reports/overview',    'Api\\RelatoriosController@overview',     ['auth']);
Router::add('GET', '/api/reports/table',       'Api\\RelatoriosController@table',        ['auth']);
Router::add('GET', '/api/reports/timeseries',  'Api\\RelatoriosController@timeseries',   ['auth']);
Router::add('GET', '/api/reports/summary',     'Api\\RelatoriosController@summary',      ['auth']);
Router::add('GET', '/api/reports/insights',    'Api\\RelatoriosController@insights',     ['auth']);
Router::add('GET', '/api/reports/comparatives', 'Api\\RelatoriosController@comparatives', ['auth']);
Router::add('GET', '/api/reports/export',      'Api\\RelatoriosController@export',       ['auth']);

// ============================================
// LANÇAMENTOS (REST)
// ============================================

Router::add('GET',    '/api/lancamentos',        'Api\\LancamentosController@index',   ['auth']);
Router::add('POST',   '/api/lancamentos',        'Api\\LancamentosController@store',   ['auth', 'csrf']);
Router::add('PUT',    '/api/lancamentos/{id}',   'Api\\LancamentosController@update',  ['auth', 'csrf']);
Router::add('DELETE', '/api/lancamentos/{id}',   'Api\\LancamentosController@destroy', ['auth', 'csrf']);
Router::add('GET',    '/api/lancamentos/usage',  'Api\\LancamentosController@usage',   ['auth']);
Router::add('GET',    '/api/lancamentos/export', 'Api\\LancamentosController@export',  ['auth']);

// Rota para histórico recente de uma conta (alias do index com limit)
Router::add('GET',    '/api/contas/{id}/lancamentos', 'Api\\LancamentosController@index', ['auth']);

// ============================================
// TRANSAÇÕES / TRANSFERÊNCIAS
// ============================================

Router::add('POST', '/api/transactions',            'Api\\FinanceiroController@store',    ['auth', 'csrf']);
Router::add('PUT',  '/api/transactions/{id}',       'Api\\FinanceiroController@update',   ['auth', 'csrf']);
Router::add('POST', '/api/transactions/{id}/update', 'Api\\FinanceiroController@update',   ['auth', 'csrf']); // Compat
Router::add('POST', '/api/transfers',               'Api\\FinanceiroController@transfer', ['auth', 'csrf']);

// ============================================
// CONTAS (REST)
// ============================================

Router::add('GET',    '/api/accounts',              'Api\\ContasController@index',      ['auth']);
Router::add('POST',   '/api/accounts',              'Api\\ContasController@store',      ['auth', 'csrf']);
Router::add('PUT',    '/api/accounts/{id}',         'Api\\ContasController@update',     ['auth', 'csrf']);
Router::add('DELETE', '/api/accounts/{id}',         'Api\\ContasController@delete',     ['auth', 'csrf']);
Router::add('POST',   '/api/accounts/{id}/archive', 'Api\\ContasController@archive',    ['auth', 'csrf']);
Router::add('POST',   '/api/accounts/{id}/restore', 'Api\\ContasController@restore',    ['auth', 'csrf']);
Router::add('POST',   '/api/accounts/{id}/delete',  'Api\\ContasController@hardDelete', ['auth', 'csrf']);

// Rotas legadas (manter compatibilidade)
Router::add('POST', '/api/accounts/archive',   'Api\\ContasController@archive',   ['auth', 'csrf']);
Router::add('POST', '/api/accounts/unarchive', 'Api\\ContasController@unarchive', ['auth', 'csrf']);

// Rotas em português (compatibilidade com frontend)
Router::add('GET',    '/api/instituicoes',             'Api\\ContasController@instituicoes', ['auth']);
Router::add('POST',   '/api/instituicoes',             'Api\\ContasController@createInstituicao', ['auth', 'csrf']);
Router::add('GET',    '/api/contas/instituicoes',      'Api\\ContasController@instituicoes', ['auth']);
Router::add('GET',    '/api/contas',                   'Api\\ContasController@index',        ['auth']);
Router::add('POST',   '/api/contas',                   'Api\\ContasController@store',        ['auth', 'csrf']);
Router::add('PUT',    '/api/contas/{id}',              'Api\\ContasController@update',       ['auth', 'csrf']);
Router::add('POST',   '/api/contas/{id}/archive',      'Api\\ContasController@archive',      ['auth', 'csrf']);
Router::add('POST',   '/api/contas/{id}/restore',      'Api\\ContasController@restore',      ['auth', 'csrf']);
Router::add('DELETE', '/api/contas/{id}',              'Api\\ContasController@destroy',      ['auth', 'csrf']);

// ============================================
// CATEGORIAS (REST)
// ============================================

Router::add('GET',    '/api/categorias',     'Api\\CategoriaController@index',  ['auth']);
Router::add('POST',   '/api/categorias',     'Api\\CategoriaController@store',  ['auth', 'csrf']);
Router::add('PUT',    '/api/categorias/{id}', 'Api\\CategoriaController@update', ['auth', 'csrf']);
Router::add('DELETE', '/api/categorias/{id}', 'Api\\CategoriaController@delete', ['auth', 'csrf']);

// ============================================
// AGENDAMENTOS (REST)
// ============================================

Router::add('GET',  '/api/agendamentos',                  'Api\\AgendamentoController@index',        ['auth']);
Router::add('GET',  '/api/agendamentos/{id}',             'Api\\AgendamentoController@show',         ['auth']);
Router::add('POST', '/api/agendamentos',                  'Api\\AgendamentoController@store',        ['auth', 'csrf']);
Router::add('PUT',  '/api/agendamentos/{id}',             'Api\\AgendamentoController@update',       ['auth', 'csrf']);
Router::add('POST', '/api/agendamentos/{id}',             'Api\\AgendamentoController@update',       ['auth', 'csrf']); // Compat
Router::add('POST', '/api/agendamentos/{id}/status',      'Api\\AgendamentoController@updateStatus', ['auth', 'csrf']);
Router::add('POST', '/api/agendamentos/{id}/executar',    'Api\\AgendamentoController@executar',     ['auth', 'csrf']);
Router::add('POST', '/api/agendamentos/{id}/cancelar',    'Api\\AgendamentoController@cancel',       ['auth', 'csrf']);
Router::add('POST', '/api/agendamentos/{id}/reativar',    'Api\\AgendamentoController@restore',      ['auth', 'csrf']);

// ============================================
// INVESTIMENTOS
// ============================================

// Estatísticas e categorias (devem vir antes das rotas dinâmicas)
Router::add('GET', '/api/investimentos/stats',      'Api\\InvestimentosController@stats',      ['auth']);
Router::add('GET', '/api/investimentos/categorias', 'Api\\InvestimentosController@categorias', ['auth']);

// CRUD de investimentos
Router::add('GET',  '/api/investimentos',             'Api\\InvestimentosController@index',   ['auth']);
Router::add('POST', '/api/investimentos',             'Api\\InvestimentosController@store',   ['auth', 'csrf']);
Router::add('GET',  '/api/investimentos/{id}',        'Api\\InvestimentosController@show',    ['auth']);
Router::add('POST', '/api/investimentos/{id}/update', 'Api\\InvestimentosController@update',  ['auth', 'csrf']);
Router::add('POST', '/api/investimentos/{id}/delete', 'Api\\InvestimentosController@destroy', ['auth', 'csrf']);
Router::add('POST', '/api/investimentos/{id}/preco',  'Api\\InvestimentosController@atualizarPreco', ['auth', 'csrf']);

// Transações de investimentos
Router::add('GET',  '/api/investimentos/{id}/transacoes', 'Api\\InvestimentosController@transacoes',     ['auth']);
Router::add('POST', '/api/investimentos/{id}/transacoes', 'Api\\InvestimentosController@criarTransacao', ['auth', 'csrf']);

// Proventos de investimentos
Router::add('GET',  '/api/investimentos/{id}/proventos', 'Api\\InvestimentosController@proventos',     ['auth']);
Router::add('POST', '/api/investimentos/{id}/proventos', 'Api\\InvestimentosController@criarProvento', ['auth', 'csrf']);

// ============================================
// NOTIFICAÇÕES
// ============================================

Router::add('GET',  '/api/notificacoes',            'Api\\NotificacaoController@index',            ['auth']);
Router::add('GET',  '/api/notificacoes/unread',     'Api\\NotificacaoController@unreadCount',      ['auth']);
Router::add('POST', '/api/notificacoes/marcar',     'Api\\NotificacaoController@marcarLida',       ['auth', 'csrf']);
Router::add('POST', '/api/notificacoes/marcar-todas', 'Api\\NotificacaoController@marcarTodasLidas', ['auth', 'csrf']);

// ============================================
// PREFERÊNCIAS DE USUÁRIO
// ============================================

Router::add('GET',  '/api/user/theme', 'Api\\PreferenciaUsuarioController@show',   ['auth']);
Router::add('POST', '/api/user/theme', 'Api\\PreferenciaUsuarioController@update', ['auth', 'csrf']);

// ============================================
// PREMIUM / ASSINATURA
// ============================================

Router::add('POST', '/premium/checkout', 'PremiumController@checkout', ['auth', 'csrf']);
Router::add('POST', '/premium/cancel',   'PremiumController@cancel',   ['auth', 'csrf']);
Router::add('GET',  '/premium/check-payment/{paymentId}', 'PremiumController@checkPayment', ['auth']);
Router::add('GET',  '/premium/pending-payment', 'PremiumController@getPendingPayment', ['auth']);
Router::add('POST', '/premium/cancel-pending', 'PremiumController@cancelPendingPayment', ['auth', 'csrf']);

// ============================================
// CUPONS DE DESCONTO
// ============================================

Router::add('GET',    '/api/cupons',           'SysAdmin\\CupomController@index',        ['auth']);
Router::add('POST',   '/api/cupons',           'SysAdmin\\CupomController@store',        ['auth', 'csrf']);
Router::add('PUT',    '/api/cupons',           'SysAdmin\\CupomController@update',       ['auth', 'csrf']);
Router::add('DELETE', '/api/cupons',           'SysAdmin\\CupomController@destroy',      ['auth', 'csrf']);
Router::add('GET',    '/api/cupons/validar',   'SysAdmin\\CupomController@validar',      ['auth']);
Router::add('GET',    '/api/cupons/estatisticas', 'SysAdmin\\CupomController@estatisticas', ['auth']);

// ============================================
// FATURAS DE CARTÃO (REST)
// ============================================

Router::add('GET',    '/api/faturas',              'Api\\FaturasController@index',       ['auth']);
Router::add('POST',   '/api/faturas',              'Api\\FaturasController@store',       ['auth', 'csrf']);
Router::add('GET',    '/api/faturas/{id}',         'Api\\FaturasController@show',        ['auth']);
Router::add('DELETE', '/api/faturas/{id}',         'Api\\FaturasController@destroy',     ['auth', 'csrf']);
Router::add('PUT',    '/api/faturas/{id}/itens/{itemId}', 'Api\\FaturasController@updateItem', ['auth', 'csrf']);
Router::add('POST',   '/api/faturas/{id}/itens/{itemId}/toggle', 'Api\\FaturasController@toggleItemPago', ['auth', 'csrf']);
Router::add('DELETE', '/api/faturas/{id}/itens/{itemId}', 'Api\\FaturasController@destroyItem', ['auth', 'csrf']);
Router::add('DELETE', '/api/faturas/{id}/itens/{itemId}/parcelamento', 'Api\\FaturasController@deleteParcelamento', ['auth', 'csrf']);

// Rotas antigas para compatibilidade (DEPRECATED - usar /api/faturas)
Router::add('GET',    '/api/parcelamentos',              'Api\\FaturasController@index',       ['auth']);
Router::add('POST',   '/api/parcelamentos',              'Api\\FaturasController@store',       ['auth', 'csrf']);
Router::add('GET',    '/api/parcelamentos/{id}',         'Api\\FaturasController@show',        ['auth']);
Router::add('DELETE', '/api/parcelamentos/{id}',         'Api\\FaturasController@destroy',     ['auth', 'csrf']);
Router::add('POST',   '/api/lancamentos/parcelado',      'Api\\FaturasController@store',       ['auth', 'csrf']);

// ============================================
// SYSADMIN
// ============================================

Router::add('GET', '/api/sysadmin/users', 'Api\\SysAdminController@listUsers', ['auth']);
Router::add('GET', '/api/sysadmin/users/{id}', 'Api\\SysAdminController@getUser', ['auth']);
Router::add('PUT', '/api/sysadmin/users/{id}', 'Api\\SysAdminController@updateUser', ['auth', 'csrf']);
Router::add('DELETE', '/api/sysadmin/users/{id}', 'Api\\SysAdminController@deleteUser', ['auth', 'csrf']);
Router::add('POST', '/api/sysadmin/grant-access', 'Api\\SysAdminController@grantAccess', ['auth', 'csrf']);
Router::add('POST', '/api/sysadmin/revoke-access', 'Api\\SysAdminController@revokeAccess', ['auth', 'csrf']);
Router::add('GET', '/api/sysadmin/stats', 'Api\\SysAdminController@getStats', ['auth']);

// ============================================
// PLANO / LIMITES
// ============================================

Router::add('GET', '/api/plan/limits',              'Api\\PlanController@limits',             ['auth']);
Router::add('GET', '/api/plan/features',            'Api\\PlanController@features',           ['auth']);
Router::add('GET', '/api/plan/can-create/{resource}', 'Api\\PlanController@canCreate',        ['auth']);
Router::add('GET', '/api/plan/history-restriction', 'Api\\PlanController@historyRestriction', ['auth']);

// ============================================
// INDICAÇÕES / REFERRAL
// ============================================

Router::add('GET', '/api/referral/info',     'Api\\ReferralController@getInfo');              // Público
Router::add('GET', '/api/referral/validate', 'Api\\ReferralController@validateCode');         // Público (para cadastro)
Router::add('GET', '/api/referral/stats',    'Api\\ReferralController@getStats',    ['auth']);
Router::add('GET', '/api/referral/code',     'Api\\ReferralController@getCode',     ['auth']);
Router::add('GET', '/api/referral/ranking',  'Api\\ReferralController@getRanking',  ['auth']);
