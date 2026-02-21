<?php

declare(strict_types=1);

namespace Application\Enums;

/**
 * Tipos de insight financeiro — define a severidade/cor do card no frontend.
 */
enum InsightType: string
{
    case SUCCESS = 'success';
    case WARNING = 'warning';
    case DANGER  = 'danger';
    case INFO    = 'info';

    /**
     * Cor CSS associada ao tipo
     */
    public function color(): string
    {
        return match ($this) {
            self::SUCCESS => '#10b981',
            self::WARNING => '#f59e0b',
            self::DANGER  => '#ef4444',
            self::INFO    => '#3b82f6',
        };
    }

    /**
     * Label amigável
     */
    public function label(): string
    {
        return match ($this) {
            self::SUCCESS => 'Positivo',
            self::WARNING => 'Atenção',
            self::DANGER  => 'Alerta',
            self::INFO    => 'Informação',
        };
    }
}
