<?php

declare(strict_types=1);

namespace Application\Services\Cartao;

use Application\Models\CartaoCredito;
use Application\Models\FaturaCartaoItem;
use Application\Models\Lancamento;

class CartaoFaturaReadService
{
    /**
     * Obter histórico de faturas pagas
     */
    public function obterHistoricoFaturasPagas(int $cartaoId, int $userId, int $limite = 12): array
    {
        $cartao = CartaoCredito::where('id', $cartaoId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $historico = FaturaCartaoItem::where('cartao_credito_id', $cartaoId)
            ->where('pago', true)
            ->selectRaw('YEAR(data_vencimento) as ano, MONTH(data_vencimento) as mes')
            ->selectRaw('MAX(data_pagamento) as data_pagamento')
            ->selectRaw('SUM(valor) as total')
            ->selectRaw('COUNT(*) as quantidade')
            ->groupBy('ano', 'mes')
            ->orderBy('ano', 'desc')
            ->orderBy('mes', 'desc')
            ->limit($limite)
            ->get();

        return [
            'cartao' => [
                'id' => $cartao->id,
                'nome' => $cartao->nome_cartao,
            ],
            'historico' => $historico->map(fn($item) => [
                'mes' => $item->mes,
                'ano' => $item->ano,
                'mes_nome' => $this->getNomeMes((int) $item->mes),
                'total' => (float) $item->total,
                'data_pagamento' => $item->data_pagamento,
                'quantidade_lancamentos' => $item->quantidade,
            ])->toArray(),
        ];
    }

    /**
     * Obter fatura do mês de um cartão
     */
    public function obterFaturaMes(int $cartaoId, int $mes, int $ano, int $userId): array
    {
        $cartao = CartaoCredito::where('id', $cartaoId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $itens = FaturaCartaoItem::where('cartao_credito_id', $cartaoId)
            ->whereYear('data_vencimento', $ano)
            ->whereMonth('data_vencimento', $mes)
            ->whereNull('cancelado_em')
            ->orderBy('data_compra', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $totalDespesasPendentes = $itens
            ->where('pago', false)
            ->where('tipo', '!=', 'estorno')
            ->sum('valor');
        $totalEstornos = $itens->where('tipo', 'estorno')->sum('valor');
        $total = max(0, $totalDespesasPendentes + $totalEstornos);
        $dataVencimento = sprintf('%04d-%02d-%02d', $ano, $mes, $cartao->dia_vencimento);

        return [
            'cartao' => [
                'id' => $cartao->id,
                'nome' => $cartao->nome_cartao,
                'ultimos_digitos' => $cartao->ultimos_digitos,
                'dia_vencimento' => $cartao->dia_vencimento,
                'bandeira' => $cartao->bandeira,
                'cor_cartao' => $cartao->cor_cartao ?? $cartao->conta->instituicaoFinanceira->cor_primaria ?? null,
            ],
            'itens' => $itens->map(fn($item) => [
                'id' => $item->id,
                'descricao' => $item->descricao,
                'valor' => $item->valor,
                'data_compra' => $item->data_compra,
                'data_vencimento' => $item->data_vencimento,
                'parcela_atual' => $item->parcela_atual ?? 1,
                'total_parcelas' => $item->total_parcelas ?? 1,
                'pago' => $item->pago,
                'categoria_id' => $item->categoria_id,
                'subcategoria_id' => $item->subcategoria_id,
                'mes_referencia' => $item->mes_referencia,
                'ano_referencia' => $item->ano_referencia,
                'recorrente' => (bool) $item->recorrente,
                'recorrencia_freq' => $item->recorrencia_freq,
                'recorrencia_pai_id' => $item->recorrencia_pai_id,
                'cancelado_em' => $item->cancelado_em,
            ])->toArray(),
            'total' => $total,
            'vencimento' => $dataVencimento,
            'mes' => $mes,
            'ano' => $ano,
        ];
    }

    /**
     * Obter meses com faturas pendentes
     */
    public function obterMesesComFaturasPendentes(int $cartaoId, int $userId): array
    {
        return FaturaCartaoItem::where('cartao_credito_id', $cartaoId)
            ->where('user_id', $userId)
            ->where('pago', false)
            ->selectRaw('YEAR(data_vencimento) as ano, MONTH(data_vencimento) as mes')
            ->groupBy('ano', 'mes')
            ->orderBy('ano')
            ->orderBy('mes')
            ->get()
            ->map(fn($item) => ['ano' => $item->ano, 'mes' => $item->mes])
            ->toArray();
    }

    /**
     * Verificar se fatura do mês está paga
     */
    public function faturaEstaPaga(int $cartaoId, int $mes, int $ano, int $userId): ?array
    {
        $cartao = CartaoCredito::where('id', $cartaoId)
            ->where('user_id', $userId)
            ->first();

        if (!$cartao) {
            return null;
        }

        $totalItens = FaturaCartaoItem::where('user_id', $userId)
            ->where('cartao_credito_id', $cartaoId)
            ->whereYear('data_vencimento', $ano)
            ->whereMonth('data_vencimento', $mes)
            ->count();

        if ($totalItens === 0) {
            return null;
        }

        $itensPagos = FaturaCartaoItem::where('user_id', $userId)
            ->where('cartao_credito_id', $cartaoId)
            ->whereYear('data_vencimento', $ano)
            ->whereMonth('data_vencimento', $mes)
            ->where('pago', true)
            ->count();

        if ($itensPagos < $totalItens) {
            return null;
        }

        $padraoObservacao = sprintf('Fatura %02d/%04d', $mes, $ano);

        $lancamentoPagamento = Lancamento::where('user_id', $userId)
            ->where('cartao_credito_id', $cartaoId)
            ->where('origem_tipo', 'pagamento_fatura')
            ->where('observacao', 'LIKE', "%{$padraoObservacao}%")
            ->orderBy('id', 'desc')
            ->first();

        if (!$lancamentoPagamento) {
            $nomeMes = $this->getNomeMes($mes);
            $padraoDescricaoAntigo = "- {$nomeMes}/{$ano}";

            $lancamentoPagamento = Lancamento::where('user_id', $userId)
                ->where('cartao_credito_id', $cartaoId)
                ->where('tipo', 'despesa')
                ->where('descricao', 'LIKE', "Pagamento Fatura%{$cartao->nome_cartao}%{$padraoDescricaoAntigo}%")
                ->orderBy('id', 'desc')
                ->first();
        }

        return [
            'pago' => true,
            'data_pagamento' => $lancamentoPagamento?->data,
            'valor' => $lancamentoPagamento ? (float) $lancamentoPagamento->valor : 0,
            'lancamento_id' => $lancamentoPagamento?->id,
        ];
    }

    /**
     * Verificar vencimentos próximos (para alertas)
     */
    public function verificarVencimentosProximos(int $userId, int $diasAlerta = 7): array
    {
        try {
            $dataHoje = new \DateTime();
            $dataLimite = (clone $dataHoje)->modify("+{$diasAlerta} days");

            $cartoes = CartaoCredito::where('user_id', $userId)
                ->where('ativo', true)
                ->get();

            $alertas = [];

            foreach ($cartoes as $cartao) {
                try {
                    $mesVenc = (int) $dataHoje->format('n');
                    $anoVenc = (int) $dataHoje->format('Y');

                    $diaReal = min($cartao->dia_vencimento, (int) date('t', mktime(0, 0, 0, $mesVenc, 1, $anoVenc)));
                    $dataVencimento = new \DateTime("{$anoVenc}-{$mesVenc}-{$diaReal}");

                    if ($dataVencimento < $dataHoje) {
                        $mesVenc++;
                        if ($mesVenc > 12) {
                            $mesVenc = 1;
                            $anoVenc++;
                        }
                        $diaReal = min($cartao->dia_vencimento, (int) date('t', mktime(0, 0, 0, $mesVenc, 1, $anoVenc)));
                        $dataVencimento = new \DateTime("{$anoVenc}-{$mesVenc}-{$diaReal}");
                    }

                    if ($dataVencimento <= $dataLimite && $dataVencimento >= $dataHoje) {
                        $totalFatura = FaturaCartaoItem::where('cartao_credito_id', $cartao->id)
                            ->where('pago', false)
                            ->whereYear('data_vencimento', $anoVenc)
                            ->whereMonth('data_vencimento', $mesVenc)
                            ->whereNull('cancelado_em')
                            ->sum('valor');

                        if ($totalFatura > 0) {
                            $diasFaltando = (int) $dataHoje->diff($dataVencimento)->format('%a');

                            $alertas[] = [
                                'cartao_id' => $cartao->id,
                                'nome_cartao' => $cartao->nome_cartao,
                                'data_vencimento' => $dataVencimento->format('Y-m-d'),
                                'dias_faltando' => $diasFaltando,
                                'valor_fatura' => (float) $totalFatura,
                                'tipo' => 'vencimento_proximo',
                                'gravidade' => $diasFaltando <= 3 ? 'critico' : 'atencao',
                                'mes' => $mesVenc,
                                'ano' => $anoVenc,
                            ];
                        }
                    }
                } catch (\Exception) {
                    continue;
                }
            }

            return $alertas;
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * Obter resumo dos parcelamentos ativos
     */
    public function obterResumoParcelamentos(int $cartaoId, int $mes, int $ano, ?int $userId = null): array
    {
        if ($userId) {
            CartaoCredito::where('id', $cartaoId)
                ->where('user_id', $userId)
                ->firstOrFail();
        }

        $itens = FaturaCartaoItem::where('cartao_credito_id', $cartaoId)
            ->where('total_parcelas', '>', 1)
            ->where('pago', false)
            ->orderBy('descricao')
            ->orderBy('parcela_atual')
            ->get();

        $parcelamentos = [];
        $agrupados = $itens->groupBy('descricao');

        foreach ($agrupados as $descricao => $grupo) {
            $primeiro = $grupo->first();
            $parcelamentos[] = [
                'descricao' => $descricao,
                'valor_parcela' => (float) $primeiro->valor,
                'parcelas_restantes' => $grupo->count(),
                'total_parcelas' => $primeiro->total_parcelas,
                'valor_restante' => (float) $grupo->sum('valor'),
            ];
        }

        $tresMeses = FaturaCartaoItem::where('cartao_credito_id', $cartaoId)
            ->where('pago', false)
            ->where('data_vencimento', '<=', now()->addMonths(3)->endOfMonth())
            ->sum('valor');

        $seisMeses = FaturaCartaoItem::where('cartao_credito_id', $cartaoId)
            ->where('pago', false)
            ->where('data_vencimento', '<=', now()->addMonths(6)->endOfMonth())
            ->sum('valor');

        return [
            'total_parcelamentos' => count($parcelamentos),
            'parcelamentos' => $parcelamentos,
            'projecao' => [
                'tres_meses' => (float) $tresMeses,
                'seis_meses' => (float) $seisMeses,
            ],
        ];
    }

    private function getNomeMes(int $mes): string
    {
        $meses = [
            1 => 'Jan',
            2 => 'Fev',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'Mai',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Set',
            10 => 'Out',
            11 => 'Nov',
            12 => 'Dez',
        ];

        return $meses[$mes] ?? (string) $mes;
    }
}
