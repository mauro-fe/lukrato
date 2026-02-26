<?php

namespace Application\Services;

use Application\Models\Lancamento;
use Application\Models\Conta;
use Application\Models\Agendamento;
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
     */
    public function generate(int $userId, string $month): ProvisaoResultDTO
    {
        $start = "{$month}-01";
        $end   = date('Y-m-t', strtotime($start));
        $now   = date('Y-m-d H:i:s');

        // 1) Agendamentos do mês
        $agendamentosMes = $this->queryAgendamentosMes($userId, $start, $end);
        [$totalPagar, $totalReceber, $countPagar, $countReceber] = $this->sumAgendamentos($agendamentosMes);

        // 2) Saldo atual
        $saldoAtual = $this->calcularSaldoAtualTotal($userId);

        // 3) Próximos vencimentos (agendamentos)
        $proximos = $this->queryProximosVencimentos($userId, $now);

        // 4) Agendamentos vencidos
        $vencidosData = $this->queryVencidos($userId, $now);

        // 5) Parcelas ativas
        [$parcelasCount, $totalMensalParcelas] = $this->queryParcelasAtivas($userId);

        // 6) Faturas de cartão ── pendentes do mês + vencidas
        $cartoes = CartaoCredito::where('user_id', $userId)->get()->keyBy('id');
        $mesNum  = (int) date('m', strtotime($start));
        $anoNum  = (int) date('Y', strtotime($start));

        [$totalFaturas, $countFaturas, $proximosFaturas] = $this->queryFaturasPendentesMes($userId, $anoNum, $mesNum, $cartoes);
        [$totalFaturasVencidas, $countFaturasVencidas, $vencidosFaturas] = $this->queryFaturasVencidas($userId, $now, $cartoes);

        // ── Mesclar e montar resposta ──
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

    private function queryAgendamentosMes(int $userId, string $start, string $end): \Illuminate\Database\Eloquent\Collection
    {
        return Agendamento::where('user_id', $userId)
            ->whereIn('status', ['pendente', 'notificado'])
            ->where(function ($q) {
                $q->whereNull('concluido_em')->orWhere('recorrente', true);
            })
            ->whereBetween('data_pagamento', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->get();
    }

    private function sumAgendamentos($agendamentos): array
    {
        $totalPagar = $totalReceber = $countPagar = $countReceber = 0;

        foreach ($agendamentos as $ag) {
            $valor = ($ag->valor_centavos ?? 0) / 100;
            if (strtolower($ag->tipo ?? '') === 'receita') {
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

        $base = Lancamento::where('user_id', $userId)->where('data', '<=', $ate)->where('conta_id', $contaId);

        $receitas = (float)(clone $base)->where('eh_transferencia', 0)->where('tipo', LancamentoTipo::RECEITA->value)->sum('valor');
        $despesas = (float)(clone $base)->where('eh_transferencia', 0)->where('tipo', LancamentoTipo::DESPESA->value)->sum('valor');

        $transfIn  = (float) Lancamento::where('user_id', $userId)->where('data', '<=', $ate)->where('eh_transferencia', 1)->where('conta_id_destino', $contaId)->sum('valor');
        $transfOut = (float) Lancamento::where('user_id', $userId)->where('data', '<=', $ate)->where('eh_transferencia', 1)->where('conta_id', $contaId)->sum('valor');

        return $saldoInicial + $receitas - $despesas + $transfIn - $transfOut;
    }

    public function calcularSaldoGlobal(int $userId, string $ate): float
    {
        $saldosIniciais = (float) Conta::where('user_id', $userId)->where('ativo', true)->sum('saldo_inicial');

        $base = Lancamento::where('user_id', $userId)->where('data', '<=', $ate)->where('eh_transferencia', 0);
        $r    = (float)(clone $base)->where('tipo', LancamentoTipo::RECEITA->value)->sum('valor');
        $d    = (float)(clone $base)->where('tipo', LancamentoTipo::DESPESA->value)->sum('valor');

        return $saldosIniciais + $r - $d;
    }

    private function queryProximosVencimentos(int $userId, string $now): \Illuminate\Support\Collection
    {
        return Agendamento::with(['categoria:id,nome'])
            ->where('user_id', $userId)
            ->whereIn('status', ['pendente', 'notificado'])
            ->where(function ($q) {
                $q->whereNull('concluido_em')->orWhere('recorrente', true);
            })
            ->where('data_pagamento', '>=', $now)
            ->orderBy('data_pagamento', 'asc')
            ->limit(5)
            ->get()
            ->map(fn($ag) => [
                'id'               => $ag->id,
                'titulo'           => $ag->titulo,
                'tipo'             => $ag->tipo,
                'valor'            => ($ag->valor_centavos ?? 0) / 100,
                'data_pagamento'   => $ag->data_pagamento instanceof \DateTimeInterface
                    ? $ag->data_pagamento->format('Y-m-d H:i:s')
                    : (string) $ag->data_pagamento,
                'categoria'        => $ag->categoria?->nome ?? null,
                'eh_parcelado'     => (bool) $ag->eh_parcelado,
                'parcela_atual'    => $ag->parcela_atual,
                'numero_parcelas'  => $ag->numero_parcelas,
                'recorrente'       => (bool) $ag->recorrente,
            ]);
    }

    private function queryVencidos(int $userId, string $now): array
    {
        $query = Agendamento::where('user_id', $userId)
            ->whereIn('status', ['pendente', 'notificado'])
            ->where(function ($q) {
                $q->whereNull('concluido_em')->orWhere('recorrente', true);
            })
            ->where('data_pagamento', '<', $now)
            ->orderBy('data_pagamento', 'asc')
            ->get();

        $despesas = $query->filter(fn($ag) => strtolower($ag->tipo ?? '') !== 'receita');
        $receitas = $query->filter(fn($ag) => strtolower($ag->tipo ?? '') === 'receita');

        $items = $query->map(fn($ag) => [
            'id'             => $ag->id,
            'titulo'         => $ag->titulo,
            'tipo'           => $ag->tipo,
            'valor'          => ($ag->valor_centavos ?? 0) / 100,
            'data_pagamento' => $ag->data_pagamento instanceof \DateTimeInterface
                ? $ag->data_pagamento->format('Y-m-d H:i:s')
                : (string) $ag->data_pagamento,
        ]);

        return [
            'items'    => $items,
            'despesas' => $despesas,
            'receitas' => $receitas,
        ];
    }

    private function queryParcelasAtivas(int $userId): array
    {
        $parcelas = Agendamento::where('user_id', $userId)
            ->where('eh_parcelado', true)
            ->whereIn('status', ['pendente', 'notificado'])
            ->whereNull('concluido_em')
            ->where('numero_parcelas', '>', 1)
            ->get();

        $total = 0;
        foreach ($parcelas as $p) {
            $total += ($p->valor_centavos ?? 0) / 100;
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

        $totalDespesasVencidas = $despesas->sum(fn($ag) => ($ag->valor_centavos ?? 0) / 100);
        $totalReceitasVencidas = $receitas->sum(fn($ag) => ($ag->valor_centavos ?? 0) / 100);

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
