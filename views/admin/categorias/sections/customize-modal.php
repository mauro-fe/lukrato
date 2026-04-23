<?php
$customizeModal = [
    'title' => 'Personalizar categorias',
    'description' => 'Comece no modo essencial e habilite os blocos quando quiser.',
    'ids' => [
        'overlay' => 'categoriasCustomizeModalOverlay',
        'title' => 'categoriasCustomizeModalTitle',
        'description' => 'categoriasCustomizeModalDescription',
        'close' => 'btnCloseCustomizeCategorias',
        'save' => 'btnSaveCustomizeCategorias',
        'presetEssential' => 'btnPresetEssencialCategorias',
        'presetComplete' => 'btnPresetCompletoCategorias',
    ],
    'groups' => [
        [
            'title' => 'Blocos da tela',
            'items' => [
                ['id' => 'toggleCategoriasKpis', 'label' => 'Cards de KPI'],
                ['id' => 'toggleCategoriasCreateCard', 'label' => 'Card de criação'],
                ['id' => 'toggleCategoriasContextCard', 'label' => 'Contexto e busca'],
            ],
        ],
    ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
