<?php

declare(strict_types=1);

namespace Application\Support\Admin;

use Application\Services\Plan\PlanContext;

final class PageCapabilityResolver
{
    /**
     * @return array<string,mixed>
     */
    public static function resolve(string $pageKey, ?PlanContext $planContext = null): array
    {
        $normalizedPageKey = strtolower(trim($pageKey));
        if ($normalizedPageKey === '') {
            $normalizedPageKey = 'dashboard';
        }

        $planSummary = $planContext?->summary('plan_tier')
            ?? PlanContext::summaryForTier('free', 'plan_tier');

        return match ($normalizedPageKey) {
            'billing' => self::resolveBilling($planSummary, $planContext),
            'categorias' => self::resolveCategorias($planSummary, $planContext),
            'dashboard' => self::resolveDashboard($planSummary, $planContext),
            'cartoes' => self::resolveCartoes($planSummary, $planContext),
            'contas' => self::resolveContas($planSummary, $planContext),
            'faturas' => self::resolveFaturas($planSummary, $planContext),
            'financas' => self::resolveFinancas($planSummary, $planContext),
            'gamification' => self::resolveGamification($planSummary, $planContext),
            'importacoes' => self::resolveImportacoes($planSummary, $planContext),
            'lancamentos' => self::resolveLancamentos($planSummary, $planContext),
            'metas' => self::resolveMetas($planSummary, $planContext),
            'orcamento' => self::resolveOrcamento($planSummary, $planContext),
            'perfil' => self::resolvePerfil($planSummary, $planContext),
            'relatorios' => self::resolveRelatorios($planSummary, $planContext),
            'sysadmin' => self::resolveSysadmin($planSummary, $planContext),
            default => self::resolveDefault($normalizedPageKey, $planSummary, $planContext),
        };
    }

    /**
     * @param array<string,bool> $preferences
     * @return array<string,bool>
     */
    public static function filterPreferences(string $pageKey, array $preferences, ?PlanContext $planContext = null): array
    {
        $resolved = self::resolve($pageKey, $planContext);
        $customizer = is_array($resolved['customizer'] ?? null)
            ? $resolved['customizer']
            : [];

        if (($customizer['enabled'] ?? false) !== true) {
            return $preferences;
        }

        if (($customizer['canCustomize'] ?? false) !== true) {
            return [];
        }

        $availableToggles = is_array($customizer['availableToggles'] ?? null)
            ? array_values(array_filter(
                $customizer['availableToggles'],
                static fn($toggleKey): bool => is_string($toggleKey) && $toggleKey !== ''
            ))
            : [];

        if ($availableToggles === []) {
            return [];
        }

        return array_intersect_key($preferences, array_fill_keys($availableToggles, true));
    }

    /**
     * @param array<string,mixed> $planSummary
     * @return array<string,mixed>
     */
    private static function resolveDashboard(array $planSummary, ?PlanContext $planContext): array
    {
        $canAccessComplete = $planContext?->allows('dashboard_avancado') ?? (bool) ($planSummary['is_pro'] ?? false);
        return self::resolvePlanGatedPage(
            'dashboard',
            $planSummary,
            $planContext,
            $canAccessComplete,
            'Personalizar dashboard',
            'Desbloquear dashboard completo'
        );
    }

    /**
     * @param array<string,mixed> $planSummary
     * @return array<string,mixed>
     */
    private static function resolveContas(array $planSummary, ?PlanContext $planContext): array
    {
        $canAccessComplete = (bool) ($planSummary['is_pro'] ?? false);
        return self::resolvePlanGatedPage(
            'contas',
            $planSummary,
            $planContext,
            $canAccessComplete,
            'Personalizar contas',
            'Desbloquear contas completas'
        );
    }

    /**
     * @param array<string,mixed> $planSummary
     * @return array<string,mixed>
     */
    private static function resolveCartoes(array $planSummary, ?PlanContext $planContext): array
    {
        $canAccessComplete = (bool) ($planSummary['is_pro'] ?? false);
        return self::resolvePlanGatedPage(
            'cartoes',
            $planSummary,
            $planContext,
            $canAccessComplete,
            'Personalizar cartões',
            'Desbloquear cartões completos'
        );
    }

    /**
     * @param array<string,mixed> $planSummary
     * @return array<string,mixed>
     */
    private static function resolveFaturas(array $planSummary, ?PlanContext $planContext): array
    {
        $canAccessComplete = (bool) ($planSummary['is_pro'] ?? false);
        return self::resolvePlanGatedPage(
            'faturas',
            $planSummary,
            $planContext,
            $canAccessComplete,
            'Personalizar faturas',
            'Desbloquear faturas completas'
        );
    }

    /**
     * @param array<string,mixed> $planSummary
     * @return array<string,mixed>
     */
    private static function resolveImportacoes(array $planSummary, ?PlanContext $planContext): array
    {
        $canAccessComplete = (bool) ($planSummary['is_pro'] ?? false);
        return self::resolvePlanGatedPage(
            'importacoes',
            $planSummary,
            $planContext,
            $canAccessComplete,
            'Personalizar importações',
            'Desbloquear importações completas'
        );
    }

    /**
     * @param array<string,mixed> $planSummary
     * @return array<string,mixed>
     */
    private static function resolveRelatorios(array $planSummary, ?PlanContext $planContext): array
    {
        $canAccessComplete = $planContext?->allows('reports') ?? (bool) ($planSummary['is_pro'] ?? false);
        return self::resolvePlanGatedPage(
            'relatorios',
            $planSummary,
            $planContext,
            $canAccessComplete,
            'Personalizar relatórios',
            'Desbloquear relatórios completos'
        );
    }

    /**
     * @param array<string,mixed> $planSummary
     * @return array<string,mixed>
     */
    private static function resolveLancamentos(array $planSummary, ?PlanContext $planContext): array
    {
        $canAccessComplete = (bool) ($planSummary['is_pro'] ?? false);
        return self::resolvePlanGatedPage(
            'lancamentos',
            $planSummary,
            $planContext,
            $canAccessComplete,
            'Personalizar transações',
            'Desbloquear transações completas'
        );
    }

    /**
     * @param array<string,mixed> $planSummary
     * @return array<string,mixed>
     */
    private static function resolveMetas(array $planSummary, ?PlanContext $planContext): array
    {
        $canAccessComplete = (bool) ($planSummary['is_pro'] ?? false);
        return self::resolvePlanGatedPage(
            'metas',
            $planSummary,
            $planContext,
            $canAccessComplete,
            'Personalizar metas',
            'Desbloquear metas completas'
        );
    }

    /**
     * @param array<string,mixed> $planSummary
     * @return array<string,mixed>
     */
    private static function resolveOrcamento(array $planSummary, ?PlanContext $planContext): array
    {
        $canAccessComplete = (bool) ($planSummary['is_pro'] ?? false);
        return self::resolvePlanGatedPage(
            'orcamento',
            $planSummary,
            $planContext,
            $canAccessComplete,
            'Personalizar orçamento',
            'Desbloquear orçamento completo'
        );
    }

    /**
     * @param array<string,mixed> $planSummary
     * @return array<string,mixed>
     */
    private static function resolveBilling(array $planSummary, ?PlanContext $planContext): array
    {
        $descriptor = PageCustomizerCatalog::resolve('billing');
        $essentialPreferences = PageCustomizerCatalog::preferencesForPreset('billing', 'essential');
        $completePreferences = PageCustomizerCatalog::preferencesForPreset('billing', 'complete');
        $allToggleKeys = PageCustomizerCatalog::toggleKeys('billing');

        return [
            'pageKey' => 'billing',
            'plan' => $planSummary,
            'customizer' => [
                'enabled' => true,
                'mode' => 'complete',
                'canAccessComplete' => true,
                'canCustomize' => true,
                'renderOverlay' => false,
                'availablePresets' => ['complete'],
                'availableToggles' => $allToggleKeys,
                'lockedToggles' => [],
                'essentialPreferences' => $essentialPreferences,
                'completePreferences' => $completePreferences,
                'forcedPreferences' => null,
                'descriptor' => $descriptor,
                'trigger' => [
                    'show' => true,
                    'label' => 'Personalizar assinatura',
                    'action' => 'customize',
                    'target' => null,
                ],
                'upgradeCta' => [
                    'show' => false,
                    'label' => '',
                    'target' => null,
                ],
                'intelligence' => [
                    'automaticInsights' => (bool) ($planContext?->allows('insights_automaticos') ?? false),
                    'balanceForecast' => (bool) ($planContext?->allows('previsao_saldo') ?? false),
                ],
            ],
        ];
    }

    /**
     * @param array<string,mixed> $planSummary
     * @return array<string,mixed>
     */
    private static function resolveCategorias(array $planSummary, ?PlanContext $planContext): array
    {
        $canAccessComplete = (bool) ($planSummary['is_pro'] ?? false);
        return self::resolvePlanGatedPage(
            'categorias',
            $planSummary,
            $planContext,
            $canAccessComplete,
            'Personalizar categorias',
            'Desbloquear categorias completas'
        );
    }

    /**
     * @param array<string,mixed> $planSummary
     * @return array<string,mixed>
     */
    private static function resolveFinancas(array $planSummary, ?PlanContext $planContext): array
    {
        $canAccessComplete = (bool) ($planSummary['is_pro'] ?? false);
        return self::resolvePlanGatedPage(
            'financas',
            $planSummary,
            $planContext,
            $canAccessComplete,
            'Personalizar finanças',
            'Desbloquear finanças completas'
        );
    }

    /**
     * @param array<string,mixed> $planSummary
     * @return array<string,mixed>
     */
    private static function resolveGamification(array $planSummary, ?PlanContext $planContext): array
    {
        $canAccessComplete = (bool) ($planSummary['is_pro'] ?? false);
        return self::resolvePlanGatedPage(
            'gamification',
            $planSummary,
            $planContext,
            $canAccessComplete,
            'Personalizar gamificação',
            'Desbloquear gamificação completa'
        );
    }

    /**
     * @param array<string,mixed> $planSummary
     * @return array<string,mixed>
     */
    private static function resolvePerfil(array $planSummary, ?PlanContext $planContext): array
    {
        $descriptor = PageCustomizerCatalog::resolve('perfil');
        $essentialPreferences = PageCustomizerCatalog::preferencesForPreset('perfil', 'essential');
        $completePreferences = PageCustomizerCatalog::preferencesForPreset('perfil', 'complete');
        $allToggleKeys = PageCustomizerCatalog::toggleKeys('perfil');

        return [
            'pageKey' => 'perfil',
            'plan' => $planSummary,
            'customizer' => [
                'enabled' => true,
                'mode' => 'complete',
                'canAccessComplete' => true,
                'canCustomize' => true,
                'renderOverlay' => false,
                'availablePresets' => ['complete'],
                'availableToggles' => $allToggleKeys,
                'lockedToggles' => [],
                'essentialPreferences' => $essentialPreferences,
                'completePreferences' => $completePreferences,
                'forcedPreferences' => null,
                'descriptor' => $descriptor,
                'trigger' => [
                    'show' => true,
                    'label' => 'Personalizar perfil',
                    'action' => 'customize',
                    'target' => null,
                ],
                'upgradeCta' => [
                    'show' => false,
                    'label' => '',
                    'target' => null,
                ],
                'intelligence' => [
                    'automaticInsights' => (bool) ($planContext?->allows('insights_automaticos') ?? false),
                    'balanceForecast' => (bool) ($planContext?->allows('previsao_saldo') ?? false),
                ],
            ],
        ];
    }

    /**
     * @param array<string,mixed> $planSummary
     * @return array<string,mixed>
     */
    private static function resolveSysadmin(array $planSummary, ?PlanContext $planContext): array
    {
        $descriptor = PageCustomizerCatalog::resolve('sysadmin');
        $essentialPreferences = PageCustomizerCatalog::preferencesForPreset('sysadmin', 'essential');
        $completePreferences = PageCustomizerCatalog::preferencesForPreset('sysadmin', 'complete');
        $allToggleKeys = PageCustomizerCatalog::toggleKeys('sysadmin');

        return [
            'pageKey' => 'sysadmin',
            'plan' => $planSummary,
            'customizer' => [
                'enabled' => true,
                'mode' => 'complete',
                'canAccessComplete' => true,
                'canCustomize' => true,
                'renderOverlay' => false,
                'availablePresets' => ['essential', 'complete'],
                'availableToggles' => $allToggleKeys,
                'lockedToggles' => [],
                'essentialPreferences' => $essentialPreferences,
                'completePreferences' => $completePreferences,
                'forcedPreferences' => null,
                'descriptor' => $descriptor,
                'trigger' => [
                    'show' => true,
                    'label' => 'Personalizar sysadmin',
                    'action' => 'customize',
                    'target' => null,
                ],
                'upgradeCta' => [
                    'show' => false,
                    'label' => '',
                    'target' => null,
                ],
                'intelligence' => [
                    'automaticInsights' => (bool) ($planContext?->allows('insights_automaticos') ?? false),
                    'balanceForecast' => (bool) ($planContext?->allows('previsao_saldo') ?? false),
                ],
            ],
        ];
    }

    /**
     * @param array<string,mixed> $planSummary
     * @return array<string,mixed>
     */
    private static function resolvePlanGatedPage(
        string $pageKey,
        array $planSummary,
        ?PlanContext $planContext,
        bool $canAccessComplete,
        string $customizeLabel,
        string $upgradeLabel
    ): array {
        $descriptor = PageCustomizerCatalog::resolve($pageKey);
        [$availableToggles, $lockedToggles] = self::splitToggleAccess($descriptor, $canAccessComplete);
        $canCustomize = $availableToggles !== [];
        $upgradeTarget = $planSummary['upgrade_target'] ?? 'pro';
        $essentialPreferences = PageCustomizerCatalog::preferencesForPreset($pageKey, 'essential');
        $completePreferences = PageCustomizerCatalog::preferencesForPreset($pageKey, 'complete');

        return [
            'pageKey' => $pageKey,
            'plan' => $planSummary,
            'customizer' => [
                'enabled' => true,
                'mode' => $canAccessComplete ? 'complete' : 'essential',
                'canAccessComplete' => $canAccessComplete,
                'canCustomize' => $canCustomize,
                'renderOverlay' => true,
                'availablePresets' => $canAccessComplete ? ['essential', 'complete'] : ['essential'],
                'availableToggles' => $availableToggles,
                'lockedToggles' => $lockedToggles,
                'essentialPreferences' => $essentialPreferences,
                'completePreferences' => $completePreferences,
                'forcedPreferences' => $canAccessComplete ? null : $essentialPreferences,
                'descriptor' => $descriptor,
                'trigger' => [
                    'show' => true,
                    'label' => $canCustomize ? $customizeLabel : $upgradeLabel,
                    'action' => $canCustomize ? 'customize' : 'upgrade',
                    'target' => $canCustomize ? null : $upgradeTarget,
                ],
                'upgradeCta' => [
                    'show' => !$canAccessComplete && $lockedToggles !== [],
                    'label' => $upgradeLabel,
                    'target' => !$canAccessComplete && $lockedToggles !== [] ? $upgradeTarget : null,
                ],
                'intelligence' => [
                    'automaticInsights' => (bool) ($planContext?->allows('insights_automaticos') ?? false),
                    'balanceForecast' => (bool) ($planContext?->allows('previsao_saldo') ?? false),
                ],
            ],
        ];
    }

    /**
     * @param array<string,mixed> $descriptor
     * @return array{0:list<string>,1:list<string>}
     */
    private static function splitToggleAccess(array $descriptor, bool $canAccessComplete): array
    {
        $items = is_array($descriptor['items'] ?? null)
            ? $descriptor['items']
            : [];
        $availableToggles = [];
        $lockedToggles = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $toggleId = trim((string) ($item['id'] ?? ''));
            if ($toggleId === '') {
                continue;
            }

            $plan = strtolower(trim((string) ($item['plan'] ?? 'free')));
            if (!$canAccessComplete && $plan !== 'free') {
                $lockedToggles[] = $toggleId;
                continue;
            }

            $availableToggles[] = $toggleId;
        }

        return [$availableToggles, $lockedToggles];
    }

    /**
     * @param array<string,mixed> $planSummary
     * @return array<string,mixed>
     */
    private static function resolveDefault(string $pageKey, array $planSummary, ?PlanContext $planContext): array
    {
        return [
            'pageKey' => $pageKey,
            'plan' => $planSummary,
            'customizer' => [
                'enabled' => false,
                'mode' => (bool) ($planSummary['is_pro'] ?? false) ? 'complete' : 'essential',
                'canAccessComplete' => (bool) ($planSummary['is_pro'] ?? false),
                'canCustomize' => false,
                'renderOverlay' => false,
                'availablePresets' => [],
                'availableToggles' => [],
                'lockedToggles' => [],
                'essentialPreferences' => [],
                'completePreferences' => [],
                'forcedPreferences' => null,
                'descriptor' => PageCustomizerCatalog::resolve($pageKey),
                'trigger' => [
                    'show' => false,
                    'label' => '',
                    'action' => 'customize',
                    'target' => null,
                ],
                'upgradeCta' => [
                    'show' => false,
                    'label' => '',
                    'target' => null,
                ],
                'intelligence' => [
                    'automaticInsights' => (bool) ($planContext?->allows('insights_automaticos') ?? false),
                    'balanceForecast' => (bool) ($planContext?->allows('previsao_saldo') ?? false),
                ],
            ],
        ];
    }
}
