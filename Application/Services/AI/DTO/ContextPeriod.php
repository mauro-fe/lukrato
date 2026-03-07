<?php

declare(strict_types=1);

namespace Application\Services\AI\DTO;

use Carbon\Carbon;

/**
 * DTO com períodos pré-calculados para os collectors de contexto.
 * Evita recalcular datas em cada collector.
 */
class ContextPeriod
{
    public readonly string $hoje;
    public readonly string $inicioMes;
    public readonly string $fimMes;
    public readonly string $inicioMesAnterior;
    public readonly string $fimMesAnterior;
    public readonly int $mesNum;
    public readonly int $anoNum;
    public readonly string $dataFormatada;
    public readonly string $mesAtual;
    public readonly string $diaDaSemana;
    public readonly Carbon $now;

    public function __construct(?Carbon $now = null)
    {
        $this->now               = $now ?? now();
        $this->hoje              = $this->now->toDateString();
        $this->inicioMes         = $this->now->copy()->startOfMonth()->toDateString();
        $this->fimMes            = $this->now->copy()->endOfMonth()->toDateString();
        $this->inicioMesAnterior = $this->now->copy()->subMonth()->startOfMonth()->toDateString();
        $this->fimMesAnterior    = $this->now->copy()->subMonth()->endOfMonth()->toDateString();
        $this->mesNum            = (int) $this->now->format('m');
        $this->anoNum            = (int) $this->now->format('Y');
        $this->dataFormatada     = $this->now->format('d/m/Y');
        $this->mesAtual          = $this->now->translatedFormat('F/Y');
        $this->diaDaSemana       = $this->now->translatedFormat('l');
    }
}
