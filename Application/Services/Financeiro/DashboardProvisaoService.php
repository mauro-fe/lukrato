<?php

namespace Application\Services\Financeiro;

use Application\Models\Lancamento;
use Application\Models\Conta;
use Application\Models\FaturaCartaoItem;
use Application\Models\CartaoCredito;
use Application\Enums\LancamentoTipo;
use Application\DTO\ProvisaoResultDTO;
use Application\DTO\ProvisaoTotaisDTO;
use Application\DTO\ProvisaoVencidosDTO;
use Application\DTO\ProvisaoParcelasDTO;

class DashboardProvisaoService
{
    /**
     * Gera todos os dados de provisão para o mês informado.
     * Baseado em lançamentos pendentes (pago = false) em vez de agendamentos.
     */
    public function generate(int $userId, string $month): ProvisaoResultDTO
    {
        $start = "{$month}-01";
        $end   = date('Y-m-t', strtotime($start));
        $now   = date('Y-m-d');

        // 1) Lançamentos pendentes do mês
        $pendentesMes = $this->queryPendentesMes($userId, $start, $end);
        [$totalPagar, $totalReceber, $countPagar, $countReceber] = $this->sumPendentes($pendentesMes);

        // 2) Saldo atual
        $saldoAtual = $this->calcularSaldoAtualTotal($userId);

        // 3) Próximos vencimentos (lançamentos pendentes futuros)
        $proximos = $this->queryProximosVencimentos($userId, $now);

        // 4) Lançamentos vencidos (data < hoje e não pagos)
        $vencidosData = $this->queryVencidos($userId, $now);

        // 5) Parcelas ativas
        [$parcelasCount, $totalMensalParcelas] = $this->queryParcelasAtivas($userId);

        // 6) Faturas de cartão ── pendentes do mês + vencidas
        $cartoes = CartaoCredito::where('user_id', $userId)->get()->keyBy('id');
        $mesNum  = (int) date('m', strtotime($start));
        $anoNum  = (int) date('Y', strtotime($start));

        [$totalFaturas, $countFaturas, $proximosFaturas] = $this->queryFaturasPendentesMes($userId, $anoNum, $mesNum, $cartoes);
        [$totalFaturasVencidas, $countFaturasVencidas, $vencidosFaturas] = $this->queryFaturasVencidas($userId, $now, $cartoes);

        // ── Montar resposta ──
        return $this->buildResponse(
            $month,
            $saldoAtual,
            $totalPagar,
            $totalReceber,
            $countPagar,
            $countReceber,
            $totalFaturas,
            $countFaturas,
            $proximos,
            $proximosFaturas,
            $vencidosData,
            $vencidosFaturas,
            $totalFaturasVencidas,
            $countFaturasVencidas,
            $parcelasCount,
            $totalMensalParcelas
        );
    }

    // ─── Queries ────────────────────────────────────────────

    /**
     * Query base para lançamentos pendentes (não pagos, não cancelados, não transferência).
     * Exclui lançamentos de cartão de crédito (já tratados via FaturaCartaoItem).
     */
    private function basePendentes(int $userId): \Illuminate\Database\Eloquent\Builder
    {
        return Lancamento::where('user_id', $userId)
            ->where('pago', false)
            ->whereNull('cancelado_em')
            ->where('eh_transferencia', false)
            ->whereNull('cartao_credito_id');
    }

    /**
     * Lançamentos pendentes do mês (pelo campo data).
     */
    private function queryPendentesMes(int $userId, string $start, string $end): \Illuminate\Database\Eloquent\Collection
    {
        return $this->basePendentes($userId)
            ->whereBetween('data', [$start, $end])
            ->get();
    }

    /**
     * Soma lançamentos pendentes separando por tipo.
     */
    private function sumPendentes($lancamentos): array
    {
        $totalPagar = $totalReceber = $countPagar = $countReceber = 0;

        foreach ($lancamentos as $l) {
            $valor = (float) ($l->valor ?? 0);
            if ($l->tipo === LancamentoTipo::RECEITA->value) {
                $totalReceber += $valor;
                $countReceber++;
            } else {
                $totalPagar += $valor;
                $countPagar++;
            }
        }

        return [$totalPagar, $totalReceber, $countPagar, $countReceber];
    }

    private function calcularSaldoAtualTotal(int $userId): float
    {
        $contas = Conta::where('user_id', $userId)->where('ativo', true)->get();
        $saldo  = 0.0;

        foreach ($contas as $conta) {
            $saldo += $this->calcularSaldoConta($userId, $conta->id, date('Y-m-d'));
        }

        return $saldo;
    }

    public function calcularSaldoConta(int $userId, int $contaId, string $ate): float
    {
        $conta = Conta::find($contaId);
        $saldoInicial = (float)($conta->saldo_inicial ?? 0);

        $base = Lancamento::where('user_id', $userId)->where('pago', 1)->where('data', '<=', $ate)->where('conta_id', $contaId);

        $receitas = (float)(clone $base)->where('eh_transferencia', 0)->where('tipo', LancamentoTipo::RECEITA->value)->sum('valor');
        $despesas = (float)(clone $base)->where('eh_transferencia', 0)->where('tipo', LancamentoTipo::DESPESA->value)->sum('valor');

        $transfIn  = (float) Lancamento::where('user_id', $userId)->where('pago', 1)->where('data', '<=', $ate)->where('eh_transferencia', 1)->where('conta_id_destino', $contaId)->sum('valor');
        $transfOut = (float) Lancamento::where('user_id', $userId)->where('pago', 1)->where('data', '<=', $ate)->where('eh_transferencia', 1)->where('conta_id', $contaId)->sum('valor');

        return $saldoInicial + $receitas - $despesas + $transfIn - $transfOut;
    }

    public function calcularSaldoGlobal(int $userId, string $ate): float
    {
        $saldosIniciais = (float) Conta::where('user_id', $userId)->where('ativo', true)->sum('saldo_inicial');

        $base = Lancamento::where('user_id', $userId)->where('pago', 1)->where('data', '<=', $ate)->where('eh_transferencia', 0);
        $r    = (float)(clone $base)->where('tipo', LancamentoTipo::RECEITA->value)->sum('valor');
        $d    = (float)(clone $base)->where('tipo', LancamentoTipo::DESPESA->value)->sum('valor');

        return $saldosIniciais + $r - $d;
    }

    /**
     * Próximos vencimentos: lançamentos pendentes com data >= hoje.
     */
    private function queryProximosVencimentos(int $userId, string $now): \Illuminate\Support\Collection
    {
        return $this->basePendentes($userId)
            ->with(['categoria:id,nome'])
            ->where('data', '>=', $now)
            ->orderBy('data', 'asc')
            ->limit(5)
            ->get()
            ->map(fn($l) => [
                'id'               => $l->id,
                'titulo'           => $l->descricao,
                'tipo'             => $l->tipo,
                'valor'            => (float) ($l->valor ?? 0),
                'data_pagamento'   => $l->data instanceof \DateTimeInterface
                    ? $l->data->format('Y-m-d')
                    : (string) $l->data,
                'categoria'        => $l->categoria?->nome ?? null,
                'eh_parcelado'     => (bool) $l->eh_parcelado,
                'parcela_atual'    => $l->parcela_atual ?? $l->numero_parcela,
                'numero_parcelas'  => $l->total_parcelas,
                'recorrente'       => (bool) $l->recorrente,
            ]);
    }

    /**
     * Lançamentos vencidos: pendentes com data < hoje.
     */
    private function queryVencidos(int $userId, string $now): array
    {
        $query = $this->basePendentes($userId)
            ->where('data', '<', $now)
            ->orderBy('data', 'asc')
            ->get();

        $despesas = $query->filter(fn($l) => $l->tipo !== LancamentoTipo::RECEITA->value);
        $receitas = $query->filter(fn($l) => $l->tipo === LancamentoTipo::RECEITA->value);

        $items = $query->map(fn($l) => [
            'id'             => $l->id,
            'titulo'         => $l->descricao,
            'tipo'           => $l->tipo,
            'valor'          => (float) ($l->valor ?? 0),
            'data_pagamento' => $l->data instanceof \DateTimeInterface
                ? $l->data->format('Y-m-d')
                : (string) $l->data,
        ]);

        return [
            'items'    => $items,
            'despesas' => $despesas,
            'receitas' => $receitas,
        ];
    }

    /**
     * Parcelas ativas: lançamentos parcelados pendentes.
     */
    private function queryParcelasAtivas(int $userId): array
    {
        $parcelas = $this->basePendentes($userId)
            ->where('eh_parcelado', true)
            ->where(function ($q) {
                $q->where('total_parcelas', '>', 1)
                    ->orWhere('numero_parcela', '>', 0);
            })
            ->get();

        $total = 0;
        foreach ($parcelas as $p) {
            $total += (float) ($p->valor ?? 0);
        }

        return [$parcelas->count(), $total];
    }

    // ─── Faturas de Cartão ──────────────────────────────────

    private function queryFaturasPendentesMes(int $userId, int $ano, int $mes, $cartoes): array
    {
        $itens = FaturaCartaoItem::where('user_id', $userId)
            ->where('pago', false)
            ->whereYear('data_vencimento', $ano)
            ->whereMonth('data_vencimento', $mes)
            ->get();

        $grouped = $this->groupByCartao($itens);

        $total = 0;
        $faturas = [];
        foreach ($grouped as $cartaoId => $dados) {
            $total += $dados['total'];
            $cartao = $cartoes->get($cartaoId);
            if (!$cartao) continue;

            $faturas[] = [
                'id'                     => "fatura_{$cartaoId}_{$mes}_{$ano}",
                'titulo'                 => 'Fatura ' . $cartao->nome_cartao,
                'tipo'                   => 'fatura',
                'valor'                  => round($dados['total'], 2),
                'data_pagamento'         => $this->formatDate($dados['data_vencimento']),
                'categoria'              => null,
                'eh_parcelado'           => false,
                'parcela_atual'          => null,
                'numero_parcelas'        => null,
                'recorrente'             => false,
                'is_fatura'              => true,
                'cartao_id'              => $cartaoId,
                'cartao_nome'            => $cartao->nome_cartao,
                'cartao_ultimos_digitos' => $cartao->ultimos_digitos,
                'itens_count'            => $dados['itens'],
            ];
        }

        return [$total, count($grouped), $faturas];
    }

    private function queryFaturasVencidas(int $userId, string $now, $cartoes): array
    {
        $itens = FaturaCartaoItem::where('user_id', $userId)
            ->where('pago', false)
            ->where('data_vencimento', '<', $now)
            ->get();

        $grouped = $this->groupByCartao($itens);

        $total = 0;
        $faturas = [];
        foreach ($grouped as $cartaoId => $dados) {
            $total += $dados['total'];
            $cartao = $cartoes->get($cartaoId);
            if (!$cartao) continue;

            $faturas[] = [
                'id'             => 'fatura_vencida_' . $cartaoId,
                'titulo'         => 'Fatura ' . $cartao->nome_cartao,
                'tipo'           => 'fatura',
                'valor'          => round($dados['total'], 2),
                'data_pagamento' => $this->formatDate($dados['data_vencimento']),
                'is_fatura'      => true,
                'cartao_nome'    => $cartao->nome_cartao,
            ];
        }

        return [$total, count($grouped), $faturas];
    }

    // ─── Helpers ────────────────────────────────────────────

    private function groupByCartao($itens): array
    {
        $grouped = [];
        foreach ($itens as $item) {
            $cid = $item->cartao_credito_id;
            if (!isset($grouped[$cid])) {
                $grouped[$cid] = ['total' => 0, 'itens' => 0, 'data_vencimento' => $item->data_vencimento];
            }
            $grouped[$cid]['total'] += (float) $item->valor;
            $grouped[$cid]['itens']++;
        }
        return $grouped;
    }

    private function formatDate($date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d H:i:s');
        }
        return (string) $date;
    }

    // ─── Response Builder ───────────────────────────────────

    private function buildResponse(
        string $month,
        float $saldoAtual,
        float $totalPagar,
        float $totalReceber,
        int $countPagar,
        int $countReceber,
        float $totalFaturas,
        int $countFaturas,
        $proximos,
        array $proximosFaturas,
        array $vencidosData,
        array $vencidosFaturas,
        float $totalFaturasVencidas,
        int $countFaturasVencidas,
        int $parcelasCount,
        float $totalMensalParcelas
    ): ProvisaoResultDTO {
        $totalPagarComFaturas = $totalPagar + $totalFaturas;
        $saldoProjetado = $saldoAtual + $totalReceber - $totalPagarComFaturas;

        // Mesclar próximos vencimentos + faturas, ordenar por data
        $todosProximos = array_merge($proximos->values()->all(), $proximosFaturas);
        usort($todosProximos, fn($a, $b) => strcmp($a['data_pagamento'] ?? '', $b['data_pagamento'] ?? ''));
        $todosProximos = array_slice($todosProximos, 0, 5);

        // Mesclar vencidos
        $vencidosItems  = $vencidosData['items'];
        $despesas       = $vencidosData['despesas'];
        $receitas       = $vencidosData['receitas'];
        $todosVencidos  = array_merge($vencidosItems->values()->take(5)->all(), $vencidosFaturas);
        $totalVencidos  = round($vencidosItems->sum('valor'), 2) + $totalFaturasVencidas;
        $countVencidos  = $vencidosItems->count() + $countFaturasVencidas;

        $totalDespesasVencidas = $despesas->sum(fn($l) => (float) ($l->valor ?? 0));
        $totalReceitasVencidas = $receitas->sum(fn($l) => (float) ($l->valor ?? 0));

        return new ProvisaoResultDTO(
            month: $month,
            provisao: new ProvisaoTotaisDTO(
                aPagar: $totalPagarComFaturas,
                aReceber: $totalReceber,
                saldoProjetado: $saldoProjetado,
                saldoAtual: $saldoAtual,
                countPagar: $countPagar,
                countReceber: $countReceber,
                countFaturas: $countFaturas,
                totalFaturas: $totalFaturas,
            ),
            proximos: $todosProximos,
            vencidos: new ProvisaoVencidosDTO(
                count: $countVencidos,
                total: $totalVencidos,
                items: array_slice($todosVencidos, 0, 5),
                countFaturas: $countFaturasVencidas,
                totalFaturas: $totalFaturasVencidas,
                countDespesas: $despesas->count(),
                totalDespesas: $totalDespesasVencidas,
                countReceitas: $receitas->count(),
                totalReceitas: $totalReceitasVencidas,
            ),
            parcelas: new ProvisaoParcelasDTO(
                ativas: $parcelasCount,
                totalMensal: $totalMensalParcelas,
            ),
        );
    }
}
