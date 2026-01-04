<?php

namespace Application\Enums;

/**
 * Enum: GamificationAction
 * 
 * Ações que geram pontos no sistema de gamificação
 * Pontos variam entre plano Free e Pro
 */
enum GamificationAction: string
{
    case CREATE_LANCAMENTO = 'create_lancamento';
    case CREATE_CATEGORIA = 'create_categoria';
    case VIEW_REPORT = 'view_report';
    case CREATE_META = 'create_meta';
    case CLOSE_MONTH = 'close_month';
    case DAILY_ACTIVITY = 'daily_activity';
    case STREAK_3_DAYS = 'streak_3_days';
    case STREAK_7_DAYS = 'streak_7_days';
    case STREAK_30_DAYS = 'streak_30_days';
    case POSITIVE_MONTH = 'positive_month';
    case LEVEL_UP = 'level_up';

    /**
     * Pontos ganhos por cada ação (Plano Free)
     */
    public function pointsFree(): int
    {
        return match ($this) {
            self::CREATE_LANCAMENTO => 10,
            self::CREATE_CATEGORIA => 20,
            self::VIEW_REPORT => 10,
            self::CREATE_META => 30,
            self::CLOSE_MONTH => 100,
            self::DAILY_ACTIVITY => 5,
            self::STREAK_3_DAYS => 20,
            self::STREAK_7_DAYS => 50,
            self::STREAK_30_DAYS => 150,
            self::POSITIVE_MONTH => 75,
            self::LEVEL_UP => 0,
        };
    }

    /**
     * Pontos ganhos por cada ação (Plano Pro)
     */
    public function pointsPro(): int
    {
        return match ($this) {
            self::CREATE_LANCAMENTO => 15,
            self::CREATE_CATEGORIA => 30,
            self::VIEW_REPORT => 25,
            self::CREATE_META => 60,
            self::CLOSE_MONTH => 200,
            self::DAILY_ACTIVITY => 10,
            self::STREAK_3_DAYS => 30,
            self::STREAK_7_DAYS => 75,
            self::STREAK_30_DAYS => 250,
            self::POSITIVE_MONTH => 100,
            self::LEVEL_UP => 0,
        };
    }

    /**
     * Retorna pontos baseado no plano do usuário
     * 
     * @param bool $isPro Se o usuário é Pro
     * @return int Pontos da ação
     */
    public function points(bool $isPro = false): int
    {
        return $isPro ? $this->pointsPro() : $this->pointsFree();
    }

    /**
     * Descrição da ação
     */
    public function description(): string
    {
        return match ($this) {
            self::CREATE_LANCAMENTO => 'Criou um lançamento',
            self::CREATE_CATEGORIA => 'Criou uma categoria',
            self::VIEW_REPORT => 'Visualizou um relatório',
            self::CREATE_META => 'Criou uma meta',
            self::CLOSE_MONTH => 'Fechou o mês',
            self::DAILY_ACTIVITY => 'Atividade diária completa',
            self::STREAK_3_DAYS => '3 dias consecutivos ativos',
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
