<?php
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$metasPageCapabilities = isset($metasPageCapabilities) && is_array($metasPageCapabilities)
    ? $metasPageCapabilities
    : ((string) ($layoutPageCapabilities['pageKey'] ?? '') === 'metas'
        ? $layoutPageCapabilities
        : []);

$metasCustomizerCapabilities = isset($metasCustomizerCapabilities) && is_array($metasCustomizerCapabilities)
    ? $metasCustomizerCapabilities
    : (is_array($metasPageCapabilities['customizer'] ?? null)
        ? $metasPageCapabilities['customizer']
        : []);

$metasCustomizerDescriptor = is_array($metasCustomizerCapabilities['descriptor'] ?? null)
    ? $metasCustomizerCapabilities['descriptor']
    : [];

$metasCanCustomize = (bool) ($metasCustomizerCapabilities['canCustomize'] ?? false);
$metasLockedToggles = is_array($metasCustomizerCapabilities['lockedToggles'] ?? null)
    ? array_values(array_filter(
        $metasCustomizerCapabilities['lockedToggles'],
        static fn($toggleId): bool => is_string($toggleId) && $toggleId !== ''
    ))
    : [];
$metasHasLockedToggles = $metasLockedToggles !== [];
$metasUpgradeCta = is_array($metasCustomizerCapabilities['upgradeCta'] ?? null)
    ? $metasCustomizerCapabilities['upgradeCta']
    : [];

$customizeModal = [
    'title' => (string) ($metasCustomizerDescriptor['title'] ?? 'Personalizar metas'),
    'description' => (string) ($metasCustomizerDescriptor['description'] ?? 'Comece com a leitura essencial e libere o topo executivo das metas quando precisar.'),
    'size' => (string) ($metasCustomizerDescriptor['size'] ?? 'wide'),
    'locked' => !$metasCanCustomize && $metasHasLockedToggles,
    'lockedState' => $metasHasLockedToggles && is_array($metasCustomizerDescriptor['lockedState'] ?? null)
        ? $metasCustomizerDescriptor['lockedState']
        : [],
    'lockedToggleIds' => $metasLockedToggles,
    'disableInputs' => !$metasCanCustomize,
    'hideSave' => !$metasCanCustomize,
    'footerCta' => $metasHasLockedToggles && !empty($metasUpgradeCta['show'])
        ? [
            'label' => (string) ($metasUpgradeCta['label'] ?? 'Desbloquear metas completas'),
            'href' => defined('BASE_URL') ? BASE_URL . 'billing' : '/billing',
        ]
        : [],
    'renderOverlay' => (bool) ($metasCustomizerCapabilities['renderOverlay'] ?? true),
    'showPresets' => count(is_array($metasCustomizerCapabilities['availablePresets'] ?? null) ? $metasCustomizerCapabilities['availablePresets'] : []) > 1,
    'trigger' => [
        'render' => false,
    ],
    'ids' => is_array($metasCustomizerDescriptor['ids'] ?? null)
        ? $metasCustomizerDescriptor['ids']
        : [
            'overlay' => 'metasCustomizeModalOverlay',
            'title' => 'metasCustomizeModalTitle',
            'description' => 'metasCustomizeModalDescription',
            'close' => 'btnCloseCustomizeMetas',
            'save' => 'btnSaveCustomizeMetas',
            'presetEssential' => 'btnPresetEssencialMetas',
            'presetComplete' => 'btnPresetCompletoMetas',
        ],
    'groups' => is_array($metasCustomizerDescriptor['groups'] ?? null)
        ? $metasCustomizerDescriptor['groups']
        : [
            [
                'title' => 'Blocos da tela',
                'items' => [
                    ['id' => 'toggleMetasSummary', 'label' => 'Resumo de metas'],
                    ['id' => 'toggleMetasFocus', 'label' => 'Foco do momento'],
                    ['id' => 'toggleMetasToolbar', 'label' => 'Barra de filtros'],
                ],
            ],
        ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
