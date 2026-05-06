<?php
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$reportsPageCapabilities = (string) ($layoutPageCapabilities['pageKey'] ?? '') === 'relatorios'
    ? $layoutPageCapabilities
    : [];

$reportsCustomizerCapabilities = is_array($reportsPageCapabilities['customizer'] ?? null)
    ? $reportsPageCapabilities['customizer']
    : [];

$reportsCustomizerDescriptor = is_array($reportsCustomizerCapabilities['descriptor'] ?? null)
    ? $reportsCustomizerCapabilities['descriptor']
    : [];

$reportsCanCustomize = (bool) ($reportsCustomizerCapabilities['canCustomize'] ?? false);
$reportsLockedToggles = is_array($reportsCustomizerCapabilities['lockedToggles'] ?? null)
    ? array_values(array_filter(
        $reportsCustomizerCapabilities['lockedToggles'],
        static fn($toggleId): bool => is_string($toggleId) && $toggleId !== ''
    ))
    : [];
$reportsHasLockedToggles = $reportsLockedToggles !== [];
$reportsUpgradeCta = is_array($reportsCustomizerCapabilities['upgradeCta'] ?? null)
    ? $reportsCustomizerCapabilities['upgradeCta']
    : [];

$customizeModal = [
    'title' => (string) ($reportsCustomizerDescriptor['title'] ?? 'Personalizar relatórios'),
    'description' => (string) ($reportsCustomizerDescriptor['description'] ?? 'Escolha entre um modo essencial, mais direto, ou a leitura completa da página.'),
    'size' => (string) ($reportsCustomizerDescriptor['size'] ?? 'wide'),
    'locked' => !$reportsCanCustomize && $reportsHasLockedToggles,
    'lockedState' => $reportsHasLockedToggles && is_array($reportsCustomizerDescriptor['lockedState'] ?? null)
        ? $reportsCustomizerDescriptor['lockedState']
        : [],
    'lockedToggleIds' => $reportsLockedToggles,
    'disableInputs' => !$reportsCanCustomize,
    'hideSave' => !$reportsCanCustomize,
    'footerCta' => $reportsHasLockedToggles && !empty($reportsUpgradeCta['show'])
        ? [
            'label' => (string) ($reportsUpgradeCta['label'] ?? 'Ver planos Pro e Ultra'),
            'href' => defined('BASE_URL') ? BASE_URL . 'billing' : '/billing',
        ]
        : [],
    'renderOverlay' => (bool) ($reportsCustomizerCapabilities['renderOverlay'] ?? true),
    'showPresets' => count(is_array($reportsCustomizerCapabilities['availablePresets'] ?? null) ? $reportsCustomizerCapabilities['availablePresets'] : []) > 1,
    'ids' => is_array($reportsCustomizerDescriptor['ids'] ?? null)
        ? $reportsCustomizerDescriptor['ids']
        : [],
    'groups' => is_array($reportsCustomizerDescriptor['groups'] ?? null)
        ? $reportsCustomizerDescriptor['groups']
        : [],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
