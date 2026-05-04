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

$dashboardTriggerAction = (string) ($dashboardCustomizerCapabilities['trigger']['action'] ?? 'customize');

$customizeModal = [
    'title' => (string) ($dashboardCustomizerDescriptor['title'] ?? 'Personalizar dashboard'),
    'description' => (string) ($dashboardCustomizerDescriptor['description'] ?? 'Comece no modo essencial e ative extras quando fizer sentido para você.'),
    'size' => (string) ($dashboardCustomizerDescriptor['size'] ?? 'wide'),
    'renderOverlay' => (bool) ($dashboardCustomizerCapabilities['renderOverlay'] ?? true),
    'showPresets' => count(is_array($dashboardCustomizerCapabilities['availablePresets'] ?? null) ? $dashboardCustomizerCapabilities['availablePresets'] : []) > 1,
    'trigger' => [
        'render' => true,
        'id' => (string) ($dashboardCustomizerDescriptor['trigger']['id'] ?? 'btnCustomizeDashboard'),
        'label' => (string) ($dashboardCustomizerCapabilities['trigger']['label'] ?? 'Personalizar dashboard'),
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
