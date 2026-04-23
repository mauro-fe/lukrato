<?php
$customizeModal = [
    'title' => 'Personalizar dashboard',
    'description' => 'Comece no modo essencial e ative extras quando fizer sentido para você.',
    'size' => 'wide',
    'trigger' => [
        'render' => true,
        'id' => 'btnCustomizeDashboard',
        'label' => 'Personalizar dashboard',
        'wrapperClass' => 'dash-customize-trigger',
    ],
    'ids' => [
        'overlay' => 'customizeModalOverlay',
        'title' => 'customizeModalTitle',
        'description' => 'customizeModalDescription',
        'close' => 'btnCloseCustomize',
        'save' => 'btnSaveCustomize',
        'presetEssential' => 'btnPresetEssencial',
        'presetComplete' => 'btnPresetCompleto',
    ],
    'groups' => [
        [
            'title' => 'Principais',
            'items' => [
                ['id' => 'toggleAlertas', 'label' => 'Alertas'],
                ['id' => 'toggleHealthScore', 'label' => 'Saúde financeira'],
                ['id' => 'toggleAiTip', 'label' => 'Dicas do Lukrato'],
                ['id' => 'toggleEvolucao', 'label' => 'Evolução financeira'],
                ['id' => 'togglePrevisao', 'label' => 'Previsão financeira'],
                ['id' => 'toggleGrafico', 'label' => 'Gráfico de categorias'],
            ],
        ],
        [
            'title' => 'Extras',
            'items' => [
                ['id' => 'toggleMetas', 'label' => 'Metas', 'checked' => false],
                ['id' => 'toggleCartoes', 'label' => 'Cartões', 'checked' => false],
                ['id' => 'toggleContas', 'label' => 'Contas', 'checked' => false],
                ['id' => 'toggleOrcamentos', 'label' => 'Orçamentos', 'checked' => false],
                ['id' => 'toggleFaturas', 'label' => 'Faturas de cartão', 'checked' => false],
                ['id' => 'toggleGamificacao', 'label' => 'Gamificação', 'checked' => false],
            ],
        ],
    ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
