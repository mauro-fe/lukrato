<?php
$customizeModal = [
    'title' => 'Personalizar importações',
    'description' => 'Comece no modo essencial e habilite os blocos quando quiser.',
    'trigger' => [
        'render' => true,
        'id' => 'btnCustomizeImportacoes',
        'label' => 'Personalizar tela',
        'wrapperClass' => 'imp-customize-trigger',
    ],
    'ids' => [
        'overlay' => 'importacoesCustomizeModalOverlay',
        'title' => 'importacoesCustomizeModalTitle',
        'description' => 'importacoesCustomizeModalDescription',
        'close' => 'btnCloseCustomizeImportacoes',
        'save' => 'btnSaveCustomizeImportacoes',
        'presetEssential' => 'btnPresetEssencialImportacoes',
        'presetComplete' => 'btnPresetCompletoImportacoes',
    ],
    'groups' => [
        [
            'title' => 'Blocos da tela',
            'items' => [
                ['id' => 'toggleImpHero', 'label' => 'Cabeçalho de contexto'],
                ['id' => 'toggleImpSidebar', 'label' => 'Painel lateral de apoio'],
            ],
        ],
    ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
