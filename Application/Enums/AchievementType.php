<?php

namespace Application\Enums;

/**
 * Enum: AchievementType
 * 
 * Tipos de conquistas disponíveis no sistema
 */
enum AchievementType: string
{
    case FIRST_LAUNCH = 'FIRST_LAUNCH';
    case STREAK_7 = 'STREAK_7';
    case STREAK_30 = 'STREAK_30';
    case POSITIVE_MONTH = 'POSITIVE_MONTH';
    case LEVEL_5 = 'LEVEL_5';
    case TOTAL_100_LAUNCHES = 'TOTAL_100_LAUNCHES';
    case TOTAL_10_CATEGORIES = 'TOTAL_10_CATEGORIES';
    case BALANCE_POSITIVE = 'BALANCE_POSITIVE';

    /**
     * Nome exibido da conquista
     */
    public function displayName(): string
    {
        return match ($this) {
            self::FIRST_LAUNCH => 'Primeiro Passo',
            self::STREAK_7 => 'Disciplinado',
            self::STREAK_30 => 'Mestre da Constância',
            self::POSITIVE_MONTH => 'Mês Vitorioso',
            self::LEVEL_5 => 'Expert Financeiro',
            self::TOTAL_100_LAUNCHES => 'Centenário',
            self::TOTAL_10_CATEGORIES => 'Organizador',
            self::BALANCE_POSITIVE => 'No Azul',
        };
    }

    /**
     * Descrição da conquista
     */
    public function description(): string
    {
        return match ($this) {
            self::FIRST_LAUNCH => 'Registre seu primeiro lançamento financeiro',
            self::STREAK_7 => 'Mantenha 7 dias consecutivos com pelo menos 1 lançamento',
            self::STREAK_30 => 'Mantenha 30 dias consecutivos com pelo menos 1 lançamento',
            self::POSITIVE_MONTH => 'Finalize um mês com saldo positivo',
            self::LEVEL_5 => 'Alcance o nível máximo 5',
            self::TOTAL_100_LAUNCHES => 'Registre 100 lançamentos no total',
            self::TOTAL_10_CATEGORIES => 'Crie 10 categorias personalizadas',
            self::BALANCE_POSITIVE => 'Atinja saldo geral positivo pela primeira vez',
        };
    }

    /**
     * Ícone FontAwesome
     */
    public function icon(): string
    {
        return match ($this) {
            self::FIRST_LAUNCH => 'fa-rocket',
            self::STREAK_7 => 'fa-fire',
            self::STREAK_30 => 'fa-trophy',
            self::POSITIVE_MONTH => 'fa-calendar-check',
            self::LEVEL_5 => 'fa-crown',
            self::TOTAL_100_LAUNCHES => 'fa-star',
            self::TOTAL_10_CATEGORIES => 'fa-folder-tree',
            self::BALANCE_POSITIVE => 'fa-chart-line',
        };
    }

    /**
     * Pontos de recompensa
     */
    public function pointsReward(): int
    {
        return match ($this) {
            self::FIRST_LAUNCH => 20,
            self::STREAK_7 => 50,
            self::STREAK_30 => 150,
            self::POSITIVE_MONTH => 75,
            self::LEVEL_5 => 200,
            self::TOTAL_100_LAUNCHES => 100,
            self::TOTAL_10_CATEGORIES => 50,
            self::BALANCE_POSITIVE => 80,
        };
    }

    /**
     * Categoria da conquista
     */
    public function category(): string
    {
        return match ($this) {
            self::STREAK_7, self::STREAK_30 => 'streak',
            self::POSITIVE_MONTH, self::BALANCE_POSITIVE => 'financial',
            self::LEVEL_5 => 'level',
            default => 'usage',
        };
    }
}
