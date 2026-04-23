<?php
$customizeModal = [
    'title' => 'Personalizar faturas',
    'description' => 'Comece no modo essencial e habilite blocos quando quiser.',
    'ids' => [
        'overlay' => 'faturasCustomizeModalOverlay',
        'title' => 'faturasCustomizeModalTitle',
        'description' => 'faturasCustomizeModalDescription',
        'close' => 'btnCloseCustomizeFaturas',
        'save' => 'btnSaveCustomizeFaturas',
        'presetEssential' => 'btnPresetEssencialFaturas',
        'presetComplete' => 'btnPresetCompletoFaturas',
    ],
    'groups' => [
        [
            'title' => 'Blocos da tela',
            'items' => [
                ['id' => 'toggleFaturasHero', 'label' => 'Suas faturas'],
                ['id' => 'toggleFaturasFiltros', 'label' => 'Painel de filtros'],
                ['id' => 'toggleFaturasViewToggle', 'label' => 'Toggle de visualização'],
            ],
        ],
    ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
