<?php
$customizeModal = [
    'title' => 'Personalizar lançamentos',
    'description' => 'Comece no modo essencial e ative blocos quando fizer sentido.',
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
                ['id' => 'toggleLanHero', 'label' => 'Fluxo financeiro'],
                ['id' => 'toggleLanSummary', 'label' => 'Resumo do período'],
                ['id' => 'toggleLanExport', 'label' => 'Exportação'],
                ['id' => 'toggleLanFilters', 'label' => 'Filtros'],
            ],
        ],
    ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
