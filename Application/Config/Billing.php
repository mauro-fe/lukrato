<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Limites de uso por plano
    |--------------------------------------------------------------------------
    |
    | Define os limites de funcionalidades para cada tipo de plano.
    | 
    */

    'limits' => [

        'free' => [
            'lancamentos_per_month' => 30,   // Reduzido para forçar conversão
            'warning_at'            => 20,   // Aviso quando atingir 67% do limite
            'warning_critical_at'   => 25,   // Aviso crítico quando atingir 83%
            'max_contas'            => 2,    // Máximo de contas bancárias
            'max_categorias_custom' => 10,   // Máximo de categorias personalizadas
            'historico_meses'       => 3,    // Apenas 3 meses de histórico visível
            'max_cartoes'           => 1,    // Apenas 1 cartão de crédito
            'max_metas'             => 2,    // Apenas 2 metas financeiras
        ],

        'pro' => [
            'lancamentos_per_month' => null, // ilimitado
            'max_contas'            => null, // ilimitado
            'max_categorias_custom' => null, // ilimitado
            'historico_meses'       => null, // ilimitado
            'max_cartoes'           => null, // ilimitado
            'max_metas'             => null, // ilimitado
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Mensagens do sistema
    |--------------------------------------------------------------------------
    |
    | Mensagens personalizadas para diferentes situações de limite.
    | Variáveis disponíveis: {used}, {limit}, {remaining}, {percentage}
    |
    */

    'messages' => [

        'limit_reached' => '🚫 Você atingiu o limite de {limit} lançamentos deste mês no plano gratuito. ' .
            'Ative o Lukrato Pro para continuar com lançamentos ilimitados!',

        'warning_normal' => '⚠️ <strong>Atenção:</strong> Você já usou {used} de {limit} lançamentos ' .
            'do plano gratuito ({percentage}%). Faltam <strong>{remaining} lançamentos</strong> este mês.',

        'warning_critical' => '🔴 <strong>Quase no limite!</strong> Você já usou {used} de {limit} lançamentos ' .
            '({percentage}%). Restam apenas <strong>{remaining} lançamentos</strong>! ' .
            '<a href="/assinatura" class="alert-link">Faça upgrade agora</a>',

        'upgrade_cta' => '🚀 Assine o Lukrato Pro: lançamentos ilimitados + relatórios avançados + exportação!',

        'contas_limit' => 'Você atingiu o limite de {limit} contas no plano gratuito. ' .
            'Faça upgrade para adicionar contas ilimitadas.',

        'categorias_limit' => 'Limite de {limit} categorias personalizadas atingido. ' .
            'Faça upgrade para criar categorias ilimitadas.',

        'historico_limit' => 'No plano gratuito, você só pode visualizar os últimos {limit} meses. ' .
            'Faça upgrade para acessar todo seu histórico financeiro.',

        'cartoes_limit' => 'Você atingiu o limite de {limit} cartão no plano gratuito. ' .
            'Faça upgrade para adicionar cartões ilimitados.',

        'metas_limit' => 'Limite de {limit} metas atingido. ' .
            'Faça upgrade para criar metas ilimitadas.',

    ],

    /*
    |--------------------------------------------------------------------------
    | Features por plano
    |--------------------------------------------------------------------------
    |
    | Define quais recursos estão disponíveis para cada plano.
    |
    */

    'features' => [

        'free' => [
            'relatorios_basicos'      => true,
            'relatorios_avancados'    => false,
            'exportacao_pdf'          => false,
            'exportacao_excel'        => false,
            'categorias_personalizadas' => true,  // Limitado a 10
            'multiplas_contas'        => true,    // Limitado a 2
            'notificacoes'            => true,
            'recorrencias'            => true,    // Básico
            'anexos_comprovantes'     => false,   // Bloqueado
            'dashboard_avancado'      => false,   // Só widgets básicos
            'backup_dados'            => false,   // Sem backup
            'suporte_prioritario'     => false,
            'reminders_email'         => false,   // Sem lembretes por email
            'metas_financeiras'       => true,    // Limitado a 2
        ],

        'pro' => [
            'relatorios_basicos'      => true,
            'relatorios_avancados'    => true,
            'exportacao_pdf'          => true,
            'exportacao_excel'        => true,
            'categorias_personalizadas' => true,
            'multiplas_contas'        => true,
            'notificacoes'            => true,
            'recorrencias'            => true,
            'anexos_comprovantes'     => true,
            'dashboard_avancado'      => true,
            'backup_dados'            => true,
            'suporte_prioritario'     => true,
            'reminders_email'         => true,
            'metas_financeiras'       => true,
        ],

    ],

];
