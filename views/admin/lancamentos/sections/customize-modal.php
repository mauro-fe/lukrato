<?php
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$lancamentosPageCapabilities = (string) ($layoutPageCapabilities['pageKey'] ?? '') === 'lancamentos'
    ? $layoutPageCapabilities
    : [];

$lancamentosCustomizerCapabilities = is_array($lancamentosPageCapabilities['customizer'] ?? null)
    ? $lancamentosPageCapabilities['customizer']
    : [];

$lancamentosCustomizerDescriptor = is_array($lancamentosCustomizerCapabilities['descriptor'] ?? null)
    ? $lancamentosCustomizerCapabilities['descriptor']
    : [];

$lancamentosCanCustomize = (bool) ($lancamentosCustomizerCapabilities['canCustomize'] ?? false);
$lancamentosLockedToggles = is_array($lancamentosCustomizerCapabilities['lockedToggles'] ?? null)
    ? array_values(array_filter(
        $lancamentosCustomizerCapabilities['lockedToggles'],
        static fn($toggleId): bool => is_string($toggleId) && $toggleId !== ''
    ))
    : [];
$lancamentosHasLockedToggles = $lancamentosLockedToggles !== [];
$lancamentosTriggerAction = (string) ($lancamentosCustomizerCapabilities['trigger']['action'] ?? 'customize');
$lancamentosUpgradeCta = is_array($lancamentosCustomizerCapabilities['upgradeCta'] ?? null)
    ? $lancamentosCustomizerCapabilities['upgradeCta']
    : [];

$customizeModal = [
    'title' => (string) ($lancamentosCustomizerDescriptor['title'] ?? 'Personalizar transações'),
    'description' => (string) ($lancamentosCustomizerDescriptor['description'] ?? 'Escolha entre um modo essencial para operar rápido ou a tela completa com exportação.'),
    'size' => (string) ($lancamentosCustomizerDescriptor['size'] ?? 'wide'),
    'locked' => !$lancamentosCanCustomize && $lancamentosHasLockedToggles,
    'lockedState' => $lancamentosHasLockedToggles && is_array($lancamentosCustomizerDescriptor['lockedState'] ?? null)
        ? $lancamentosCustomizerDescriptor['lockedState']
        : [],
    'lockedToggleIds' => $lancamentosLockedToggles,
    'disableInputs' => !$lancamentosCanCustomize,
    'hideSave' => !$lancamentosCanCustomize,
    'footerCta' => $lancamentosHasLockedToggles && !empty($lancamentosUpgradeCta['show'])
        ? [
            'label' => (string) ($lancamentosUpgradeCta['label'] ?? 'Desbloquear transações completas'),
            'href' => defined('BASE_URL') ? BASE_URL . 'billing' : '/billing',
        ]
        : [],
    'renderOverlay' => (bool) ($lancamentosCustomizerCapabilities['renderOverlay'] ?? true),
    'showPresets' => count(is_array($lancamentosCustomizerCapabilities['availablePresets'] ?? null) ? $lancamentosCustomizerCapabilities['availablePresets'] : []) > 1,
    'trigger' => [
        'render' => true,
        'id' => (string) ($lancamentosCustomizerDescriptor['trigger']['id'] ?? 'btnCustomizeLancamentos'),
        'label' => (string) ($lancamentosCustomizerCapabilities['trigger']['label'] ?? 'Personalizar transações'),
        'href' => '',
        'wrapperClass' => (string) ($lancamentosCustomizerDescriptor['trigger']['wrapperClass'] ?? 'lan-customize-trigger'),
        'buttonClass' => $lancamentosTriggerAction === 'upgrade'
            ? 'lk-customize-open surface-button surface-button--upgrade'
            : 'lk-customize-open surface-button surface-button--subtle',
    ],
    'ids' => is_array($lancamentosCustomizerDescriptor['ids'] ?? null)
        ? $lancamentosCustomizerDescriptor['ids']
        : [],
    'groups' => is_array($lancamentosCustomizerDescriptor['groups'] ?? null)
        ? $lancamentosCustomizerDescriptor['groups']
        : [],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
