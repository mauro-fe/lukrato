<?php
$customizeModal = [
    'title' => 'Personalizar lançamentos',
    'description' => 'Ative apenas os blocos operacionais que você usa.',
    'trigger' => [
        'render' => true,
        'id' => 'btnCustomizeLancamentos',
        'label' => 'Personalizar tela',
        'wrapperClass' => 'lan-customize-trigger',
    ],
    'ids' => [
        'overlay' => 'lanCustomizeModalOverlay',
        'title' => 'lanCustomizeModalTitle',
        'description' => 'lanCustomizeModalDescription',
        'close' => 'btnCloseCustomizeLancamentos',
        'save' => 'btnSaveCustomizeLancamentos',
        'presetEssential' => 'btnPresetEssencialLancamentos',
        'presetComplete' => 'btnPresetCompletoLancamentos',
    ],
    'groups' => [
        [
            'title' => 'Blocos da página',
            'items' => [
                ['id' => 'toggleLanFilters', 'label' => 'Filtros'],
                ['id' => 'toggleLanExport', 'label' => 'Exportação'],
            ],
        ],
    ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
