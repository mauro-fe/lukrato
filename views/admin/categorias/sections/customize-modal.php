<?php
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$categoriasPageCapabilities = isset($categoriasPageCapabilities) && is_array($categoriasPageCapabilities)
    ? $categoriasPageCapabilities
    : ((string) ($layoutPageCapabilities['pageKey'] ?? '') === 'categorias'
        ? $layoutPageCapabilities
        : []);

$categoriasCustomizerCapabilities = isset($categoriasCustomizerCapabilities) && is_array($categoriasCustomizerCapabilities)
    ? $categoriasCustomizerCapabilities
    : (is_array($categoriasPageCapabilities['customizer'] ?? null)
        ? $categoriasPageCapabilities['customizer']
        : []);

$categoriasCustomizerDescriptor = is_array($categoriasCustomizerCapabilities['descriptor'] ?? null)
    ? $categoriasCustomizerCapabilities['descriptor']
    : [];

$categoriasCanCustomize = (bool) ($categoriasCustomizerCapabilities['canCustomize'] ?? false);
$categoriasLockedToggles = is_array($categoriasCustomizerCapabilities['lockedToggles'] ?? null)
    ? array_values(array_filter(
        $categoriasCustomizerCapabilities['lockedToggles'],
        static fn($toggleId): bool => is_string($toggleId) && $toggleId !== ''
    ))
    : [];
$categoriasHasLockedToggles = $categoriasLockedToggles !== [];
$categoriasUpgradeCta = is_array($categoriasCustomizerCapabilities['upgradeCta'] ?? null)
    ? $categoriasCustomizerCapabilities['upgradeCta']
    : [];

$customizeModal = [
    'title' => (string) ($categoriasCustomizerDescriptor['title'] ?? 'Personalizar categorias'),
    'description' => (string) ($categoriasCustomizerDescriptor['description'] ?? 'Comece com a grade principal e libere os KPIs quando quiser uma visão mais executiva.'),
    'size' => (string) ($categoriasCustomizerDescriptor['size'] ?? 'wide'),
    'locked' => !$categoriasCanCustomize && $categoriasHasLockedToggles,
    'lockedState' => $categoriasHasLockedToggles && is_array($categoriasCustomizerDescriptor['lockedState'] ?? null)
        ? $categoriasCustomizerDescriptor['lockedState']
        : [],
    'lockedToggleIds' => $categoriasLockedToggles,
    'disableInputs' => !$categoriasCanCustomize,
    'hideSave' => !$categoriasCanCustomize,
    'footerCta' => $categoriasHasLockedToggles && !empty($categoriasUpgradeCta['show'])
        ? [
            'label' => (string) ($categoriasUpgradeCta['label'] ?? 'Desbloquear categorias completas'),
            'href' => defined('BASE_URL') ? BASE_URL . 'billing' : '/billing',
        ]
        : [],
    'renderOverlay' => (bool) ($categoriasCustomizerCapabilities['renderOverlay'] ?? true),
    'showPresets' => count(is_array($categoriasCustomizerCapabilities['availablePresets'] ?? null) ? $categoriasCustomizerCapabilities['availablePresets'] : []) > 1,
    'trigger' => [
        'render' => false,
    ],
    'ids' => is_array($categoriasCustomizerDescriptor['ids'] ?? null)
        ? $categoriasCustomizerDescriptor['ids']
        : [
            'overlay' => 'categoriasCustomizeModalOverlay',
            'title' => 'categoriasCustomizeModalTitle',
            'description' => 'categoriasCustomizeModalDescription',
            'close' => 'btnCloseCustomizeCategorias',
            'save' => 'btnSaveCustomizeCategorias',
            'presetEssential' => 'btnPresetEssencialCategorias',
            'presetComplete' => 'btnPresetCompletoCategorias',
        ],
    'groups' => is_array($categoriasCustomizerDescriptor['groups'] ?? null)
        ? $categoriasCustomizerDescriptor['groups']
        : [
            [
                'title' => 'Blocos da tela',
                'items' => [
                    ['id' => 'toggleCategoriasKpis', 'label' => 'Cards de KPI'],
                    ['id' => 'toggleCategoriasCreateCard', 'label' => 'Card de criação'],
                ],
            ],
        ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
