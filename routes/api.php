<?php

declare(strict_types=1);

use Application\Core\Router;

/**
 * ============================================
 * ROTAS DA API REST
 * ============================================
 * Padrão REST com autenticação e CSRF
 */


Router::add('POST', '/api/tour/complete', 'Api\TourController@complete', ['auth', 'csrf', 'ratelimit']);


// ============================================
// SEGURANÇA / UTILIDADES
// ============================================

Router::add('POST', '/api/csrf/refresh', 'Api\\User\\SecurityController@refreshCsrf', ['ratelimit']);

// ============================================
// GERENCIAMENTO DE SESSÃO
// ============================================

// Status e Renew sem middleware auth para permitir verificação/renovação mesmo com sessão expirada
Router::add('GET',  '/api/session/status',    'Api\\User\\SessionController@status');
Router::add('POST', '/api/session/renew',     'Api\\User\\SessionController@renew', ['ratelimit']);
Router::add('POST', '/api/session/heartbeat', 'Api\\User\\SessionController@heartbeat', ['auth']);

// ============================================
// CONTATO / SUPORTE (Público) - Rate limiting para evitar spam
// ============================================

Router::add('POST', '/api/contato/enviar', 'Api\\User\\ContactController@send', ['ratelimit']);
Router::add('POST', '/api/suporte/enviar', 'Api\\User\\SupportController@send', ['ratelimit']);

// ============================================
// PERFIL
// ============================================

Router::add('GET',  '/api/perfil', 'Api\\User\\PerfilController@show',   ['auth']);
Router::add('POST', '/api/perfil', 'Api\\User\\PerfilController@update', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/perfil/senha', 'Api\\User\\PerfilController@updatePassword', ['auth', 'csrf', 'ratelimit_strict']);
Router::add('POST', '/api/perfil/tema', 'Api\\User\\PerfilController@updateTheme', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/perfil/avatar', 'Api\\User\\PerfilController@uploadAvatar', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/perfil/avatar/preferences', 'Api\\User\\PerfilController@updateAvatarPreferences', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/perfil/avatar', 'Api\\User\\PerfilController@removeAvatar', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/perfil/delete', 'Api\\User\\PerfilController@delete', ['auth', 'csrf', 'ratelimit_strict']);

// ============================================
// ONBOARDING
// ============================================

Router::add('GET',  '/api/onboarding/status',    'Api\\User\\OnboardingController@status',   ['auth']);
Router::add('GET',  '/api/onboarding/checklist', 'Api\\User\\OnboardingController@checklist', ['auth']);
Router::add('POST', '/api/onboarding/complete',  'Api\\User\\OnboardingController@complete', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/onboarding/skip-tour', 'Api\\User\\OnboardingController@skipTour', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/onboarding/reset',     'Api\\User\\OnboardingController@reset',    ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/onboarding/conta',       'Api\\User\\OnboardingController@storeConta',      ['auth']);
Router::add('POST', '/api/onboarding/lancamento',  'Api\\User\\OnboardingController@storeLancamento', ['auth']);

// ============================================
// DASHBOARD
// ============================================

Router::add('GET', '/api/dashboard/metrics',      'Api\\Financeiro\\FinanceiroController@metrics',      ['auth']);
Router::add('GET', '/api/dashboard/transactions', 'Api\\Financeiro\\DashboardController@transactions',  ['auth']);
Router::add('GET', '/api/dashboard/comparativo-competencia', 'Api\\Financeiro\\DashboardController@comparativoCompetenciaCaixa', ['auth']);
Router::add('GET', '/api/dashboard/provisao',     'Api\\Financeiro\\DashboardController@provisao',       ['auth']);
Router::add('GET', '/api/dashboard/health-score', 'Api\\Financeiro\\DashboardController@healthScore',    ['auth']);
Router::add('GET', '/api/dashboard/health-score/insights', 'Api\\Financeiro\\DashboardController@healthScoreInsights', ['auth']);
Router::add('GET', '/api/dashboard/greeting-insight', 'Api\\Financeiro\\DashboardController@greetingInsight', ['auth']);
Router::add('GET', '/api/options',                'Api\\Financeiro\\FinanceiroController@options',      ['auth']);

// ============================================
// RELATÓRIOS
// ============================================

Router::add('GET', '/api/reports',             'Api\\Report\\RelatoriosController@index',        ['auth']);
Router::add('GET', '/api/reports/summary',     'Api\\Report\\RelatoriosController@summary',      ['auth']);
Router::add('GET', '/api/reports/insights',    'Api\\Report\\RelatoriosController@insights',     ['auth']);
Router::add('GET', '/api/reports/insights-teaser', 'Api\\Report\\RelatoriosController@insightsTeaser', ['auth']);
Router::add('GET', '/api/reports/comparatives', 'Api\\Report\\RelatoriosController@comparatives', ['auth']);
Router::add('GET', '/api/reports/card-details/{id}', 'Api\\Report\\RelatoriosController@cardDetails', ['auth']);
Router::add('GET', '/api/reports/export',      'Api\\Report\\RelatoriosController@export',       ['auth', 'ratelimit']);

// ============================================
// LANÇAMENTOS (REST)
// ============================================

Router::add('GET',    '/api/lancamentos',        'Api\\Lancamentos\\IndexController@__invoke',                ['auth']);
Router::add('POST',   '/api/lancamentos',        'Api\\Lancamentos\\StoreController@__invoke',                ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/lancamentos/delete',  'Api\\Lancamentos\\DestroyController@bulkDelete',            ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/lancamentos/{id}',   'Api\\Lancamentos\\UpdateController@__invoke',               ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/lancamentos/{id}',   'Api\\Lancamentos\\DestroyController@__invoke',              ['auth', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/lancamentos/usage',  'Api\\Lancamentos\\UsageController@__invoke',                ['auth']);
Router::add('GET',    '/api/lancamentos/export', 'Api\\Lancamentos\\ExportController@__invoke',               ['auth', 'ratelimit']);
Router::add('POST',   '/api/lancamentos/{id}/cancelar-recorrencia', 'Api\\Lancamentos\\CancelarRecorrenciaController@__invoke', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/lancamentos/{id}/pagar',                'Api\\Lancamentos\\MarcarPagoController@__invoke',          ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/lancamentos/{id}/despagar',              'Api\\Lancamentos\\MarcarPagoController@desmarcar',          ['auth', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/lancamentos/{id}/fatura-detalhes',      'Api\\Lancamentos\\FaturaDetalhesController@__invoke',      ['auth']);

// Rota para histórico recente de uma conta (alias do index com limit)
Router::add('GET',    '/api/contas/{id}/lancamentos', 'Api\\Lancamentos\\IndexController@__invoke', ['auth']);

// ============================================
// TRANSAÇÕES / TRANSFERÊNCIAS
// ============================================

Router::add('POST', '/api/transactions',            'Api\\Financeiro\\FinanceiroController@store',    ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',  '/api/transactions/{id}',       'Api\\Financeiro\\FinanceiroController@update',   ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/transactions/{id}/update', 'Api\\Financeiro\\FinanceiroController@update',   ['auth', 'csrf', 'ratelimit']); // Compat
Router::add('POST', '/api/transfers',               'Api\\Financeiro\\FinanceiroController@transfer', ['auth', 'csrf', 'ratelimit_strict']);

// ============================================
// CONTAS (REST)
// ============================================

Router::add('GET',    '/api/accounts',              'Api\\Conta\\ContasController@index',      ['auth']);
Router::add('POST',   '/api/accounts',              'Api\\Conta\\ContasController@store',      ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/accounts/{id}',         'Api\\Conta\\ContasController@update',     ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/accounts/{id}',         'Api\\Conta\\ContasController@destroy',    ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/accounts/{id}/archive', 'Api\\Conta\\ContasController@archive',    ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/accounts/{id}/restore', 'Api\\Conta\\ContasController@restore',    ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/accounts/{id}/delete',  'Api\\Conta\\ContasController@hardDelete', ['auth', 'csrf', 'ratelimit']);

// Rotas legadas (manter compatibilidade)
Router::add('POST', '/api/accounts/archive',   'Api\\Conta\\ContasController@archive',   ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/accounts/unarchive', 'Api\\Conta\\ContasController@restore',   ['auth', 'csrf', 'ratelimit']);

// Rotas em português (compatibilidade com frontend)
Router::add('GET',    '/api/instituicoes',             'Api\\Conta\\ContasController@instituicoes', ['auth']);
Router::add('POST',   '/api/instituicoes',             'Api\\Conta\\ContasController@createInstituicao', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/contas/instituicoes',      'Api\\Conta\\ContasController@instituicoes', ['auth']);
Router::add('GET',    '/api/contas',                   'Api\\Conta\\ContasController@index',        ['auth']);
Router::add('POST',   '/api/contas',                   'Api\\Conta\\ContasController@store',        ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/contas/{id}',              'Api\\Conta\\ContasController@update',       ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/contas/{id}/archive',      'Api\\Conta\\ContasController@archive',      ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/contas/{id}/restore',      'Api\\Conta\\ContasController@restore',      ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/contas/{id}/delete',       'Api\\Conta\\ContasController@hardDelete',   ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/contas/{id}',              'Api\\Conta\\ContasController@destroy',      ['auth', 'csrf', 'ratelimit']);

// ============================================
// CATEGORIAS (REST)
// ============================================

Router::add('GET',    '/api/categorias',          'Api\\Categoria\\CategoriaController@index',   ['auth']);
Router::add('POST',   '/api/categorias',          'Api\\Categoria\\CategoriaController@store',   ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/categorias/reorder',   'Api\\Categoria\\CategoriaController@reorder', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/categorias/{id}',      'Api\\Categoria\\CategoriaController@update',  ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/categorias/{id}',      'Api\\Categoria\\CategoriaController@delete',  ['auth', 'csrf', 'ratelimit']);

// ============================================
// SUBCATEGORIAS (REST)
// ============================================

Router::add('GET',    '/api/categorias/{id}/subcategorias', 'Api\\Categoria\\SubcategoriaController@index',  ['auth']);
Router::add('POST',   '/api/categorias/{id}/subcategorias', 'Api\\Categoria\\SubcategoriaController@store',  ['auth', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/subcategorias/grouped',         'Api\\Categoria\\SubcategoriaController@grouped', ['auth']);
Router::add('PUT',    '/api/subcategorias/{id}',            'Api\\Categoria\\SubcategoriaController@update', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/subcategorias/{id}',            'Api\\Categoria\\SubcategoriaController@delete', ['auth', 'csrf', 'ratelimit']);

// ============================================
// CARTÕES DE CRÉDITO (REST)
// ============================================

Router::add('GET',    '/api/cartoes',                     'Api\\Cartao\\CartoesController@index',           ['auth']);
Router::add('GET',    '/api/cartoes/resumo',              'Api\\Cartao\\CartoesController@summary',         ['auth']);
Router::add('GET',    '/api/cartoes/alertas',             'Api\\Cartao\\CartoesController@alertas',         ['auth']);
Router::add('GET',    '/api/cartoes/validar-integridade', 'Api\\Cartao\\CartoesController@validarIntegridade', ['auth']);
Router::add('GET',    '/api/cartoes/{id}',                'Api\\Cartao\\CartoesController@show',            ['auth']);
Router::add('POST',   '/api/cartoes',                     'Api\\Cartao\\CartoesController@store',           ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/cartoes/{id}',                'Api\\Cartao\\CartoesController@update',          ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/cartoes/{id}/deactivate',     'Api\\Cartao\\CartoesController@deactivate',      ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/cartoes/{id}/reactivate',     'Api\\Cartao\\CartoesController@reactivate',      ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/cartoes/{id}/archive',        'Api\\Cartao\\CartoesController@archive',         ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/cartoes/{id}/restore',        'Api\\Cartao\\CartoesController@restore',         ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/cartoes/{id}/delete',         'Api\\Cartao\\CartoesController@delete',          ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/cartoes/{id}',                'Api\\Cartao\\CartoesController@destroy',         ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/cartoes/{id}/limite',         'Api\\Cartao\\CartoesController@updateLimit',     ['auth', 'csrf', 'ratelimit']);

// Faturas de Cartão
Router::add('GET',    '/api/cartoes/{id}/fatura',         'Api\\Cartao\\CartoesController@fatura',          ['auth']);
Router::add('POST',   '/api/cartoes/{id}/fatura/pagar',   'Api\\Cartao\\CartoesController@pagarFatura',     ['auth', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/cartoes/{id}/fatura/status',  'Api\\Cartao\\CartoesController@statusFatura',    ['auth']);
Router::add('POST',   '/api/cartoes/{id}/fatura/desfazer-pagamento', 'Api\\Cartao\\CartoesController@desfazerPagamentoFatura', ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/cartoes/{id}/parcelas/pagar', 'Api\\Cartao\\CartoesController@pagarParcelas',   ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/cartoes/parcelas/{id}/desfazer-pagamento', 'Api\\Cartao\\CartoesController@desfazerPagamentoParcela', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/cartoes/{id}/faturas-pendentes', 'Api\\Cartao\\CartoesController@faturasPendentes', ['auth']);
Router::add('GET',    '/api/cartoes/{id}/faturas-historico', 'Api\\Cartao\\CartoesController@faturasHistorico', ['auth']);
Router::add('GET',    '/api/cartoes/{id}/parcelamentos-resumo', 'Api\\Cartao\\CartoesController@parcelamentosResumo', ['auth']);

// Recorrências / Assinaturas de Cartão
Router::add('GET',    '/api/cartoes/recorrencias',                'Api\\Cartao\\CartoesController@recorrencias',         ['auth']);
Router::add('GET',    '/api/cartoes/{id}/recorrencias',           'Api\\Cartao\\CartoesController@recorrenciasCartao',   ['auth']);
Router::add('POST',   '/api/cartoes/recorrencias/{id}/cancelar',  'Api\\Cartao\\CartoesController@cancelarRecorrencia',  ['auth', 'csrf', 'ratelimit']);

// ============================================
// GAMIFICAÇÃO
// ============================================

Router::add('GET',  '/api/gamification/progress',      'Api\\Gamification\\GamificationController@getProgress',         ['auth']);
Router::add('GET',  '/api/gamification/achievements',  'Api\\Gamification\\GamificationController@getAchievements',     ['auth']);
Router::add('GET',  '/api/gamification/achievements/pending', 'Api\\Gamification\\GamificationController@getPendingAchievements', ['auth']);
Router::add('GET',  '/api/gamification/stats',         'Api\\Gamification\\GamificationController@getStats',            ['auth']);
Router::add('GET',  '/api/gamification/history',       'Api\\Gamification\\GamificationController@getHistory',          ['auth']);
Router::add('POST', '/api/gamification/achievements/mark-seen', 'Api\\Gamification\\GamificationController@markAchievementsSeen', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',  '/api/gamification/leaderboard',   'Api\\Gamification\\GamificationController@getLeaderboard',      ['auth']);

// ============================================
// FINANÇAS (Metas + Orçamentos)
// ============================================

Router::add('GET',    '/api/financas/resumo',                    'Api\\Financeiro\\FinancasController@resumo',                  ['auth']);
Router::add('GET',    '/api/financas/metas',                     'Api\\Financeiro\\FinancasController@metasIndex',              ['auth']);
Router::add('POST',   '/api/financas/metas',                     'Api\\Financeiro\\FinancasController@metasStore',              ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/financas/metas/{id}',                'Api\\Financeiro\\FinancasController@metasUpdate',             ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/financas/metas/{id}/aporte',         'Api\\Financeiro\\FinancasController@metasAporte',             ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/financas/metas/{id}',                'Api\\Financeiro\\FinancasController@metasDestroy',            ['auth', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/financas/metas/templates',           'Api\\Financeiro\\FinancasController@metasTemplates',          ['auth']);
Router::add('GET',    '/api/financas/orcamentos',                'Api\\Financeiro\\FinancasController@orcamentosIndex',         ['auth']);
Router::add('POST',   '/api/financas/orcamentos',                'Api\\Financeiro\\FinancasController@orcamentosStore',         ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/financas/orcamentos/bulk',           'Api\\Financeiro\\FinancasController@orcamentosBulk',          ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/financas/orcamentos/{id}',           'Api\\Financeiro\\FinancasController@orcamentosDestroy',       ['auth', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/financas/orcamentos/sugestoes',      'Api\\Financeiro\\FinancasController@orcamentosSugestoes',     ['auth']);
Router::add('POST',   '/api/financas/orcamentos/aplicar-sugestoes', 'Api\\Financeiro\\FinancasController@orcamentosAplicarSugestoes', ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/financas/orcamentos/copiar-mes',    'Api\\Financeiro\\FinancasController@orcamentosCopiarMes',     ['auth', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/financas/insights',                  'Api\\Financeiro\\FinancasController@insights',                ['auth']);

// ============================================
// INVESTIMENTOS (REST)
// ============================================

// Estatísticas e categorias (devem vir antes das rotas dinâmicas)
Router::add('GET', '/api/investimentos/stats',      'Api\\Financeiro\\InvestimentosController@stats',      ['auth']);
Router::add('GET', '/api/investimentos/categorias', 'Api\\Financeiro\\InvestimentosController@categorias', ['auth']);

// CRUD de investimentos
Router::add('GET',  '/api/investimentos',             'Api\\Financeiro\\InvestimentosController@index',   ['auth']);
Router::add('POST', '/api/investimentos',             'Api\\Financeiro\\InvestimentosController@store',   ['auth', 'csrf', 'ratelimit']);
Router::add('GET',  '/api/investimentos/{id}',        'Api\\Financeiro\\InvestimentosController@show',    ['auth']);
Router::add('POST', '/api/investimentos/{id}/update', 'Api\\Financeiro\\InvestimentosController@update',  ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/investimentos/{id}/delete', 'Api\\Financeiro\\InvestimentosController@destroy', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/investimentos/{id}/preco',  'Api\\Financeiro\\InvestimentosController@atualizarPreco', ['auth', 'csrf', 'ratelimit']);

// Transações de investimentos
Router::add('GET',  '/api/investimentos/{id}/transacoes', 'Api\\Financeiro\\InvestimentosController@transacoes',     ['auth']);
Router::add('POST', '/api/investimentos/{id}/transacoes', 'Api\\Financeiro\\InvestimentosController@criarTransacao', ['auth', 'csrf', 'ratelimit']);

// Proventos de investimentos
Router::add('GET',  '/api/investimentos/{id}/proventos', 'Api\\Financeiro\\InvestimentosController@proventos',     ['auth']);
Router::add('POST', '/api/investimentos/{id}/proventos', 'Api\\Financeiro\\InvestimentosController@criarProvento', ['auth', 'csrf', 'ratelimit']);


// ============================================
// NOTIFICAÇÕES
// ============================================

Router::add('GET',  '/api/notificacoes',            'Api\\Notification\\NotificacaoController@index',            ['auth']);
Router::add('GET',  '/api/notificacoes/unread',     'Api\\Notification\\NotificacaoController@unreadCount',      ['auth']);
Router::add('POST', '/api/notificacoes/marcar',     'Api\\Notification\\NotificacaoController@marcarLida',       ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/notificacoes/marcar-todas', 'Api\\Notification\\NotificacaoController@marcarTodasLidas', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',  '/api/notificacoes/referral-rewards',      'Api\\Notification\\NotificacaoController@getReferralRewards',     ['auth']);
Router::add('POST', '/api/notificacoes/referral-rewards/seen', 'Api\\Notification\\NotificacaoController@markReferralRewardsSeen', ['auth', 'csrf', 'ratelimit']);

// ============================================
// PREFERÊNCIAS DE USUÁRIO
// ============================================

Router::add('GET',  '/api/user/theme', 'Api\\User\\PreferenciaUsuarioController@show',   ['auth']);
Router::add('POST', '/api/user/theme', 'Api\\User\\PreferenciaUsuarioController@update', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',  '/api/user/birthday-check', 'Api\\User\\PreferenciaUsuarioController@birthdayCheck', ['auth']);

// ============================================
// PREMIUM / ASSINATURA
// ============================================

Router::add('POST', '/premium/checkout', 'PremiumController@checkout', ['auth', 'csrf', 'ratelimit_strict']);
Router::add('POST', '/premium/cancel',   'PremiumController@cancel',   ['auth', 'csrf', 'ratelimit_strict']);
Router::add('GET',  '/premium/check-payment/{paymentId}', 'PremiumController@checkPayment', ['auth']);
Router::add('GET',  '/premium/pending-payment', 'PremiumController@getPendingPayment', ['auth']);
Router::add('GET',  '/premium/pending-pix', 'PremiumController@getPendingPix', ['auth']);
Router::add('POST', '/premium/cancel-pending', 'PremiumController@cancelPendingPayment', ['auth', 'csrf', 'ratelimit_strict']);

// ============================================
// CUPONS DE DESCONTO (CRUD: SysAdmin | Validar: Usuários)
// ============================================

Router::add('GET',    '/api/cupons',              'SysAdmin\\CupomController@index',        ['auth', 'sysadmin']);
Router::add('POST',   '/api/cupons',              'SysAdmin\\CupomController@store',        ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/cupons',              'SysAdmin\\CupomController@update',       ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/cupons',              'SysAdmin\\CupomController@destroy',      ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/cupons/validar',      'SysAdmin\\CupomController@validar',      ['auth']);  // Usuários validam no checkout
Router::add('GET',    '/api/cupons/estatisticas', 'SysAdmin\\CupomController@estatisticas', ['auth', 'sysadmin']);

// ============================================
// FATURAS DE CARTÃO (REST)
// ============================================

Router::add('GET',    '/api/faturas',              'Api\\Fatura\\FaturasController@index',       ['auth']);
Router::add('POST',   '/api/faturas',              'Api\\Fatura\\FaturasController@store',       ['auth', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/faturas/{id}',         'Api\\Fatura\\FaturasController@show',        ['auth']);
Router::add('DELETE', '/api/faturas/{id}',         'Api\\Fatura\\FaturasController@destroy',     ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/faturas/{id}/itens/{itemId}', 'Api\\Fatura\\FaturasController@updateItem', ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/faturas/{id}/itens/{itemId}/toggle', 'Api\\Fatura\\FaturasController@toggleItemPago', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/faturas/{id}/itens/{itemId}', 'Api\\Fatura\\FaturasController@destroyItem', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/faturas/{id}/itens/{itemId}/parcelamento', 'Api\\Fatura\\FaturasController@deleteParcelamento', ['auth', 'csrf', 'ratelimit']);

// Parcelamentos sem cartão (parcelas via conta bancária)
Router::add('GET',    '/api/parcelamentos',              'Api\\Financeiro\\ParcelamentosController@index',   ['auth']);
Router::add('POST',   '/api/parcelamentos',              'Api\\Financeiro\\ParcelamentosController@store',   ['auth', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/parcelamentos/{id}',         'Api\\Financeiro\\ParcelamentosController@show',    ['auth']);
Router::add('DELETE', '/api/parcelamentos/{id}',         'Api\\Financeiro\\ParcelamentosController@destroy', ['auth', 'csrf', 'ratelimit']);

// ============================================
// SYSADMIN - Acesso restrito a administradores
// ============================================

Router::add('GET', '/api/sysadmin/users', 'Api\\Admin\\SysAdminController@listUsers', ['auth', 'sysadmin']);
Router::add('GET', '/api/sysadmin/users/{id}', 'Api\\Admin\\SysAdminController@getUser', ['auth', 'sysadmin']);
Router::add('PUT', '/api/sysadmin/users/{id}', 'Api\\Admin\\SysAdminController@updateUser', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/sysadmin/users/{id}', 'Api\\Admin\\SysAdminController@deleteUser', ['auth', 'sysadmin', 'csrf', 'ratelimit_strict']);
Router::add('POST', '/api/sysadmin/grant-access', 'Api\\Admin\\SysAdminController@grantAccess', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('POST', '/api/sysadmin/revoke-access', 'Api\\Admin\\SysAdminController@revokeAccess', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('GET', '/api/sysadmin/stats', 'Api\\Admin\\SysAdminController@getStats', ['auth', 'sysadmin']);
Router::add('POST', '/api/sysadmin/maintenance', 'Api\\Admin\\SysAdminController@toggleMaintenance', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('GET', '/api/sysadmin/maintenance', 'Api\\Admin\\SysAdminController@maintenanceStatus', ['auth', 'sysadmin']);

// Error Logs (SysAdmin)
Router::add('GET',    '/api/sysadmin/error-logs',               'Api\\Admin\\SysAdminController@errorLogs',        ['auth', 'sysadmin']);
Router::add('GET',    '/api/sysadmin/error-logs/summary',       'Api\\Admin\\SysAdminController@errorLogsSummary', ['auth', 'sysadmin']);
Router::add('PUT',    '/api/sysadmin/error-logs/{id}/resolve',  'Api\\Admin\\SysAdminController@resolveErrorLog',  ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/sysadmin/error-logs/cleanup',       'Api\\Admin\\SysAdminController@cleanupErrorLogs', ['auth', 'sysadmin', 'csrf', 'ratelimit']);

// Cache Management (SysAdmin)
Router::add('POST',   '/api/sysadmin/clear-cache',              'Api\\Admin\\SysAdminController@clearCache',       ['auth', 'sysadmin', 'csrf', 'ratelimit']);

// Feedback (SysAdmin)
Router::add('GET', '/api/sysadmin/feedback',        'Api\\Admin\\FeedbackAdminController@index',  ['auth', 'sysadmin']);
Router::add('GET', '/api/sysadmin/feedback/stats',  'Api\\Admin\\FeedbackAdminController@stats',  ['auth', 'sysadmin']);
Router::add('GET', '/api/sysadmin/feedback/export', 'Api\\Admin\\FeedbackAdminController@export', ['auth', 'sysadmin']);

// ============================================
// BLOG / APRENDA (SYSADMIN)
// ============================================

Router::add('GET',    '/api/sysadmin/blog/posts',       'SysAdmin\\BlogController@index',      ['auth', 'sysadmin']);
Router::add('POST',   '/api/sysadmin/blog/posts',       'SysAdmin\\BlogController@store',      ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/sysadmin/blog/posts/{id}',  'SysAdmin\\BlogController@show',       ['auth', 'sysadmin']);
Router::add('PUT',    '/api/sysadmin/blog/posts/{id}',  'SysAdmin\\BlogController@update',     ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/sysadmin/blog/posts/{id}',  'SysAdmin\\BlogController@delete',     ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/sysadmin/blog/upload',      'SysAdmin\\BlogController@upload',     ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/sysadmin/blog/categorias',  'SysAdmin\\BlogController@categorias', ['auth', 'sysadmin']);

// ============================================
// IA (USUÁRIO)
// ============================================

Router::add('POST', '/api/ai/chat',                'Api\\AI\\UserAiController@chat',               ['auth', 'csrf', 'ai.ratelimit', 'ai.quota']);
Router::add('POST', '/api/ai/suggest-category',    'Api\\AI\\UserAiController@suggestCategory',    ['auth', 'csrf', 'ai.ratelimit', 'ai.quota']);
Router::add('POST', '/api/ai/analyze',             'Api\\AI\\UserAiController@analyze',            ['auth', 'csrf', 'ai.ratelimit', 'ai.quota']);
Router::add('POST', '/api/ai/extract-transaction', 'Api\\AI\\UserAiController@extractTransaction', ['auth', 'csrf', 'ai.ratelimit', 'ai.quota']);

// IA — Quota e Conversas
Router::add('GET',    '/api/ai/quota',                        'Api\\AI\\UserAiController@getQuota',            ['auth']);
Router::add('GET',    '/api/ai/conversations',                'Api\\AI\\UserAiController@listConversations',   ['auth']);
Router::add('POST',   '/api/ai/conversations',                'Api\\AI\\UserAiController@createConversation',  ['auth', 'csrf', 'ai.ratelimit']);
Router::add('GET',    '/api/ai/conversations/{id}/messages',  'Api\\AI\\UserAiController@getMessages',         ['auth']);
Router::add('POST',   '/api/ai/conversations/{id}/messages',  'Api\\AI\\UserAiController@sendMessage',         ['auth', 'csrf', 'ai.ratelimit', 'ai.quota']);
Router::add('DELETE', '/api/ai/conversations/{id}',           'Api\\AI\\UserAiController@deleteConversation',  ['auth', 'csrf', 'ai.ratelimit']);

// IA — Ações Pendentes (confirmar/rejeitar criação de entidades)
Router::add('POST',   '/api/ai/actions/{id}/confirm',         'Api\\AI\\UserAiController@confirmAction',       ['auth', 'csrf', 'ai.ratelimit']);
Router::add('POST',   '/api/ai/actions/{id}/reject',          'Api\\AI\\UserAiController@rejectAction',        ['auth', 'csrf', 'ai.ratelimit']);

// WhatsApp — Vínculo de telefone
Router::add('POST', '/api/whatsapp/link',   'Api\\AI\\WhatsAppLinkController@requestLink',  ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/whatsapp/verify', 'Api\\AI\\WhatsAppLinkController@verify',       ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/whatsapp/unlink', 'Api\\AI\\WhatsAppLinkController@unlink',       ['auth', 'csrf', 'ratelimit']);
Router::add('GET',  '/api/whatsapp/status', 'Api\\AI\\WhatsAppLinkController@status',       ['auth']);

// Telegram — Vínculo de conta
Router::add('POST', '/api/telegram/link',   'Api\\AI\\TelegramLinkController@requestLink',  ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/telegram/unlink', 'Api\\AI\\TelegramLinkController@unlink',       ['auth', 'csrf', 'ratelimit']);
Router::add('GET',  '/api/telegram/status', 'Api\\AI\\TelegramLinkController@status',       ['auth']);

// ============================================// IA (SYSADMIN)
// ============================================

Router::add('GET',  '/api/sysadmin/ai/health-proxy',    'SysAdmin\\AiApiController@healthProxy',     ['auth', 'sysadmin']);
Router::add('GET',  '/api/sysadmin/ai/quota',            'SysAdmin\\AiApiController@quota',           ['auth', 'sysadmin']);
Router::add('POST', '/api/sysadmin/ai/chat',             'SysAdmin\\AiApiController@chat',            ['auth', 'sysadmin', 'csrf', 'ai.ratelimit']);
Router::add('POST', '/api/sysadmin/ai/suggest-category', 'SysAdmin\\AiApiController@suggestCategory',  ['auth', 'sysadmin', 'ai.ratelimit']);
Router::add('POST', '/api/sysadmin/ai/analyze-spending', 'SysAdmin\\AiApiController@analyzeSpending',  ['auth', 'sysadmin', 'csrf', 'ai.ratelimit']);

// AI Logs (SysAdmin)
Router::add('GET',    '/api/sysadmin/ai/logs',          'SysAdmin\\AiLogsApiController@index',   ['auth', 'sysadmin']);
Router::add('GET',    '/api/sysadmin/ai/logs/summary',  'SysAdmin\\AiLogsApiController@summary', ['auth', 'sysadmin']);
Router::add('GET',    '/api/sysadmin/ai/logs/quality',  'SysAdmin\\AiLogsApiController@quality', ['auth', 'sysadmin']);
Router::add('DELETE', '/api/sysadmin/ai/logs/cleanup',  'SysAdmin\\AiLogsApiController@cleanup', ['auth', 'sysadmin', 'csrf', 'ratelimit']);

// ============================================// CAMPANHAS DE MENSAGENS (SYSADMIN)
// ============================================

Router::add('GET',  '/api/campaigns',                'Api\\Notification\\CampaignController@index',        ['auth', 'sysadmin']);
Router::add('POST', '/api/campaigns',                'Api\\Notification\\CampaignController@store',        ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('GET',  '/api/campaigns/preview',        'Api\\Notification\\CampaignController@preview',      ['auth', 'sysadmin']);
Router::add('GET',  '/api/campaigns/stats',          'Api\\Notification\\CampaignController@stats',        ['auth', 'sysadmin']);
Router::add('GET',  '/api/campaigns/options',        'Api\\Notification\\CampaignController@options',      ['auth', 'sysadmin']);
Router::add('GET',  '/api/campaigns/birthdays',      'Api\\Notification\\CampaignController@birthdays',    ['auth', 'sysadmin']);
Router::add('POST', '/api/campaigns/birthdays/send', 'Api\\Notification\\CampaignController@sendBirthdays', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('GET',  '/api/campaigns/{id}',           'Api\\Notification\\CampaignController@show',         ['auth', 'sysadmin']);
Router::add('POST', '/api/campaigns/{id}/cancel',    'Api\\Notification\\CampaignController@cancelScheduled', ['auth', 'sysadmin', 'csrf', 'ratelimit']);

// ============================================
// NOTIFICAÇÕES (USUÁRIO)
// ============================================

Router::add('GET',    '/api/notifications',            'Api\\Notification\\NotificationController@index',         ['auth']);
Router::add('GET',    '/api/notifications/count',      'Api\\Notification\\NotificationController@count',         ['auth']);
Router::add('POST',   '/api/notifications/{id}/read',  'Api\\Notification\\NotificationController@markAsRead',    ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/notifications/read-all',   'Api\\Notification\\NotificationController@markAllAsRead', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/notifications/{id}',       'Api\\Notification\\NotificationController@destroy',       ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/notifications/read',       'Api\\Notification\\NotificationController@deleteRead',    ['auth', 'csrf', 'ratelimit']);

// ============================================
// PLANO / LIMITES
// ============================================

Router::add('GET', '/api/plan/limits',              'Api\\Plan\\PlanController@limits',             ['auth']);
Router::add('GET', '/api/plan/features',            'Api\\Plan\\PlanController@features',           ['auth']);
Router::add('GET', '/api/plan/can-create/{resource}', 'Api\\Plan\\PlanController@canCreate',        ['auth']);
Router::add('GET', '/api/plan/history-restriction', 'Api\\Plan\\PlanController@historyRestriction', ['auth']);

// ============================================
// INDICAÇÕES / REFERRAL
// ============================================

Router::add('GET', '/api/referral/info',     'Api\\Referral\\ReferralController@getInfo');              // Público
Router::add('GET', '/api/referral/validate', 'Api\\Referral\\ReferralController@validateCode');         // Público (para cadastro)
Router::add('GET', '/api/referral/stats',    'Api\\Referral\\ReferralController@getStats',    ['auth']);
Router::add('GET', '/api/referral/code',     'Api\\Referral\\ReferralController@getCode',     ['auth']);
Router::add('GET', '/api/referral/ranking',  'Api\\Referral\\ReferralController@getRanking',  ['auth']);

// ============================================
// FEEDBACK (USUÁRIO)
// ============================================

Router::add('POST', '/api/feedback',           'Api\\Feedback\\FeedbackController@store',    ['auth', 'csrf', 'ratelimit']);
Router::add('GET',  '/api/feedback/check-nps', 'Api\\Feedback\\FeedbackController@checkNps', ['auth']);
Router::add('GET',  '/api/feedback/can-micro', 'Api\\Feedback\\FeedbackController@canMicro', ['auth']);
