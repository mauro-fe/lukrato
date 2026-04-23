<?php
$customizeModal = [
    'title' => 'Personalizar sysadmin',
    'description' => 'Comece no modo essencial e habilite os blocos quando quiser.',
    'ids' => [
        'overlay' => 'sysadminCustomizeModalOverlay',
        'title' => 'sysadminCustomizeModalTitle',
        'description' => 'sysadminCustomizeModalDescription',
        'close' => 'btnCloseCustomizeSysadmin',
        'save' => 'btnSaveCustomizeSysadmin',
        'presetEssential' => 'btnPresetEssencialSysadmin',
        'presetComplete' => 'btnPresetCompletoSysadmin',
    ],
    'groups' => [
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
