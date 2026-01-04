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
    case STREAK_3 = 'STREAK_3';                             // 3 dias consecutivos
    case STREAK_7 = 'STREAK_7';                             // 7 dias consecutivos
    case DAYS_30_USING = 'DAYS_30_USING';                   // 30 dias usando o sistema
    case FIRST_META = 'FIRST_META';                         // Primeira meta criada
    case TOTAL_10_LAUNCHES = 'TOTAL_10_LAUNCHES';           // 10 lançamentos
    case TOTAL_5_CATEGORIES = 'TOTAL_5_CATEGORIES';         // 5 categorias

        // ========== CONQUISTAS PRO ==========
    case MASTER_ORGANIZATION = 'MASTER_ORGANIZATION';       // Mestre da Organização (Pro)
    case ECONOMIST_MASTER = 'ECONOMIST_MASTER';             // Economista Nato (Pro)
    case CONSISTENCY_TOTAL = 'CONSISTENCY_TOTAL';           // Consistência Total - 30 dias seguidos (Pro)
    case META_ACHIEVED = 'META_ACHIEVED';                   // Meta Batida (Pro)
    case PREMIUM_USER = 'PREMIUM_USER';                     // Usuário Premium (Pro)
    case LEVEL_8 = 'LEVEL_8';                              // Nível Máximo (Pro)

        // ========== CONQUISTAS COMUNS ==========
    case POSITIVE_MONTH = 'POSITIVE_MONTH';                 // Mês positivo
    case TOTAL_100_LAUNCHES = 'TOTAL_100_LAUNCHES';         // 100 lançamentos
    case LEVEL_5 = 'LEVEL_5';                              // Nível 5

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
            self::LEVEL_8 => true,
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
            self::STREAK_3 => '3 Dias Seguidos',
            self::STREAK_7 => '7 Dias Seguidos',
            self::DAYS_30_USING => '30 Dias Usando',
            self::FIRST_META => 'Primeira Meta',
            self::TOTAL_10_LAUNCHES => '10 Lançamentos',
            self::TOTAL_5_CATEGORIES => '5 Categorias',

            // Pro
            self::MASTER_ORGANIZATION => 'Mestre da Organização',
            self::ECONOMIST_MASTER => 'Economista Nato',
            self::CONSISTENCY_TOTAL => 'Consistência Total',
            self::META_ACHIEVED => 'Meta Batida',
            self::PREMIUM_USER => 'Usuário Premium',
            self::LEVEL_8 => 'Nível Máximo',

            // Comuns
            self::POSITIVE_MONTH => 'Mês Vitorioso',
            self::TOTAL_100_LAUNCHES => 'Centenário',
            self::LEVEL_5 => 'Expert Financeiro',
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
            self::STREAK_3 => 'Mantenha 3 dias consecutivos com pelo menos 1 lançamento',
            self::STREAK_7 => 'Mantenha 7 dias consecutivos com pelo menos 1 lançamento',
            self::DAYS_30_USING => 'Use o sistema por 30 dias',
            self::FIRST_META => 'Crie sua primeira meta financeira',
            self::TOTAL_10_LAUNCHES => 'Registre 10 lançamentos no total',
            self::TOTAL_5_CATEGORIES => 'Crie 5 categorias personalizadas',

            // Pro
            self::MASTER_ORGANIZATION => 'Organize suas finanças como um profissional (Pro)',
            self::ECONOMIST_MASTER => 'Demonstre maestria em gestão financeira (Pro)',
            self::CONSISTENCY_TOTAL => 'Mantenha 30 dias consecutivos com lançamentos (Pro)',
            self::META_ACHIEVED => 'Bata sua primeira meta (Pro)',
            self::PREMIUM_USER => 'Torne-se um assinante Pro',
            self::LEVEL_8 => 'Alcance o nível máximo 8 (Pro)',

            // Comuns
            self::POSITIVE_MONTH => 'Finalize um mês com saldo positivo',
            self::TOTAL_100_LAUNCHES => 'Registre 100 lançamentos no total',
            self::LEVEL_5 => 'Alcance o nível 5',
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
            self::FIRST_META => '🎪',
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
            self::FIRST_META => 40,
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
        };
    }

    /**
     * Categoria da conquista
     */
    public function category(): string
    {
        return match ($this) {
            self::STREAK_3, self::STREAK_7, self::CONSISTENCY_TOTAL => 'streak',
            self::POSITIVE_MONTH, self::ECONOMIST_MASTER, self::META_ACHIEVED => 'financial',
            self::LEVEL_5, self::LEVEL_8 => 'level',
            self::PREMIUM_USER => 'premium',
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
}
