<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Models\Lancamento;
use Application\Models\CartaoCredito;
use Application\Models\Conta;
use Application\Models\Fatura;
use Application\Models\FaturaCartaoItem;
use Application\Models\Categoria;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Service para gerenciar faturas de cartão de crédito
 */
class CartaoFaturaService
{
    /**
     * Obter histórico de faturas pagas
     */
    public function obterHistoricoFaturasPagas(int $cartaoId, int $limite = 12): array
    {
        $cartao = CartaoCredito::findOrFail($cartaoId);

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
                'mes_nome' => $this->getNomeMes($item->mes),
                'total' => (float) $item->total,
                'data_pagamento' => $item->data_pagamento,
                'quantidade_lancamentos' => $item->quantidade,
            ])->toArray()
        ];
    }

    /**
     * Obter fatura do mês de um cartão
     */
    public function obterFaturaMes(int $cartaoId, int $mes, int $ano): array
    {
        $cartao = CartaoCredito::findOrFail($cartaoId);

        $itens = FaturaCartaoItem::where('cartao_credito_id', $cartaoId)
            ->whereYear('data_vencimento', $ano)
            ->whereMonth('data_vencimento', $mes)
            ->orderBy('data_compra')
            ->get();

        $total = $itens->where('pago', false)->sum('valor');
        $dataVencimento = sprintf('%04d-%02d-%02d', $ano, $mes, $cartao->dia_vencimento);

        return [
            'cartao' => [
                'id' => $cartao->id,
                'nome' => $cartao->nome_cartao,
                'ultimos_digitos' => $cartao->ultimos_digitos,
                'dia_vencimento' => $cartao->dia_vencimento,
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
            ])->toArray(),
            'total' => $total,
            'vencimento' => $dataVencimento,
            'mes' => $mes,
            'ano' => $ano,
        ];
    }

    /**
     * Pagar fatura completa do mês
     */
    public function pagarFatura(int $cartaoId, int $mes, int $ano, int $userId): array
    {
        error_log("💳 [FATURA] Iniciando pagamento - Cartão: {$cartaoId}, Mês: {$mes}/{$ano}, User: {$userId}");

        DB::beginTransaction();

        try {
            $cartao = CartaoCredito::where('id', $cartaoId)
                ->where('user_id', $userId)
                ->firstOrFail();

            error_log("💳 [FATURA] Cartão encontrado: {$cartao->nome_cartao} (Conta: " . ($cartao->conta_id ?? 'NULL') . ")");

            $fatura = $this->obterFaturaMes($cartaoId, $mes, $ano);

            if (empty($fatura['itens'])) {
                throw new \Exception('Não há itens para pagar neste mês.');
            }

            $itensNaoPagos = array_filter($fatura['itens'], fn($item) => !$item['pago']);

            if (empty($itensNaoPagos)) {
                throw new \Exception('Todos os itens desta fatura já foram pagos.');
            }

            $totalPagar = array_sum(array_column($itensNaoPagos, 'valor'));
            $contaId = $cartao->conta_id;

            if (!$contaId) {
                throw new \Exception('Cartão não está vinculado a nenhuma conta.');
            }

            $conta = Conta::where('id', $contaId)->where('user_id', $userId)->first();

            if (!$conta) {
                throw new \Exception('Conta não encontrada ou não pertence ao usuário.');
            }

            $saldoAtual = $this->calcularSaldoConta($contaId, $userId);

            if ($saldoAtual < $totalPagar) {
                throw new \Exception(sprintf(
                    'Saldo insuficiente. Disponível: R$ %.2f, Necessário: R$ %.2f',
                    $saldoAtual,
                    $totalPagar
                ));
            }

            $dataPagamento = now()->format('Y-m-d');
            $itensIds = [];
            $lancamentosCriados = [];
            $faturasAfetadas = [];

            error_log("💳 [FATURA] Transformando " . count($itensNaoPagos) . " itens em lançamentos na data {$dataPagamento}");

            // Para cada item não pago, criar um lançamento individual
            foreach ($itensNaoPagos as $itemData) {
                $item = FaturaCartaoItem::find($itemData['id']);
                if (!$item) continue;

                // Criar lançamento do item na data de PAGAMENTO (hoje), não na data de vencimento
                $lancamento = Lancamento::create([
                    'user_id' => $userId,
                    'conta_id' => $contaId,
                    'categoria_id' => $item->categoria_id,
                    'tipo' => 'despesa',
                    'valor' => $item->valor,
                    'descricao' => $item->descricao,
                    'data' => $dataPagamento, // Data do pagamento, não do vencimento
                    'observacao' => sprintf(
                        'Fatura %s •••• %s - %02d/%04d',
                        $cartao->nome_cartao,
                        $cartao->ultimos_digitos,
                        $mes,
                        $ano
                    ),
                    'pago' => true,
                    'data_pagamento' => $dataPagamento,
                ]);

                // Vincular o item ao lançamento criado
                $item->lancamento_id = $lancamento->id;
                $item->pago = true;
                $item->data_pagamento = $dataPagamento;
                $item->save();

                $itensIds[] = $item->id;
                $lancamentosCriados[] = $lancamento->id;

                if ($item->fatura_id && !in_array($item->fatura_id, $faturasAfetadas)) {
                    $faturasAfetadas[] = $item->fatura_id;
                }
            }

            $this->atualizarStatusFaturas($faturasAfetadas);

            // Liberar limite do cartão
            $cartao->limite_disponivel += $totalPagar;
            $cartao->save();

            DB::commit();

            error_log("✅ [FATURA] Pagamento concluído - " . count($lancamentosCriados) . " lançamentos criados: " . implode(', ', $lancamentosCriados));

            return [
                'success' => true,
                'message' => sprintf('Fatura paga! %d item(s) transformado(s) em lançamento(s).', count($itensIds)),
                'valor_pago' => $totalPagar,
                'itens_pagos' => count($itensIds),
                'novo_limite_disponivel' => $cartao->limite_disponivel,
                'lancamentos_criados' => $lancamentosCriados,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            error_log("❌ [FATURA] Erro ao processar pagamento: " . $e->getMessage() . " | Line: " . $e->getLine());
            throw $e;
        }
    }

    /**
     * Pagar parcelas individuais selecionadas
     */
    public function pagarParcelas(int $cartaoId, array $parcelaIds, int $mes, int $ano, int $userId): array
    {
        error_log("💳 [PARCELAS] Iniciando pagamento - Cartão: {$cartaoId}, Parcelas: " . count($parcelaIds) . ", User: {$userId}");

        DB::beginTransaction();

        try {
            $cartao = CartaoCredito::where('id', $cartaoId)
                ->where('user_id', $userId)
                ->firstOrFail();

            error_log("💳 [PARCELAS] Cartão encontrado: {$cartao->nome_cartao} (Conta: " . ($cartao->conta_id ?? 'NULL') . ")");

            $itens = FaturaCartaoItem::whereIn('id', $parcelaIds)
                ->where('user_id', $userId)
                ->where('cartao_credito_id', $cartaoId)
                ->where('pago', false)
                ->get();

            if ($itens->isEmpty()) {
                throw new \Exception('Nenhuma parcela válida encontrada para pagamento.');
            }

            $totalPagar = $itens->sum('valor');
            $contaId = $cartao->conta_id;

            if (!$contaId) {
                throw new \Exception('Cartão não está vinculado a nenhuma conta.');
            }

            $conta = Conta::where('id', $contaId)->where('user_id', $userId)->first();

            if (!$conta) {
                throw new \Exception('Conta não encontrada ou não pertence ao usuário.');
            }

            $saldoAtual = $this->calcularSaldoConta($contaId, $userId);

            if ($saldoAtual < $totalPagar) {
                throw new \Exception(sprintf(
                    'Saldo insuficiente. Disponível: R$ %.2f, Necessário: R$ %.2f',
                    $saldoAtual,
                    $totalPagar
                ));
            }

            $faturasAfetadas = $itens->filter(fn($item) => $item->fatura_id)
                ->pluck('fatura_id')
                ->unique()
                ->toArray();

            $dataPagamento = now()->format('Y-m-d');
            $lancamentosCriados = [];

            error_log("💳 [PARCELAS] Transformando " . $itens->count() . " parcelas em lançamentos na data {$dataPagamento}");

            // Para cada parcela, criar um lançamento individual
            foreach ($itens as $item) {
                // Criar lançamento do item na data de PAGAMENTO (hoje)
                $lancamento = Lancamento::create([
                    'user_id' => $userId,
                    'conta_id' => $contaId,
                    'categoria_id' => $item->categoria_id,
                    'tipo' => 'despesa',
                    'valor' => $item->valor,
                    'descricao' => $item->descricao,
                    'data' => $dataPagamento, // Data do pagamento, não do vencimento
                    'observacao' => sprintf(
                        'Fatura %s •••• %s - %02d/%04d',
                        $cartao->nome_cartao,
                        $cartao->ultimos_digitos,
                        $mes,
                        $ano
                    ),
                    'pago' => true,
                    'data_pagamento' => $dataPagamento,
                ]);

                // Vincular o item ao lançamento criado
                $item->lancamento_id = $lancamento->id;
                $item->pago = true;
                $item->data_pagamento = $dataPagamento;
                $item->save();

                $lancamentosCriados[] = $lancamento->id;
            }

            $this->atualizarStatusFaturas($faturasAfetadas);

            // Liberar limite do cartão
            $cartao->limite_disponivel += $totalPagar;
            $cartao->save();

            DB::commit();

            $descricaoParcelas = count($parcelaIds) === 1 ? '1 item' : count($parcelaIds) . ' itens';

            error_log("✅ [PARCELAS] Pagamento concluído - " . count($lancamentosCriados) . " lançamentos criados: " . implode(', ', $lancamentosCriados));

            return [
                'success' => true,
                'message' => sprintf('Pagamento realizado! %s transformado(s) em lançamento(s).', ucfirst($descricaoParcelas)),
                'valor_pago' => $totalPagar,
                'parcelas_pagas' => count($parcelaIds),
                'novo_limite_disponivel' => $cartao->limite_disponivel,
                'lancamentos_criados' => $lancamentosCriados,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            error_log("❌ [PARCELAS] Erro ao processar pagamento: " . $e->getMessage() . " | Line: " . $e->getLine());
            throw $e;
        }
    }

    /**
     * Desfazer pagamento de uma parcela individual
     */
    public function desfazerPagamentoParcela(int $parcelaId, int $userId): array
    {
        DB::beginTransaction();

        try {
            $item = FaturaCartaoItem::where('id', $parcelaId)
                ->where('user_id', $userId)
                ->whereNotNull('cartao_credito_id')
                ->where('pago', true)
                ->firstOrFail();

            $cartao = CartaoCredito::where('id', $item->cartao_credito_id)
                ->where('user_id', $userId)
                ->firstOrFail();

            $mes = (int) date('n', strtotime($item->data_vencimento));
            $ano = (int) date('Y', strtotime($item->data_vencimento));
            $padraoDescricao = sprintf('- %02d/%04d', $mes, $ano);

            $lancamentosPagamento = Lancamento::where('user_id', $userId)
                ->whereNull('cartao_credito_id')
                ->where('tipo', 'despesa')
                ->where('descricao', 'LIKE', "Pagamento Fatura%{$cartao->nome_cartao}%{$padraoDescricao}%")
                ->get();

            if ($lancamentosPagamento->isEmpty()) {
                throw new \Exception('Pagamento não encontrado para esta parcela.');
            }

            $itensPagosNoMes = FaturaCartaoItem::where('user_id', $userId)
                ->where('cartao_credito_id', $cartao->id)
                ->whereYear('data_vencimento', $ano)
                ->whereMonth('data_vencimento', $mes)
                ->where('pago', true)
                ->count();

            if ($itensPagosNoMes === 1) {
                foreach ($lancamentosPagamento as $pagamento) {
                    $pagamento->delete();
                }
            } else {
                $lancamentoPagamento = $lancamentosPagamento->first();
                $lancamentoPagamento->valor -= $item->valor;
                $lancamentoPagamento->save();
            }

            $faturaId = $item->fatura_id;

            $item->pago = false;
            $item->data_pagamento = null;
            $item->save();

            if ($faturaId) {
                $this->atualizarStatusFaturas([$faturaId]);
            }

            $cartao->limite_disponivel -= $item->valor;
            $cartao->save();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Pagamento da parcela desfeito com sucesso!',
                'valor_desfeito' => (float) $item->valor,
                'novo_limite_disponivel' => (float) $cartao->limite_disponivel,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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

        $padraoDescricao = sprintf('- %02d/%04d', $mes, $ano);
        $lancamentoPagamento = Lancamento::where('user_id', $userId)
            ->whereNull('cartao_credito_id')
            ->where('tipo', 'despesa')
            ->where('descricao', 'LIKE', "Pagamento Fatura%{$cartao->nome_cartao}%{$padraoDescricao}%")
            ->orderBy('id', 'desc')
            ->first();

        return [
            'pago' => true,
            'data_pagamento' => $lancamentoPagamento?->data,
            'valor' => $lancamentoPagamento ? (float) $lancamentoPagamento->valor : 0,
            'lancamento_id' => $lancamentoPagamento?->id,
        ];
    }

    /**
     * Desfazer pagamento de uma fatura completa
     */
    public function desfazerPagamentoFatura(int $cartaoId, int $mes, int $ano, int $userId): array
    {
        DB::beginTransaction();

        try {
            $cartao = CartaoCredito::where('id', $cartaoId)
                ->where('user_id', $userId)
                ->firstOrFail();

            $padraoDescricao = sprintf('- %02d/%04d', $mes, $ano);

            $lancamentosPagamento = Lancamento::where('user_id', $userId)
                ->whereNull('cartao_credito_id')
                ->where('tipo', 'despesa')
                ->where('descricao', 'LIKE', "Pagamento Fatura%{$cartao->nome_cartao}%{$padraoDescricao}%")
                ->get();

            if ($lancamentosPagamento->isEmpty()) {
                throw new \Exception('Nenhum pagamento encontrado para esta fatura.');
            }

            $totalPagamentos = $lancamentosPagamento->sum('valor');

            // Coletar faturas afetadas
            $faturasAfetadas = FaturaCartaoItem::where('user_id', $userId)
                ->where('cartao_credito_id', $cartao->id)
                ->whereYear('data_vencimento', $ano)
                ->whereMonth('data_vencimento', $mes)
                ->where('pago', true)
                ->whereNotNull('fatura_id')
                ->pluck('fatura_id')
                ->unique()
                ->toArray();

            // Desmarcar parcelas
            FaturaCartaoItem::where('user_id', $userId)
                ->where('cartao_credito_id', $cartao->id)
                ->whereYear('data_vencimento', $ano)
                ->whereMonth('data_vencimento', $mes)
                ->where('pago', true)
                ->update(['pago' => false, 'data_pagamento' => null]);

            $this->atualizarStatusFaturas($faturasAfetadas);

            $cartao->limite_disponivel -= $totalPagamentos;
            $cartao->save();

            foreach ($lancamentosPagamento as $pagamento) {
                $pagamento->delete();
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Pagamento desfeito com sucesso!',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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
                    $mesAtual = (int) $dataHoje->format('n');
                    $anoAtual = (int) $dataHoje->format('Y');

                    $dataVencimento = \DateTime::createFromFormat(
                        'Y-n-j',
                        "{$anoAtual}-{$mesAtual}-{$cartao->dia_vencimento}"
                    );

                    if (!$dataVencimento) {
                        continue;
                    }

                    if ($dataVencimento < $dataHoje) {
                        $dataVencimento->modify('+1 month');
                        $mesAtual = (int) $dataVencimento->format('n');
                        $anoAtual = (int) $dataVencimento->format('Y');
                    }

                    if ($dataVencimento <= $dataLimite && $dataVencimento >= $dataHoje) {
                        $totalFatura = FaturaCartaoItem::where('cartao_credito_id', $cartao->id)
                            ->where('pago', false)
                            ->whereYear('data_vencimento', $anoAtual)
                            ->whereMonth('data_vencimento', $mesAtual)
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
                                'mes' => $mesAtual,
                                'ano' => $anoAtual,
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            return $alertas;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obter resumo dos parcelamentos ativos
     */
    public function obterResumoParcelamentos(int $cartaoId, int $mes, int $ano): array
    {
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

        // Projeção: soma das parcelas nos próximos meses
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

    // ========================================================================
    // MÉTODOS PRIVADOS
    // ========================================================================

    /**
     * Calcular saldo da conta (exclui lançamentos de cartão)
     */
    private function calcularSaldoConta(int $contaId, int $userId): float
    {
        $conta = Conta::find($contaId);
        if (!$conta) {
            return 0;
        }

        return $conta->saldo_inicial +
            Lancamento::where('conta_id', $contaId)
            ->where('user_id', $userId)
            ->whereNull('cartao_credito_id')
            ->sum(DB::raw("CASE WHEN tipo = 'receita' THEN valor ELSE -valor END"));
    }

    /**
     * Atualizar status de múltiplas faturas
     */
    private function atualizarStatusFaturas(array $faturaIds): void
    {
        foreach ($faturaIds as $faturaId) {
            $fatura = Fatura::find($faturaId);
            if ($fatura) {
                $fatura->atualizarStatus();
            }
        }
    }

    /**
     * Obter ou criar categoria de Pagamento de Cartão
     */
    private function obterCategoriaPagamentoCartao(int $userId): int
    {
        $categoria = Categoria::where('user_id', $userId)
            ->where('nome', 'Pagamento de Cartão')
            ->first();

        if (!$categoria) {
            $categoria = Categoria::create([
                'user_id' => $userId,
                'nome' => 'Pagamento de Cartão',
                'tipo' => 'despesa',
                'cor' => '#e67e22',
                'icone' => 'credit-card',
            ]);
        }

        return $categoria->id;
    }

    /**
     * Obter nome do mês
     */
    private function getNomeMes(int $mes): string
    {
        $meses = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro'
        ];
        return $meses[$mes] ?? '';
    }
}
