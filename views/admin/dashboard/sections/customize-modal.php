<?php
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$dashboardPageCapabilities = (string) ($layoutPageCapabilities['pageKey'] ?? '') === 'dashboard'
    ? $layoutPageCapabilities
    : [];

$dashboardCustomizerCapabilities = is_array($dashboardPageCapabilities['customizer'] ?? null)
    ? $dashboardPageCapabilities['customizer']
    : [];

$dashboardCustomizerDescriptor = is_array($dashboardCustomizerCapabilities['descriptor'] ?? null)
    ? $dashboardCustomizerCapabilities['descriptor']
    : [];

$dashboardCanCustomize = (bool) ($dashboardCustomizerCapabilities['canCustomize'] ?? false);
$dashboardLockedToggles = is_array($dashboardCustomizerCapabilities['lockedToggles'] ?? null)
    ? array_values(array_filter(
        $dashboardCustomizerCapabilities['lockedToggles'],
        static fn($toggleId): bool => is_string($toggleId) && $toggleId !== ''
    ))
    : [];
$dashboardHasLockedToggles = $dashboardLockedToggles !== [];
$dashboardTriggerAction = (string) ($dashboardCustomizerCapabilities['trigger']['action'] ?? 'customize');
$dashboardUpgradeCta = is_array($dashboardCustomizerCapabilities['upgradeCta'] ?? null)
    ? $dashboardCustomizerCapabilities['upgradeCta']
    : [];

$customizeModal = [
    'title' => (string) ($dashboardCustomizerDescriptor['title'] ?? 'Personalizar dashboard'),
    'description' => (string) ($dashboardCustomizerDescriptor['description'] ?? 'Comece no modo essencial e ative extras quando fizer sentido para você.'),
    'size' => (string) ($dashboardCustomizerDescriptor['size'] ?? 'wide'),
    'locked' => !$dashboardCanCustomize && $dashboardHasLockedToggles,
    'lockedState' => $dashboardHasLockedToggles && is_array($dashboardCustomizerDescriptor['lockedState'] ?? null)
        ? $dashboardCustomizerDescriptor['lockedState']
        : [],
    'lockedToggleIds' => $dashboardLockedToggles,
    'disableInputs' => !$dashboardCanCustomize,
    'hideSave' => !$dashboardCanCustomize,
    'footerCta' => $dashboardHasLockedToggles && !empty($dashboardUpgradeCta['show'])
        ? [
            'label' => (string) ($dashboardUpgradeCta['label'] ?? 'Ver planos Pro e Ultra'),
            'href' => defined('BASE_URL') ? BASE_URL . 'billing' : '/billing',
        ]
        : [],
    'renderOverlay' => (bool) ($dashboardCustomizerCapabilities['renderOverlay'] ?? true),
    'showPresets' => count(is_array($dashboardCustomizerCapabilities['availablePresets'] ?? null) ? $dashboardCustomizerCapabilities['availablePresets'] : []) > 1,
    'trigger' => [
        'render' => true,
        'id' => (string) ($dashboardCustomizerDescriptor['trigger']['id'] ?? 'btnCustomizeDashboard'),
        'label' => (string) ($dashboardCustomizerCapabilities['trigger']['label'] ?? 'Personalizar dashboard'),
        'href' => '',
        'wrapperClass' => (string) ($dashboardCustomizerDescriptor['trigger']['wrapperClass'] ?? 'dash-customize-trigger'),
        'buttonClass' => $dashboardTriggerAction === 'upgrade'
            ? 'lk-customize-open surface-button surface-button--upgrade'
            : 'lk-customize-open surface-button surface-button--subtle',
    ],
    'ids' => is_array($dashboardCustomizerDescriptor['ids'] ?? null)
        ? $dashboardCustomizerDescriptor['ids']
        : [],
    'groups' => is_array($dashboardCustomizerDescriptor['groups'] ?? null)
        ? $dashboardCustomizerDescriptor['groups']
        : [],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
