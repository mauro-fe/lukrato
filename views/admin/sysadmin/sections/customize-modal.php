<?php
$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$sysadminPageCapabilities = isset($sysadminPageCapabilities) && is_array($sysadminPageCapabilities)
    ? $sysadminPageCapabilities
    : ((string) ($layoutPageCapabilities['pageKey'] ?? '') === 'sysadmin'
        ? $layoutPageCapabilities
        : []);

$sysadminCustomizerCapabilities = isset($sysadminCustomizerCapabilities) && is_array($sysadminCustomizerCapabilities)
    ? $sysadminCustomizerCapabilities
    : (is_array($sysadminPageCapabilities['customizer'] ?? null)
        ? $sysadminPageCapabilities['customizer']
        : []);

$sysadminCustomizerDescriptor = is_array($sysadminCustomizerCapabilities['descriptor'] ?? null)
    ? $sysadminCustomizerCapabilities['descriptor']
    : [];

$customizeModal = [
    'title' => (string) ($sysadminCustomizerDescriptor['title'] ?? 'Personalizar sysadmin'),
    'description' => (string) ($sysadminCustomizerDescriptor['description'] ?? 'Ajuste o painel administrativo entre uma leitura essencial e a visão completa.'),
    'size' => (string) ($sysadminCustomizerDescriptor['size'] ?? 'wide'),
    'renderOverlay' => (bool) ($sysadminCustomizerCapabilities['renderOverlay'] ?? false),
    'showPresets' => count(is_array($sysadminCustomizerCapabilities['availablePresets'] ?? null) ? $sysadminCustomizerCapabilities['availablePresets'] : []) > 1,
    'trigger' => [
        'render' => false,
    ],
    'ids' => is_array($sysadminCustomizerDescriptor['ids'] ?? null)
        ? $sysadminCustomizerDescriptor['ids']
        : [
            'overlay' => 'sysadminCustomizeModalOverlay',
            'title' => 'sysadminCustomizeModalTitle',
            'description' => 'sysadminCustomizeModalDescription',
            'close' => 'btnCloseCustomizeSysadmin',
            'save' => 'btnSaveCustomizeSysadmin',
            'presetEssential' => 'btnPresetEssencialSysadmin',
            'presetComplete' => 'btnPresetCompletoSysadmin',
        ],
    'groups' => is_array($sysadminCustomizerDescriptor['groups'] ?? null)
        ? $sysadminCustomizerDescriptor['groups']
        : [
            [
                'title' => 'Blocos da tela',
                'items' => [
                    ['id' => 'toggleSysStats', 'label' => 'Cards de status'],
                    ['id' => 'toggleSysTabs', 'label' => 'Menu de abas'],
                    ['id' => 'toggleSysDashboard', 'label' => 'Painel visão geral'],
                    ['id' => 'toggleSysFeedback', 'label' => 'Painel feedback'],
                ],
            ],
        ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
