<?php
$customizeModal = [
    'title' => 'Personalizar cartões',
    'description' => 'Comece no modo essencial e ative os blocos quando fizer sentido.',
    'trigger' => [
        'render' => true,
        'id' => 'btnCustomizeCartoes',
        'label' => 'Personalizar tela',
        'wrapperClass' => 'cart-customize-trigger',
    ],
    'ids' => [
        'overlay' => 'cartoesCustomizeModalOverlay',
        'title' => 'cartoesCustomizeModalTitle',
        'description' => 'cartoesCustomizeModalDescription',
        'close' => 'btnCloseCustomizeCartoes',
        'save' => 'btnSaveCustomizeCartoes',
        'presetEssential' => 'btnPresetEssencialCartoes',
        'presetComplete' => 'btnPresetCompletoCartoes',
    ],
    'groups' => [
        [
            'title' => 'Blocos da tela',
            'items' => [
                ['id' => 'toggleCartoesKpis', 'label' => 'Resumo consolidado'],
                ['id' => 'toggleCartoesToolbar', 'label' => 'Barra de filtros'],
            ],
        ],
    ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
