<?php
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$faturasPageCapabilities = isset($faturasPageCapabilities) && is_array($faturasPageCapabilities)
    ? $faturasPageCapabilities
    : ((string) ($layoutPageCapabilities['pageKey'] ?? '') === 'faturas'
        ? $layoutPageCapabilities
        : []);

$faturasCustomizerCapabilities = isset($faturasCustomizerCapabilities) && is_array($faturasCustomizerCapabilities)
    ? $faturasCustomizerCapabilities
    : (is_array($faturasPageCapabilities['customizer'] ?? null)
        ? $faturasPageCapabilities['customizer']
        : []);

$faturasCustomizerDescriptor = is_array($faturasCustomizerCapabilities['descriptor'] ?? null)
    ? $faturasCustomizerCapabilities['descriptor']
    : [];

$faturasCanCustomize = (bool) ($faturasCustomizerCapabilities['canCustomize'] ?? false);
$faturasLockedToggles = is_array($faturasCustomizerCapabilities['lockedToggles'] ?? null)
    ? array_values(array_filter(
        $faturasCustomizerCapabilities['lockedToggles'],
        static fn($toggleId): bool => is_string($toggleId) && $toggleId !== ''
    ))
    : [];
$faturasHasLockedToggles = $faturasLockedToggles !== [];
$faturasUpgradeCta = is_array($faturasCustomizerCapabilities['upgradeCta'] ?? null)
    ? $faturasCustomizerCapabilities['upgradeCta']
    : [];

$customizeModal = [
    'title' => (string) ($faturasCustomizerDescriptor['title'] ?? 'Personalizar faturas'),
    'description' => (string) ($faturasCustomizerDescriptor['description'] ?? 'Mantenha uma leitura essencial das faturas ou libere a navegação completa com filtros.'),
    'size' => (string) ($faturasCustomizerDescriptor['size'] ?? 'wide'),
    'locked' => !$faturasCanCustomize && $faturasHasLockedToggles,
    'lockedState' => $faturasHasLockedToggles && is_array($faturasCustomizerDescriptor['lockedState'] ?? null)
        ? $faturasCustomizerDescriptor['lockedState']
        : [],
    'lockedToggleIds' => $faturasLockedToggles,
    'disableInputs' => !$faturasCanCustomize,
    'hideSave' => !$faturasCanCustomize,
    'footerCta' => $faturasHasLockedToggles && !empty($faturasUpgradeCta['show'])
        ? [
            'label' => (string) ($faturasUpgradeCta['label'] ?? 'Desbloquear faturas completas'),
            'href' => defined('BASE_URL') ? BASE_URL . 'billing' : '/billing',
        ]
        : [],
    'renderOverlay' => (bool) ($faturasCustomizerCapabilities['renderOverlay'] ?? true),
    'showPresets' => count(is_array($faturasCustomizerCapabilities['availablePresets'] ?? null) ? $faturasCustomizerCapabilities['availablePresets'] : []) > 1,
    'trigger' => [
        'render' => false,
    ],
    'ids' => is_array($faturasCustomizerDescriptor['ids'] ?? null)
        ? $faturasCustomizerDescriptor['ids']
        : [
            'overlay' => 'faturasCustomizeModalOverlay',
            'title' => 'faturasCustomizeModalTitle',
            'description' => 'faturasCustomizeModalDescription',
            'close' => 'btnCloseCustomizeFaturas',
            'save' => 'btnSaveCustomizeFaturas',
            'presetEssential' => 'btnPresetEssencialFaturas',
            'presetComplete' => 'btnPresetCompletoFaturas',
        ],
    'groups' => is_array($faturasCustomizerDescriptor['groups'] ?? null)
        ? $faturasCustomizerDescriptor['groups']
        : [
            [
                'title' => 'Blocos da tela',
                'items' => [
                    ['id' => 'toggleFaturasHero', 'label' => 'Suas faturas'],
                    ['id' => 'toggleFaturasFiltros', 'label' => 'Painel de filtros'],
                    ['id' => 'toggleFaturasViewToggle', 'label' => 'Toggle de visualização'],
                ],
            ],
        ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
