<?php

namespace Application\Enums;

/**
 * Enum: GamificationAction
 * 
 * Ações que geram pontos no sistema de gamificação
 */
enum GamificationAction: string
{
    case CREATE_LANCAMENTO = 'create_lancamento';
    case CREATE_CATEGORIA = 'create_categoria';
    case DAILY_ACTIVITY = 'daily_activity';
    case STREAK_7_DAYS = 'streak_7_days';
    case STREAK_30_DAYS = 'streak_30_days';
    case POSITIVE_MONTH = 'positive_month';
    case LEVEL_UP = 'level_up';

    /**
     * Pontos ganhos por cada ação
     */
    public function points(): int
    {
        return match ($this) {
            self::CREATE_LANCAMENTO => 5,
            self::CREATE_CATEGORIA => 10,
            self::DAILY_ACTIVITY => 10,
            self::STREAK_7_DAYS => 30,
            self::STREAK_30_DAYS => 100,
            self::POSITIVE_MONTH => 50,
            self::LEVEL_UP => 0, // Pontos são dados pela conquista, não pela ação
        };
    }

    /**
     * Descrição da ação
     */
    public function description(): string
    {
        return match ($this) {
            self::CREATE_LANCAMENTO => 'Criou um lançamento',
            self::CREATE_CATEGORIA => 'Criou uma categoria',
            self::DAILY_ACTIVITY => 'Atividade diária completa',
            self::STREAK_7_DAYS => '7 dias consecutivos ativos',
            self::STREAK_30_DAYS => '30 dias consecutivos ativos',
            self::POSITIVE_MONTH => 'Mês com saldo positivo',
            self::LEVEL_UP => 'Subiu de nível',
        };
    }

    /**
     * Verificar se pode ser registrado apenas uma vez por dia
     */
    public function isOncePerDay(): bool
    {
        return match ($this) {
            self::DAILY_ACTIVITY => true,
            default => false,
        };
    }
}
