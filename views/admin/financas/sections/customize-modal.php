<?php
$customizeModal = [
    'title' => 'Personalizar finanças',
    'description' => 'Comece no modo essencial e habilite os blocos quando quiser.',
    'trigger' => [
        'render' => true,
        'id' => 'btnCustomizeFinancas',
        'label' => 'Personalizar tela',
        'wrapperClass' => 'fin-customize-trigger',
    ],
    'ids' => [
        'overlay' => 'financasCustomizeModalOverlay',
        'title' => 'financasCustomizeModalTitle',
        'description' => 'financasCustomizeModalDescription',
        'close' => 'btnCloseCustomizeFinancas',
        'save' => 'btnSaveCustomizeFinancas',
        'presetEssential' => 'btnPresetEssencialFinancas',
        'presetComplete' => 'btnPresetCompletoFinancas',
    ],
    'groups' => [
        [
            'title' => 'Blocos da tela',
            'items' => [
                ['id' => 'toggleFinSummary', 'label' => 'Cards de resumo'],
                ['id' => 'toggleFinOrcActions', 'label' => 'Ações da aba orçamentos'],
                ['id' => 'toggleFinMetasActions', 'label' => 'Ações da aba metas'],
                ['id' => 'toggleFinInsights', 'label' => 'Insights de orçamentos'],
            ],
        ],
    ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
