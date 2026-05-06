<?php
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$orcamentoPageCapabilities = isset($orcamentoPageCapabilities) && is_array($orcamentoPageCapabilities)
    ? $orcamentoPageCapabilities
    : ((string) ($layoutPageCapabilities['pageKey'] ?? '') === 'orcamento'
        ? $layoutPageCapabilities
        : []);

$orcamentoCustomizerCapabilities = isset($orcamentoCustomizerCapabilities) && is_array($orcamentoCustomizerCapabilities)
    ? $orcamentoCustomizerCapabilities
    : (is_array($orcamentoPageCapabilities['customizer'] ?? null)
        ? $orcamentoPageCapabilities['customizer']
        : []);

$orcamentoCustomizerDescriptor = is_array($orcamentoCustomizerCapabilities['descriptor'] ?? null)
    ? $orcamentoCustomizerCapabilities['descriptor']
    : [];

$orcamentoCanCustomize = (bool) ($orcamentoCustomizerCapabilities['canCustomize'] ?? false);
$orcamentoLockedToggles = is_array($orcamentoCustomizerCapabilities['lockedToggles'] ?? null)
    ? array_values(array_filter(
        $orcamentoCustomizerCapabilities['lockedToggles'],
        static fn($toggleId): bool => is_string($toggleId) && $toggleId !== ''
    ))
    : [];
$orcamentoHasLockedToggles = $orcamentoLockedToggles !== [];
$orcamentoTrigger = is_array($orcamentoCustomizerCapabilities['trigger'] ?? null)
    ? $orcamentoCustomizerCapabilities['trigger']
    : [];
$orcamentoUpgradeCta = is_array($orcamentoCustomizerCapabilities['upgradeCta'] ?? null)
    ? $orcamentoCustomizerCapabilities['upgradeCta']
    : [];

$customizeModal = [
    'title' => (string) ($orcamentoCustomizerDescriptor['title'] ?? 'Personalizar orçamento'),
    'description' => (string) ($orcamentoCustomizerDescriptor['description'] ?? 'Comece no modo essencial e libere os blocos de apoio quando fizer sentido.'),
    'size' => (string) ($orcamentoCustomizerDescriptor['size'] ?? 'wide'),
    'locked' => !$orcamentoCanCustomize && $orcamentoHasLockedToggles,
    'lockedState' => $orcamentoHasLockedToggles && is_array($orcamentoCustomizerDescriptor['lockedState'] ?? null)
        ? $orcamentoCustomizerDescriptor['lockedState']
        : [],
    'lockedToggleIds' => $orcamentoLockedToggles,
    'disableInputs' => !$orcamentoCanCustomize,
    'hideSave' => !$orcamentoCanCustomize,
    'footerCta' => $orcamentoHasLockedToggles && !empty($orcamentoUpgradeCta['show'])
        ? [
            'label' => (string) ($orcamentoUpgradeCta['label'] ?? 'Desbloquear orçamento completo'),
            'href' => defined('BASE_URL') ? BASE_URL . 'billing' : '/billing',
        ]
        : [],
    'renderOverlay' => (bool) ($orcamentoCustomizerCapabilities['renderOverlay'] ?? true),
    'showPresets' => count(is_array($orcamentoCustomizerCapabilities['availablePresets'] ?? null) ? $orcamentoCustomizerCapabilities['availablePresets'] : []) > 1,
    'trigger' => [
        'render' => (bool) ($orcamentoTrigger['show'] ?? true),
        'id' => (string) ($orcamentoCustomizerDescriptor['trigger']['id'] ?? 'btnCustomizeOrcamento'),
        'label' => (string) ($orcamentoTrigger['label'] ?? 'Personalizar orçamento'),
        'wrapperClass' => (string) ($orcamentoCustomizerDescriptor['trigger']['wrapperClass'] ?? 'orc-customize-trigger'),
    ],
    'ids' => is_array($orcamentoCustomizerDescriptor['ids'] ?? null)
        ? $orcamentoCustomizerDescriptor['ids']
        : [
            'overlay' => 'orcamentoCustomizeModalOverlay',
            'title' => 'orcamentoCustomizeModalTitle',
            'description' => 'orcamentoCustomizeModalDescription',
            'close' => 'btnCloseCustomizeOrcamento',
            'save' => 'btnSaveCustomizeOrcamento',
            'presetEssential' => 'btnPresetEssencialOrcamento',
            'presetComplete' => 'btnPresetCompletoOrcamento',
        ],
    'groups' => is_array($orcamentoCustomizerDescriptor['groups'] ?? null)
        ? $orcamentoCustomizerDescriptor['groups']
        : [
            [
                'title' => 'Blocos da tela',
                'items' => [
                    ['id' => 'toggleOrcSummary', 'label' => 'Cards de resumo'],
                    ['id' => 'toggleOrcFocus', 'label' => 'Foco do período'],
                    ['id' => 'toggleOrcToolbar', 'label' => 'Barra de filtros'],
                ],
            ],
        ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
