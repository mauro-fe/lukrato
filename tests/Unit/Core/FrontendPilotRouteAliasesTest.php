<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Application\Core\Router;
use PHPUnit\Framework\TestCase;

class FrontendPilotRouteAliasesTest extends TestCase
{
    /** @var array<int, array{method:string,path:string,callback:mixed,middlewares:array<int,string>}> */
    private array $routes = [];

    protected function setUp(): void
    {
        parent::setUp();

        Router::reset();
        require BASE_PATH . '/routes/api.php';
        $this->routes = $this->registeredRoutes();
    }

    protected function tearDown(): void
    {
        Router::reset();
        parent::tearDown();
    }

    public function testVersionedPilotAliasesMirrorLegacyRouteMappings(): void
    {
        $pairs = [
            ['POST', '/api/csrf/refresh', '/api/v1/csrf/refresh'],
            ['GET', '/api/session/status', '/api/v1/session/status'],
            ['POST', '/api/session/renew', '/api/v1/session/renew'],
            ['POST', '/api/session/heartbeat', '/api/v1/session/heartbeat'],
            ['POST', '/api/contato/enviar', '/api/v1/contato/enviar'],
            ['POST', '/api/suporte/enviar', '/api/v1/suporte/enviar'],
            ['GET', '/api/perfil', '/api/v1/perfil'],
            ['POST', '/api/perfil', '/api/v1/perfil'],
            ['POST', '/api/perfil/senha', '/api/v1/perfil/senha'],
            ['POST', '/api/perfil/tema', '/api/v1/perfil/tema'],
            ['POST', '/api/perfil/avatar', '/api/v1/perfil/avatar'],
            ['POST', '/api/perfil/avatar/preferences', '/api/v1/perfil/avatar/preferences'],
            ['GET', '/api/perfil/dashboard-preferences', '/api/v1/perfil/dashboard-preferences'],
            ['POST', '/api/perfil/dashboard-preferences', '/api/v1/perfil/dashboard-preferences'],
            ['DELETE', '/api/perfil/avatar', '/api/v1/perfil/avatar'],
            ['DELETE', '/api/perfil/delete', '/api/v1/perfil/delete'],
            ['GET', '/api/user/theme', '/api/v1/user/theme'],
            ['POST', '/api/user/theme', '/api/v1/user/theme'],
            ['POST', '/api/user/display-name', '/api/v1/user/display-name'],
            ['GET', '/api/user/help-preferences', '/api/v1/user/help-preferences'],
            ['POST', '/api/user/help-preferences', '/api/v1/user/help-preferences'],
            ['GET', '/api/user/ui-preferences/{page}', '/api/v1/user/ui-preferences/{page}'],
            ['POST', '/api/user/ui-preferences/{page}', '/api/v1/user/ui-preferences/{page}'],
            ['GET', '/api/user/birthday-check', '/api/v1/user/birthday-check'],
            ['GET', '/api/notificacoes', '/api/v1/notificacoes'],
            ['GET', '/api/notificacoes/unread', '/api/v1/notificacoes/unread'],
            ['POST', '/api/notificacoes/marcar', '/api/v1/notificacoes/marcar'],
            ['POST', '/api/notificacoes/marcar-todas', '/api/v1/notificacoes/marcar-todas'],
            ['GET', '/api/notificacoes/referral-rewards', '/api/v1/notificacoes/referral-rewards'],
            ['POST', '/api/notificacoes/referral-rewards/seen', '/api/v1/notificacoes/referral-rewards/seen'],
            ['GET', '/api/contas', '/api/v1/contas'],
            ['GET', '/api/categorias', '/api/v1/categorias'],
            ['GET', '/api/categorias/{id}/subcategorias', '/api/v1/categorias/{id}/subcategorias'],
            ['GET', '/api/cartoes', '/api/v1/cartoes'],
            ['GET', '/api/financas/resumo', '/api/v1/financas/resumo'],
            ['GET', '/api/financas/metas', '/api/v1/financas/metas'],
            ['POST', '/api/financas/metas', '/api/v1/financas/metas'],
            ['PUT', '/api/financas/metas/{id}', '/api/v1/financas/metas/{id}'],
            ['POST', '/api/financas/metas/{id}/aporte', '/api/v1/financas/metas/{id}/aporte'],
            ['DELETE', '/api/financas/metas/{id}', '/api/v1/financas/metas/{id}'],
            ['GET', '/api/financas/metas/templates', '/api/v1/financas/metas/templates'],
            ['GET', '/api/financas/orcamentos', '/api/v1/financas/orcamentos'],
            ['POST', '/api/financas/orcamentos', '/api/v1/financas/orcamentos'],
            ['POST', '/api/financas/orcamentos/bulk', '/api/v1/financas/orcamentos/bulk'],
            ['DELETE', '/api/financas/orcamentos/{id}', '/api/v1/financas/orcamentos/{id}'],
            ['GET', '/api/financas/orcamentos/sugestoes', '/api/v1/financas/orcamentos/sugestoes'],
            ['POST', '/api/financas/orcamentos/aplicar-sugestoes', '/api/v1/financas/orcamentos/aplicar-sugestoes'],
            ['POST', '/api/financas/orcamentos/copiar-mes', '/api/v1/financas/orcamentos/copiar-mes'],
            ['GET', '/api/financas/insights', '/api/v1/financas/insights'],
            ['GET', '/api/faturas', '/api/v1/faturas'],
            ['POST', '/api/faturas', '/api/v1/faturas'],
            ['GET', '/api/faturas/{id}', '/api/v1/faturas/{id}'],
            ['DELETE', '/api/faturas/{id}', '/api/v1/faturas/{id}'],
            ['PUT', '/api/faturas/{id}/itens/{itemId}', '/api/v1/faturas/{id}/itens/{itemId}'],
            ['POST', '/api/faturas/{id}/itens/{itemId}/toggle', '/api/v1/faturas/{id}/itens/{itemId}/toggle'],
            ['DELETE', '/api/faturas/{id}/itens/{itemId}', '/api/v1/faturas/{id}/itens/{itemId}'],
            ['DELETE', '/api/faturas/{id}/itens/{itemId}/parcelamento', '/api/v1/faturas/{id}/itens/{itemId}/parcelamento'],
            ['POST', '/api/cartoes/{id}/fatura/pagar', '/api/v1/cartoes/{id}/fatura/pagar'],
            ['POST', '/api/cartoes/{id}/fatura/desfazer-pagamento', '/api/v1/cartoes/{id}/fatura/desfazer-pagamento'],
            ['POST', '/api/cartoes/parcelas/{id}/desfazer-pagamento', '/api/v1/cartoes/parcelas/{id}/desfazer-pagamento'],
            ['GET', '/api/lancamentos', '/api/v1/lancamentos'],
            ['POST', '/api/lancamentos', '/api/v1/lancamentos'],
            ['POST', '/api/lancamentos/delete', '/api/v1/lancamentos/delete'],
            ['PUT', '/api/lancamentos/{id}', '/api/v1/lancamentos/{id}'],
            ['DELETE', '/api/lancamentos/{id}', '/api/v1/lancamentos/{id}'],
            ['GET', '/api/lancamentos/usage', '/api/v1/lancamentos/usage'],
            ['GET', '/api/lancamentos/export', '/api/v1/lancamentos/export'],
            ['POST', '/api/lancamentos/{id}/cancelar-recorrencia', '/api/v1/lancamentos/{id}/cancelar-recorrencia'],
            ['PUT', '/api/lancamentos/{id}/pagar', '/api/v1/lancamentos/{id}/pagar'],
            ['PUT', '/api/lancamentos/{id}/despagar', '/api/v1/lancamentos/{id}/despagar'],
            ['GET', '/api/lancamentos/{id}/fatura-detalhes', '/api/v1/lancamentos/{id}/fatura-detalhes'],
            ['GET', '/api/contas/{id}/lancamentos', '/api/v1/contas/{id}/lancamentos'],
            ['GET', '/api/importacoes/page-init', '/api/v1/importacoes/page-init'],
            ['GET', '/api/importacoes/configuracoes/page-init', '/api/v1/importacoes/configuracoes/page-init'],
            ['GET', '/api/importacoes/configuracoes', '/api/v1/importacoes/configuracoes'],
            ['POST', '/api/importacoes/configuracoes', '/api/v1/importacoes/configuracoes'],
            ['GET', '/api/importacoes/modelos/csv', '/api/v1/importacoes/modelos/csv'],
            ['GET', '/api/importacoes/historico/page-init', '/api/v1/importacoes/historico/page-init'],
            ['GET', '/api/importacoes/historico', '/api/v1/importacoes/historico'],
            ['DELETE', '/api/importacoes/historico/{id}', '/api/v1/importacoes/historico/{id}'],
            ['GET', '/api/importacoes/jobs/{id}', '/api/v1/importacoes/jobs/{id}'],
            ['POST', '/api/importacoes/preview', '/api/v1/importacoes/preview'],
            ['POST', '/api/importacoes/confirm', '/api/v1/importacoes/confirm'],
            ['GET', '/api/parcelamentos', '/api/v1/parcelamentos'],
            ['POST', '/api/parcelamentos', '/api/v1/parcelamentos'],
            ['GET', '/api/parcelamentos/{id}', '/api/v1/parcelamentos/{id}'],
            ['DELETE', '/api/parcelamentos/{id}', '/api/v1/parcelamentos/{id}'],
            ['POST', '/api/transactions', '/api/v1/transactions'],
            ['PUT', '/api/transactions/{id}', '/api/v1/transactions/{id}'],
            ['POST', '/api/transactions/{id}/update', '/api/v1/transactions/{id}/update'],
            ['POST', '/api/transfers', '/api/v1/transfers'],
            ['GET', '/api/reports', '/api/v1/reports'],
            ['GET', '/api/reports/summary', '/api/v1/reports/summary'],
            ['GET', '/api/reports/insights', '/api/v1/reports/insights'],
            ['GET', '/api/reports/insights-teaser', '/api/v1/reports/insights-teaser'],
            ['GET', '/api/reports/comparatives', '/api/v1/reports/comparatives'],
            ['GET', '/api/reports/card-details/{id}', '/api/v1/reports/card-details/{id}'],
            ['GET', '/api/reports/export', '/api/v1/reports/export'],
            ['GET', '/api/gamification/progress', '/api/v1/gamification/progress'],
            ['GET', '/api/gamification/achievements', '/api/v1/gamification/achievements'],
            ['GET', '/api/gamification/achievements/pending', '/api/v1/gamification/achievements/pending'],
            ['GET', '/api/gamification/stats', '/api/v1/gamification/stats'],
            ['GET', '/api/gamification/history', '/api/v1/gamification/history'],
            ['POST', '/api/gamification/achievements/mark-seen', '/api/v1/gamification/achievements/mark-seen'],
            ['GET', '/api/gamification/leaderboard', '/api/v1/gamification/leaderboard'],
            ['GET', '/api/gamification/missions', '/api/v1/gamification/missions'],
            ['GET', '/api/dashboard/evolucao', '/api/v1/dashboard/evolucao'],
            ['GET', '/api/plan/limits', '/api/v1/plan/limits'],
            ['GET', '/api/plan/features', '/api/v1/plan/features'],
            ['GET', '/api/plan/can-create/{resource}', '/api/v1/plan/can-create/{resource}'],
            ['GET', '/api/plan/history-restriction', '/api/v1/plan/history-restriction'],
            ['GET', '/api/referral/info', '/api/v1/referral/info'],
            ['GET', '/api/referral/validate', '/api/v1/referral/validate'],
            ['GET', '/api/referral/stats', '/api/v1/referral/stats'],
            ['GET', '/api/referral/code', '/api/v1/referral/code'],
            ['GET', '/api/referral/ranking', '/api/v1/referral/ranking'],
            ['POST', '/api/feedback', '/api/v1/feedback'],
            ['GET', '/api/feedback/check-nps', '/api/v1/feedback/check-nps'],
            ['GET', '/api/feedback/can-micro', '/api/v1/feedback/can-micro'],
            ['GET', '/api/cupons/validar', '/api/v1/cupons/validar'],
        ];

        $this->assertAliasPairs($pairs);
    }

    public function testVersionedSysadminAliasesMirrorLegacyRouteMappings(): void
    {
        $pairs = [
            ['GET', '/api/sysadmin/users', '/api/v1/sysadmin/users'],
            ['GET', '/api/sysadmin/users/{id}', '/api/v1/sysadmin/users/{id}'],
            ['PUT', '/api/sysadmin/users/{id}', '/api/v1/sysadmin/users/{id}'],
            ['DELETE', '/api/sysadmin/users/{id}', '/api/v1/sysadmin/users/{id}'],
            ['POST', '/api/sysadmin/grant-access', '/api/v1/sysadmin/grant-access'],
            ['POST', '/api/sysadmin/revoke-access', '/api/v1/sysadmin/revoke-access'],
            ['GET', '/api/sysadmin/stats', '/api/v1/sysadmin/stats'],
            ['POST', '/api/sysadmin/maintenance', '/api/v1/sysadmin/maintenance'],
            ['GET', '/api/sysadmin/maintenance', '/api/v1/sysadmin/maintenance'],
            ['GET', '/api/sysadmin/error-logs', '/api/v1/sysadmin/error-logs'],
            ['GET', '/api/sysadmin/error-logs/summary', '/api/v1/sysadmin/error-logs/summary'],
            ['PUT', '/api/sysadmin/error-logs/{id}/resolve', '/api/v1/sysadmin/error-logs/{id}/resolve'],
            ['DELETE', '/api/sysadmin/error-logs/cleanup', '/api/v1/sysadmin/error-logs/cleanup'],
            ['POST', '/api/sysadmin/clear-cache', '/api/v1/sysadmin/clear-cache'],
            ['GET', '/api/sysadmin/feedback', '/api/v1/sysadmin/feedback'],
            ['GET', '/api/sysadmin/feedback/stats', '/api/v1/sysadmin/feedback/stats'],
            ['GET', '/api/sysadmin/feedback/export', '/api/v1/sysadmin/feedback/export'],
            ['GET', '/api/sysadmin/blog/posts', '/api/v1/sysadmin/blog/posts'],
            ['POST', '/api/sysadmin/blog/posts', '/api/v1/sysadmin/blog/posts'],
            ['GET', '/api/sysadmin/blog/posts/{id}', '/api/v1/sysadmin/blog/posts/{id}'],
            ['PUT', '/api/sysadmin/blog/posts/{id}', '/api/v1/sysadmin/blog/posts/{id}'],
            ['DELETE', '/api/sysadmin/blog/posts/{id}', '/api/v1/sysadmin/blog/posts/{id}'],
            ['POST', '/api/sysadmin/blog/upload', '/api/v1/sysadmin/blog/upload'],
            ['GET', '/api/sysadmin/blog/categorias', '/api/v1/sysadmin/blog/categorias'],
            ['GET', '/api/sysadmin/ai/health-proxy', '/api/v1/sysadmin/ai/health-proxy'],
            ['GET', '/api/sysadmin/ai/quota', '/api/v1/sysadmin/ai/quota'],
            ['POST', '/api/sysadmin/ai/chat', '/api/v1/sysadmin/ai/chat'],
            ['POST', '/api/sysadmin/ai/suggest-category', '/api/v1/sysadmin/ai/suggest-category'],
            ['POST', '/api/sysadmin/ai/analyze-spending', '/api/v1/sysadmin/ai/analyze-spending'],
            ['GET', '/api/sysadmin/ai/logs', '/api/v1/sysadmin/ai/logs'],
            ['GET', '/api/sysadmin/ai/logs/summary', '/api/v1/sysadmin/ai/logs/summary'],
            ['GET', '/api/sysadmin/ai/logs/quality', '/api/v1/sysadmin/ai/logs/quality'],
            ['DELETE', '/api/sysadmin/ai/logs/cleanup', '/api/v1/sysadmin/ai/logs/cleanup'],
            ['GET', '/api/campaigns', '/api/v1/campaigns'],
            ['POST', '/api/campaigns', '/api/v1/campaigns'],
            ['GET', '/api/campaigns/preview', '/api/v1/campaigns/preview'],
            ['GET', '/api/campaigns/stats', '/api/v1/campaigns/stats'],
            ['GET', '/api/campaigns/options', '/api/v1/campaigns/options'],
            ['GET', '/api/campaigns/birthdays', '/api/v1/campaigns/birthdays'],
            ['POST', '/api/campaigns/birthdays/send', '/api/v1/campaigns/birthdays/send'],
            ['POST', '/api/campaigns/process-due', '/api/v1/campaigns/process-due'],
            ['GET', '/api/campaigns/{id}', '/api/v1/campaigns/{id}'],
            ['POST', '/api/campaigns/{id}/cancel', '/api/v1/campaigns/{id}/cancel'],
            ['GET', '/api/cupons', '/api/v1/cupons'],
            ['POST', '/api/cupons', '/api/v1/cupons'],
            ['PUT', '/api/cupons', '/api/v1/cupons'],
            ['DELETE', '/api/cupons', '/api/v1/cupons'],
            ['GET', '/api/cupons/estatisticas', '/api/v1/cupons/estatisticas'],
        ];

        $this->assertAliasPairs($pairs);
    }

    public function testVersionedFinanceManagementAndUserAiAliasesMirrorLegacyRouteMappings(): void
    {
        $pairs = [
            ['GET', '/api/dashboard/overview', '/api/v1/dashboard/overview'],
            ['GET', '/api/instituicoes', '/api/v1/instituicoes'],
            ['POST', '/api/instituicoes', '/api/v1/instituicoes'],
            ['GET', '/api/contas/instituicoes', '/api/v1/contas/instituicoes'],
            ['POST', '/api/contas', '/api/v1/contas'],
            ['PUT', '/api/contas/{id}', '/api/v1/contas/{id}'],
            ['POST', '/api/contas/{id}/archive', '/api/v1/contas/{id}/archive'],
            ['POST', '/api/contas/{id}/restore', '/api/v1/contas/{id}/restore'],
            ['POST', '/api/contas/{id}/delete', '/api/v1/contas/{id}/delete'],
            ['DELETE', '/api/contas/{id}', '/api/v1/contas/{id}'],
            ['POST', '/api/categorias', '/api/v1/categorias'],
            ['PUT', '/api/categorias/reorder', '/api/v1/categorias/reorder'],
            ['PUT', '/api/categorias/{id}', '/api/v1/categorias/{id}'],
            ['DELETE', '/api/categorias/{id}', '/api/v1/categorias/{id}'],
            ['POST', '/api/categorias/{id}/subcategorias', '/api/v1/categorias/{id}/subcategorias'],
            ['GET', '/api/subcategorias/grouped', '/api/v1/subcategorias/grouped'],
            ['PUT', '/api/subcategorias/{id}', '/api/v1/subcategorias/{id}'],
            ['DELETE', '/api/subcategorias/{id}', '/api/v1/subcategorias/{id}'],
            ['GET', '/api/cartoes/resumo', '/api/v1/cartoes/resumo'],
            ['GET', '/api/cartoes/alertas', '/api/v1/cartoes/alertas'],
            ['GET', '/api/cartoes/{id}', '/api/v1/cartoes/{id}'],
            ['POST', '/api/cartoes', '/api/v1/cartoes'],
            ['PUT', '/api/cartoes/{id}', '/api/v1/cartoes/{id}'],
            ['POST', '/api/cartoes/{id}/archive', '/api/v1/cartoes/{id}/archive'],
            ['POST', '/api/cartoes/{id}/restore', '/api/v1/cartoes/{id}/restore'],
            ['POST', '/api/cartoes/{id}/delete', '/api/v1/cartoes/{id}/delete'],
            ['GET', '/api/cartoes/{id}/fatura', '/api/v1/cartoes/{id}/fatura'],
            ['GET', '/api/cartoes/{id}/fatura/status', '/api/v1/cartoes/{id}/fatura/status'],
            ['POST', '/api/cartoes/{id}/parcelas/pagar', '/api/v1/cartoes/{id}/parcelas/pagar'],
            ['GET', '/api/cartoes/{id}/faturas-pendentes', '/api/v1/cartoes/{id}/faturas-pendentes'],
            ['GET', '/api/cartoes/{id}/faturas-historico', '/api/v1/cartoes/{id}/faturas-historico'],
            ['GET', '/api/cartoes/{id}/parcelamentos-resumo', '/api/v1/cartoes/{id}/parcelamentos-resumo'],
            ['POST', '/api/ai/suggest-category', '/api/v1/ai/suggest-category'],
            ['GET', '/api/ai/quota', '/api/v1/ai/quota'],
            ['GET', '/api/ai/conversations', '/api/v1/ai/conversations'],
            ['POST', '/api/ai/conversations', '/api/v1/ai/conversations'],
            ['GET', '/api/ai/conversations/{id}/messages', '/api/v1/ai/conversations/{id}/messages'],
            ['POST', '/api/ai/conversations/{id}/messages', '/api/v1/ai/conversations/{id}/messages'],
            ['POST', '/api/ai/actions/{id}/confirm', '/api/v1/ai/actions/{id}/confirm'],
            ['POST', '/api/ai/actions/{id}/reject', '/api/v1/ai/actions/{id}/reject'],
        ];

        $this->assertAliasPairs($pairs);
    }

    public function testVersionedGoogleAuthRoutesAreRegistered(): void
    {
        $routes = [
            ['GET', '/api/v1/auth/google/login', 'Auth\\GoogleLoginController@login'],
            ['GET', '/api/v1/auth/google/register', 'Auth\\GoogleLoginController@login'],
            ['GET', '/api/v1/auth/google/callback', 'Auth\\GoogleCallbackController@callback'],
            ['GET', '/api/v1/auth/google/pending', 'Auth\\GoogleCallbackController@pending'],
            ['GET', '/api/v1/auth/google/confirm-page', 'Auth\\GoogleCallbackController@confirmPage'],
            ['GET', '/api/v1/auth/google/confirm', 'Auth\\GoogleCallbackController@confirm'],
            ['GET', '/api/v1/auth/google/cancel', 'Auth\\GoogleCallbackController@cancel'],
        ];

        foreach ($routes as [$method, $path, $callback]) {
            $this->assertRouteDefinition($method, $path, $callback);
        }
    }

    public function testVersionedAuthRecoveryRoutesAreRegistered(): void
    {
        $routes = [
            ['GET', '/api/v1/auth/email/verify', 'Auth\\EmailVerificationController@verify', []],
            ['GET', '/api/v1/auth/email/notice', 'Auth\\EmailVerificationController@noticeData', []],
            ['POST', '/api/v1/auth/email/resend', 'Auth\\EmailVerificationController@resend', ['ratelimit']],
            ['POST', '/api/v1/auth/password/forgot', 'Auth\\ForgotPasswordController@sendResetLink', ['ratelimit']],
            ['GET', '/api/v1/auth/password/reset/validate', 'Auth\\ForgotPasswordController@validateResetLink', []],
            ['POST', '/api/v1/auth/password/reset', 'Auth\\ForgotPasswordController@resetPassword', ['ratelimit']],
        ];

        foreach ($routes as [$method, $path, $callback, $middlewares]) {
            $this->assertRouteDefinition($method, $path, $callback, $middlewares);
        }
    }

    public function testEveryLegacyApiRouteHasAVersionedAlias(): void
    {
        foreach ($this->routes as $legacyRoute) {
            if (!str_starts_with($legacyRoute['path'], '/api/') || str_starts_with($legacyRoute['path'], '/api/v1/')) {
                continue;
            }

            $versionedRoute = $this->findRoute(
                $legacyRoute['method'],
                preg_replace('#^/api/#', '/api/v1/', $legacyRoute['path']) ?? $legacyRoute['path']
            );

            $this->assertNotNull(
                $versionedRoute,
                sprintf('Alias v1 ausente para %s %s', $legacyRoute['method'], $legacyRoute['path'])
            );
            $this->assertSame($legacyRoute['callback'], $versionedRoute['callback']);
            $this->assertSame($legacyRoute['middlewares'], $versionedRoute['middlewares']);
        }
    }

    /**
     * @param array<int, array{0:string,1:string,2:string}> $pairs
     */
    private function assertAliasPairs(array $pairs): void
    {

        foreach ($pairs as [$method, $legacyPath, $versionedPath]) {
            $legacyRoute = $this->findRoute($method, $legacyPath);
            $versionedRoute = $this->findRoute($method, $versionedPath);

            $this->assertNotNull($legacyRoute, sprintf('Rota legada ausente: %s %s', $method, $legacyPath));
            $this->assertNotNull($versionedRoute, sprintf('Alias v1 ausente: %s %s', $method, $versionedPath));
            $this->assertSame($legacyRoute['callback'], $versionedRoute['callback']);
            $this->assertSame($legacyRoute['middlewares'], $versionedRoute['middlewares']);
        }
    }

    /**
     * @param array<int, string> $middlewares
     */
    private function assertRouteDefinition(
        string $method,
        string $path,
        mixed $callback,
        array $middlewares = []
    ): void {
        $route = $this->findRoute($method, $path);

        $this->assertNotNull($route, sprintf('Rota ausente: %s %s', $method, $path));
        $this->assertSame($callback, $route['callback']);
        $this->assertSame($middlewares, $route['middlewares']);
    }

    /**
     * @return array{method:string,path:string,callback:mixed,middlewares:array<int,string>}|null
     */
    private function findRoute(string $method, string $path): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $path) {
                return $route;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{method:string,path:string,callback:mixed,middlewares:array<int,string>}>
     */
    private function registeredRoutes(): array
    {
        $property = new \ReflectionProperty(Router::class, 'routes');
        $property->setAccessible(true);
        $routes = $property->getValue();

        return is_array($routes) ? $routes : [];
    }
}
