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
            'lancamentos_per_month' => 100,  // Limite mensal de lançamentos
            'warning_at'            => 50,   // Aviso suave quando atingir 50% (50/100)
            'warning_medium_at'     => 80,   // Aviso médio quando atingir 80% (80/100)
            'warning_critical_at'   => 90,   // Aviso crítico quando atingir 90% (90/100)
            'grace_extra'           => 10,   // Lançamentos extras de cortesia após limite
            'max_contas'            => 2,    // Máximo de contas bancárias
            'max_categorias_custom' => 15,   // Máximo de categorias personalizadas
            'max_subcategorias_custom' => 30, // Máximo de subcategorias personalizadas
            'historico_meses'       => 3,    // Apenas 3 meses de histórico visível
            'max_cartoes'           => 1,    // Apenas 1 cartão de crédito
            'max_metas'             => 3,    // Até 3 metas financeiras
            'max_orcamentos'        => 3,    // Até 3 orçamentos por categoria
            'ai_messages_per_month' => 5,    // 5 mensagens chat IA/mês (degustação)
            'ai_categorization_per_month' => 5, // 5 sugestões de categoria IA/mês (degustação)
        ],

        'pro' => [
            'lancamentos_per_month' => null, // ilimitado
            'max_contas'            => null, // ilimitado
            'max_categorias_custom' => null, // ilimitado
            'max_subcategorias_custom' => null, // ilimitado
            'historico_meses'       => null, // ilimitado
            'max_cartoes'           => null, // ilimitado
            'max_metas'             => null, // ilimitado
            'max_orcamentos'        => null, // ilimitado
            'ai_messages_per_month' => null, // ilimitado
            'ai_categorization_per_month' => null, // ilimitado
        ],

        'ultra' => [
            'lancamentos_per_month' => null, // ilimitado
            'max_contas'            => null, // ilimitado
            'max_categorias_custom' => null, // ilimitado
            'max_subcategorias_custom' => null, // ilimitado
            'historico_meses'       => null, // ilimitado
            'max_cartoes'           => null, // ilimitado
            'max_metas'             => null, // ilimitado
            'max_orcamentos'        => null, // ilimitado
            'ai_messages_per_month' => null, // ilimitado
            'ai_categorization_per_month' => null, // ilimitado
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

        'limit_reached' => '🚫 Você usou todos os {limit} lançamentos deste mês. ' .
            'Faça upgrade para o Pro e tenha lançamentos ilimitados!',

        'grace_period' => '🎁 <strong>Cortesia:</strong> Você passou do limite, mas liberamos +{grace} lançamentos extras este mês. ' .
            'No próximo mês, considere fazer upgrade para continuar sem interrupções.',

        'warning_soft' => '💡 <strong>Dica:</strong> Você já usou {used} de {limit} lançamentos ({percentage}%). ' .
            'Restam <strong>{remaining}</strong> este mês.',

        'warning_medium' => '⚠️ <strong>Atenção:</strong> {used} de {limit} lançamentos usados ({percentage}%). ' .
            'Apenas <strong>{remaining} restantes</strong>. ' .
            '<a href="/billing" class="alert-link fw-bold">Considere o upgrade</a>',

        'warning_critical' => '🔴 <strong>Quase no limite!</strong> Você usou {used} de {limit} lançamentos ({percentage}%). ' .
            'Restam apenas <strong>{remaining}</strong>! ' .
            '<a href="/billing" class="alert-link fw-bold">Faça upgrade agora</a>',

        'upgrade_cta' => '🚀 Lukrato Pro: lançamentos ilimitados + relatórios avançados + exportação PDF/Excel!',

        'ai_limit' => 'Você atingiu o limite de {limit} sugestões com IA este mês. ' .
            'Faça upgrade para o Pro e tenha IA ilimitada!',

        'ai_blocked' => '🤖 Você usou suas 5 mensagens de IA gratuitas este mês. ' .
            'Faça upgrade para o Pro e tenha IA ilimitada!',

        'ai_categorization_blocked' => '🏷️ Você usou suas 5 sugestões de categoria com IA gratuitas este mês. ' .
            'Faça upgrade para o Pro e tenha sugestões ilimitadas!',

        'ultra_feature_blocked' => '⚡ Este recurso é exclusivo do plano Ultra. ' .
            'Faça upgrade para desbloquear análise financeira com IA, insights automáticos e previsão de saldo.',

        'ai_quota_warning' => '⚠️ Você usou {used} de {limit} mensagens com IA este mês. ' .
            'Restam {remaining}.',

        'contas_limit' => 'Você atingiu o limite de {limit} contas no plano gratuito. ' .
            'Faça upgrade para adicionar contas ilimitadas.',

        'categorias_limit' => 'Limite de {limit} categorias personalizadas atingido. ' .
            'Faça upgrade para criar categorias ilimitadas.',

        'subcategorias_limit' => 'Limite de {limit} subcategorias personalizadas atingido. ' .
            'Faça upgrade para criar subcategorias ilimitadas.',

        'historico_limit' => 'No plano gratuito, você só pode visualizar os últimos {limit} meses. ' .
            'Faça upgrade para acessar todo seu histórico financeiro.',

        'cartoes_limit' => 'Você atingiu o limite de {limit} cartão no plano gratuito. ' .
            'Faça upgrade para adicionar cartões ilimitados.',

        'metas_limit' => 'Limite de {limit} metas atingido. ' .
            'Faça upgrade para criar metas ilimitadas.',

        'orcamentos_limit' => 'Limite de {limit} orçamentos por categoria atingido. ' .
            'Faça upgrade para definir orçamentos ilimitados.',

    ],

    /*
    |--------------------------------------------------------------------------
    | Mensagens Contextuais de Upgrade (por página)
    |--------------------------------------------------------------------------
    |
    | Mensagens específicas para cada contexto, evitando repetição.
    |
    */

    'contextual_messages' => [
        'relatorios'          => '📊 Análises completas e exportação com o Pro',
        'cartoes'             => '💳 Gerencie todos os seus cartões de crédito',
        'contas'              => '🏦 Organize todas as suas contas bancárias',
        'agendamentos'        => '⏰ Lembretes automáticos por email e notificações',
        'metas'               => '🎯 Crie metas ilimitadas e acompanhe seu progresso',
        'categorias'          => '🏷️ Personalize suas categorias sem limites',
        'lancamentos'         => '💰 Registre suas transações sem preocupações',
        'dashboard'           => '📈 Dashboard avançado com insights personalizados',
        'default'             => '🚀 Desbloqueie todo o potencial do Lukrato',
        'ai_chat'             => '🤖 Assistente IA: tire dúvidas sobre suas finanças',
        'analise_ia'          => '🧠 Análise financeira com IA exclusiva do Ultra',
        'insights_automaticos' => '💡 Insights automáticos sobre seus gastos com o Ultra',
        'previsao_saldo'      => '📈 Previsão de saldo inteligente exclusiva do Ultra',
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
            'reports'                 => false,
            'relatorios_basicos'      => true,
            'relatorios_avancados'    => false,
            'exportacao_pdf'          => false,
            'exportacao_excel'        => false,
            'categorias_personalizadas' => true,  // Limitado a 15
            'multiplas_contas'        => true,    // Limitado a 2
            'notificacoes'            => true,
            'recorrencias'            => true,    // Básico
            'anexos_comprovantes'     => false,   // Bloqueado
            'dashboard_avancado'      => false,   // Só widgets básicos
            'backup_dados'            => false,   // Sem backup
            'suporte_prioritario'     => false,
            'reminders_email'         => false,   // Sem lembretes por email
            'metas_financeiras'       => true,    // Limitado a 3
            'ai_chat'                 => true,    // Degustação: 5 sugestões/mês
            'analise_financeira_ia'   => false,   // Exclusivo Ultra
            'insights_automaticos'    => false,   // Exclusivo Ultra
            'previsao_saldo'          => false,   // Exclusivo Ultra
            'chat_financeiro_ia'      => false,   // Exclusivo Ultra
        ],

        'pro' => [
            'reports'                 => true,
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
            'ai_chat'                 => true,
            'analise_financeira_ia'   => false,   // Exclusivo Ultra
            'insights_automaticos'    => false,   // Exclusivo Ultra
            'previsao_saldo'          => false,   // Exclusivo Ultra
            'chat_financeiro_ia'      => false,   // Exclusivo Ultra
        ],

        'ultra' => [
            'reports'                 => true,
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
            'ai_chat'                 => true,
            'analise_financeira_ia'   => true,    // Exclusivo Ultra
            'insights_automaticos'    => true,    // Exclusivo Ultra
            'previsao_saldo'          => true,    // Exclusivo Ultra
            'chat_financeiro_ia'      => true,    // Exclusivo Ultra
        ],

    ],

];
