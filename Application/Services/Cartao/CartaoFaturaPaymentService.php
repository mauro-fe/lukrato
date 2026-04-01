<?php

declare(strict_types=1);

namespace Application\Services\Cartao;

use Application\Enums\LogCategory;
use Application\Models\CartaoCredito;
use Application\Models\Categoria;
use Application\Models\Conta;
use Application\Models\Fatura;
use Application\Models\FaturaCartaoItem;
use Application\Models\Lancamento;
use Application\Services\Infrastructure\LogService;
use Illuminate\Database\Capsule\Manager as DB;

class CartaoFaturaPaymentService
{
    private CartaoFaturaReadService $readService;

    public function __construct(?CartaoFaturaReadService $readService = null)
    {
        $this->readService = $readService ?? new CartaoFaturaReadService();
    }

    /**
     * Pagar fatura completa ou parcial do mês
     *
     * @param int|null $contaIdOverride Conta para débito (se null, usa a vinculada ao cartão)
     * @param float|null $valorParcial Valor parcial a pagar (se null, paga toda a fatura)
     */
    public function pagarFatura(
        int $cartaoId,
        int $mes,
        int $ano,
        int $userId,
        ?int $contaIdOverride = null,
        ?float $valorParcial = null
    ): array {
        DB::beginTransaction();

        try {
            $cartao = CartaoCredito::where('id', $cartaoId)
                ->where('user_id', $userId)
                ->firstOrFail();

            $fatura = $this->readService->obterFaturaMes($cartaoId, $mes, $ano, $userId);

            if (empty($fatura['itens'])) {
                throw new \Exception('Não há itens para pagar neste mês.');
            }

            $itensNaoPagos = array_filter($fatura['itens'], fn($item) => !$item['pago']);

            if (empty($itensNaoPagos)) {
                throw new \Exception('Todos os itens desta fatura já foram pagos.');
            }

            $totalFatura = array_sum(array_column($itensNaoPagos, 'valor'));

            $totalPagar = $valorParcial !== null ? $valorParcial : $totalFatura;
            $isPagamentoParcial = $valorParcial !== null && $valorParcial < $totalFatura;

            if ($totalPagar <= 0) {
                throw new \Exception('O valor do pagamento deve ser maior que zero.');
            }

            if ($totalPagar > $totalFatura) {
                throw new \Exception(sprintf(
                    'O valor informado (R$ %.2f) é maior que o total da fatura (R$ %.2f).',
                    $totalPagar,
                    $totalFatura
                ));
            }

            $contaId = $contaIdOverride ?? $cartao->conta_id;

            if (!$contaId) {
                throw new \Exception('Selecione uma conta para débito do pagamento.');
            }

            $conta = Conta::where('id', $contaId)->where('user_id', $userId)->first();

            if (!$conta) {
                throw new \Exception('Conta não encontrada ou não pertence ao usuário.');
            }

            $saldoAtual = $this->calcularSaldoConta($contaId, $userId);

            if ($saldoAtual < $totalPagar) {
                throw new \Exception(sprintf(
                    'Saldo insuficiente na conta %s. Disponível: R$ %.2f, Necessário: R$ %.2f',
                    $conta->nome,
                    $saldoAtual,
                    $totalPagar
                ));
            }

            $dataPagamento = now()->format('Y-m-d');
            $itensIds = [];
            $faturasAfetadas = [];

            $primeiroItem = reset($itensNaoPagos);
            $mesCompetencia = $primeiroItem['mes_referencia'] ?? $mes;
            $anoCompetencia = $primeiroItem['ano_referencia'] ?? $ano;
            $nomeMes = $this->getNomeMes((int) $mesCompetencia);
            $tipoPagamento = $isPagamentoParcial ? 'Pagamento Parcial' : 'Pagamento';
            $descricaoFatura = sprintf(
                '%s Fatura %s •••• %s - %s/%04d',
                $tipoPagamento,
                $cartao->nome_cartao,
                $cartao->ultimos_digitos,
                $nomeMes,
                $anoCompetencia
            );

            $itensParaPagar = [];
            $valorAcumulado = 0;

            if ($isPagamentoParcial) {
                usort($itensNaoPagos, fn($a, $b) => $a['valor'] <=> $b['valor']);

                foreach ($itensNaoPagos as $itemData) {
                    if ($valorAcumulado + $itemData['valor'] <= $totalPagar) {
                        $itensParaPagar[] = $itemData;
                        $valorAcumulado += $itemData['valor'];
                    }
                }
            } else {
                $itensParaPagar = $itensNaoPagos;
            }

            $qtdItensPagos = count($itensParaPagar);
            $observacao = $isPagamentoParcial
                ? sprintf(
                    'Pagamento parcial R$ %.2f - %d de %d item(s) - Fatura %02d/%04d',
                    $totalPagar,
                    $qtdItensPagos,
                    count($itensNaoPagos),
                    $mes,
                    $ano
                )
                : sprintf('%d item(s) pago(s) - Fatura %02d/%04d', $qtdItensPagos, $mes, $ano);

            $categoriaCartao = Categoria::where('user_id', $userId)
                ->where('nome', 'Cartão de Crédito')
                ->where('tipo', 'despesa')
                ->first();

            $lancamento = Lancamento::create([
                'user_id' => $userId,
                'conta_id' => $contaId,
                'categoria_id' => $categoriaCartao?->id,
                'cartao_credito_id' => $cartaoId,
                'forma_pagamento' => 'debito_conta',
                'tipo' => 'despesa',
                'valor' => $totalPagar,
                'descricao' => $descricaoFatura,
                'data' => $dataPagamento,
                'data_competencia' => $dataPagamento,
                'observacao' => $observacao,
                'pago' => true,
                'data_pagamento' => $dataPagamento,
                'afeta_competencia' => true,
                'afeta_caixa' => true,
                'origem_tipo' => 'pagamento_fatura',
            ]);

            foreach ($itensParaPagar as $itemData) {
                $item = FaturaCartaoItem::forUser($userId)->find($itemData['id']);
                if (!$item) {
                    continue;
                }

                $item->pago = true;
                $item->data_pagamento = $dataPagamento;
                $item->save();

                $itensIds[] = $item->id;

                if ($item->fatura_id && !in_array($item->fatura_id, $faturasAfetadas, true)) {
                    $faturasAfetadas[] = $item->fatura_id;
                }
            }

            $this->atualizarStatusFaturas($faturasAfetadas, $userId);
            $cartao->atualizarLimiteDisponivel();

            DB::commit();

            $mensagem = $isPagamentoParcial
                ? sprintf('Pagamento parcial realizado! R$ %.2f pago(s), %d item(s) quitado(s).', $totalPagar, count($itensIds))
                : sprintf('Fatura paga! %d item(s) marcado(s) como pago(s).', count($itensIds));

            $valorRestante = $totalFatura - $totalPagar;

            return [
                'success' => true,
                'message' => $mensagem,
                'valor_pago' => $totalPagar,
                'valor_restante' => $valorRestante,
                'itens_pagos' => count($itensIds),
                'total_itens' => count($itensNaoPagos),
                'pagamento_parcial' => $isPagamentoParcial,
                'novo_limite_disponivel' => $cartao->limite_disponivel,
                'lancamento_id' => $lancamento->id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            LogService::captureException($e, LogCategory::FATURA, [
                'cartao_id' => $cartaoId,
                'mes' => $mes,
                'ano' => $ano,
                'user_id' => $userId,
            ]);
            throw $e;
        }
    }

    /**
     * Pagar parcelas individuais selecionadas
     */
    public function pagarParcelas(int $cartaoId, array $parcelaIds, int $mes, int $ano, int $userId): array
    {
        DB::beginTransaction();

        try {
            $cartao = CartaoCredito::where('id', $cartaoId)
                ->where('user_id', $userId)
                ->firstOrFail();

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

            $nomeMes = $this->getNomeMes($mes);
            $qtdItens = $itens->count();
            $descricaoFatura = sprintf(
                'Pagamento Parcial Fatura %s •••• %s - %s/%04d (%d %s)',
                $cartao->nome_cartao,
                $cartao->ultimos_digitos,
                $nomeMes,
                $ano,
                $qtdItens,
                $qtdItens === 1 ? 'item' : 'itens'
            );

            $categoriaCartao = Categoria::where('user_id', $userId)
                ->where('nome', 'Cartão de Crédito')
                ->where('tipo', 'despesa')
                ->first();

            $lancamento = Lancamento::create([
                'user_id' => $userId,
                'conta_id' => $contaId,
                'categoria_id' => $categoriaCartao?->id,
                'cartao_credito_id' => $cartaoId,
                'forma_pagamento' => 'debito_conta',
                'tipo' => 'despesa',
                'valor' => $totalPagar,
                'descricao' => $descricaoFatura,
                'data' => $dataPagamento,
                'data_competencia' => $dataPagamento,
                'observacao' => sprintf(
                    '%d item(s) pago(s) - Fatura %02d/%04d',
                    $qtdItens,
                    $mes,
                    $ano
                ),
                'pago' => true,
                'data_pagamento' => $dataPagamento,
                'afeta_competencia' => true,
                'afeta_caixa' => true,
                'origem_tipo' => 'pagamento_fatura',
            ]);

            foreach ($itens as $item) {
                $item->pago = true;
                $item->data_pagamento = $dataPagamento;
                $item->save();
            }

            $this->atualizarStatusFaturas($faturasAfetadas, $userId);
            $cartao->atualizarLimiteDisponivel();

            DB::commit();

            $descricaoParcelas = count($parcelaIds) === 1 ? '1 item' : count($parcelaIds) . ' itens';

            return [
                'success' => true,
                'message' => sprintf('Pagamento realizado! %s marcado(s) como pago(s).', ucfirst($descricaoParcelas)),
                'valor_pago' => $totalPagar,
                'parcelas_pagas' => count($parcelaIds),
                'novo_limite_disponivel' => $cartao->limite_disponivel,
                'lancamento_id' => $lancamento->id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            LogService::captureException($e, LogCategory::FATURA, [
                'action' => 'pagar_parcelas',
                'cartao_id' => $cartaoId,
                'parcela_ids' => $parcelaIds ?? [],
                'user_id' => $userId,
            ]);
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

            $mes = (int) date('n', strtotime((string) $item->data_vencimento));
            $ano = (int) date('Y', strtotime((string) $item->data_vencimento));
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
                $this->atualizarStatusFaturas([$faturaId], $userId);
            }

            $cartao->atualizarLimiteDisponivel();

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
     * Desfazer pagamento de uma fatura completa
     */
    public function desfazerPagamentoFatura(int $cartaoId, int $mes, int $ano, int $userId): array
    {
        DB::beginTransaction();

        try {
            $cartao = CartaoCredito::where('id', $cartaoId)
                ->where('user_id', $userId)
                ->firstOrFail();

            $padraoObservacao = sprintf('Fatura %02d/%04d', $mes, $ano);

            $lancamentosPagamento = Lancamento::where('user_id', $userId)
                ->where('cartao_credito_id', $cartaoId)
                ->where('origem_tipo', 'pagamento_fatura')
                ->where('observacao', 'LIKE', "%{$padraoObservacao}%")
                ->get();

            if ($lancamentosPagamento->isEmpty()) {
                $nomeMes = $this->getNomeMes($mes);
                $padraoDescricaoAntigo = "- {$nomeMes}/{$ano}";

                $lancamentosPagamento = Lancamento::where('user_id', $userId)
                    ->where('cartao_credito_id', $cartaoId)
                    ->where('tipo', 'despesa')
                    ->where('descricao', 'LIKE', "Pagamento Fatura%{$cartao->nome_cartao}%{$padraoDescricaoAntigo}%")
                    ->get();
            }

            if ($lancamentosPagamento->isEmpty()) {
                throw new \Exception('Nenhum pagamento encontrado para esta fatura.');
            }

            $totalPagamentos = $lancamentosPagamento->sum('valor');

            $faturasAfetadas = FaturaCartaoItem::where('user_id', $userId)
                ->where('cartao_credito_id', $cartao->id)
                ->whereYear('data_vencimento', $ano)
                ->whereMonth('data_vencimento', $mes)
                ->where('pago', true)
                ->whereNotNull('fatura_id')
                ->pluck('fatura_id')
                ->unique()
                ->toArray();

            $itensRevertidos = FaturaCartaoItem::where('user_id', $userId)
                ->where('cartao_credito_id', $cartao->id)
                ->whereYear('data_vencimento', $ano)
                ->whereMonth('data_vencimento', $mes)
                ->where('pago', true)
                ->update(['pago' => false, 'data_pagamento' => null]);

            $this->atualizarStatusFaturas($faturasAfetadas, $userId);

            foreach ($lancamentosPagamento as $pagamento) {
                $pagamento->delete();
            }

            $cartao->atualizarLimiteDisponivel();

            DB::commit();

            return [
                'status' => 'success',
                'success' => true,
                'message' => 'Pagamento desfeito com sucesso!',
                'itens_revertidos' => $itensRevertidos,
                'valor_revertido' => $totalPagamentos,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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

    private function calcularSaldoConta(int $contaId, int $userId): float
    {
        $conta = Conta::forUser($userId)->find($contaId);
        if (!$conta) {
            return 0;
        }

        $saldoInicial = (float) ($conta->saldo_inicial ?? 0);

        $receitas = Lancamento::where('conta_id', $contaId)
            ->where('user_id', $userId)
            ->where('tipo', 'receita')
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->where('eh_transferencia', 0)
            ->sum('valor');

        $despesas = Lancamento::where('conta_id', $contaId)
            ->where('user_id', $userId)
            ->where('tipo', 'despesa')
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->where('eh_transferencia', 0)
            ->sum('valor');

        $transfIn = Lancamento::where('conta_id_destino', $contaId)
            ->where('user_id', $userId)
            ->where('eh_transferencia', 1)
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->sum('valor');

        $transfOut = Lancamento::where('conta_id', $contaId)
            ->where('user_id', $userId)
            ->where('eh_transferencia', 1)
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->sum('valor');

        return $saldoInicial + $receitas - $despesas + $transfIn - $transfOut;
    }

    private function atualizarStatusFaturas(array $faturaIds, int $userId): void
    {
        foreach ($faturaIds as $faturaId) {
            $fatura = Fatura::forUser($userId)->find($faturaId);
            if ($fatura) {
                $fatura->atualizarStatus();
            }
        }
    }
}
