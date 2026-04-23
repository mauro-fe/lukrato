<?php
$customizeModal = [
    'title' => 'Personalizar metas',
    'description' => 'Comece no modo essencial e habilite os blocos quando quiser.',
    'ids' => [
        'overlay' => 'metasCustomizeModalOverlay',
        'title' => 'metasCustomizeModalTitle',
        'description' => 'metasCustomizeModalDescription',
        'close' => 'btnCloseCustomizeMetas',
        'save' => 'btnSaveCustomizeMetas',
        'presetEssential' => 'btnPresetEssencialMetas',
        'presetComplete' => 'btnPresetCompletoMetas',
    ],
    'groups' => [
        [
            'title' => 'Blocos da tela',
            'items' => [
                ['id' => 'toggleMetasSummary', 'label' => 'Resumo de metas'],
                ['id' => 'toggleMetasFocus', 'label' => 'Foco do momento'],
                ['id' => 'toggleMetasToolbar', 'label' => 'Barra de filtros'],
            ],
        ],
    ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
