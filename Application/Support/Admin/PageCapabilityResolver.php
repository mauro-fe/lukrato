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
            'dashboard' => self::resolveDashboard($planSummary, $planContext),
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
        $descriptor = PageCustomizerCatalog::resolve('dashboard');
        $canAccessComplete = $planContext?->allows('dashboard_avancado') ?? (bool) ($planSummary['is_pro'] ?? false);
        $canCustomize = $canAccessComplete;
        $upgradeTarget = $planSummary['upgrade_target'] ?? 'pro';
        $essentialPreferences = PageCustomizerCatalog::preferencesForPreset('dashboard', 'essential');
        $completePreferences = PageCustomizerCatalog::preferencesForPreset('dashboard', 'complete');
        $allToggleKeys = PageCustomizerCatalog::toggleKeys('dashboard');

        return [
            'pageKey' => 'dashboard',
            'plan' => $planSummary,
            'customizer' => [
                'enabled' => true,
                'mode' => $canCustomize ? 'complete' : 'essential',
                'canAccessComplete' => $canAccessComplete,
                'canCustomize' => $canCustomize,
                'renderOverlay' => $canCustomize,
                'availablePresets' => $canCustomize ? ['essential', 'complete'] : ['essential'],
                'availableToggles' => $canCustomize ? $allToggleKeys : [],
                'lockedToggles' => $canCustomize ? [] : $allToggleKeys,
                'essentialPreferences' => $essentialPreferences,
                'completePreferences' => $completePreferences,
                'forcedPreferences' => $canCustomize ? null : $essentialPreferences,
                'descriptor' => $descriptor,
                'trigger' => [
                    'show' => true,
                    'label' => $canCustomize ? 'Personalizar dashboard' : 'Desbloquear dashboard completo',
                    'action' => $canCustomize ? 'customize' : 'upgrade',
                    'target' => $canCustomize ? null : $upgradeTarget,
                ],
                'upgradeCta' => [
                    'show' => !$canCustomize && $upgradeTarget !== null,
                    'label' => 'Desbloquear dashboard completo',
                    'target' => !$canCustomize ? $upgradeTarget : null,
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
