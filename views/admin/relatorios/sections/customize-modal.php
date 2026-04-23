<?php
$customizeModal = [
    'title' => 'Personalizar relatórios',
    'description' => 'Comece no modo essencial e habilite os blocos quando quiser.',
    'ids' => [
        'overlay' => 'relatoriosCustomizeModalOverlay',
        'title' => 'relatoriosCustomizeModalTitle',
        'description' => 'relatoriosCustomizeModalDescription',
        'close' => 'btnCloseCustomizeRelatorios',
        'save' => 'btnSaveCustomizeRelatorios',
        'presetEssential' => 'btnPresetEssencialRelatorios',
        'presetComplete' => 'btnPresetCompletoRelatorios',
    ],
    'groups' => [
        [
            'title' => 'Blocos da tela',
            'items' => [
                ['id' => 'toggleRelQuickStats', 'label' => 'Cards de resumo rápido'],
                ['id' => 'toggleRelOverviewCharts', 'label' => 'Mini gráficos da visão geral'],
                ['id' => 'toggleRelControls', 'label' => 'Barra de controles'],
            ],
        ],
    ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
