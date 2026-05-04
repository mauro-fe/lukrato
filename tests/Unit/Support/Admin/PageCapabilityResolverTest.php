<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Admin;

use Application\Models\Usuario;
use Application\Services\Plan\PlanContext;
use Application\Support\Admin\PageCapabilityResolver;
use PHPUnit\Framework\TestCase;

class PageCapabilityResolverTest extends TestCase
{
    public function testResolveDashboardIncludesSharedDescriptorAndHidesOverlayForFreeUsers(): void
    {
        $resolved = PageCapabilityResolver::resolve('dashboard', $this->makePlanContext(false, 'free'));

        $this->assertSame('dashboard', $resolved['pageKey']);
        $this->assertFalse($resolved['customizer']['renderOverlay']);
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

        $this->assertSame([], $filtered);
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

    public function testFilterPreferencesLeavesOtherPagesUntouched(): void
    {
        $filtered = PageCapabilityResolver::filterPreferences(
            'relatorios',
            [
                'toggleRelOverviewCharts' => true,
                'toggleRelControls' => false,
            ],
            $this->makePlanContext(false, 'free')
        );

        $this->assertSame([
            'toggleRelOverviewCharts' => true,
            'toggleRelControls' => false,
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
