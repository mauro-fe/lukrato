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
     * Pagar fatura completa ou parcial do mês
     * 
     * REFATORADO: Agora cria UM ÚNICO lançamento para o pagamento da fatura inteira
     * - Não cria mais lançamento por item
     * - O lançamento representa o débito na conta = "Pagamento Fatura Cartão X - Jan/2026"
     * @param int|null $contaIdOverride - Conta para débito (se null, usa a vinculada ao cartão)
     * @param float|null $valorParcial - Valor parcial a pagar (se null, paga toda a fatura)
     */
    public function pagarFatura(int $cartaoId, int $mes, int $ano, int $userId, ?int $contaIdOverride = null, ?float $valorParcial = null): array
    {
        error_log("💳 [FATURA] Iniciando pagamento - Cartão: {$cartaoId}, Mês: {$mes}/{$ano}, User: {$userId}, ContaOverride: " . ($contaIdOverride ?? 'NULL') . ", ValorParcial: " . ($valorParcial ?? 'NULL'));

        DB::beginTransaction();

        try {
            $cartao = CartaoCredito::where('id', $cartaoId)
                ->where('user_id', $userId)
                ->firstOrFail();

            error_log("💳 [FATURA] Cartão encontrado: {$cartao->nome_cartao} (Conta padrão: " . ($cartao->conta_id ?? 'NULL') . ")");

            $fatura = $this->obterFaturaMes($cartaoId, $mes, $ano);

            if (empty($fatura['itens'])) {
                throw new \Exception('Não há itens para pagar neste mês.');
            }

            $itensNaoPagos = array_filter($fatura['itens'], fn($item) => !$item['pago']);

            if (empty($itensNaoPagos)) {
                throw new \Exception('Todos os itens desta fatura já foram pagos.');
            }

            $totalFatura = array_sum(array_column($itensNaoPagos, 'valor'));
            
            // Se valor parcial informado, usa ele; senão, paga tudo
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

            // Usar conta informada ou a vinculada ao cartão
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

            // ===============================================================
            // REFATORADO: Criar UM ÚNICO lançamento para o pagamento da fatura
            // ===============================================================
            $nomeMes = $this->getNomeMes($mes);
            $tipoPagamento = $isPagamentoParcial ? 'Pagamento Parcial' : 'Pagamento';
            $descricaoFatura = sprintf(
                '%s Fatura %s •••• %s - %s/%04d',
                $tipoPagamento,
                $cartao->nome_cartao,
                $cartao->ultimos_digitos,
                $nomeMes,
                $ano
            );

            // Determinar quantos itens serão marcados como pagos
            // Em pagamento parcial, marcamos itens proporcionalmente ao valor
            $itensParaPagar = [];
            $valorAcumulado = 0;
            
            if ($isPagamentoParcial) {
                // Ordenar itens por valor (menor primeiro) para pagar mais itens possíveis
                usort($itensNaoPagos, fn($a, $b) => $a['valor'] <=> $b['valor']);
                
                foreach ($itensNaoPagos as $itemData) {
                    if ($valorAcumulado + $itemData['valor'] <= $totalPagar) {
                        $itensParaPagar[] = $itemData;
                        $valorAcumulado += $itemData['valor'];
                    }
                }
                
                // Se não conseguiu pagar nenhum item completo, marca o primeiro parcialmente
                // (neste caso, ainda assim marcamos pelo menos como pagamento registrado)
                if (empty($itensParaPagar) && !empty($itensNaoPagos)) {
                    // Pagamento não cobre nenhum item completo, mas registra o valor pago
                    error_log("💳 [FATURA] Pagamento parcial: valor não cobre nenhum item completo, apenas registrando lançamento");
                }
            } else {
                $itensParaPagar = $itensNaoPagos;
            }

            $qtdItensPagos = count($itensParaPagar);
            $observacao = $isPagamentoParcial 
                ? sprintf('Pagamento parcial R$ %.2f - %d de %d item(s) - Fatura %02d/%04d', $totalPagar, $qtdItensPagos, count($itensNaoPagos), $mes, $ano)
                : sprintf('%d item(s) pago(s) - Fatura %02d/%04d', $qtdItensPagos, $mes, $ano);

            $lancamento = Lancamento::create([
                'user_id' => $userId,
                'conta_id' => $contaId,
                'categoria_id' => null,                    // Fatura não tem categoria específica
                'cartao_credito_id' => $cartaoId,
                'forma_pagamento' => 'debito_conta',       // Débito na conta bancária
                'tipo' => 'despesa',
                'valor' => $totalPagar,
                'descricao' => $descricaoFatura,
                'data' => $dataPagamento,
                'data_competencia' => $dataPagamento,
                'observacao' => $observacao,
                'pago' => true,
                'data_pagamento' => $dataPagamento,
                'afeta_competencia' => true,
                'afeta_caixa' => true,                     // ✅ Agora sim afeta o saldo!
                'origem_tipo' => 'pagamento_fatura',
            ]);

            error_log("💳 [FATURA] Lançamento ÚNICO criado - ID: {$lancamento->id}, Valor: {$totalPagar}, Itens a pagar: {$qtdItensPagos}");

            // Marcar itens como pagos (apenas os que cabem no valor pago)
            foreach ($itensParaPagar as $itemData) {
                $item = FaturaCartaoItem::find($itemData['id']);
                if (!$item) continue;

                $item->pago = true;
                $item->data_pagamento = $dataPagamento;
                $item->save();

                $itensIds[] = $item->id;

                if ($item->fatura_id && !in_array($item->fatura_id, $faturasAfetadas)) {
                    $faturasAfetadas[] = $item->fatura_id;
                }
            }

            $this->atualizarStatusFaturas($faturasAfetadas);

            // Liberar limite do cartão (recalcula baseado nos itens não pagos)
            $cartao->atualizarLimiteDisponivel();

            DB::commit();

            error_log("✅ [FATURA] Pagamento concluído - Lançamento único ID: {$lancamento->id}, " . count($itensIds) . " itens marcados como pagos");

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
            error_log("❌ [FATURA] Erro ao processar pagamento: " . $e->getMessage() . " | Line: " . $e->getLine());
            throw $e;
        }
    }

    /**
     * Obter nome do mês
     */
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
            12 => 'Dez'
        ];
        return $meses[$mes] ?? (string)$mes;
    }

    /**
     * Pagar parcelas individuais selecionadas
     * 
     * REFATORADO: Agora cria UM ÚNICO lançamento para o pagamento parcial
     * - Não cria mais lançamento por item
     * - O lançamento representa o débito na conta
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

            error_log("💳 [PARCELAS] Pagando " . $itens->count() . " itens, valor total: {$totalPagar}");

            // ===============================================================
            // REFATORADO: Criar UM ÚNICO lançamento para o pagamento parcial
            // ===============================================================
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

            $lancamento = Lancamento::create([
                'user_id' => $userId,
                'conta_id' => $contaId,
                'categoria_id' => null,
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

            error_log("💳 [PARCELAS] Lançamento ÚNICO criado - ID: {$lancamento->id}, Valor: {$totalPagar}");

            // Marcar todos os itens como pagos
            foreach ($itens as $item) {
                $item->pago = true;
                $item->data_pagamento = $dataPagamento;
                $item->save();
            }

            $this->atualizarStatusFaturas($faturasAfetadas);

            // Liberar limite do cartão (recalcula baseado nos itens não pagos)
            $cartao->atualizarLimiteDisponivel();

            DB::commit();

            $descricaoParcelas = count($parcelaIds) === 1 ? '1 item' : count($parcelaIds) . ' itens';

            error_log("✅ [PARCELAS] Pagamento concluído - Lançamento único ID: {$lancamento->id}");

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

            // Recalcular limite do cartão baseado nos itens não pagos
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

            error_log("🔄 [DESFAZER FATURA] Cartão: {$cartao->nome_cartao}, Mês: {$mes}/{$ano}");

            // Buscar lançamento de pagamento de fatura pelo origem_tipo (mais confiável)
            // Observação contém: "X item(s) pago(s) - Fatura MM/YYYY"
            $padraoObservacao = sprintf('Fatura %02d/%04d', $mes, $ano);

            $lancamentosPagamento = Lancamento::where('user_id', $userId)
                ->where('cartao_credito_id', $cartaoId)
                ->where('origem_tipo', 'pagamento_fatura')
                ->where('observacao', 'LIKE', "%{$padraoObservacao}%")
                ->get();

            error_log("🔍 [DESFAZER FATURA] Padrão de busca: {$padraoObservacao}");
            error_log("🔍 [DESFAZER FATURA] Lançamentos encontrados: {$lancamentosPagamento->count()}");

            if ($lancamentosPagamento->isEmpty()) {
                // Fallback: buscar pelo padrão antigo na descrição
                $nomeMes = $this->getNomeMes($mes);
                $padraoDescricaoAntigo = "- {$nomeMes}/{$ano}";

                $lancamentosPagamento = Lancamento::where('user_id', $userId)
                    ->where('cartao_credito_id', $cartaoId)
                    ->where('tipo', 'despesa')
                    ->where('descricao', 'LIKE', "Pagamento Fatura%{$cartao->nome_cartao}%{$padraoDescricaoAntigo}%")
                    ->get();

                error_log("🔍 [DESFAZER FATURA] Fallback - Padrão descrição: {$padraoDescricaoAntigo}, Encontrados: {$lancamentosPagamento->count()}");
            }

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

            // Contar itens que serão revertidos
            $itensParaReverter = FaturaCartaoItem::where('user_id', $userId)
                ->where('cartao_credito_id', $cartao->id)
                ->whereYear('data_vencimento', $ano)
                ->whereMonth('data_vencimento', $mes)
                ->where('pago', true)
                ->count();

            // Desmarcar parcelas
            $itensRevertidos = FaturaCartaoItem::where('user_id', $userId)
                ->where('cartao_credito_id', $cartao->id)
                ->whereYear('data_vencimento', $ano)
                ->whereMonth('data_vencimento', $mes)
                ->where('pago', true)
                ->update(['pago' => false, 'data_pagamento' => null]);

            error_log("📊 [DESFAZER FATURA] {$itensRevertidos} itens revertidos");

            $this->atualizarStatusFaturas($faturasAfetadas);

            foreach ($lancamentosPagamento as $pagamento) {
                error_log("🗑️ [DESFAZER FATURA] Excluindo lançamento ID: {$pagamento->id}");
                $pagamento->delete();
            }

            // Recalcular limite do cartão baseado nos itens não pagos
            $cartao->atualizarLimiteDisponivel();

            DB::commit();

            error_log("✅ [DESFAZER FATURA] Concluído com sucesso");

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
                    // Determinar a data de vencimento
                    $mesVenc = (int) $dataHoje->format('n');
                    $anoVenc = (int) $dataHoje->format('Y');

                    $dataVencimento = \DateTime::createFromFormat(
                        'Y-n-j',
                        "{$anoVenc}-{$mesVenc}-{$cartao->dia_vencimento}"
                    );

                    if (!$dataVencimento) {
                        continue;
                    }

                    // Se vencimento já passou neste mês, vai para o próximo
                    if ($dataVencimento < $dataHoje) {
                        $dataVencimento->modify('+1 month');
                        $mesVenc = (int) $dataVencimento->format('n');
                        $anoVenc = (int) $dataVencimento->format('Y');
                    }

                    if ($dataVencimento <= $dataLimite && $dataVencimento >= $dataHoje) {
                        // CORRIGIDO: mes_referencia é o mês ANTERIOR ao vencimento
                        // A fatura FECHA em janeiro e VENCE em fevereiro
                        // Logo, itens com mes_referencia = 1 vencem em 02
                        $mesRef = $mesVenc - 1;
                        $anoRef = $anoVenc;
                        if ($mesRef < 1) {
                            $mesRef = 12;
                            $anoRef--;
                        }

                        $totalFatura = FaturaCartaoItem::where('cartao_credito_id', $cartao->id)
                            ->where('pago', false)
                            ->where('mes_referencia', $mesRef)
                            ->where('ano_referencia', $anoRef)
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
                                'mes' => $mesRef,
                                'ano' => $anoRef,
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
     * Calcular saldo da conta (usando afeta_caixa para considerar apenas lançamentos efetivos)
     */
    private function calcularSaldoConta(int $contaId, int $userId): float
    {
        $conta = Conta::find($contaId);
        if (!$conta) {
            return 0;
        }

        $saldoInicial = (float) ($conta->saldo_inicial ?? 0);

        // Receitas que afetam caixa
        $receitas = Lancamento::where('conta_id', $contaId)
            ->where('user_id', $userId)
            ->where('tipo', 'receita')
            ->where('afeta_caixa', true)
            ->sum('valor');

        // Despesas que afetam caixa
        $despesas = Lancamento::where('conta_id', $contaId)
            ->where('user_id', $userId)
            ->where('tipo', 'despesa')
            ->where('afeta_caixa', true)
            ->sum('valor');

        // Transferências recebidas
        $transfIn = Lancamento::where('conta_id_destino', $contaId)
            ->where('user_id', $userId)
            ->where('eh_transferencia', 1)
            ->sum('valor');

        // Transferências enviadas
        $transfOut = Lancamento::where('conta_id', $contaId)
            ->where('user_id', $userId)
            ->where('eh_transferencia', 1)
            ->sum('valor');

        return $saldoInicial + $receitas - $despesas + $transfIn - $transfOut;
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
}
