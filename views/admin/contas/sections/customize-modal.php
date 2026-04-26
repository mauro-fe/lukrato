<?php
$customizeModal = [
    'title' => 'Personalizar contas',
    'description' => 'Comece no modo essencial e habilite blocos extras quando quiser.',
    'ids' => [
        'overlay' => 'contasCustomizeModalOverlay',
        'title' => 'contasCustomizeModalTitle',
        'description' => 'contasCustomizeModalDescription',
        'close' => 'btnCloseCustomizeContas',
        'save' => 'btnSaveCustomizeContas',
        'presetEssential' => 'btnPresetEssencialContas',
        'presetComplete' => 'btnPresetCompletoContas',
    ],
    'groups' => [
        [
            'title' => 'Blocos da tela',
            'items' => [
                ['id' => 'toggleContasHero', 'label' => 'Visão consolidada'],
                ['id' => 'toggleContasKpis', 'label' => 'Cards de KPI'],
            ],
        ],
    ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
