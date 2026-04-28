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
                ['id' => 'toggleAlertas', 'label' => 'Alertas', 'description' => 'Avisos importantes para agir antes que virem problema.'],
                ['id' => 'toggleHealthScore', 'label' => 'Saúde financeira', 'description' => 'Resumo rápido do momento atual das suas finanças.'],
                ['id' => 'toggleAiTip', 'label' => 'Dicas do Lukrato', 'description' => 'Insights e recomendações geradas com base no seu cenário.'],
                ['id' => 'toggleEvolucao', 'label' => 'Evolução financeira', 'description' => 'Acompanhe a tendência da sua performance ao longo do tempo.'],
                ['id' => 'togglePrevisao', 'label' => 'Previsão financeira', 'description' => 'Veja o impacto projetado das próximas movimentações.'],
                ['id' => 'toggleGrafico', 'label' => 'Gráfico de categorias', 'description' => 'Distribuição visual dos seus gastos por categoria.'],
            ],
        ],
        [
            'title' => 'Extras',
            'items' => [
                ['id' => 'toggleMetas', 'label' => 'Metas', 'description' => 'Seus objetivos ativos e quanto falta para concluir cada um.', 'checked' => false],
                ['id' => 'toggleCartoes', 'label' => 'Cartões', 'description' => 'Limites, uso atual e visão rápida dos seus cartões.', 'checked' => false],
                ['id' => 'toggleContas', 'label' => 'Contas', 'description' => 'Saldo consolidado e status das contas conectadas.', 'checked' => false],
                ['id' => 'toggleOrcamentos', 'label' => 'Orçamentos', 'description' => 'Controle visual do que já consumiu em cada orçamento.', 'checked' => false],
                ['id' => 'toggleFaturas', 'label' => 'Faturas de cartão', 'description' => 'Próximos fechamentos e situação das faturas abertas.', 'checked' => false],
                ['id' => 'toggleGamificacao', 'label' => 'Gamificação', 'description' => 'Nível, progresso e incentivos para manter sua rotina.', 'checked' => false],
            ],
        ],
    ],
];

require dirname(__DIR__, 2) . '/shared/customize-modal.php';
