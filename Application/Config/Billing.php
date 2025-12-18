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
            'lancamentos_per_month' => 50,
            'warning_at'            => 40,  // Aviso quando atingir 80% do limite
            'warning_critical_at'   => 45,  // Aviso crítico quando atingir 90%
        ],

        'pro' => [
            'lancamentos_per_month' => null, // ilimitado
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

        'limit_reached' => 'Você atingiu o limite de {limit} lançamentos deste mês no plano gratuito. ' .
                          'Ative o Lukrato Pro para continuar com lançamentos ilimitados.',

        'warning_normal' => '⚠️ <strong>Atenção:</strong> Você já usou {used} de {limit} lançamentos ' .
                          'do plano gratuito ({percentage}%). Faltam <strong>{remaining} lançamentos</strong> este mês.',

        'warning_critical' => '🔴 <strong>Atenção crítica!</strong> Você já usou {used} de {limit} lançamentos ' .
                            '({percentage}%). Restam apenas <strong>{remaining} lançamentos</strong> este mês.',

        'upgrade_cta' => 'Assine o Lukrato Pro e tenha lançamentos ilimitados!',

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
            'relatorios_basicos' => true,
            'relatorios_avancados' => false,
            'exportacao_pdf' => false,
            'exportacao_excel' => false,
            'categorias_personalizadas' => true,
            'multiplas_contas' => true,
            'notificacoes' => true,
        ],

        'pro' => [
            'relatorios_basicos' => true,
            'relatorios_avancados' => true,
            'exportacao_pdf' => true,
            'exportacao_excel' => true,
            'categorias_personalizadas' => true,
            'multiplas_contas' => true,
            'notificacoes' => true,
        ],

    ],

];
