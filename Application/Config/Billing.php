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
            'lancamentos_per_month' => 30,   // Limite mensal de lançamentos
            'warning_at'            => 15,   // Aviso suave quando atingir 50% (15/30)
            'warning_medium_at'     => 24,   // Aviso médio quando atingir 80% (24/30)
            'warning_critical_at'   => 27,   // Aviso crítico quando atingir 90% (27/30)
            'grace_extra'           => 5,    // Lançamentos extras de cortesia após limite
            'max_contas'            => 2,    // Máximo de contas bancárias
            'max_categorias_custom' => 10,   // Máximo de categorias personalizadas
            'max_subcategorias_custom' => 20, // Máximo de subcategorias personalizadas
            'historico_meses'       => 3,    // Apenas 3 meses de histórico visível
            'max_cartoes'           => 1,    // Apenas 1 cartão de crédito
            'max_metas'             => 2,    // Apenas 2 metas financeiras
            'max_orcamentos'        => 5,    // Apenas 5 orçamentos por categoria
            'ai_messages_per_month' => 0,    // Sem acesso à IA
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
            'ai_messages_per_month' => 100,  // 100 mensagens IA/mês
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

        'ai_limit' => 'Você atingiu o limite de {limit} mensagens com IA este mês. ' .
            'Faça upgrade para o Ultra e tenha IA ilimitada.',

        'ai_blocked' => '🤖 O assistente IA está disponível a partir do plano Pro. ' .
            'Faça upgrade para conversar com a IA.',

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
        'relatorios'   => '📊 Análises completas e exportação com o Pro',
        'cartoes'      => '💳 Gerencie todos os seus cartões de crédito',
        'contas'       => '🏦 Organize todas as suas contas bancárias',
        'agendamentos' => '⏰ Lembretes automáticos por email e notificações',
        'metas'        => '🎯 Crie metas ilimitadas e acompanhe seu progresso',
        'categorias'   => '🏷️ Personalize suas categorias sem limites',
        'lancamentos'  => '💰 Registre suas transações sem preocupações',
        'dashboard'    => '📈 Dashboard avançado com insights personalizados',
        'default'      => '🚀 Desbloqueie todo o potencial do Lukrato',
        'ai_chat'      => '🤖 Assistente IA: tire dúvidas sobre suas finanças',
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
            'ai_chat'                 => false,   // Sem chatbot IA
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
        ],

    ],

];
