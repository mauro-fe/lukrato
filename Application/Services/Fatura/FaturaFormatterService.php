<?php

declare(strict_types=1);

namespace Application\Services\Fatura;

use Application\Container\ApplicationContainer;
use Application\Models\CartaoCredito;
use Application\Models\Fatura;

class FaturaFormatterService
{
    private FaturaInstallmentCalculatorService $calculator;

    public function __construct(
        ?FaturaInstallmentCalculatorService $calculator = null
    ) {
        $this->calculator = ApplicationContainer::resolveOrNew($calculator, FaturaInstallmentCalculatorService::class);
    }

    /**
     * Formatar fatura para listagem.
     *
     * @param int|null $mesRef Mês de referência para filtrar itens
     * @param int|null $anoRef Ano de referência para filtrar itens
     */
    public function formatarFaturaListagem(Fatura $fatura, ?int $mesRef = null, ?int $anoRef = null): array
    {
        $itens = $fatura->itens;

        $itensPagos = $itens->where('pago', 1)->count();
        $totalItens = $itens->count();
        $progresso = $totalItens > 0 ? ($itensPagos / $totalItens) * 100 : 0;

        $valorPendenteItens = $itens->where('pago', 0)->sum('valor');
        $valorEstornos = $itens->where('tipo', 'estorno')->sum('valor');
        $valorPendente = max(0, $valorPendenteItens + $valorEstornos);

        $totalDespesas = $itens->where('tipo', '!=', 'estorno')->sum('valor');
        $totalEstornos = abs($itens->where('tipo', 'estorno')->sum('valor'));

        $primeiroItemPendente = $itens->where('pago', 0)
            ->sortBy(function ($item) {
                return $item->ano_referencia * 100 + $item->mes_referencia;
            })
            ->first();

        $mesesReferencia = $itens
            ->map(function ($item) {
                return [
                    'mes' => $item->mes_referencia,
                    'ano' => $item->ano_referencia,
                ];
            })
            ->unique(function ($item) {
                return $item['ano'] . '-' . $item['mes'];
            })
            ->sortBy(function ($item) {
                return $item['ano'] * 100 + $item['mes'];
            })
            ->values()
            ->toArray();

        $primeiroMesRef = $itens->sortBy(function ($item) {
            return $item->ano_referencia * 100 + $item->mes_referencia;
        })->first();

        $dataVencimento = null;
        $mesFatura = null;
        $anoFatura = null;
        if (preg_match('/(\d{1,2})\/(\d{4})/', $fatura->descricao, $matches)) {
            $mesFatura = (int) $matches[1];
            $anoFatura = (int) $matches[2];
        }

        if ($mesFatura && $anoFatura && $fatura->cartaoCredito) {
            $diaVencimento = $fatura->cartaoCredito->dia_vencimento ?? null;
            if ($diaVencimento) {
                $maxDia = cal_days_in_month(CAL_GREGORIAN, $mesFatura, $anoFatura);
                $dia = min((int) $diaVencimento, $maxDia);
                $dataVencimento = sprintf('%04d-%02d-%02d', $anoFatura, $mesFatura, $dia);
            }
        }

        $mesRetorno = $mesFatura ?? ($mesRef !== null ? $mesRef : ($primeiroMesRef ? $primeiroMesRef->mes_referencia : null));
        $anoRetorno = $anoFatura ?? ($anoRef !== null ? $anoRef : ($primeiroMesRef ? $primeiroMesRef->ano_referencia : null));

        return [
            'id' => $fatura->id,
            'descricao' => $fatura->descricao,
            'valor_total' => round($valorPendente, 2),
            'numero_parcelas' => $fatura->numero_parcelas,
            'valor_parcela' => $fatura->valor_parcela,
            'data_compra' => $fatura->data_compra->format('Y-m-d'),
            'data_vencimento' => $dataVencimento,
            'mes_referencia' => $mesRetorno,
            'ano_referencia' => $anoRetorno,
            'meses_referencia' => $mesesReferencia,
            'proxima_parcela' => $primeiroItemPendente ? [
                'numero' => $primeiroItemPendente->parcela_atual,
                'mes' => $primeiroItemPendente->mes_referencia,
                'ano' => $primeiroItemPendente->ano_referencia,
            ] : null,
            'cartao' => $this->formatarCartao($fatura->cartaoCredito),
            'parcelas_pagas' => $itensPagos,
            'parcelas_pendentes' => $totalItens - $itensPagos,
            'total_despesas' => round((float) $totalDespesas, 2),
            'total_estornos' => round((float) $totalEstornos, 2),
            'progresso' => round($progresso, 2),
            'status' => $fatura->status ?? $this->calculator->determinarStatus((float) $progresso),
        ];
    }

    /**
     * Formatar fatura detalhada.
     */
    public function formatarFaturaDetalhada(Fatura $fatura): array
    {
        $parcelas = $fatura->itens->map(function ($item) use ($fatura) {
            return [
                'id' => $item->id,
                'numero_parcela' => $item->parcela_atual,
                'total_parcelas' => $item->total_parcelas ?? $fatura->numero_parcelas,
                'valor_parcela' => round((float) $item->valor, 2),
                'descricao' => $item->descricao ?? $fatura->descricao,
                'data_compra' => $item->data_compra?->format('Y-m-d'),
                'mes_referencia' => $item->mes_referencia,
                'ano_referencia' => $item->ano_referencia,
                'pago' => (bool) $item->pago,
                'data_pagamento' => $item->data_pagamento?->format('Y-m-d'),
                'tipo' => $item->tipo ?? 'despesa',
                'recorrente' => (bool) $item->recorrente,
                'recorrencia_freq' => $item->recorrencia_freq,
                'categoria_id' => $item->categoria_id ? (int) $item->categoria_id : null,
                'subcategoria_id' => $item->subcategoria_id ? (int) $item->subcategoria_id : null,
                'categoria' => $item->categoria ? [
                    'id' => (int) $item->categoria->id,
                    'nome' => $item->categoria->nome,
                    'icone' => $item->categoria->icone,
                    'tipo' => $item->categoria->tipo,
                ] : null,
                'subcategoria' => $item->subcategoria ? [
                    'id' => (int) $item->subcategoria->id,
                    'nome' => $item->subcategoria->nome,
                    'icone' => $item->subcategoria->icone,
                    'tipo' => $item->subcategoria->tipo,
                ] : null,
            ];
        })->values()->toArray();

        $itensPagos = $fatura->itens->where('pago', 1)->count();
        $totalItens = $fatura->itens->count();

        $valorPendenteItens = $fatura->itens->where('pago', 0)->sum('valor');
        $valorEstornos = $fatura->itens->where('tipo', 'estorno')->sum('valor');
        $valorPendente = max(0, $valorPendenteItens + $valorEstornos);

        $progresso = $fatura->progresso;
        $totalDespesas = $fatura->itens->where('tipo', '!=', 'estorno')->sum('valor');
        $totalEstornos = abs($fatura->itens->where('tipo', 'estorno')->sum('valor'));

        $mesReferencia = null;
        $anoReferencia = null;
        if (preg_match('/(\d{1,2})\/(\d{4})/', $fatura->descricao, $matches)) {
            $mesReferencia = (int) $matches[1];
            $anoReferencia = (int) $matches[2];
        }

        $dataVencimento = null;
        if ($mesReferencia && $anoReferencia && $fatura->cartaoCredito) {
            $diaVencimento = $fatura->cartaoCredito->dia_vencimento ?? 1;
            $dataVencimento = sprintf('%04d-%02d-%02d', $anoReferencia, $mesReferencia, $diaVencimento);
        }

        return [
            'id' => $fatura->id,
            'descricao' => $fatura->descricao,
            'valor_total' => round((float) $valorPendente, 2),
            'valor_original' => round((float) $fatura->valor_total, 2),
            'total_despesas' => round((float) $totalDespesas, 2),
            'total_estornos' => round((float) $totalEstornos, 2),
            'numero_parcelas' => $fatura->numero_parcelas,
            'data_compra' => $fatura->data_compra->format('Y-m-d'),
            'data_vencimento' => $dataVencimento,
            'mes_referencia' => $mesReferencia,
            'ano_referencia' => $anoReferencia,
            'cartao' => $this->formatarCartao($fatura->cartaoCredito),
            'parcelas' => $parcelas,
            'parcelas_pagas' => $itensPagos,
            'parcelas_pendentes' => $totalItens - $itensPagos,
            'progresso' => round($progresso, 2),
            'status' => $fatura->status ?? $this->calculator->determinarStatus((float) $progresso),
        ];
    }

    private function formatarCartao(?CartaoCredito $cartao): ?array
    {
        if ($cartao === null) {
            return null;
        }

        $cor = $cartao->cor_cartao
            ?? $cartao->conta->instituicaoFinanceira->cor_primaria
            ?? null;

        return [
            'id' => $cartao->id,
            'nome' => $cartao->nome_cartao ?? $cartao->bandeira,
            'bandeira' => $cartao->bandeira,
            'ultimos_digitos' => $cartao->ultimos_digitos ?? '',
            'dia_vencimento' => $cartao->dia_vencimento ?? null,
            'cor_cartao' => $cor,
        ];
    }
}
