<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\User;

use Application\Controllers\Api\User\BootstrapController;
use Application\Models\Usuario;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class BootstrapControllerTest extends TestCase
{
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testShowReturnsAdminRuntimeConfigForAuthenticatedUser(): void
    {
        $this->seedAuthenticatedSession();
        $_GET = [
            'menu' => 'perfil',
            'view_path' => 'perfil',
            'view_id' => 'perfil',
        ];

        $controller = new BootstrapController();

        $response = $controller->show();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertSame(rtrim(BASE_URL, '/') . '/', $payload['data']['baseUrl']);
        $this->assertSame(rtrim(BASE_URL, '/') . '/', $payload['data']['apiBaseUrl']);
        $this->assertSame(77, $payload['data']['userId']);
        $this->assertSame('Maria Silva', $payload['data']['username']);
        $this->assertSame('maria@example.com', $payload['data']['userEmail']);
        $this->assertSame('perfil', $payload['data']['currentMenu']);
        $this->assertSame('perfil', $payload['data']['currentViewId']);
        $this->assertSame('perfil', $payload['data']['currentViewPath']);
        $this->assertSame('light', $payload['data']['userTheme']);
        $this->assertFalse($payload['data']['isPro']);
        $this->assertFalse($payload['data']['isUltra']);
        $this->assertSame('free', $payload['data']['planTier']);
        $this->assertSame('FREE', $payload['data']['planLabel']);
        $this->assertSame('pro', $payload['data']['upgradeTarget']);
        $this->assertTrue($payload['data']['showUpgradeCTA']);
        $this->assertTrue($payload['data']['tourCompleted']);
        $this->assertFalse($payload['data']['needsDisplayNamePrompt']);
        $this->assertSame(rtrim(BASE_URL, '/') . '/uploads/avatar-maria.png', $payload['data']['userAvatar']);
        $this->assertSame([
            'position_x' => 65,
            'position_y' => 35,
            'zoom' => 1.25,
        ], $payload['data']['userAvatarSettings']);
        $this->assertTrue($payload['data']['feedback']['generalFeedbackEnabled']);
        $this->assertSame(7, $payload['data']['feedback']['minimumAccountAgeDays']);
        $this->assertNotEmpty($payload['data']['feedback']['generalFeedbackAvailableAt']);
        $this->assertSame([
            'settings' => [
                'auto_offer' => false,
            ],
            'tour_completed' => [
                'perfil.desktop' => 'v2',
            ],
            'offer_dismissed' => [],
            'tips_seen' => [
                'perfil' => 'v1',
            ],
        ], $payload['data']['helpCenter']);
        $this->assertSame('perfil', $payload['data']['pageContext']['currentMenu']);
        $this->assertSame('perfil', $payload['data']['pageContext']['currentViewId']);
        $this->assertSame('perfil', $payload['data']['pageContext']['currentViewPath']);
        $this->assertArrayHasKey('bundle', $payload['data']);
        $this->assertArrayHasKey('pageJsViewId', $payload['data']['bundle']);
    }

    public function testShowDisablesGeneralFeedbackForRecentAccounts(): void
    {
        $this->startIsolatedSession('bootstrap-controller-test-recent-user');

        $user = new Usuario();
        $user->id = 88;
        $user->nome = 'Conta Nova';
        $user->email = 'nova@example.com';
        $user->theme_preference = 'dark';
        $user->is_admin = 0;
        $user->created_at = (new \DateTimeImmutable('-2 days'))->format('Y-m-d H:i:s');

        $_SESSION['usuario_logged_in'] = true;
        $_SESSION['user_id'] = 88;
        $_SESSION['usuario_nome'] = 'Conta Nova';
        $_SESSION['last_activity'] = time();
        $_SESSION['usuario_cache'] = [
            'id' => 88,
            'data' => $user,
        ];

        $controller = new BootstrapController();

        $response = $controller->show();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($payload['data']['feedback']['generalFeedbackEnabled']);
        $this->assertSame(7, $payload['data']['feedback']['minimumAccountAgeDays']);
        $this->assertNotEmpty($payload['data']['feedback']['generalFeedbackAvailableAt']);
    }

    public function testShowIncludesDashboardPageCapabilitiesForFreeUsers(): void
    {
        $this->seedAuthenticatedSession();
        $_GET = [
            'menu' => 'dashboard',
            'view_path' => 'dashboard',
            'view_id' => 'dashboard',
        ];

        $controller = new BootstrapController();

        $response = $controller->show();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('dashboard', $payload['data']['pageCapabilities']['pageKey']);
        $this->assertSame('essential', $payload['data']['pageCapabilities']['customizer']['mode']);
        $this->assertTrue($payload['data']['pageCapabilities']['customizer']['canCustomize']);
        $this->assertFalse($payload['data']['pageCapabilities']['customizer']['canAccessComplete']);
        $this->assertTrue($payload['data']['pageCapabilities']['customizer']['renderOverlay']);
        $this->assertSame('Personalizar dashboard', $payload['data']['pageCapabilities']['customizer']['trigger']['label']);
        $this->assertSame('customize', $payload['data']['pageCapabilities']['customizer']['trigger']['action']);
        $this->assertNull($payload['data']['pageCapabilities']['customizer']['trigger']['target']);
        $this->assertSame([
            'essential',
        ], $payload['data']['pageCapabilities']['customizer']['availablePresets']);
        $this->assertSame([
            'toggleAlertas',
            'toggleGrafico',
        ], $payload['data']['pageCapabilities']['customizer']['availableToggles']);
        $this->assertSame([
            'toggleAlertas' => true,
            'toggleHealthScore' => false,
            'toggleAiTip' => false,
            'toggleEvolucao' => false,
            'togglePrevisao' => false,
            'toggleGrafico' => true,
            'toggleMetas' => false,
            'toggleCartoes' => false,
            'toggleContas' => false,
            'toggleOrcamentos' => false,
            'toggleFaturas' => false,
            'toggleGamificacao' => false,
        ], $payload['data']['pageCapabilities']['customizer']['forcedPreferences']);
        $this->assertSame('btnCustomizeDashboard', $payload['data']['pageCapabilities']['customizer']['descriptor']['trigger']['id']);
    }

    public function testShowIncludesContasPageCapabilitiesForFreeUsers(): void
    {
        $this->seedAuthenticatedSession();
        $_GET = [
            'menu' => 'contas',
            'view_path' => 'contas',
            'view_id' => 'contas',
        ];

        $controller = new BootstrapController();

        $response = $controller->show();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('contas', $payload['data']['pageCapabilities']['pageKey']);
        $this->assertSame('essential', $payload['data']['pageCapabilities']['customizer']['mode']);
        $this->assertTrue($payload['data']['pageCapabilities']['customizer']['canCustomize']);
        $this->assertFalse($payload['data']['pageCapabilities']['customizer']['canAccessComplete']);
        $this->assertTrue($payload['data']['pageCapabilities']['customizer']['renderOverlay']);
        $this->assertSame('Personalizar contas', $payload['data']['pageCapabilities']['customizer']['trigger']['label']);
        $this->assertSame('customize', $payload['data']['pageCapabilities']['customizer']['trigger']['action']);
        $this->assertNull($payload['data']['pageCapabilities']['customizer']['trigger']['target']);
        $this->assertSame([
            'essential',
        ], $payload['data']['pageCapabilities']['customizer']['availablePresets']);
        $this->assertSame([
            'toggleContasHero',
        ], $payload['data']['pageCapabilities']['customizer']['availableToggles']);
        $this->assertSame([
            'toggleContasHero' => true,
            'toggleContasKpis' => false,
        ], $payload['data']['pageCapabilities']['customizer']['forcedPreferences']);
        $this->assertSame('btnCustomizeContas', $payload['data']['pageCapabilities']['customizer']['descriptor']['trigger']['id']);
    }

    public function testShowIncludesCartoesPageCapabilitiesForFreeUsers(): void
    {
        $this->seedAuthenticatedSession();
        $_GET = [
            'menu' => 'cartoes',
            'view_path' => 'cartoes',
            'view_id' => 'cartoes',
        ];

        $controller = new BootstrapController();

        $response = $controller->show();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('cartoes', $payload['data']['pageCapabilities']['pageKey']);
        $this->assertSame('essential', $payload['data']['pageCapabilities']['customizer']['mode']);
        $this->assertFalse($payload['data']['pageCapabilities']['customizer']['canCustomize']);
        $this->assertFalse($payload['data']['pageCapabilities']['customizer']['canAccessComplete']);
        $this->assertTrue($payload['data']['pageCapabilities']['customizer']['renderOverlay']);
        $this->assertSame('Desbloquear cartões completos', $payload['data']['pageCapabilities']['customizer']['trigger']['label']);
        $this->assertSame('upgrade', $payload['data']['pageCapabilities']['customizer']['trigger']['action']);
        $this->assertSame('pro', $payload['data']['pageCapabilities']['customizer']['trigger']['target']);
        $this->assertSame([
            'essential',
        ], $payload['data']['pageCapabilities']['customizer']['availablePresets']);
        $this->assertSame([], $payload['data']['pageCapabilities']['customizer']['availableToggles']);
        $this->assertSame([
            'toggleCartoesKpis' => false,
            'toggleCartoesToolbar' => false,
        ], $payload['data']['pageCapabilities']['customizer']['forcedPreferences']);
        $this->assertSame('btnCustomizeCartoes', $payload['data']['pageCapabilities']['customizer']['descriptor']['trigger']['id']);
    }

    public function testShowIncludesFaturasPageCapabilitiesForFreeUsers(): void
    {
        $this->seedAuthenticatedSession();
        $_GET = [
            'menu' => 'faturas',
            'view_path' => 'faturas',
            'view_id' => 'faturas',
        ];

        $controller = new BootstrapController();

        $response = $controller->show();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('faturas', $payload['data']['pageCapabilities']['pageKey']);
        $this->assertSame('essential', $payload['data']['pageCapabilities']['customizer']['mode']);
        $this->assertTrue($payload['data']['pageCapabilities']['customizer']['canCustomize']);
        $this->assertFalse($payload['data']['pageCapabilities']['customizer']['canAccessComplete']);
        $this->assertTrue($payload['data']['pageCapabilities']['customizer']['renderOverlay']);
        $this->assertSame('Personalizar faturas', $payload['data']['pageCapabilities']['customizer']['trigger']['label']);
        $this->assertSame('customize', $payload['data']['pageCapabilities']['customizer']['trigger']['action']);
        $this->assertNull($payload['data']['pageCapabilities']['customizer']['trigger']['target']);
        $this->assertSame([
            'essential',
        ], $payload['data']['pageCapabilities']['customizer']['availablePresets']);
        $this->assertSame([
            'toggleFaturasHero',
        ], $payload['data']['pageCapabilities']['customizer']['availableToggles']);
        $this->assertSame([
            'toggleFaturasHero' => true,
            'toggleFaturasFiltros' => false,
            'toggleFaturasViewToggle' => false,
        ], $payload['data']['pageCapabilities']['customizer']['forcedPreferences']);
        $this->assertSame('btnCustomizeFaturas', $payload['data']['pageCapabilities']['customizer']['descriptor']['trigger']['id']);
    }

    public function testShowIncludesImportacoesPageCapabilitiesForFreeUsers(): void
    {
        $this->seedAuthenticatedSession();
        $_GET = [
            'menu' => 'importacoes',
            'view_path' => 'importacoes',
            'view_id' => 'importacoes',
        ];

        $controller = new BootstrapController();

        $response = $controller->show();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('importacoes', $payload['data']['pageCapabilities']['pageKey']);
        $this->assertSame('essential', $payload['data']['pageCapabilities']['customizer']['mode']);
        $this->assertFalse($payload['data']['pageCapabilities']['customizer']['canCustomize']);
        $this->assertFalse($payload['data']['pageCapabilities']['customizer']['canAccessComplete']);
        $this->assertTrue($payload['data']['pageCapabilities']['customizer']['renderOverlay']);
        $this->assertSame('Desbloquear importações completas', $payload['data']['pageCapabilities']['customizer']['trigger']['label']);
        $this->assertSame('upgrade', $payload['data']['pageCapabilities']['customizer']['trigger']['action']);
        $this->assertSame('pro', $payload['data']['pageCapabilities']['customizer']['trigger']['target']);
        $this->assertSame([
            'essential',
        ], $payload['data']['pageCapabilities']['customizer']['availablePresets']);
        $this->assertSame([], $payload['data']['pageCapabilities']['customizer']['availableToggles']);
        $this->assertSame([
            'toggleImpHero' => false,
            'toggleImpSidebar' => false,
        ], $payload['data']['pageCapabilities']['customizer']['forcedPreferences']);
        $this->assertSame('btnCustomizeImportacoes', $payload['data']['pageCapabilities']['customizer']['descriptor']['trigger']['id']);
    }

    public function testShowIncludesMetasPageCapabilitiesForFreeUsers(): void
    {
        $this->seedAuthenticatedSession();
        $_GET = [
            'menu' => 'metas',
            'view_path' => 'metas',
            'view_id' => 'metas',
        ];

        $controller = new BootstrapController();

        $response = $controller->show();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('metas', $payload['data']['pageCapabilities']['pageKey']);
        $this->assertSame('essential', $payload['data']['pageCapabilities']['customizer']['mode']);
        $this->assertTrue($payload['data']['pageCapabilities']['customizer']['canCustomize']);
        $this->assertFalse($payload['data']['pageCapabilities']['customizer']['canAccessComplete']);
        $this->assertTrue($payload['data']['pageCapabilities']['customizer']['renderOverlay']);
        $this->assertSame('Personalizar metas', $payload['data']['pageCapabilities']['customizer']['trigger']['label']);
        $this->assertSame('customize', $payload['data']['pageCapabilities']['customizer']['trigger']['action']);
        $this->assertNull($payload['data']['pageCapabilities']['customizer']['trigger']['target']);
        $this->assertSame([
            'essential',
        ], $payload['data']['pageCapabilities']['customizer']['availablePresets']);
        $this->assertSame([
            'toggleMetasToolbar',
        ], $payload['data']['pageCapabilities']['customizer']['availableToggles']);
        $this->assertSame([
            'toggleMetasSummary' => false,
            'toggleMetasFocus' => false,
            'toggleMetasToolbar' => true,
        ], $payload['data']['pageCapabilities']['customizer']['forcedPreferences']);
        $this->assertSame('btnCustomizeMetas', $payload['data']['pageCapabilities']['customizer']['descriptor']['trigger']['id']);
    }

    public function testShowIncludesOrcamentoPageCapabilitiesForFreeUsers(): void
    {
        $this->seedAuthenticatedSession();
        $_GET = [
            'menu' => 'orcamento',
            'view_path' => 'orcamento',
            'view_id' => 'orcamento',
        ];

        $controller = new BootstrapController();

        $response = $controller->show();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('orcamento', $payload['data']['pageCapabilities']['pageKey']);
        $this->assertSame('essential', $payload['data']['pageCapabilities']['customizer']['mode']);
        $this->assertTrue($payload['data']['pageCapabilities']['customizer']['canCustomize']);
        $this->assertFalse($payload['data']['pageCapabilities']['customizer']['canAccessComplete']);
        $this->assertTrue($payload['data']['pageCapabilities']['customizer']['renderOverlay']);
        $this->assertSame('Personalizar orçamento', $payload['data']['pageCapabilities']['customizer']['trigger']['label']);
        $this->assertSame('customize', $payload['data']['pageCapabilities']['customizer']['trigger']['action']);
        $this->assertNull($payload['data']['pageCapabilities']['customizer']['trigger']['target']);
        $this->assertSame([
            'essential',
        ], $payload['data']['pageCapabilities']['customizer']['availablePresets']);
        $this->assertSame([
            'toggleOrcToolbar',
        ], $payload['data']['pageCapabilities']['customizer']['availableToggles']);
        $this->assertSame([
            'toggleOrcSummary' => false,
            'toggleOrcFocus' => false,
            'toggleOrcToolbar' => true,
        ], $payload['data']['pageCapabilities']['customizer']['forcedPreferences']);
        $this->assertSame('btnCustomizeOrcamento', $payload['data']['pageCapabilities']['customizer']['descriptor']['trigger']['id']);
    }

    public function testShowIncludesReportsPageCapabilitiesForFreeUsers(): void
    {
        $this->seedAuthenticatedSession();
        $_GET = [
            'menu' => 'relatorios',
            'view_path' => 'relatorios',
            'view_id' => 'relatorios',
        ];

        $controller = new BootstrapController();

        $response = $controller->show();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('relatorios', $payload['data']['pageCapabilities']['pageKey']);
        $this->assertSame('essential', $payload['data']['pageCapabilities']['customizer']['mode']);
        $this->assertFalse($payload['data']['pageCapabilities']['customizer']['canCustomize']);
        $this->assertFalse($payload['data']['pageCapabilities']['customizer']['canAccessComplete']);
        $this->assertTrue($payload['data']['pageCapabilities']['customizer']['renderOverlay']);
        $this->assertSame('Desbloquear relatórios completos', $payload['data']['pageCapabilities']['customizer']['trigger']['label']);
        $this->assertSame('upgrade', $payload['data']['pageCapabilities']['customizer']['trigger']['action']);
        $this->assertSame('pro', $payload['data']['pageCapabilities']['customizer']['trigger']['target']);
        $this->assertSame([
            'essential',
        ], $payload['data']['pageCapabilities']['customizer']['availablePresets']);
        $this->assertSame([], $payload['data']['pageCapabilities']['customizer']['availableToggles']);
        $this->assertSame([
            'toggleRelSectionInsights' => false,
            'toggleRelSectionRelatorios' => false,
            'toggleRelControls' => false,
            'toggleRelSectionComparativos' => false,
        ], $payload['data']['pageCapabilities']['customizer']['forcedPreferences']);
        $this->assertSame('btnCustomizeRelatorios', $payload['data']['pageCapabilities']['customizer']['descriptor']['trigger']['id']);
    }

    public function testShowIncludesLancamentosPageCapabilitiesForFreeUsers(): void
    {
        $this->seedAuthenticatedSession();
        $_GET = [
            'menu' => 'lancamentos',
            'view_path' => 'lancamentos',
            'view_id' => 'lancamentos',
        ];

        $controller = new BootstrapController();

        $response = $controller->show();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('lancamentos', $payload['data']['pageCapabilities']['pageKey']);
        $this->assertSame('essential', $payload['data']['pageCapabilities']['customizer']['mode']);
        $this->assertTrue($payload['data']['pageCapabilities']['customizer']['canCustomize']);
        $this->assertFalse($payload['data']['pageCapabilities']['customizer']['canAccessComplete']);
        $this->assertTrue($payload['data']['pageCapabilities']['customizer']['renderOverlay']);
        $this->assertSame('Personalizar transações', $payload['data']['pageCapabilities']['customizer']['trigger']['label']);
        $this->assertSame('customize', $payload['data']['pageCapabilities']['customizer']['trigger']['action']);
        $this->assertNull($payload['data']['pageCapabilities']['customizer']['trigger']['target']);
        $this->assertSame([
            'essential',
        ], $payload['data']['pageCapabilities']['customizer']['availablePresets']);
        $this->assertSame([
            'toggleLanFilters',
        ], $payload['data']['pageCapabilities']['customizer']['availableToggles']);
        $this->assertSame([
            'toggleLanFilters' => true,
            'toggleLanExport' => false,
        ], $payload['data']['pageCapabilities']['customizer']['forcedPreferences']);
        $this->assertSame('btnCustomizeLancamentos', $payload['data']['pageCapabilities']['customizer']['descriptor']['trigger']['id']);
    }

    public function testShowIncludesBillingPageCapabilitiesForFreeUsers(): void
    {
        $this->seedAuthenticatedSession();
        $_GET = [
            'menu' => 'billing',
            'view_path' => 'billing',
            'view_id' => 'billing',
        ];

        $controller = new BootstrapController();

        $response = $controller->show();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('billing', $payload['data']['pageCapabilities']['pageKey']);
        $this->assertSame('complete', $payload['data']['pageCapabilities']['customizer']['mode']);
        $this->assertTrue($payload['data']['pageCapabilities']['customizer']['canCustomize']);
        $this->assertTrue($payload['data']['pageCapabilities']['customizer']['canAccessComplete']);
        $this->assertFalse($payload['data']['pageCapabilities']['customizer']['renderOverlay']);
        $this->assertSame('Personalizar assinatura', $payload['data']['pageCapabilities']['customizer']['trigger']['label']);
        $this->assertSame(['complete'], $payload['data']['pageCapabilities']['customizer']['availablePresets']);
        $this->assertSame([
            'toggleBillingHeader',
            'toggleBillingPlans',
        ], $payload['data']['pageCapabilities']['customizer']['availableToggles']);
        $this->assertNull($payload['data']['pageCapabilities']['customizer']['forcedPreferences']);
    }

    public function testShowIncludesCategoriasPageCapabilitiesForFreeUsers(): void
    {
        $this->seedAuthenticatedSession();
        $_GET = [
            'menu' => 'categorias',
            'view_path' => 'categorias',
            'view_id' => 'categorias',
        ];

        $controller = new BootstrapController();

        $response = $controller->show();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('categorias', $payload['data']['pageCapabilities']['pageKey']);
        $this->assertSame('essential', $payload['data']['pageCapabilities']['customizer']['mode']);
        $this->assertTrue($payload['data']['pageCapabilities']['customizer']['canCustomize']);
        $this->assertTrue($payload['data']['pageCapabilities']['customizer']['renderOverlay']);
        $this->assertSame('Personalizar categorias', $payload['data']['pageCapabilities']['customizer']['trigger']['label']);
        $this->assertSame([
            'toggleCategoriasCreateCard',
        ], $payload['data']['pageCapabilities']['customizer']['availableToggles']);
        $this->assertSame([
            'toggleCategoriasKpis' => false,
            'toggleCategoriasCreateCard' => true,
        ], $payload['data']['pageCapabilities']['customizer']['forcedPreferences']);
    }

    public function testShowIncludesFinancasPageCapabilitiesForFreeUsers(): void
    {
        $this->seedAuthenticatedSession();
        $_GET = [
            'menu' => 'financas',
            'view_path' => 'financas',
            'view_id' => 'financas',
        ];

        $controller = new BootstrapController();

        $response = $controller->show();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('financas', $payload['data']['pageCapabilities']['pageKey']);
        $this->assertSame('essential', $payload['data']['pageCapabilities']['customizer']['mode']);
        $this->assertTrue($payload['data']['pageCapabilities']['customizer']['canCustomize']);
        $this->assertSame('Personalizar finanças', $payload['data']['pageCapabilities']['customizer']['trigger']['label']);
        $this->assertSame([
            'toggleFinOrcActions',
            'toggleFinMetasActions',
        ], $payload['data']['pageCapabilities']['customizer']['availableToggles']);
        $this->assertSame([
            'toggleFinSummary' => false,
            'toggleFinOrcActions' => true,
            'toggleFinMetasActions' => true,
            'toggleFinInsights' => false,
        ], $payload['data']['pageCapabilities']['customizer']['forcedPreferences']);
    }

    public function testShowIncludesGamificationPageCapabilitiesForFreeUsers(): void
    {
        $this->seedAuthenticatedSession();
        $_GET = [
            'menu' => 'gamification',
            'view_path' => 'gamification',
            'view_id' => 'gamification',
        ];

        $controller = new BootstrapController();

        $response = $controller->show();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('gamification', $payload['data']['pageCapabilities']['pageKey']);
        $this->assertSame('essential', $payload['data']['pageCapabilities']['customizer']['mode']);
        $this->assertTrue($payload['data']['pageCapabilities']['customizer']['canCustomize']);
        $this->assertSame('Personalizar gamificação', $payload['data']['pageCapabilities']['customizer']['trigger']['label']);
        $this->assertSame([
            'toggleGamHeader',
            'toggleGamProgress',
            'toggleGamAchievements',
        ], $payload['data']['pageCapabilities']['customizer']['availableToggles']);
        $this->assertSame([
            'toggleGamHeader' => true,
            'toggleGamProgress' => true,
            'toggleGamAchievements' => true,
            'toggleGamHistory' => false,
            'toggleGamLeaderboard' => false,
        ], $payload['data']['pageCapabilities']['customizer']['forcedPreferences']);
    }

    public function testShowIncludesPerfilPageCapabilitiesForFreeUsers(): void
    {
        $this->seedAuthenticatedSession();
        $_GET = [
            'menu' => 'perfil',
            'view_path' => 'perfil',
            'view_id' => 'perfil',
        ];

        $controller = new BootstrapController();

        $response = $controller->show();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('perfil', $payload['data']['pageCapabilities']['pageKey']);
        $this->assertSame('complete', $payload['data']['pageCapabilities']['customizer']['mode']);
        $this->assertTrue($payload['data']['pageCapabilities']['customizer']['canCustomize']);
        $this->assertFalse($payload['data']['pageCapabilities']['customizer']['renderOverlay']);
        $this->assertSame('Personalizar perfil', $payload['data']['pageCapabilities']['customizer']['trigger']['label']);
        $this->assertSame(['complete'], $payload['data']['pageCapabilities']['customizer']['availablePresets']);
        $this->assertSame([
            'togglePerfilHeader',
            'togglePerfilTabs',
        ], $payload['data']['pageCapabilities']['customizer']['availableToggles']);
        $this->assertNull($payload['data']['pageCapabilities']['customizer']['forcedPreferences']);
    }

    public function testShowIncludesSysadminPageCapabilitiesForFreeUsers(): void
    {
        $this->seedAuthenticatedSession();
        $_GET = [
            'menu' => 'sysadmin',
            'view_path' => 'sysadmin',
            'view_id' => 'sysadmin',
        ];

        $controller = new BootstrapController();

        $response = $controller->show();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('sysadmin', $payload['data']['pageCapabilities']['pageKey']);
        $this->assertSame('complete', $payload['data']['pageCapabilities']['customizer']['mode']);
        $this->assertTrue($payload['data']['pageCapabilities']['customizer']['canCustomize']);
        $this->assertFalse($payload['data']['pageCapabilities']['customizer']['renderOverlay']);
        $this->assertSame('Personalizar sysadmin', $payload['data']['pageCapabilities']['customizer']['trigger']['label']);
        $this->assertSame([
            'essential',
            'complete',
        ], $payload['data']['pageCapabilities']['customizer']['availablePresets']);
        $this->assertSame([
            'toggleSysStats',
            'toggleSysTabs',
            'toggleSysDashboard',
            'toggleSysFeedback',
        ], $payload['data']['pageCapabilities']['customizer']['availableToggles']);
        $this->assertNull($payload['data']['pageCapabilities']['customizer']['forcedPreferences']);
    }

    private function seedAuthenticatedSession(): void
    {
        $this->startIsolatedSession('bootstrap-controller-test');

        $user = new Usuario();
        $user->id = 77;
        $user->nome = 'Maria Silva';
        $user->email = 'maria@example.com';
        $user->theme_preference = 'light';
        $user->is_admin = 0;
        $user->avatar = 'uploads/avatar-maria.png';
        $user->avatar_focus_x = 65;
        $user->avatar_focus_y = 35;
        $user->avatar_zoom = 1.25;
        $user->created_at = '2024-03-20 09:00:00';
        $user->tour_completed_at = '2024-04-02 10:30:00';
        $user->dashboard_preferences = [
            'help_center' => [
                'settings' => [
                    'auto_offer' => false,
                ],
                'tour_completed' => [
                    'perfil.desktop' => 'v2',
                ],
                'tips_seen' => [
                    'perfil' => 'v1',
                ],
            ],
        ];

        $_SESSION['usuario_logged_in'] = true;
        $_SESSION['user_id'] = 77;
        $_SESSION['usuario_nome'] = 'Maria Silva';
        $_SESSION['last_activity'] = time();
        $_SESSION['usuario_cache'] = [
            'id' => 77,
            'data' => $user,
        ];
    }
}
