<?php

declare(strict_types=1);

namespace Application\DTO;

/**
 * Resultado estruturado da provisão financeira do dashboard.
 * Substitui o array solto com 16+ parâmetros no buildResponse().
 */
readonly class ProvisaoResultDTO
{
    public function __construct(
        public string              $month,
        public ProvisaoTotaisDTO   $provisao,
        public array               $proximos,
        public ProvisaoVencidosDTO $vencidos,
        public ProvisaoParcelasDTO $parcelas,
    ) {}

    public function toArray(): array
    {
        return [
            'month'    => $this->month,
            'provisao' => $this->provisao->toArray(),
            'proximos' => $this->proximos,
            'vencidos' => $this->vencidos->toArray(),
            'parcelas' => $this->parcelas->toArray(),
        ];
    }
}

readonly class ProvisaoTotaisDTO
{
    public function __construct(
        public float $aPagar,
        public float $aReceber,
        public float $saldoProjetado,
        public float $saldoAtual,
        public int   $countPagar,
        public int   $countReceber,
        public int   $countFaturas,
        public float $totalFaturas,
    ) {}

    public function toArray(): array
    {
        return [
            'a_pagar'         => round($this->aPagar, 2),
            'a_receber'       => round($this->aReceber, 2),
            'saldo_projetado' => round($this->saldoProjetado, 2),
            'saldo_atual'     => round($this->saldoAtual, 2),
            'count_pagar'     => $this->countPagar,
            'count_receber'   => $this->countReceber,
            'count_faturas'   => $this->countFaturas,
            'total_faturas'   => round($this->totalFaturas, 2),
        ];
    }
}

readonly class ProvisaoVencidosDTO
{
    public function __construct(
        public int   $count,
        public float $total,
        public array $items,
        public int   $countFaturas,
        public float $totalFaturas,
        public int   $countDespesas,
        public float $totalDespesas,
        public int   $countReceitas,
        public float $totalReceitas,
    ) {}

    public function toArray(): array
    {
        return [
            'count'         => $this->count,
            'total'         => round($this->total, 2),
            'items'         => $this->items,
            'count_faturas' => $this->countFaturas,
            'total_faturas' => round($this->totalFaturas, 2),
            'despesas'      => [
                'count' => $this->countDespesas,
                'total' => round($this->totalDespesas, 2),
            ],
            'receitas'      => [
                'count' => $this->countReceitas,
                'total' => round($this->totalReceitas, 2),
            ],
        ];
    }
}

readonly class ProvisaoParcelasDTO
{
    public function __construct(
        public int   $ativas,
        public float $totalMensal,
    ) {}

    public function toArray(): array
    {
        return [
            'ativas'       => $this->ativas,
            'total_mensal' => round($this->totalMensal, 2),
        ];
    }
}
