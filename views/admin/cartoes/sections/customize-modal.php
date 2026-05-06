<?php
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$cartoesPageCapabilities = isset($cartoesPageCapabilities) && is_array($cartoesPageCapabilities)
    ? $cartoesPageCapabilities
    : ((string) ($layoutPageCapabilities['pageKey'] ?? '') === 'cartoes'
        ? $layoutPageCapabilities
        : []);

$cartoesCustomizerCapabilities = isset($cartoesCustomizerCapabilities) && is_array($cartoesCustomizerCapabilities)
    ? $cartoesCustomizerCapabilities
    : (is_array($cartoesPageCapabilities['customizer'] ?? null)
        ? $cartoesPageCapabilities['customizer']
        : []);

$cartoesCustomizerDescriptor = is_array($cartoesCustomizerCapabilities['descriptor'] ?? null)
    ? $cartoesCustomizerCapabilities['descriptor']
    : [];

$cartoesCanCustomize = (bool) ($cartoesCustomizerCapabilities['canCustomize'] ?? false);
$cartoesLockedToggles = is_array($cartoesCustomizerCapabilities['lockedToggles'] ?? null)
    ? array_values(array_filter(
        $cartoesCustomizerCapabilities['lockedToggles'],
        static fn($toggleId): bool => is_string($toggleId) && $toggleId !== ''
    ))
    : [];
$cartoesHasLockedToggles = $cartoesLockedToggles !== [];
$cartoesTrigger = is_array($cartoesCustomizerCapabilities['trigger'] ?? null)
    ? $cartoesCustomizerCapabilities['trigger']
    : [];
$cartoesUpgradeCta = is_array($cartoesCustomizerCapabilities['upgradeCta'] ?? null)
    ? $cartoesCustomizerCapabilities['upgradeCta']
    : [];

$customizeModal = [
    'title' => (string) ($cartoesCustomizerDescriptor['title'] ?? 'Personalizar cartões'),
    'description' => (string) ($cartoesCustomizerDescriptor['description'] ?? 'Escolha entre a lista essencial de cartões ou a tela completa com resumo e filtros.'),
    'size' => (string) ($cartoesCustomizerDescriptor['size'] ?? 'wide'),
    'locked' => !$cartoesCanCustomize && $cartoesHasLockedToggles,
    'lockedState' => $cartoesHasLockedToggles && is_array($cartoesCustomizerDescriptor['lockedState'] ?? null)
        ? $cartoesCustomizerDescriptor['lockedState']
        : [],
    'lockedToggleIds' => $cartoesLockedToggles,
    'disableInputs' => !$cartoesCanCustomize,
    'hideSave' => !$cartoesCanCustomize,
    'footerCta' => $cartoesHasLockedToggles && !empty($cartoesUpgradeCta['show'])
        ? [
            'label' => (string) ($cartoesUpgradeCta['label'] ?? 'Desbloquear cartões completos'),
            'href' => defined('BASE_URL') ? BASE_URL . 'billing' : '/billing',
        ]
        : [],
    'renderOverlay' => (bool) ($cartoesCustomizerCapabilities['renderOverlay'] ?? true),
    'showPresets' => count(is_array($cartoesCustomizerCapabilities['availablePresets'] ?? null) ? $cartoesCustomizerCapabilities['availablePresets'] : []) > 1,
    'trigger' => [
        'render' => (bool) ($cartoesTrigger['show'] ?? true),
        'id' => (string) ($cartoesCustomizerDescriptor['trigger']['id'] ?? 'btnCustomizeCartoes'),
        'label' => (string) ($cartoesTrigger['label'] ?? 'Personalizar cartões'),
        'wrapperClass' => (string) ($cartoesCustomizerDescriptor['trigger']['wrapperClass'] ?? 'cart-customize-trigger'),
    ],
    'ids' => is_array($cartoesCustomizerDescriptor['ids'] ?? null)
        ? $cartoesCustomizerDescriptor['ids']
        : [
            'overlay' => 'cartoesCustomizeModalOverlay',
            'title' => 'cartoesCustomizeModalTitle',
            'description' => 'cartoesCustomizeModalDescription',
            'close' => 'btnCloseCustomizeCartoes',
            'save' => 'btnSaveCustomizeCartoes',
            'presetEssential' => 'btnPresetEssencialCartoes',
            'presetComplete' => 'btnPresetCompletoCartoes',
        ],
    'groups' => is_array($cartoesCustomizerDescriptor['groups'] ?? null)
        ? $cartoesCustomizerDescriptor['groups']
        : [
            [
                'title' => 'Blocos da tela',
                'items' => [
                    ['id' => 'toggleCartoesKpis', 'label' => 'Resumo consolidado'],
                    ['id' => 'toggleCartoesToolbar', 'label' => 'Barra de filtros'],
                ],
            ],
        ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
