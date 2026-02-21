<?php

declare(strict_types=1);

namespace Application\Enums;

/**
 * Níveis de severidade para logs persistidos no banco.
 */
enum LogLevel: string
{
    case INFO     = 'info';
    case WARNING  = 'warning';
    case ERROR    = 'error';
    case CRITICAL = 'critical';

    /**
     * Cor CSS para exibição no painel admin
     */
    public function color(): string
    {
        return match ($this) {
            self::INFO     => '#3b82f6',
            self::WARNING  => '#f59e0b',
            self::ERROR    => '#ef4444',
            self::CRITICAL => '#dc2626',
        };
    }

    /**
     * Ícone Lucide associado
     */
    public function icon(): string
    {
        return match ($this) {
            self::INFO     => 'info',
            self::WARNING  => 'alert-triangle',
            self::ERROR    => 'x-circle',
            self::CRITICAL => 'skull',
        };
    }

    /**
     * Se deve notificar o admin (push/email)
     */
    public function shouldNotify(): bool
    {
        return match ($this) {
            self::INFO, self::WARNING => false,
            self::ERROR, self::CRITICAL => true,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::INFO     => 'Info',
            self::WARNING  => 'Aviso',
            self::ERROR    => 'Erro',
            self::CRITICAL => 'Crítico',
        };
    }
}
