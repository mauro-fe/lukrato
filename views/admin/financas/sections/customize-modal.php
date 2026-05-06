<?php
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$financasPageCapabilities = isset($financasPageCapabilities) && is_array($financasPageCapabilities)
    ? $financasPageCapabilities
    : ((string) ($layoutPageCapabilities['pageKey'] ?? '') === 'financas'
        ? $layoutPageCapabilities
        : []);

$financasCustomizerCapabilities = isset($financasCustomizerCapabilities) && is_array($financasCustomizerCapabilities)
    ? $financasCustomizerCapabilities
    : (is_array($financasPageCapabilities['customizer'] ?? null)
        ? $financasPageCapabilities['customizer']
        : []);

$financasCustomizerDescriptor = is_array($financasCustomizerCapabilities['descriptor'] ?? null)
    ? $financasCustomizerCapabilities['descriptor']
    : [];

$financasCanCustomize = (bool) ($financasCustomizerCapabilities['canCustomize'] ?? false);
$financasLockedToggles = is_array($financasCustomizerCapabilities['lockedToggles'] ?? null)
    ? array_values(array_filter(
        $financasCustomizerCapabilities['lockedToggles'],
        static fn($toggleId): bool => is_string($toggleId) && $toggleId !== ''
    ))
    : [];
$financasHasLockedToggles = $financasLockedToggles !== [];
$financasTrigger = is_array($financasCustomizerCapabilities['trigger'] ?? null)
    ? $financasCustomizerCapabilities['trigger']
    : [];
$financasUpgradeCta = is_array($financasCustomizerCapabilities['upgradeCta'] ?? null)
    ? $financasCustomizerCapabilities['upgradeCta']
    : [];

$customizeModal = [
    'title' => (string) ($financasCustomizerDescriptor['title'] ?? 'Personalizar finanças'),
    'description' => (string) ($financasCustomizerDescriptor['description'] ?? 'Mantenha a operação essencial das abas ou libere o topo completo com resumos e insights.'),
    'size' => (string) ($financasCustomizerDescriptor['size'] ?? 'wide'),
    'locked' => !$financasCanCustomize && $financasHasLockedToggles,
    'lockedState' => $financasHasLockedToggles && is_array($financasCustomizerDescriptor['lockedState'] ?? null)
        ? $financasCustomizerDescriptor['lockedState']
        : [],
    'lockedToggleIds' => $financasLockedToggles,
    'disableInputs' => !$financasCanCustomize,
    'hideSave' => !$financasCanCustomize,
    'footerCta' => $financasHasLockedToggles && !empty($financasUpgradeCta['show'])
        ? [
            'label' => (string) ($financasUpgradeCta['label'] ?? 'Desbloquear finanças completas'),
            'href' => defined('BASE_URL') ? BASE_URL . 'billing' : '/billing',
        ]
        : [],
    'renderOverlay' => (bool) ($financasCustomizerCapabilities['renderOverlay'] ?? true),
    'showPresets' => count(is_array($financasCustomizerCapabilities['availablePresets'] ?? null) ? $financasCustomizerCapabilities['availablePresets'] : []) > 1,
    'trigger' => [
        'render' => (bool) ($financasTrigger['show'] ?? true),
        'id' => (string) ($financasCustomizerDescriptor['trigger']['id'] ?? 'btnCustomizeFinancas'),
        'label' => (string) ($financasTrigger['label'] ?? 'Personalizar finanças'),
        'wrapperClass' => (string) ($financasCustomizerDescriptor['trigger']['wrapperClass'] ?? 'fin-customize-trigger'),
    ],
    'ids' => is_array($financasCustomizerDescriptor['ids'] ?? null)
        ? $financasCustomizerDescriptor['ids']
        : [
            'overlay' => 'financasCustomizeModalOverlay',
            'title' => 'financasCustomizeModalTitle',
            'description' => 'financasCustomizeModalDescription',
            'close' => 'btnCloseCustomizeFinancas',
            'save' => 'btnSaveCustomizeFinancas',
            'presetEssential' => 'btnPresetEssencialFinancas',
            'presetComplete' => 'btnPresetCompletoFinancas',
        ],
    'groups' => is_array($financasCustomizerDescriptor['groups'] ?? null)
        ? $financasCustomizerDescriptor['groups']
        : [
            [
                'title' => 'Blocos da tela',
                'items' => [
                    ['id' => 'toggleFinSummary', 'label' => 'Cards de resumo'],
                    ['id' => 'toggleFinOrcActions', 'label' => 'Ações da aba orçamentos'],
                    ['id' => 'toggleFinMetasActions', 'label' => 'Ações da aba metas'],
                    ['id' => 'toggleFinInsights', 'label' => 'Insights de orçamentos'],
                ],
            ],
        ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
