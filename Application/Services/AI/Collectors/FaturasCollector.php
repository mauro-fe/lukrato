<?php

declare(strict_types=1);

namespace Application\Services\AI\Collectors;

use Application\Models\CartaoCredito;
use Application\Models\Fatura;
use Application\Models\FaturaCartaoItem;
use Application\Services\AI\DTO\ContextPeriod;
use Application\Services\AI\Interfaces\ContextCollectorInterface;
use Illuminate\Database\Capsule\Manager as DB;

class FaturasCollector implements ContextCollectorInterface
{
    public function collect(ContextPeriod $period, ?int $userId = null): array
    {
        // Quando user_id definido, pegar apenas cartões do usuário
        $cartaoIds = $userId
            ? CartaoCredito::where('user_id', $userId)->pluck('id')->toArray()
            : null;

        $faturaBase = Fatura::query();
        if ($cartaoIds !== null) $faturaBase->whereIn('cartao_credito_id', $cartaoIds);

        $totalFaturas   = (clone $faturaBase)->count();
        $pendentes      = (clone $faturaBase)->where('status', 'pendente')->count();
        $pagas          = (clone $faturaBase)->where('status', 'paga')->count();
        $parciais       = (clone $faturaBase)->where('status', 'parcial')->count();
        $canceladas     = (clone $faturaBase)->where('status', 'cancelado')->count();
        $valorPendente  = round((float) (clone $faturaBase)->where('status', 'pendente')->sum('valor_total'), 2);

        $itensMes = FaturaCartaoItem::where('mes_referencia', $period->mesNum)
            ->where('ano_referencia', $period->anoNum);
        if ($cartaoIds !== null) $itensMes->whereIn('cartao_credito_id', $cartaoIds);
        $totalItensMes   = $itensMes->count();
        $valorItensMes   = round((float) (clone $itensMes)->sum('valor'), 2);
        $itensPagosMes   = (clone $itensMes)->where('pago', 1)->count();
        $itensPendMes    = (clone $itensMes)->where('pago', 0)->whereNull('cancelado_em')->count();

        $topCartoesQuery = DB::table('faturas_cartao_itens')
            ->join('cartoes_credito', 'faturas_cartao_itens.cartao_credito_id', '=', 'cartoes_credito.id')
            ->where('faturas_cartao_itens.mes_referencia', $period->mesNum)
            ->where('faturas_cartao_itens.ano_referencia', $period->anoNum);
        if ($cartaoIds !== null) $topCartoesQuery->whereIn('faturas_cartao_itens.cartao_credito_id', $cartaoIds);

        $topCartoes = $topCartoesQuery->select('cartoes_credito.nome_cartao', DB::raw('SUM(faturas_cartao_itens.valor) as total'), DB::raw('COUNT(*) as qtd'))
            ->groupBy('cartoes_credito.nome_cartao')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'cartao'     => $r->nome_cartao,
                'total'      => round((float) $r->total, 2),
                'quantidade' => (int) $r->qtd,
            ])->toArray();

        return [
            'faturas' => [
                'total'                 => $totalFaturas,
                'pendentes'             => $pendentes,
                'pagas'                 => $pagas,
                'parciais'              => $parciais,
                'canceladas'            => $canceladas,
                'valor_pendente'        => $valorPendente,
                'itens_fatura_mes'      => $totalItensMes,
                'valor_itens_fatura_mes' => $valorItensMes,
                'itens_pagos_mes'       => $itensPagosMes,
                'itens_pendentes_mes'   => $itensPendMes,
                'top_cartoes_mes'       => $topCartoes,
            ],
        ];
    }
}
