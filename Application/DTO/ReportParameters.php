<?php

namespace Application\DTO;

use Carbon\Carbon;

/**
 * Data Transfer Object (DTO) para parâmetros de relatórios.
 * 
 * Encapsula todos os parâmetros necessários para geração de relatórios,
 * centralizando a lógica de determinação de uso de transferências.
 */
readonly class ReportParameters
{
    public function __construct(
        public Carbon $start,
        public Carbon $end,
        public ?int $accountId = null,
        public ?int $userId = null,
        public bool $includeTransfers = false
    ) {
    }

    /**
     * Determina se as transferências devem ser incluídas nos cálculos.
     * 
     * Regra: Transferências são sempre incluídas quando há filtro de conta específica,
     * ou quando explicitamente solicitado via parâmetro.
     */
    public function useTransfers(): bool
    {
        return $this->includeTransfers || $this->accountId !== null;
    }

    /**
     * Verifica se o relatório é filtrado por uma conta específica.
     */
    public function isAccountSpecific(): bool
    {
        return $this->accountId !== null;
    }

    /**
     * Verifica se o relatório é global (todas as contas).
     */
    public function isGlobal(): bool
    {
        return $this->accountId === null;
    }

    /**
     * Retorna o período em dias.
     */
    public function getPeriodInDays(): int
    {
        return $this->start->diffInDays($this->end) + 1;
    }

    /**
     * Verifica se o período engloba um único mês.
     */
    public function isSingleMonth(): bool
    {
        return $this->start->year === $this->end->year 
            && $this->start->month === $this->end->month;
    }

    /**
     * Verifica se o período engloba um único ano.
     */
    public function isSingleYear(): bool
    {
        return $this->start->year === $this->end->year;
    }

    /**
     * Retorna uma representação textual do período.
     */
    public function getPeriodLabel(): string
    {
        if ($this->isSingleMonth()) {
            return $this->start->format('m/Y');
        }

        if ($this->isSingleYear()) {
            return (string)$this->start->year;
        }

        return sprintf(
            '%s - %s',
            $this->start->format('d/m/Y'),
            $this->end->format('d/m/Y')
        );
    }

    /**
     * Factory method: Cria parâmetros para um mês específico.
     */
    public static function forMonth(int $year, int $month, ?int $accountId = null, ?int $userId = null): self
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = (clone $start)->endOfMonth()->endOfDay();

        return new self($start, $end, $accountId, $userId);
    }

    /**
     * Factory method: Cria parâmetros para um ano completo.
     */
    public static function forYear(int $year, ?int $accountId = null, ?int $userId = null): self
    {
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = (clone $start)->endOfYear()->endOfDay();

        return new self($start, $end, $accountId, $userId);
    }

    /**
     * Factory method: Cria parâmetros para período customizado.
     */
    public static function forPeriod(Carbon $start, Carbon $end, ?int $accountId = null, ?int $userId = null): self
    {
        return new self(
            (clone $start)->startOfDay(),
            (clone $end)->endOfDay(),
            $accountId,
            $userId
        );
    }

    /**
     * Cria uma cópia com transferências habilitadas/desabilitadas.
     */
    public function withTransfers(bool $includeTransfers): self
    {
        return new self(
            $this->start,
            $this->end,
            $this->accountId,
            $this->userId,
            $includeTransfers
        );
    }

    /**
     * Cria uma cópia para uma conta diferente.
     */
    public function forAccount(?int $accountId): self
    {
        return new self(
            $this->start,
            $this->end,
            $accountId,
            $this->userId,
            $this->includeTransfers
        );
    }
}