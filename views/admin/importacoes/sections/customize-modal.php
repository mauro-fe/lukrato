<?php
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$importacoesPageCapabilities = isset($importacoesPageCapabilities) && is_array($importacoesPageCapabilities)
    ? $importacoesPageCapabilities
    : ((string) ($layoutPageCapabilities['pageKey'] ?? '') === 'importacoes'
        ? $layoutPageCapabilities
        : []);

$importacoesCustomizerCapabilities = isset($importacoesCustomizerCapabilities) && is_array($importacoesCustomizerCapabilities)
    ? $importacoesCustomizerCapabilities
    : (is_array($importacoesPageCapabilities['customizer'] ?? null)
        ? $importacoesPageCapabilities['customizer']
        : []);

$importacoesCustomizerDescriptor = is_array($importacoesCustomizerCapabilities['descriptor'] ?? null)
    ? $importacoesCustomizerCapabilities['descriptor']
    : [];

$importacoesCanCustomize = (bool) ($importacoesCustomizerCapabilities['canCustomize'] ?? false);
$importacoesLockedToggles = is_array($importacoesCustomizerCapabilities['lockedToggles'] ?? null)
    ? array_values(array_filter(
        $importacoesCustomizerCapabilities['lockedToggles'],
        static fn($toggleId): bool => is_string($toggleId) && $toggleId !== ''
    ))
    : [];
$importacoesHasLockedToggles = $importacoesLockedToggles !== [];
$importacoesTrigger = is_array($importacoesCustomizerCapabilities['trigger'] ?? null)
    ? $importacoesCustomizerCapabilities['trigger']
    : [];
$importacoesUpgradeCta = is_array($importacoesCustomizerCapabilities['upgradeCta'] ?? null)
    ? $importacoesCustomizerCapabilities['upgradeCta']
    : [];

$customizeModal = [
    'title' => (string) ($importacoesCustomizerDescriptor['title'] ?? 'Personalizar importações'),
    'description' => (string) ($importacoesCustomizerDescriptor['description'] ?? 'Mantenha o fluxo focado no envio do arquivo ou libere o contexto completo da tela.'),
    'size' => (string) ($importacoesCustomizerDescriptor['size'] ?? 'wide'),
    'locked' => !$importacoesCanCustomize && $importacoesHasLockedToggles,
    'lockedState' => $importacoesHasLockedToggles && is_array($importacoesCustomizerDescriptor['lockedState'] ?? null)
        ? $importacoesCustomizerDescriptor['lockedState']
        : [],
    'lockedToggleIds' => $importacoesLockedToggles,
    'disableInputs' => !$importacoesCanCustomize,
    'hideSave' => !$importacoesCanCustomize,
    'footerCta' => $importacoesHasLockedToggles && !empty($importacoesUpgradeCta['show'])
        ? [
            'label' => (string) ($importacoesUpgradeCta['label'] ?? 'Desbloquear importações completas'),
            'href' => defined('BASE_URL') ? BASE_URL . 'billing' : '/billing',
        ]
        : [],
    'renderOverlay' => (bool) ($importacoesCustomizerCapabilities['renderOverlay'] ?? true),
    'showPresets' => count(is_array($importacoesCustomizerCapabilities['availablePresets'] ?? null) ? $importacoesCustomizerCapabilities['availablePresets'] : []) > 1,
    'trigger' => [
        'render' => (bool) ($importacoesTrigger['show'] ?? true),
        'id' => (string) ($importacoesCustomizerDescriptor['trigger']['id'] ?? 'btnCustomizeImportacoes'),
        'label' => (string) ($importacoesTrigger['label'] ?? 'Personalizar importações'),
        'wrapperClass' => (string) ($importacoesCustomizerDescriptor['trigger']['wrapperClass'] ?? 'imp-customize-trigger'),
    ],
    'ids' => is_array($importacoesCustomizerDescriptor['ids'] ?? null)
        ? $importacoesCustomizerDescriptor['ids']
        : [
            'overlay' => 'importacoesCustomizeModalOverlay',
            'title' => 'importacoesCustomizeModalTitle',
            'description' => 'importacoesCustomizeModalDescription',
            'close' => 'btnCloseCustomizeImportacoes',
            'save' => 'btnSaveCustomizeImportacoes',
            'presetEssential' => 'btnPresetEssencialImportacoes',
            'presetComplete' => 'btnPresetCompletoImportacoes',
        ],
    'groups' => is_array($importacoesCustomizerDescriptor['groups'] ?? null)
        ? $importacoesCustomizerDescriptor['groups']
        : [
            [
                'title' => 'Blocos da tela',
                'items' => [
                    ['id' => 'toggleImpHero', 'label' => 'Cabeçalho de contexto'],
                    ['id' => 'toggleImpSidebar', 'label' => 'Painel lateral de apoio'],
                ],
            ],
        ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
