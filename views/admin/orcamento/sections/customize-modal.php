<?php
$customizeModal = [
    'title' => 'Personalizar orçamento',
    'description' => 'Comece no modo essencial e habilite os blocos quando quiser.',
    'trigger' => [
        'render' => true,
        'id' => 'btnCustomizeOrcamento',
        'label' => 'Personalizar tela',
        'wrapperClass' => 'orc-customize-trigger',
    ],
    'ids' => [
        'overlay' => 'orcamentoCustomizeModalOverlay',
        'title' => 'orcamentoCustomizeModalTitle',
        'description' => 'orcamentoCustomizeModalDescription',
        'close' => 'btnCloseCustomizeOrcamento',
        'save' => 'btnSaveCustomizeOrcamento',
        'presetEssential' => 'btnPresetEssencialOrcamento',
        'presetComplete' => 'btnPresetCompletoOrcamento',
    ],
    'groups' => [
        [
            'title' => 'Blocos da tela',
            'items' => [
                ['id' => 'toggleOrcSummary', 'label' => 'Cards de resumo'],
                ['id' => 'toggleOrcFocus', 'label' => 'Foco do período'],
                ['id' => 'toggleOrcToolbar', 'label' => 'Barra de filtros'],
            ],
        ],
    ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
