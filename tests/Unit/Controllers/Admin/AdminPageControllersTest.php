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
    use Application\Controllers\Admin\LancamentoController;
    use Application\Controllers\Admin\MetasController;
    use Application\Controllers\Admin\OnboardingController;
    use Application\Controllers\Admin\OrcamentoController;
    use Application\Controllers\Admin\PerfilController;
    use Application\Controllers\Admin\RelatoriosController;
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

        public function testOnboardingRedirectsToDashboardWhenAuthenticated(): void
        {
            $this->seedAuthenticatedSession(3101, 'Admin User');

            $controller = new OnboardingController();
            $response = $controller->index();

            $this->assertSame(302, $response->getStatusCode());
            $this->assertSame(BASE_URL . 'dashboard', $response->getHeaders()['Location'] ?? null);
        }

        public function testOnboardingRedirectsToLoginWhenSessionIsMissing(): void
        {
            $controller = new OnboardingController();

            try {
                $controller->index();
                $this->fail('Expected HttpResponseException');
            } catch (HttpResponseException $e) {
                $response = $e->getResponse();

                $this->assertSame(302, $response->getStatusCode());
                $this->assertSame(BASE_URL . 'login', $response->getHeaders()['Location'] ?? null);
            }
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

        public function testCartoesPagesRender(): void
        {
            $this->seedAuthenticatedSession(3106, 'Cartoes User');

            $controller = new CartoesController();

            $index = $controller->index();
            $archived = $controller->archived();

            $this->assertSame(200, $index->getStatusCode());
            $this->assertStringContainsString('cartoes-page', $index->getContent());
            $this->assertSame(200, $archived->getStatusCode());
            $this->assertStringContainsString('Cartões Arquivados', $archived->getContent());
        }

        public function testCategoriaIndexRendersPage(): void
        {
            $this->seedAuthenticatedSession(3107, 'Categoria User');

            $controller = new CategoriaController();
            $response = $controller->index();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('cat-page', $response->getContent());
        }

        public function testConfigIndexRendersConfiguracoesView(): void
        {
            $this->seedAuthenticatedSession(3108, 'Config User');

            $controller = new ConfigController();
            $response = $controller->index();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('Configurações', $response->getContent());
        }

        public function testContasPagesRender(): void
        {
            $this->seedAuthenticatedSession(3109, 'Contas User');

            $controller = new ContasController();

            $index = $controller->index();
            $archived = $controller->archived();

            $this->assertSame(200, $index->getStatusCode());
            $this->assertStringContainsString('cont-page', $index->getContent());
            $this->assertSame(200, $archived->getStatusCode());
            $this->assertStringContainsString('Contas Arquivadas', $archived->getContent());
        }

        public function testDashboardIndexRendersPage(): void
        {
            $this->seedAuthenticatedSession(3110, 'Dashboard User');

            $controller = new DashboardController();
            $response = $controller->dashboard();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('modern-dashboard', $response->getContent());
        }

        public function testFaturaIndexRendersPage(): void
        {
            $this->seedAuthenticatedSession(3111, 'Fatura User');

            $controller = new FaturaController();
            $response = $controller->index();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('parc-page', $response->getContent());
        }

        public function testFinancasIndexRendersPage(): void
        {
            $this->seedAuthenticatedSession(3112, 'Financas User');

            $controller = new FinancasController();
            $response = $controller->index();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('fin-page', $response->getContent());
        }

        public function testLancamentoIndexRendersPage(): void
        {
            $this->seedAuthenticatedSession(3113, 'Lancamento User');

            $controller = new LancamentoController();
            $response = $controller->index();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('lan-page', $response->getContent());
        }

        public function testRelatoriosViewRendersPage(): void
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

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('rel-page', $response->getContent());
        }

        public function testPerfilIndexRendersPage(): void
        {
            $this->seedAuthenticatedSession(3103, 'Perfil User');

            $controller = new PerfilController();
            $response = $controller->index();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('profile-page', $response->getContent());
        }

        public function testOrcamentoIndexRendersPage(): void
        {
            $this->seedAuthenticatedSession(3104, 'Orcamento User');

            $controller = new OrcamentoController();
            $response = $controller->index();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('orc-page', $response->getContent());
        }

        public function testMetasIndexRendersPage(): void
        {
            $this->seedAuthenticatedSession(3105, 'Metas User');

            $controller = new MetasController();
            $response = $controller->index();

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('met-page', $response->getContent());
        }

        private function seedAuthenticatedSession(int $userId, string $name, ?Usuario $user = null): void
        {
            $this->startIsolatedSession('admin-page-controllers-test');

            $user ??= new Usuario();
            $user->id = $userId;
            $user->nome = $name;
            $user->is_admin = 0;

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
