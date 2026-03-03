<?php

namespace Application\Enums;

/**
 * Enum: AchievementType
 * 
 * Tipos de conquistas disponíveis no sistema
 * Divididas entre Free e Pro
 */
enum AchievementType: string
{
    // ========== CONQUISTAS GRATUITAS ==========
    case FIRST_LAUNCH = 'FIRST_LAUNCH';                     // Primeiro lançamento
    case STREAK_3 = 'STREAK_3';                             // 3 dias ativos (antigo: consecutivos)
    case STREAK_7 = 'STREAK_7';                             // 7 dias ativos
    case DAYS_30_USING = 'DAYS_30_USING';                   // 30 dias usando o sistema
    case TOTAL_10_LAUNCHES = 'TOTAL_10_LAUNCHES';           // 10 lançamentos
    case TOTAL_5_CATEGORIES = 'TOTAL_5_CATEGORIES';         // 5 categorias

        // ========== CONQUISTAS PRO ==========
    case MASTER_ORGANIZATION = 'MASTER_ORGANIZATION';       // Mestre da Organização (Pro)
    case ECONOMIST_MASTER = 'ECONOMIST_MASTER';             // Economista Nato (Pro)
    case CONSISTENCY_TOTAL = 'CONSISTENCY_TOTAL';           // Consistência Total - 30 dias ativos (Pro)
    case META_ACHIEVED = 'META_ACHIEVED';                   // Meta Batida (Pro)
    case PREMIUM_USER = 'PREMIUM_USER';                     // Usuário Premium (Pro)
    case LEVEL_8 = 'LEVEL_8';                               // Nível 8 (Pro)

        // ========== CONQUISTAS COMUNS ==========
    case POSITIVE_MONTH = 'POSITIVE_MONTH';                 // Mês positivo
    case TOTAL_100_LAUNCHES = 'TOTAL_100_LAUNCHES';         // 100 lançamentos
    case LEVEL_5 = 'LEVEL_5';                               // Nível 5

        // ========== NOVAS CONQUISTAS - LANÇAMENTOS ==========
    case TOTAL_250_LAUNCHES = 'TOTAL_250_LAUNCHES';         // 250 lançamentos
    case TOTAL_500_LAUNCHES = 'TOTAL_500_LAUNCHES';         // 500 lançamentos
    case TOTAL_1000_LAUNCHES = 'TOTAL_1000_LAUNCHES';       // 1000 lançamentos

        // ========== NOVAS CONQUISTAS - DIAS ATIVOS ==========
    case DAYS_50_ACTIVE = 'DAYS_50_ACTIVE';                 // 50 dias ativos
    case DAYS_100_ACTIVE = 'DAYS_100_ACTIVE';               // 100 dias ativos
    case DAYS_365_ACTIVE = 'DAYS_365_ACTIVE';               // 365 dias ativos (1 ano)

        // ========== NOVAS CONQUISTAS - ECONOMIA ==========
    case SAVER_10 = 'SAVER_10';                             // Economizar 10% em um mês
    case SAVER_20 = 'SAVER_20';                             // Economizar 20% em um mês
    case SAVER_30 = 'SAVER_30';                             // Economizar 30% em um mês
    case POSITIVE_3_MONTHS = 'POSITIVE_3_MONTHS';           // 3 meses seguidos saldo positivo
    case POSITIVE_6_MONTHS = 'POSITIVE_6_MONTHS';           // 6 meses seguidos saldo positivo
    case POSITIVE_12_MONTHS = 'POSITIVE_12_MONTHS';         // 12 meses seguidos saldo positivo

        // ========== NOVAS CONQUISTAS - ORGANIZAÇÃO ==========
    case TOTAL_15_CATEGORIES = 'TOTAL_15_CATEGORIES';       // 15 categorias
    case TOTAL_25_CATEGORIES = 'TOTAL_25_CATEGORIES';       // 25 categorias
    case PERFECTIONIST = 'PERFECTIONIST';                   // Todas despesas categorizadas no mês

        // ========== NOVAS CONQUISTAS - CARTÕES ==========
    case FIRST_CARD = 'FIRST_CARD';                         // Primeiro cartão cadastrado
    case FIRST_INVOICE_PAID = 'FIRST_INVOICE_PAID';         // Primeira fatura paga
    case INVOICES_12_PAID = 'INVOICES_12_PAID';             // 12 faturas pagas no ano

        // ========== NOVAS CONQUISTAS - TEMPO DE USO ==========
    case ANNIVERSARY_1_YEAR = 'ANNIVERSARY_1_YEAR';         // 1 ano usando o app
    case ANNIVERSARY_2_YEARS = 'ANNIVERSARY_2_YEARS';       // 2 anos usando o app

        // ========== NOVAS CONQUISTAS - NÍVEIS ==========
    case LEVEL_10 = 'LEVEL_10';                             // Nível 10
    case LEVEL_12 = 'LEVEL_12';                             // Nível 12
    case LEVEL_15 = 'LEVEL_15';                             // Nível 15 (máximo)

        // ========== NOVAS CONQUISTAS - ESPECIAIS (DIVERTIDAS) ==========
    case EARLY_BIRD = 'EARLY_BIRD';                         // Lançamento antes das 6h
    case NIGHT_OWL = 'NIGHT_OWL';                           // Lançamento após 23h
    case CHRISTMAS = 'CHRISTMAS';                           // Lançamento no dia 25/12
    case NEW_YEAR = 'NEW_YEAR';                             // Lançamento no dia 01/01
    case WEEKEND_WARRIOR = 'WEEKEND_WARRIOR';               // 10 lançamentos em fins de semana
    case SPEED_DEMON = 'SPEED_DEMON';                       // 5 lançamentos em um único dia

        // ========== CONQUISTA DE PERFIL ==========
    case PROFILE_COMPLETE = 'PROFILE_COMPLETE';             // Perfil completo

        // ========== CONQUISTAS DE INDICAÇÃO ==========
    case FIRST_REFERRAL = 'FIRST_REFERRAL';                 // Primeira indicação
    case REFERRALS_5 = 'REFERRALS_5';                       // 5 indicações
    case REFERRALS_10 = 'REFERRALS_10';                     // 10 indicações
    case REFERRALS_25 = 'REFERRALS_25';                     // 25 indicações (Influenciador)

    /**
     * Retorna se a conquista é exclusiva Pro
     */
    public function isProOnly(): bool
    {
        return match ($this) {
            self::MASTER_ORGANIZATION,
            self::ECONOMIST_MASTER,
            self::CONSISTENCY_TOTAL,
            self::META_ACHIEVED,
            self::PREMIUM_USER,
            self::LEVEL_8,
            self::LEVEL_10,
            self::LEVEL_12,
            self::LEVEL_15,
            self::SAVER_30,
            self::POSITIVE_6_MONTHS,
            self::POSITIVE_12_MONTHS,
            self::INVOICES_12_PAID,
            self::ANNIVERSARY_2_YEARS,
            self::DAYS_365_ACTIVE,
            self::TOTAL_1000_LAUNCHES => true,
            default => false,
        };
    }

    /**
     * Nome exibido da conquista
     */
    public function displayName(): string
    {
        return match ($this) {
            // Free
            self::FIRST_LAUNCH => 'Início',
            self::STREAK_3 => '3 Dias Ativos',
            self::STREAK_7 => '7 Dias Ativos',
            self::DAYS_30_USING => '30 Dias Usando',
            self::TOTAL_10_LAUNCHES => '10 Lançamentos',
            self::TOTAL_5_CATEGORIES => '5 Categorias',

            // Pro
            self::MASTER_ORGANIZATION => 'Mestre da Organização',
            self::ECONOMIST_MASTER => 'Economista Nato',
            self::CONSISTENCY_TOTAL => 'Consistência Total',
            self::META_ACHIEVED => 'Meta Batida',
            self::PREMIUM_USER => 'Usuário Premium',
            self::LEVEL_8 => 'Nível 8',

            // Comuns
            self::POSITIVE_MONTH => 'Mês Vitorioso',
            self::TOTAL_100_LAUNCHES => 'Centenário',
            self::LEVEL_5 => 'Expert Financeiro',

            // Novas - Lançamentos
            self::TOTAL_250_LAUNCHES => 'Produtivo',
            self::TOTAL_500_LAUNCHES => 'Historiador',
            self::TOTAL_1000_LAUNCHES => 'Arquivista',

            // Novas - Dias Ativos
            self::DAYS_50_ACTIVE => 'Dedicado',
            self::DAYS_100_ACTIVE => 'Comprometido',
            self::DAYS_365_ACTIVE => 'Veterano Anual',

            // Novas - Economia
            self::SAVER_10 => 'Poupador',
            self::SAVER_20 => 'Investidor',
            self::SAVER_30 => 'Milionário',
            self::POSITIVE_3_MONTHS => 'Consistente',
            self::POSITIVE_6_MONTHS => 'Focado',
            self::POSITIVE_12_MONTHS => 'Imbatível',

            // Novas - Organização
            self::TOTAL_15_CATEGORIES => 'Categorizador',
            self::TOTAL_25_CATEGORIES => 'Organizador Master',
            self::PERFECTIONIST => 'Perfeccionista',

            // Novas - Cartões
            self::FIRST_CARD => 'Primeiro Cartão',
            self::FIRST_INVOICE_PAID => 'Fatura Paga',
            self::INVOICES_12_PAID => 'Controle Total',

            // Novas - Tempo de Uso
            self::ANNIVERSARY_1_YEAR => 'Aniversário',
            self::ANNIVERSARY_2_YEARS => 'Fiel',

            // Novas - Níveis
            self::LEVEL_10 => 'Veterano',
            self::LEVEL_12 => 'Guru Financeiro',
            self::LEVEL_15 => 'Imperador',

            // Novas - Especiais
            self::EARLY_BIRD => 'Madrugador',
            self::NIGHT_OWL => 'Coruja',
            self::CHRISTMAS => 'Natalino',
            self::NEW_YEAR => 'Ano Novo',
            self::WEEKEND_WARRIOR => 'Guerreiro de Fim de Semana',
            self::SPEED_DEMON => 'Velocista',

            // Perfil
            self::PROFILE_COMPLETE => 'Perfil Completo',

            // Indicação
            self::FIRST_REFERRAL => 'Primeira Indicação',
            self::REFERRALS_5 => 'Influenciador',
            self::REFERRALS_10 => 'Embaixador',
            self::REFERRALS_25 => 'Lenda',
        };
    }

    /**
     * Descrição da conquista
     */
    public function description(): string
    {
        return match ($this) {
            // Free
            self::FIRST_LAUNCH => 'Registre seu primeiro lançamento financeiro',
            self::STREAK_3 => 'Alcance 3 dias ativos com lançamentos',
            self::STREAK_7 => 'Alcance 7 dias ativos com lançamentos',
            self::DAYS_30_USING => 'Use o sistema por 30 dias',
            self::TOTAL_10_LAUNCHES => 'Registre 10 lançamentos no total',
            self::TOTAL_5_CATEGORIES => 'Crie 5 categorias personalizadas',

            // Pro
            self::MASTER_ORGANIZATION => 'Tenha 50+ lançamentos categorizados corretamente',
            self::ECONOMIST_MASTER => 'Economize 25% da receita em um mês',
            self::CONSISTENCY_TOTAL => 'Alcance 30 dias ativos com lançamentos',
            self::META_ACHIEVED => 'Bata uma meta financeira',
            self::PREMIUM_USER => 'Torne-se um assinante Pro',
            self::LEVEL_8 => 'Alcance o nível 8',

            // Comuns
            self::POSITIVE_MONTH => 'Finalize um mês com saldo positivo',
            self::TOTAL_100_LAUNCHES => 'Registre 100 lançamentos no total',
            self::LEVEL_5 => 'Alcance o nível 5',

            // Novas - Lançamentos
            self::TOTAL_250_LAUNCHES => 'Registre 250 lançamentos no total',
            self::TOTAL_500_LAUNCHES => 'Registre 500 lançamentos no total',
            self::TOTAL_1000_LAUNCHES => 'Registre 1.000 lançamentos no total',

            // Novas - Dias Ativos
            self::DAYS_50_ACTIVE => 'Alcance 50 dias ativos com lançamentos',
            self::DAYS_100_ACTIVE => 'Alcance 100 dias ativos com lançamentos',
            self::DAYS_365_ACTIVE => 'Alcance 365 dias ativos (1 ano de dedicação!)',

            // Novas - Economia
            self::SAVER_10 => 'Economize 10% da receita em um mês',
            self::SAVER_20 => 'Economize 20% da receita em um mês',
            self::SAVER_30 => 'Economize 30% da receita em um mês',
            self::POSITIVE_3_MONTHS => '3 meses seguidos com saldo positivo',
            self::POSITIVE_6_MONTHS => '6 meses seguidos com saldo positivo',
            self::POSITIVE_12_MONTHS => '12 meses seguidos com saldo positivo',

            // Novas - Organização
            self::TOTAL_15_CATEGORIES => 'Crie 15 categorias personalizadas',
            self::TOTAL_25_CATEGORIES => 'Crie 25 categorias personalizadas',
            self::PERFECTIONIST => 'Categorize todas despesas em um mês',

            // Novas - Cartões
            self::FIRST_CARD => 'Cadastre seu primeiro cartão de crédito',
            self::FIRST_INVOICE_PAID => 'Pague sua primeira fatura de cartão',
            self::INVOICES_12_PAID => 'Pague 12 faturas de cartão no ano',

            // Novas - Tempo de Uso
            self::ANNIVERSARY_1_YEAR => 'Complete 1 ano usando o Lukrato',
            self::ANNIVERSARY_2_YEARS => 'Complete 2 anos usando o Lukrato',

            // Novas - Níveis
            self::LEVEL_10 => 'Alcance o nível 10',
            self::LEVEL_12 => 'Alcance o nível 12',
            self::LEVEL_15 => 'Alcance o nível máximo 15',

            // Novas - Especiais
            self::EARLY_BIRD => 'Faça um lançamento antes das 6h da manhã',
            self::NIGHT_OWL => 'Faça um lançamento após as 23h',
            self::CHRISTMAS => 'Faça um lançamento no dia de Natal (25/12)',
            self::NEW_YEAR => 'Faça um lançamento no Ano Novo (01/01)',
            self::WEEKEND_WARRIOR => 'Faça 10 lançamentos em fins de semana',
            self::SPEED_DEMON => 'Faça 5 lançamentos em um único dia',

            // Perfil
            self::PROFILE_COMPLETE => 'Complete todos os dados do seu perfil',

            // Indicação
            self::FIRST_REFERRAL => 'Faça sua primeira indicação completada',
            self::REFERRALS_5 => 'Faça 5 indicações completadas',
            self::REFERRALS_10 => 'Faça 10 indicações completadas',
            self::REFERRALS_25 => 'Faça 25 indicações completadas',
        };
    }

    /**
     * Ícone FontAwesome ou Emoji
     */
    public function icon(): string
    {
        return match ($this) {
            // Free
            self::FIRST_LAUNCH => '🎯',
            self::STREAK_3 => '🔥',
            self::STREAK_7 => '⚡',
            self::DAYS_30_USING => '📅',
            self::TOTAL_10_LAUNCHES => '📊',
            self::TOTAL_5_CATEGORIES => '🎨',

            // Pro
            self::MASTER_ORGANIZATION => '👑',
            self::ECONOMIST_MASTER => '💎',
            self::CONSISTENCY_TOTAL => '🏆',
            self::META_ACHIEVED => '🎖️',
            self::PREMIUM_USER => '⭐',
            self::LEVEL_8 => '🌟',

            // Comuns
            self::POSITIVE_MONTH => '💰',
            self::TOTAL_100_LAUNCHES => '💯',
            self::LEVEL_5 => '🎓',

            // Novas - Lançamentos
            self::TOTAL_250_LAUNCHES => '📝',
            self::TOTAL_500_LAUNCHES => '📚',
            self::TOTAL_1000_LAUNCHES => '🏛️',

            // Novas - Dias Ativos
            self::DAYS_50_ACTIVE => '🌟',
            self::DAYS_100_ACTIVE => '💫',
            self::DAYS_365_ACTIVE => '🌠',

            // Novas - Economia
            self::SAVER_10 => '💵',
            self::SAVER_20 => '💰',
            self::SAVER_30 => '🏦',
            self::POSITIVE_3_MONTHS => '📈',
            self::POSITIVE_6_MONTHS => '🎯',
            self::POSITIVE_12_MONTHS => '🏅',

            // Novas - Organização
            self::TOTAL_15_CATEGORIES => '🗂️',
            self::TOTAL_25_CATEGORIES => '📁',
            self::PERFECTIONIST => '✅',

            // Novas - Cartões
            self::FIRST_CARD => '💳',
            self::FIRST_INVOICE_PAID => '🧾',
            self::INVOICES_12_PAID => '📆',

            // Novas - Tempo de Uso
            self::ANNIVERSARY_1_YEAR => '🎂',
            self::ANNIVERSARY_2_YEARS => '🏅',

            // Novas - Níveis
            self::LEVEL_10 => '🎖️',
            self::LEVEL_12 => '🧙',
            self::LEVEL_15 => '👑',

            // Novas - Especiais
            self::EARLY_BIRD => '🌅',
            self::NIGHT_OWL => '🌙',
            self::CHRISTMAS => '🎄',
            self::NEW_YEAR => '🎆',
            self::WEEKEND_WARRIOR => '⚔️',
            self::SPEED_DEMON => '🚀',

            // Perfil
            self::PROFILE_COMPLETE => '👤',

            // Indicação
            self::FIRST_REFERRAL => '🤝',
            self::REFERRALS_5 => '👥',
            self::REFERRALS_10 => '📢',
            self::REFERRALS_25 => '🏆',
        };
    }

    /**
     * Pontos de recompensa
     */
    public function pointsReward(): int
    {
        return match ($this) {
            // Free
            self::FIRST_LAUNCH => 20,
            self::STREAK_3 => 30,
            self::STREAK_7 => 50,
            self::DAYS_30_USING => 100,
            self::TOTAL_10_LAUNCHES => 30,
            self::TOTAL_5_CATEGORIES => 25,

            // Pro
            self::MASTER_ORGANIZATION => 200,
            self::ECONOMIST_MASTER => 250,
            self::CONSISTENCY_TOTAL => 300,
            self::META_ACHIEVED => 150,
            self::PREMIUM_USER => 100,
            self::LEVEL_8 => 500,

            // Comuns
            self::POSITIVE_MONTH => 75,
            self::TOTAL_100_LAUNCHES => 150,
            self::LEVEL_5 => 200,

            // Novas - Lançamentos
            self::TOTAL_250_LAUNCHES => 200,
            self::TOTAL_500_LAUNCHES => 350,
            self::TOTAL_1000_LAUNCHES => 750,

            // Novas - Dias Ativos
            self::DAYS_50_ACTIVE => 100,
            self::DAYS_100_ACTIVE => 250,
            self::DAYS_365_ACTIVE => 1000,

            // Novas - Economia
            self::SAVER_10 => 50,
            self::SAVER_20 => 100,
            self::SAVER_30 => 200,
            self::POSITIVE_3_MONTHS => 150,
            self::POSITIVE_6_MONTHS => 300,
            self::POSITIVE_12_MONTHS => 600,

            // Novas - Organização
            self::TOTAL_15_CATEGORIES => 50,
            self::TOTAL_25_CATEGORIES => 100,
            self::PERFECTIONIST => 75,

            // Novas - Cartões
            self::FIRST_CARD => 30,
            self::FIRST_INVOICE_PAID => 50,
            self::INVOICES_12_PAID => 300,

            // Novas - Tempo de Uso
            self::ANNIVERSARY_1_YEAR => 500,
            self::ANNIVERSARY_2_YEARS => 1000,

            // Novas - Níveis
            self::LEVEL_10 => 750,
            self::LEVEL_12 => 1000,
            self::LEVEL_15 => 2000,

            // Novas - Especiais
            self::EARLY_BIRD => 25,
            self::NIGHT_OWL => 25,
            self::CHRISTMAS => 100,
            self::NEW_YEAR => 100,
            self::WEEKEND_WARRIOR => 50,
            self::SPEED_DEMON => 40,

            // Perfil
            self::PROFILE_COMPLETE => 50,

            // Indicação
            self::FIRST_REFERRAL => 75,
            self::REFERRALS_5 => 200,
            self::REFERRALS_10 => 400,
            self::REFERRALS_25 => 1000,
        };
    }

    /**
     * Categoria da conquista
     */
    public function category(): string
    {
        return match ($this) {
            self::STREAK_3, self::STREAK_7, self::CONSISTENCY_TOTAL,
            self::DAYS_50_ACTIVE, self::DAYS_100_ACTIVE, self::DAYS_365_ACTIVE => 'streak',

            self::POSITIVE_MONTH, self::ECONOMIST_MASTER, self::META_ACHIEVED,
            self::SAVER_10, self::SAVER_20, self::SAVER_30,
            self::POSITIVE_3_MONTHS, self::POSITIVE_6_MONTHS, self::POSITIVE_12_MONTHS => 'financial',

            self::LEVEL_5, self::LEVEL_8, self::LEVEL_10, self::LEVEL_12, self::LEVEL_15 => 'level',

            self::PREMIUM_USER => 'premium',

            self::FIRST_CARD, self::FIRST_INVOICE_PAID, self::INVOICES_12_PAID => 'cards',

            self::ANNIVERSARY_1_YEAR, self::ANNIVERSARY_2_YEARS => 'milestone',

            self::EARLY_BIRD, self::NIGHT_OWL, self::CHRISTMAS, self::NEW_YEAR,
            self::WEEKEND_WARRIOR, self::SPEED_DEMON => 'special',

            default => 'usage',
        };
    }

    /**
     * Obter tooltip explicativo
     */
    public function tooltip(): string
    {
        $desc = $this->description();
        if ($this->isProOnly()) {
            return $desc . ' [Exclusivo Pro 💎]';
        }
        return $desc;
    }

    /**
     * Tipo de plano para a conquista
     */
    public function planType(): string
    {
        if ($this->isProOnly()) {
            return 'pro';
        }

        // Conquistas disponíveis para todos
        return match ($this) {
            self::POSITIVE_MONTH, self::TOTAL_100_LAUNCHES, self::LEVEL_5,
            self::TOTAL_250_LAUNCHES, self::TOTAL_500_LAUNCHES,
            self::DAYS_50_ACTIVE, self::DAYS_100_ACTIVE,
            self::SAVER_10, self::SAVER_20,
            self::POSITIVE_3_MONTHS,
            self::TOTAL_15_CATEGORIES, self::TOTAL_25_CATEGORIES, self::PERFECTIONIST,
            self::FIRST_CARD, self::FIRST_INVOICE_PAID,
            self::ANNIVERSARY_1_YEAR,
            self::LEVEL_10,
            self::EARLY_BIRD, self::NIGHT_OWL, self::CHRISTMAS, self::NEW_YEAR,
            self::WEEKEND_WARRIOR, self::SPEED_DEMON => 'all',
            default => 'free',
        };
    }
}
