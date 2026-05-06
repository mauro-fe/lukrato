<?php
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$contasPageCapabilities = (string) ($layoutPageCapabilities['pageKey'] ?? '') === 'contas'
    ? $layoutPageCapabilities
    : [];

$contasCustomizerCapabilities = is_array($contasPageCapabilities['customizer'] ?? null)
    ? $contasPageCapabilities['customizer']
    : [];

$contasCustomizerDescriptor = is_array($contasCustomizerCapabilities['descriptor'] ?? null)
    ? $contasCustomizerCapabilities['descriptor']
    : [];

$contasCanCustomize = (bool) ($contasCustomizerCapabilities['canCustomize'] ?? false);
$contasLockedToggles = is_array($contasCustomizerCapabilities['lockedToggles'] ?? null)
    ? array_values(array_filter(
        $contasCustomizerCapabilities['lockedToggles'],
        static fn($toggleId): bool => is_string($toggleId) && $toggleId !== ''
    ))
    : [];
$contasHasLockedToggles = $contasLockedToggles !== [];
$contasUpgradeCta = is_array($contasCustomizerCapabilities['upgradeCta'] ?? null)
    ? $contasCustomizerCapabilities['upgradeCta']
    : [];

$customizeModal = [
    'title' => (string) ($contasCustomizerDescriptor['title'] ?? 'Personalizar contas'),
    'description' => (string) ($contasCustomizerDescriptor['description'] ?? 'Escolha entre uma visão essencial das contas ou o topo completo com KPIs.'),
    'size' => (string) ($contasCustomizerDescriptor['size'] ?? 'wide'),
    'locked' => !$contasCanCustomize && $contasHasLockedToggles,
    'lockedState' => $contasHasLockedToggles && is_array($contasCustomizerDescriptor['lockedState'] ?? null)
        ? $contasCustomizerDescriptor['lockedState']
        : [],
    'lockedToggleIds' => $contasLockedToggles,
    'disableInputs' => !$contasCanCustomize,
    'hideSave' => !$contasCanCustomize,
    'footerCta' => $contasHasLockedToggles && !empty($contasUpgradeCta['show'])
        ? [
            'label' => (string) ($contasUpgradeCta['label'] ?? 'Desbloquear contas completas'),
            'href' => defined('BASE_URL') ? BASE_URL . 'billing' : '/billing',
        ]
        : [],
    'renderOverlay' => (bool) ($contasCustomizerCapabilities['renderOverlay'] ?? true),
    'showPresets' => count(is_array($contasCustomizerCapabilities['availablePresets'] ?? null) ? $contasCustomizerCapabilities['availablePresets'] : []) > 1,
    'trigger' => [
        'render' => false,
    ],
    'ids' => is_array($contasCustomizerDescriptor['ids'] ?? null)
        ? $contasCustomizerDescriptor['ids']
        : [],
    'groups' => is_array($contasCustomizerDescriptor['groups'] ?? null)
        ? $contasCustomizerDescriptor['groups']
        : [],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
