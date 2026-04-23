<?php

declare(strict_types=1);

namespace {
    if (!function_exists('vite_scripts')) {
        function vite_scripts(string ...$entries): string
        {
            return '';
        }
    }
}

namespace Tests\Unit\Controllers\Admin {

    use Application\Controllers\Admin\BillingController;
    use Application\Controllers\Admin\CartoesController;
    use Application\Controllers\Admin\CategoriaController;
    use Application\Controllers\Admin\ConfigController;
    use Application\Controllers\Admin\ContasController;
    use Application\Controllers\Admin\DashboardController;
    use Application\Controllers\Admin\FaturaController;
    use Application\Controllers\Admin\FinancasController;
    use Application\Controllers\Admin\ImportacoesConfiguracoesController;
    use Application\Controllers\Admin\ImportacoesController;
    use Application\Controllers\Admin\ImportacoesHistoricoController;
    use Application\Controllers\Admin\LancamentoController;
    use Application\Controllers\Admin\MetasController;
    use Application\Controllers\Admin\OrcamentoController;
    use Application\Controllers\Admin\PerfilController;
    use Application\Controllers\Admin\RelatoriosController;
    use Application\Controllers\SysAdmin\BlogViewController;
    use Application\Core\Exceptions\HttpResponseException;
    use Application\Lib\Auth;
    use Application\Models\Usuario;
    use PHPUnit\Framework\TestCase;
    use Tests\Support\SessionIsolation;

    class AdminPageControllersTest extends TestCase
    {
        use SessionIsolation;

        protected function setUp(): void
        {
            parent::setUp();
            $this->resetSessionState();
            Auth::resolveUserUsing(null);
            $_GET = [];
            $_POST = [];
            $_REQUEST = [];
            $_SERVER['REQUEST_METHOD'] = 'GET';
        }

        protected function tearDown(): void
        {
            $_GET = [];
            $_POST = [];
            $_REQUEST = [];
            unset($_SERVER['REQUEST_METHOD']);
            Auth::resolveUserUsing(null);
            $this->resetSessionState();
            parent::tearDown();
        }

        public function testBillingRedirectsToLoginWhenSessionIsMissing(): void
        {
            $controller = new BillingController();

            try {
                $controller->index();
                $this->fail('Expected HttpResponseException');
            } catch (HttpResponseException $e) {
                $response = $e->getResponse();

                $this->assertSame(302, $response->getStatusCode());
                $this->assertSame(BASE_URL . 'login', $response->getHeaders()['Location'] ?? null);
            }
        }

        public function testBillingIndexRendersBootstrapShellPage(): void
        {
            $this->seedAuthenticatedSession(3116, 'Billing User');

            $controller = new BillingController();
            $response = $controller->index();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('billing-page', $response->getContent());
            $this->assertStringContainsString('data-lk-menu="billing"', $response->getContent());
        }

        public function testCartoesPagesRender(): void
        {
            $this->seedAuthenticatedSession(3106, 'Cartoes User');

            $controller = new CartoesController();

            $index = $controller->index();
            $archived = $controller->archived();
            $indexContent = $index->getContent();
            $archivedContent = $archived->getContent();

            $this->assertSame(200, $index->getStatusCode());
            $this->assertStringContainsString('cartoes-page', $indexContent);
            $this->assertStringContainsString('data-lk-menu="cartoes"', $indexContent);
            $this->assertStringContainsString('data-cartoes-import-ofx-link', $indexContent);
            $this->assertStringContainsString('import_target=cartao', $indexContent);
            $this->assertStringNotContainsString('import_target=cartao&source_type=ofx', $indexContent);
            $this->assertSame(200, $archived->getStatusCode());
            $this->assertStringContainsString('Cartões Arquivados', $archivedContent);
            $this->assertStringContainsString('data-lk-menu="cartoes"', $archivedContent);
        }

        public function testCategoriaIndexRendersBootstrapShellPage(): void
        {
            $this->seedAuthenticatedSession(3107, 'Categoria User');

            $controller = new CategoriaController();
            $response = $controller->index();
            $content = $response->getContent();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('cat-page', $content);
            $this->assertStringContainsString('data-lk-menu="categorias"', $content);
        }

        public function testConfigIndexRendersConfiguracoesView(): void
        {
            $this->seedAuthenticatedSession(3108, 'Config User');

            $controller = new ConfigController();
            $response = $controller->index();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('Configurações', $response->getContent());
            $this->assertStringContainsString('data-account-root', $response->getContent());
            $this->assertStringContainsString('data-lk-menu="configuracoes"', $response->getContent());
        }

        public function testContasPagesRender(): void
        {
            $this->seedAuthenticatedSession(3109, 'Contas User');

            $controller = new ContasController();

            $index = $controller->index();
            $archived = $controller->archived();
            $indexContent = $index->getContent();
            $archivedContent = $archived->getContent();

            $this->assertSame(200, $index->getStatusCode());
            $this->assertStringContainsString('cont-page', $indexContent);
            $this->assertStringContainsString('data-lk-menu="contas"', $indexContent);
            $this->assertSame(200, $archived->getStatusCode());
            $this->assertStringContainsString('Contas Arquivadas', $archivedContent);
            $this->assertStringContainsString('data-lk-menu="contas"', $archivedContent);
        }

        public function testDashboardIndexRendersPage(): void
        {
            $this->seedAuthenticatedSession(3110, 'Dashboard User');

            $controller = new DashboardController();
            $response = $controller->dashboard();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('modern-dashboard', $response->getContent());
        }

        public function testDashboardHidesSuggestionEntryForRecentAccounts(): void
        {
            $user = new Usuario();
            $user->created_at = (new \DateTimeImmutable('-2 days'))->format('Y-m-d H:i:s');

            $this->seedAuthenticatedSession(3991, 'Conta Nova', $user);

            $controller = new DashboardController();
            $response = $controller->dashboard();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringNotContainsString('id="sidebarSuggestionBtn"', $response->getContent());
        }

        public function testDashboardShowsSuggestionEntryForMatureAccounts(): void
        {
            $user = new Usuario();
            $user->created_at = '2024-03-20 09:00:00';

            $this->seedAuthenticatedSession(3992, 'Conta Antiga', $user);

            $controller = new DashboardController();
            $response = $controller->dashboard();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('id="sidebarSuggestionBtn"', $response->getContent());
        }

        public function testFaturaIndexRendersBootstrapShellPage(): void
        {
            $this->seedAuthenticatedSession(3111, 'Fatura User');

            $controller = new FaturaController();
            $response = $controller->index();
            $content = $response->getContent();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('parc-page', $content);
            $this->assertStringContainsString('data-lk-menu="faturas"', $content);
            $this->assertStringContainsString('data-faturas-import-ofx-link', $content);
            $this->assertStringContainsString('import_target=cartao', $content);
            $this->assertStringNotContainsString('import_target=cartao&source_type=ofx', $content);
        }

        public function testFinancasIndexRendersBootstrapShellPage(): void
        {
            $this->seedAuthenticatedSession(3112, 'Financas User');

            $controller = new FinancasController();
            $response = $controller->index();
            $content = $response->getContent();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('fin-page', $content);
            $this->assertStringContainsString('data-lk-menu="financas"', $content);
        }

        public function testFinancasLayoutSmokeRendersSidebarCurrentMenuAndBundleMarkers(): void
        {
            $this->seedAuthenticatedSession(3114, 'Financas Smoke User');

            $controller = new FinancasController();
            $response = $controller->index();
            $content = $response->getContent();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('id="sidebar-main"', $content);
            $this->assertStringContainsString('data-lk-menu="financas"', $content);
            $this->assertStringContainsString('data-lk-view-id="admin-financas-index"', $content);
            $this->assertStringContainsString('data-lk-view-path="admin/financas/index"', $content);
            $this->assertStringContainsString('bundle', $content);
            $this->assertStringContainsString('GLOBAL INFRASTRUCTURE BUNDLE (Vite)', $content);
            $this->assertStringNotContainsString('window.__LK_CONFIG =', $content);
        }

        public function testImportacoesPagesRender(): void
        {
            $this->seedAuthenticatedSession(3116, 'Importacoes User');

            $indexController = new ImportacoesController();
            $configController = new ImportacoesConfiguracoesController();
            $historyController = new ImportacoesHistoricoController();

            $index = $indexController->index();
            $config = $configController->index();
            $history = $historyController->index();

            $this->assertSame(200, $index->getStatusCode());
            $this->assertStringContainsString('imp-page', $index->getContent());
            $this->assertStringContainsString('data-imp-preview-region', $index->getContent());
            $this->assertStringContainsString('data-imp-preview-badge', $index->getContent());
            $this->assertStringContainsString('data-imp-preview-table-wrap', $index->getContent());
            $this->assertStringContainsString('data-imp-active-account-id', $index->getContent());
            $this->assertStringContainsString('data-imp-config-page-base-url', $index->getContent());
            $this->assertStringContainsString('data-imp-quota-warning', $index->getContent());
            $this->assertStringContainsString('data-imp-advanced-panel', $index->getContent());
            $this->assertStringContainsString('data-imp-advanced-template-auto', $index->getContent());
            $this->assertStringContainsString('data-imp-advanced-template-manual', $index->getContent());
            $this->assertStringContainsString('data-imp-advanced-account-name', $index->getContent());
            $this->assertStringContainsString('data-imp-profile-account-name', $index->getContent());
            $this->assertStringContainsString('data-imp-config-link', $index->getContent());
            $this->assertStringContainsString('data-imp-guide-path-card', $index->getContent());
            $this->assertStringContainsString('data-imp-guide-context-card', $index->getContent());
            $this->assertStringContainsString('data-imp-guide-readiness-card', $index->getContent());
            $this->assertStringContainsString('data-imp-file-note', $index->getContent());
            $this->assertTrue(
                str_contains($index->getContent(), 'data-imp-account-select-main')
                    || str_contains($index->getContent(), 'data-imp-account-warning')
            );

            $this->assertSame(200, $config->getStatusCode());
            $this->assertStringContainsString('imp-config-page', $config->getContent());
            $this->assertStringContainsString('data-imp-active-account-id', $config->getContent());
            $this->assertTrue(
                str_contains($config->getContent(), 'data-imp-account-select')
                    || str_contains($config->getContent(), 'Nenhuma conta ativa encontrada')
            );
            $this->assertTrue(
                str_contains($config->getContent(), 'data-imp-config-save-form')
                    || str_contains($config->getContent(), 'Usar este perfil na importacao')
                    || str_contains($config->getContent(), 'liberar a configuracao de importacoes')
                    || str_contains($config->getContent(), 'liberar a configuração de importações')
            );
            $this->assertTrue(
                str_contains($config->getContent(), 'data-imp-csv-mapping-mode')
                    || str_contains($config->getContent(), 'Nenhuma conta ativa encontrada')
            );
            $this->assertTrue(
                preg_match('/data-imp-csv-template-auto[^>]*data-no-transition="true"[^>]*download/', $config->getContent()) === 1
                    || str_contains($config->getContent(), 'Nenhuma conta ativa encontrada')
            );
            $this->assertTrue(
                preg_match('/data-imp-csv-template-manual[^>]*data-no-transition="true"[^>]*download/', $config->getContent()) === 1
                    || str_contains($config->getContent(), 'Nenhuma conta ativa encontrada')
            );

            $this->assertSame(200, $history->getStatusCode());
            $this->assertStringContainsString('imp-history-page', $history->getContent());
            $this->assertStringContainsString('data-imp-history-filters', $history->getContent());
            $this->assertStringContainsString('data-imp-history-filter-account', $history->getContent());
            $this->assertTrue(
                str_contains($history->getContent(), 'data-imp-history-table')
                    || str_contains($history->getContent(), 'Nenhum lote registrado')
            );
        }

        public function testImportacoesLayoutSmokeRendersSidebarCurrentMenuAndBundleMarkers(): void
        {
            $this->seedAuthenticatedSession(3117, 'Importacoes Smoke User');

            $controller = new ImportacoesController();
            $response = $controller->index();
            $content = $response->getContent();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('id="sidebar-main"', $content);
            $this->assertStringContainsString('data-lk-menu="importacoes"', $content);
            $this->assertStringContainsString('data-lk-view-id="admin-importacoes-index"', $content);
            $this->assertStringContainsString('data-lk-view-path="admin/importacoes/index"', $content);
            $this->assertStringContainsString('bundle', $content);
            $this->assertStringContainsString('GLOBAL INFRASTRUCTURE BUNDLE (Vite)', $content);
            $this->assertStringNotContainsString('window.__LK_CONFIG =', $content);
        }

        public function testLancamentoIndexRendersPage(): void
        {
            $this->seedAuthenticatedSession(3113, 'Lancamento User');

            $controller = new LancamentoController();
            $response = $controller->index();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('lan-page', $response->getContent());
        }

        public function testRelatoriosViewRendersBootstrapShellPage(): void
        {
            $user = new class extends Usuario {
                public function isPro(): bool
                {
                    return true;
                }
            };

            $this->seedAuthenticatedSession(3102, 'Relatorios User', $user);

            $controller = new RelatoriosController();
            $response = $controller->view();
            $content = $response->getContent();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('rel-page', $content);
            $this->assertStringContainsString('data-lk-menu="relatorios"', $content);
        }

        public function testPerfilIndexRendersPage(): void
        {
            $this->seedAuthenticatedSession(3103, 'Perfil User');

            $controller = new PerfilController();
            $response = $controller->index();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('profile-page', $response->getContent());
            $this->assertStringContainsString('data-profile-root', $response->getContent());
            $this->assertStringContainsString('data-profile-display-name-root', $response->getContent());
            $this->assertStringContainsString('data-lk-menu="perfil"', $response->getContent());
        }

        public function testOrcamentoIndexRendersBootstrapShellPage(): void
        {
            $this->seedAuthenticatedSession(3104, 'Orcamento User');

            $controller = new OrcamentoController();
            $response = $controller->index();
            $content = $response->getContent();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('orc-page', $content);
            $this->assertStringContainsString('data-lk-menu="orcamento"', $content);
        }

        public function testMetasIndexRendersBootstrapShellPage(): void
        {
            $this->seedAuthenticatedSession(3105, 'Metas User');

            $controller = new MetasController();
            $response = $controller->index();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('met-page', $response->getContent());
            $this->assertStringContainsString('data-lk-menu="metas"', $response->getContent());
        }

        public function testSysadminBlogLayoutSmokeRendersSidebarCurrentMenuAndBundleMarkers(): void
        {
            $this->seedAuthenticatedSession(3115, 'Sysadmin Smoke User', null, true);

            $controller = new BlogViewController();
            $response = $controller->index();
            $content = $response->getContent();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('id="sidebar-main"', $content);
            $this->assertStringContainsString('data-lk-menu="super_admin"', $content);
            $this->assertStringContainsString('data-lk-view-id="admin-sysadmin-blog"', $content);
            $this->assertStringContainsString('data-lk-view-path="admin/sysadmin/blog"', $content);
            $this->assertStringContainsString('bundle', $content);
            $this->assertStringContainsString('GLOBAL INFRASTRUCTURE BUNDLE (Vite)', $content);
            $this->assertStringNotContainsString('window.__LK_CONFIG =', $content);
        }

        private function seedAuthenticatedSession(int $userId, string $name, ?Usuario $user = null, bool $isAdmin = false): void
        {
            $this->startIsolatedSession('admin-page-controllers-test');

            $user ??= new Usuario();
            $user->id = $userId;
            $user->nome = $name;
            $user->is_admin = $isAdmin ? 1 : 0;

            $_SESSION['usuario_logged_in'] = true;
            $_SESSION['user_id'] = $userId;
            $_SESSION['usuario_nome'] = $name;
            $_SESSION['usuario_cache'] = [
                'id' => $userId,
                'data' => $user,
            ];
        }
    }
}
