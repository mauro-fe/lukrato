<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Admin;

use Application\Models\Usuario;
use Application\Services\Plan\PlanContext;
use Application\Support\Admin\PageCapabilityResolver;
use PHPUnit\Framework\TestCase;

class PageCapabilityResolverTest extends TestCase
{
    public function testResolveDashboardIncludesSharedDescriptorAndAllowsLockedOverlayForFreeUsers(): void
    {
        $resolved = PageCapabilityResolver::resolve('dashboard', $this->makePlanContext(false, 'free'));

        $this->assertSame('dashboard', $resolved['pageKey']);
        $this->assertTrue($resolved['customizer']['renderOverlay']);
        $this->assertSame([
            'toggleAlertas' => 'sectionAlertas',
            'toggleHealthScore' => 'sectionHealthScore',
            'toggleAiTip' => 'sectionAiTip',
            'toggleEvolucao' => 'sectionEvolucao',
            'togglePrevisao' => 'sectionPrevisao',
            'toggleGrafico' => 'chart-section',
            'toggleMetas' => 'sectionMetas',
            'toggleCartoes' => 'sectionCartoes',
            'toggleContas' => 'sectionContas',
            'toggleOrcamentos' => 'sectionOrcamentos',
            'toggleFaturas' => 'sectionFaturas',
            'toggleGamificacao' => 'sectionGamificacao',
        ], $resolved['customizer']['descriptor']['sectionMap']);
        $this->assertSame([
            'toggleMetas',
            'toggleCartoes',
            'toggleContas',
            'toggleOrcamentos',
            'toggleFaturas',
        ], $resolved['customizer']['descriptor']['gridToggleKeys']);
    }

    public function testFilterPreferencesDropsDashboardTogglesForFreeUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'dashboard',
            [
                'toggleAlertas' => false,
                'toggleGrafico' => false,
                'toggleHealthScore' => true,
            ],
            $this->makePlanContext(false, 'free')
        );

        $this->assertSame([
            'toggleAlertas' => false,
            'toggleGrafico' => false,
        ], $filtered);
    }

    public function testFilterPreferencesKeepsDashboardTogglesForProUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'dashboard',
            [
                'toggleAlertas' => false,
                'toggleGrafico' => false,
                'toggleHealthScore' => true,
                'toggleInventado' => true,
            ],
            $this->makePlanContext(true, 'pro')
        );

        $this->assertSame([
            'toggleAlertas' => false,
            'toggleGrafico' => false,
            'toggleHealthScore' => true,
        ], $filtered);
    }

    public function testResolveContasIncludesSharedDescriptorAndLockedOverlayForFreeUsers(): void
    {
        $resolved = PageCapabilityResolver::resolve('contas', $this->makePlanContext(false, 'free'));

        $this->assertSame('contas', $resolved['pageKey']);
        $this->assertTrue($resolved['customizer']['renderOverlay']);
        $this->assertSame([
            'toggleContasHero' => 'contasHero',
            'toggleContasKpis' => 'contasKpis',
        ], $resolved['customizer']['descriptor']['sectionMap']);
        $this->assertSame('Pro', $resolved['customizer']['descriptor']['lockedState']['tiers'][0]['name']);
        $this->assertSame('Personalizar contas', $resolved['customizer']['trigger']['label']);
    }

    public function testResolveCartoesIncludesSharedDescriptorAndLockedOverlayForFreeUsers(): void
    {
        $resolved = PageCapabilityResolver::resolve('cartoes', $this->makePlanContext(false, 'free'));

        $this->assertSame('cartoes', $resolved['pageKey']);
        $this->assertTrue($resolved['customizer']['renderOverlay']);
        $this->assertSame([
            'toggleCartoesKpis' => 'cartoesKpis',
            'toggleCartoesToolbar' => 'cartoesToolbar',
        ], $resolved['customizer']['descriptor']['sectionMap']);
        $this->assertSame('Pro', $resolved['customizer']['descriptor']['lockedState']['tiers'][0]['name']);
        $this->assertSame('Desbloquear cartões completos', $resolved['customizer']['trigger']['label']);
    }

    public function testResolveFaturasIncludesSharedDescriptorAndLockedOverlayForFreeUsers(): void
    {
        $resolved = PageCapabilityResolver::resolve('faturas', $this->makePlanContext(false, 'free'));

        $this->assertSame('faturas', $resolved['pageKey']);
        $this->assertTrue($resolved['customizer']['renderOverlay']);
        $this->assertSame([
            'toggleFaturasHero' => 'faturasHero',
            'toggleFaturasFiltros' => 'faturasFilters',
            'toggleFaturasViewToggle' => 'faturasViewToggle',
        ], $resolved['customizer']['descriptor']['sectionMap']);
        $this->assertSame('Pro', $resolved['customizer']['descriptor']['lockedState']['tiers'][0]['name']);
        $this->assertSame('Personalizar faturas', $resolved['customizer']['trigger']['label']);
    }

    public function testResolveImportacoesIncludesSharedDescriptorAndLockedOverlayForFreeUsers(): void
    {
        $resolved = PageCapabilityResolver::resolve('importacoes', $this->makePlanContext(false, 'free'));

        $this->assertSame('importacoes', $resolved['pageKey']);
        $this->assertTrue($resolved['customizer']['renderOverlay']);
        $this->assertSame([
            'toggleImpHero' => 'impHeroSection',
            'toggleImpSidebar' => 'impIndexSideSection',
        ], $resolved['customizer']['descriptor']['sectionMap']);
        $this->assertSame('Pro', $resolved['customizer']['descriptor']['lockedState']['tiers'][0]['name']);
        $this->assertSame('Desbloquear importações completas', $resolved['customizer']['trigger']['label']);
    }

    public function testResolveMetasIncludesSharedDescriptorAndLockedOverlayForFreeUsers(): void
    {
        $resolved = PageCapabilityResolver::resolve('metas', $this->makePlanContext(false, 'free'));

        $this->assertSame('metas', $resolved['pageKey']);
        $this->assertTrue($resolved['customizer']['renderOverlay']);
        $this->assertSame([
            'toggleMetasSummary' => 'summaryMetas',
            'toggleMetasFocus' => 'metFocusPanel',
            'toggleMetasToolbar' => 'metToolbarSection',
        ], $resolved['customizer']['descriptor']['sectionMap']);
        $this->assertSame('Pro', $resolved['customizer']['descriptor']['lockedState']['tiers'][0]['name']);
        $this->assertSame('Personalizar metas', $resolved['customizer']['trigger']['label']);
    }

    public function testResolveOrcamentoIncludesSharedDescriptorAndLockedOverlayForFreeUsers(): void
    {
        $resolved = PageCapabilityResolver::resolve('orcamento', $this->makePlanContext(false, 'free'));

        $this->assertSame('orcamento', $resolved['pageKey']);
        $this->assertTrue($resolved['customizer']['renderOverlay']);
        $this->assertSame([
            'toggleOrcSummary' => 'summaryOrcamentos',
            'toggleOrcFocus' => 'orcFocusPanel',
            'toggleOrcToolbar' => 'orcToolbarSection',
        ], $resolved['customizer']['descriptor']['sectionMap']);
        $this->assertSame('Pro', $resolved['customizer']['descriptor']['lockedState']['tiers'][0]['name']);
        $this->assertSame('Personalizar orçamento', $resolved['customizer']['trigger']['label']);
    }

    public function testFilterPreferencesDropsContasTogglesForFreeUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'contas',
            [
                'toggleContasHero' => false,
                'toggleContasKpis' => true,
            ],
            $this->makePlanContext(false, 'free')
        );

        $this->assertSame([
            'toggleContasHero' => false,
        ], $filtered);
    }

    public function testFilterPreferencesKeepsContasTogglesForProUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'contas',
            [
                'toggleContasHero' => false,
                'toggleContasKpis' => true,
                'toggleContasInventado' => true,
            ],
            $this->makePlanContext(true, 'pro')
        );

        $this->assertSame([
            'toggleContasHero' => false,
            'toggleContasKpis' => true,
        ], $filtered);
    }

    public function testFilterPreferencesDropsCartoesTogglesForFreeUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'cartoes',
            [
                'toggleCartoesKpis' => true,
                'toggleCartoesToolbar' => false,
            ],
            $this->makePlanContext(false, 'free')
        );

        $this->assertSame([], $filtered);
    }

    public function testFilterPreferencesDropsFaturasTogglesForFreeUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'faturas',
            [
                'toggleFaturasHero' => false,
                'toggleFaturasFiltros' => true,
                'toggleFaturasViewToggle' => true,
            ],
            $this->makePlanContext(false, 'free')
        );

        $this->assertSame([
            'toggleFaturasHero' => false,
        ], $filtered);
    }

    public function testFilterPreferencesDropsImportacoesTogglesForFreeUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'importacoes',
            [
                'toggleImpHero' => true,
                'toggleImpSidebar' => false,
            ],
            $this->makePlanContext(false, 'free')
        );

        $this->assertSame([], $filtered);
    }

    public function testFilterPreferencesDropsMetasTogglesForFreeUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'metas',
            [
                'toggleMetasSummary' => true,
                'toggleMetasFocus' => false,
                'toggleMetasToolbar' => false,
            ],
            $this->makePlanContext(false, 'free')
        );

        $this->assertSame([
            'toggleMetasToolbar' => false,
        ], $filtered);
    }

    public function testFilterPreferencesDropsOrcamentoTogglesForFreeUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'orcamento',
            [
                'toggleOrcSummary' => true,
                'toggleOrcFocus' => false,
                'toggleOrcToolbar' => false,
            ],
            $this->makePlanContext(false, 'free')
        );

        $this->assertSame([
            'toggleOrcToolbar' => false,
        ], $filtered);
    }

    public function testFilterPreferencesKeepsCartoesTogglesForProUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'cartoes',
            [
                'toggleCartoesKpis' => true,
                'toggleCartoesToolbar' => false,
                'toggleCartoesInventado' => true,
            ],
            $this->makePlanContext(true, 'pro')
        );

        $this->assertSame([
            'toggleCartoesKpis' => true,
            'toggleCartoesToolbar' => false,
        ], $filtered);
    }

    public function testFilterPreferencesKeepsFaturasTogglesForProUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'faturas',
            [
                'toggleFaturasHero' => false,
                'toggleFaturasFiltros' => true,
                'toggleFaturasViewToggle' => false,
                'toggleFaturasInventado' => true,
            ],
            $this->makePlanContext(true, 'pro')
        );

        $this->assertSame([
            'toggleFaturasHero' => false,
            'toggleFaturasFiltros' => true,
            'toggleFaturasViewToggle' => false,
        ], $filtered);
    }

    public function testFilterPreferencesKeepsImportacoesTogglesForProUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'importacoes',
            [
                'toggleImpHero' => false,
                'toggleImpSidebar' => true,
                'toggleImpInventado' => true,
            ],
            $this->makePlanContext(true, 'pro')
        );

        $this->assertSame([
            'toggleImpHero' => false,
            'toggleImpSidebar' => true,
        ], $filtered);
    }

    public function testFilterPreferencesKeepsMetasTogglesForProUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'metas',
            [
                'toggleMetasSummary' => false,
                'toggleMetasFocus' => true,
                'toggleMetasToolbar' => true,
                'toggleMetasInventado' => true,
            ],
            $this->makePlanContext(true, 'pro')
        );

        $this->assertSame([
            'toggleMetasSummary' => false,
            'toggleMetasFocus' => true,
            'toggleMetasToolbar' => true,
        ], $filtered);
    }

    public function testFilterPreferencesKeepsOrcamentoTogglesForProUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'orcamento',
            [
                'toggleOrcSummary' => false,
                'toggleOrcFocus' => true,
                'toggleOrcToolbar' => true,
                'toggleOrcInventado' => true,
            ],
            $this->makePlanContext(true, 'pro')
        );

        $this->assertSame([
            'toggleOrcSummary' => false,
            'toggleOrcFocus' => true,
            'toggleOrcToolbar' => true,
        ], $filtered);
    }

    public function testResolveRelatoriosIncludesSharedDescriptorAndThreePlanMessaging(): void
    {
        $resolved = PageCapabilityResolver::resolve('relatorios', $this->makePlanContext(false, 'free'));

        $this->assertSame('relatorios', $resolved['pageKey']);
        $this->assertTrue($resolved['customizer']['renderOverlay']);
        $this->assertSame([
            'toggleRelOverviewCharts' => 'relOverviewChartsRow',
            'toggleRelSectionInsights' => 'section-insights',
            'toggleRelSectionRelatorios' => 'section-relatorios',
            'toggleRelControls' => 'relControlsRow',
            'toggleRelSectionComparativos' => 'section-comparativos',
        ], $resolved['customizer']['descriptor']['sectionMap']);
        $this->assertSame('Ultra', $resolved['customizer']['descriptor']['lockedState']['tiers'][1]['name']);
        $this->assertSame('Personalizar relatórios', $resolved['customizer']['trigger']['label']);
    }

    public function testResolveLancamentosIncludesSharedDescriptorAndLockedOverlayForFreeUsers(): void
    {
        $resolved = PageCapabilityResolver::resolve('lancamentos', $this->makePlanContext(false, 'free'));

        $this->assertSame('lancamentos', $resolved['pageKey']);
        $this->assertTrue($resolved['customizer']['renderOverlay']);
        $this->assertSame([
            'toggleLanFilters' => 'lanFiltersSection',
            'toggleLanExport' => 'exportCard',
        ], $resolved['customizer']['descriptor']['sectionMap']);
        $this->assertSame('Pro', $resolved['customizer']['descriptor']['lockedState']['tiers'][0]['name']);
        $this->assertSame('Personalizar transações', $resolved['customizer']['trigger']['label']);
    }

    public function testFilterPreferencesDropsRelatoriosTogglesForFreeUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'relatorios',
            [
                'toggleRelOverviewCharts' => true,
                'toggleRelControls' => false,
                'toggleRelSectionInsights' => true,
            ],
            $this->makePlanContext(false, 'free')
        );

        $this->assertSame([
            'toggleRelOverviewCharts' => true,
        ], $filtered);
    }

    public function testFilterPreferencesKeepsRelatoriosTogglesForProUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'relatorios',
            [
                'toggleRelOverviewCharts' => true,
                'toggleRelControls' => false,
                'toggleRelSectionInsights' => true,
                'toggleRelInventado' => true,
            ],
            $this->makePlanContext(true, 'pro')
        );

        $this->assertSame([
            'toggleRelOverviewCharts' => true,
            'toggleRelControls' => false,
            'toggleRelSectionInsights' => true,
        ], $filtered);
    }

    public function testFilterPreferencesDropsLancamentosTogglesForFreeUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'lancamentos',
            [
                'toggleLanFilters' => false,
                'toggleLanExport' => true,
            ],
            $this->makePlanContext(false, 'free')
        );

        $this->assertSame([
            'toggleLanFilters' => false,
        ], $filtered);
    }

    public function testFilterPreferencesKeepsLancamentosTogglesForProUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'lancamentos',
            [
                'toggleLanFilters' => false,
                'toggleLanExport' => true,
                'toggleLanInventado' => true,
            ],
            $this->makePlanContext(true, 'pro')
        );

        $this->assertSame([
            'toggleLanFilters' => false,
            'toggleLanExport' => true,
        ], $filtered);
    }

    public function testResolveRelatoriosExposesUltraIntelligenceFlags(): void
    {
        $resolved = PageCapabilityResolver::resolve('relatorios', $this->makePlanContext(true, 'ultra'));

        $this->assertSame('ultra', $resolved['plan']['plan_tier']);
        $this->assertTrue($resolved['customizer']['canAccessComplete']);
        $this->assertTrue($resolved['customizer']['canCustomize']);
        $this->assertTrue($resolved['customizer']['intelligence']['automaticInsights']);
        $this->assertTrue($resolved['customizer']['intelligence']['balanceForecast']);
    }

    public function testResolveBillingKeepsCustomizationAvailableForFreeUsers(): void
    {
        $resolved = PageCapabilityResolver::resolve('billing', $this->makePlanContext(false, 'free'));

        $this->assertSame('billing', $resolved['pageKey']);
        $this->assertFalse($resolved['customizer']['renderOverlay']);
        $this->assertTrue($resolved['customizer']['canCustomize']);
        $this->assertSame([
            'toggleBillingHeader' => 'billingHeaderSection',
            'toggleBillingPlans' => 'billingPlansSection',
        ], $resolved['customizer']['descriptor']['sectionMap']);
    }

    public function testResolveCategoriasIncludesSharedDescriptorAndLockedOverlayForFreeUsers(): void
    {
        $resolved = PageCapabilityResolver::resolve('categorias', $this->makePlanContext(false, 'free'));

        $this->assertSame('categorias', $resolved['pageKey']);
        $this->assertTrue($resolved['customizer']['renderOverlay']);
        $this->assertSame([
            'toggleCategoriasKpis' => 'categoriasKpis',
            'toggleCategoriasCreateCard' => 'categoriasCreateCard',
        ], $resolved['customizer']['descriptor']['sectionMap']);
        $this->assertSame('Personalizar categorias', $resolved['customizer']['trigger']['label']);
    }

    public function testResolveFinancasIncludesSharedDescriptorAndLockedOverlayForFreeUsers(): void
    {
        $resolved = PageCapabilityResolver::resolve('financas', $this->makePlanContext(false, 'free'));

        $this->assertSame('financas', $resolved['pageKey']);
        $this->assertTrue($resolved['customizer']['renderOverlay']);
        $this->assertSame([
            'toggleFinSummary' => 'finSummarySection',
            'toggleFinOrcActions' => 'finOrcActionsSection',
            'toggleFinMetasActions' => 'finMetasActionsSection',
            'toggleFinInsights' => 'insightsSection',
        ], $resolved['customizer']['descriptor']['sectionMap']);
        $this->assertSame('Personalizar finanças', $resolved['customizer']['trigger']['label']);
    }

    public function testResolveGamificationIncludesSharedDescriptorAndLockedOverlayForFreeUsers(): void
    {
        $resolved = PageCapabilityResolver::resolve('gamification', $this->makePlanContext(false, 'free'));

        $this->assertSame('gamification', $resolved['pageKey']);
        $this->assertTrue($resolved['customizer']['renderOverlay']);
        $this->assertSame([
            'toggleGamHeader' => 'gamHeaderSection',
            'toggleGamProgress' => 'gamProgressSection',
            'toggleGamAchievements' => 'gamAchievementsSection',
            'toggleGamHistory' => 'gamHistorySection',
            'toggleGamLeaderboard' => 'gamLeaderboardSection',
        ], $resolved['customizer']['descriptor']['sectionMap']);
        $this->assertSame('Personalizar gamificação', $resolved['customizer']['trigger']['label']);
    }

    public function testResolvePerfilKeepsCustomizationAvailableForFreeUsers(): void
    {
        $resolved = PageCapabilityResolver::resolve('perfil', $this->makePlanContext(false, 'free'));

        $this->assertSame('perfil', $resolved['pageKey']);
        $this->assertFalse($resolved['customizer']['renderOverlay']);
        $this->assertTrue($resolved['customizer']['canCustomize']);
        $this->assertSame([
            'togglePerfilHeader' => 'profileHeaderSection',
            'togglePerfilTabs' => 'profileTabsSection',
        ], $resolved['customizer']['descriptor']['sectionMap']);
    }

    public function testResolveSysadminKeepsCustomizationAvailableForFreeUsers(): void
    {
        $resolved = PageCapabilityResolver::resolve('sysadmin', $this->makePlanContext(false, 'free'));

        $this->assertSame('sysadmin', $resolved['pageKey']);
        $this->assertFalse($resolved['customizer']['renderOverlay']);
        $this->assertTrue($resolved['customizer']['canCustomize']);
        $this->assertSame([
            'toggleSysStats' => 'sysStatsGrid',
            'toggleSysTabs' => 'sysTabsNav',
            'toggleSysDashboard' => 'panel-dashboard',
            'toggleSysFeedback' => 'panel-feedback',
        ], $resolved['customizer']['descriptor']['sectionMap']);
    }

    public function testFilterPreferencesKeepsBillingTogglesForFreeUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'billing',
            [
                'toggleBillingHeader' => false,
                'toggleBillingPlans' => true,
                'toggleBillingInventado' => true,
            ],
            $this->makePlanContext(false, 'free')
        );

        $this->assertSame([
            'toggleBillingHeader' => false,
            'toggleBillingPlans' => true,
        ], $filtered);
    }

    public function testFilterPreferencesDropsCategoriasTogglesForFreeUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'categorias',
            [
                'toggleCategoriasKpis' => true,
                'toggleCategoriasCreateCard' => false,
            ],
            $this->makePlanContext(false, 'free')
        );

        $this->assertSame([
            'toggleCategoriasCreateCard' => false,
        ], $filtered);
    }

    public function testFilterPreferencesKeepsCategoriasTogglesForProUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'categorias',
            [
                'toggleCategoriasKpis' => false,
                'toggleCategoriasCreateCard' => true,
                'toggleCategoriasInventado' => true,
            ],
            $this->makePlanContext(true, 'pro')
        );

        $this->assertSame([
            'toggleCategoriasKpis' => false,
            'toggleCategoriasCreateCard' => true,
        ], $filtered);
    }

    public function testFilterPreferencesDropsFinancasTogglesForFreeUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'financas',
            [
                'toggleFinSummary' => true,
                'toggleFinOrcActions' => false,
                'toggleFinMetasActions' => false,
                'toggleFinInsights' => true,
            ],
            $this->makePlanContext(false, 'free')
        );

        $this->assertSame([
            'toggleFinOrcActions' => false,
            'toggleFinMetasActions' => false,
        ], $filtered);
    }

    public function testFilterPreferencesKeepsFinancasTogglesForProUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'financas',
            [
                'toggleFinSummary' => false,
                'toggleFinOrcActions' => true,
                'toggleFinMetasActions' => true,
                'toggleFinInsights' => true,
                'toggleFinInventado' => true,
            ],
            $this->makePlanContext(true, 'pro')
        );

        $this->assertSame([
            'toggleFinSummary' => false,
            'toggleFinOrcActions' => true,
            'toggleFinMetasActions' => true,
            'toggleFinInsights' => true,
        ], $filtered);
    }

    public function testFilterPreferencesDropsGamificationTogglesForFreeUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'gamification',
            [
                'toggleGamHeader' => false,
                'toggleGamProgress' => false,
                'toggleGamAchievements' => true,
                'toggleGamHistory' => true,
                'toggleGamLeaderboard' => false,
            ],
            $this->makePlanContext(false, 'free')
        );

        $this->assertSame([
            'toggleGamHeader' => false,
            'toggleGamProgress' => false,
            'toggleGamAchievements' => true,
        ], $filtered);
    }

    public function testFilterPreferencesKeepsGamificationTogglesForProUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'gamification',
            [
                'toggleGamHeader' => false,
                'toggleGamProgress' => true,
                'toggleGamAchievements' => true,
                'toggleGamHistory' => true,
                'toggleGamLeaderboard' => false,
                'toggleGamInventado' => true,
            ],
            $this->makePlanContext(true, 'pro')
        );

        $this->assertSame([
            'toggleGamHeader' => false,
            'toggleGamProgress' => true,
            'toggleGamAchievements' => true,
            'toggleGamHistory' => true,
            'toggleGamLeaderboard' => false,
        ], $filtered);
    }

    public function testFilterPreferencesKeepsPerfilTogglesForFreeUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'perfil',
            [
                'togglePerfilHeader' => false,
                'togglePerfilTabs' => true,
                'togglePerfilInventado' => true,
            ],
            $this->makePlanContext(false, 'free')
        );

        $this->assertSame([
            'togglePerfilHeader' => false,
            'togglePerfilTabs' => true,
        ], $filtered);
    }

    public function testFilterPreferencesKeepsSysadminTogglesForFreeUsers(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'sysadmin',
            [
                'toggleSysStats' => false,
                'toggleSysTabs' => true,
                'toggleSysDashboard' => true,
                'toggleSysFeedback' => false,
                'toggleSysInventado' => true,
            ],
            $this->makePlanContext(false, 'free')
        );

        $this->assertSame([
            'toggleSysStats' => false,
            'toggleSysTabs' => true,
            'toggleSysDashboard' => true,
            'toggleSysFeedback' => false,
        ], $filtered);
    }

    private function makePlanContext(bool $isPro, string $planCode): PlanContext
    {
        $user = $this->getMockBuilder(Usuario::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isPro', 'planoAtual'])
            ->getMock();

        $user->id = 77;
        $user->method('isPro')->willReturn($isPro);
        $user->method('planoAtual')->willReturn((object) ['code' => $planCode]);

        return new PlanContext($user);
    }
}
