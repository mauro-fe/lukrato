<?php
$customizeModal = [
    'title' => 'Personalizar relatórios',
    'description' => 'Ative apenas os blocos de análise que você usa.',
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
                ['id' => 'toggleRelOverviewCharts', 'label' => 'Gráficos da visão geral'],
                ['id' => 'toggleRelControls', 'label' => 'Barra de controles'],
            ],
        ],
    ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
