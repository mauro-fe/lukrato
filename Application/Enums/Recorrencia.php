<?php

declare(strict_types=1);

namespace Application\Enums;

/**
 * Enum Recorrencia - Frequências de recorrência para lançamentos e agendamentos
 */
enum Recorrencia: string
{
    case SEMANAL    = 'semanal';
    case QUINZENAL  = 'quinzenal';
    case MENSAL     = 'mensal';
    case BIMESTRAL  = 'bimestral';
    case TRIMESTRAL = 'trimestral';
    case SEMESTRAL  = 'semestral';
    case ANUAL      = 'anual';

    /**
     * Retorna o intervalo DateInterval ou dias para cálculo de próxima data
     */
    public function interval(): string|int
    {
        return match ($this) {
            self::SEMANAL    => 7,
            self::QUINZENAL  => 14,
            self::MENSAL     => 'P1M',
            self::BIMESTRAL  => 'P2M',
            self::TRIMESTRAL => 'P3M',
            self::SEMESTRAL  => 'P6M',
            self::ANUAL      => 'P1Y',
        };
    }

    /**
     * Label amigável para exibição
     */
    public function label(): string
    {
        return match ($this) {
            self::SEMANAL    => 'Semanal',
            self::QUINZENAL  => 'Quinzenal',
            self::MENSAL     => 'Mensal',
            self::BIMESTRAL  => 'Bimestral',
            self::TRIMESTRAL => 'Trimestral',
            self::SEMESTRAL  => 'Semestral',
            self::ANUAL      => 'Anual',
        };
    }

    /**
     * Avança a data base pela frequência
     */
    public function advance(\DateTime $date): void
    {
        $interval = $this->interval();
        if (is_int($interval)) {
            $date->modify("+{$interval} days");
        } else {
            $date->add(new \DateInterval($interval));
        }
    }

    /**
     * Tenta criar a partir de string, retorna null se inválido
     */
    public static function tryFromString(?string $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::tryFrom(strtolower(trim($value)));
    }
}
