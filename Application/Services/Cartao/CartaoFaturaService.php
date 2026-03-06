<?php

declare(strict_types=1);

namespace Application\Services\Cartao;

use Application\Models\Lancamento;
use Application\Models\CartaoCredito;
use Application\Models\Conta;
use Application\Models\Fatura;
use Application\Models\FaturaCartaoItem;
use Application\Models\Categoria;
use Application\Enums\LogCategory;
use Illuminate\Database\Capsule\Manager as DB;
use Application\Services\Infrastructure\LogService;

/**
 * Service para gerenciar faturas de cartão de crédito.
 *
 * NOTA: Este service filtra faturas por YEAR(data_vencimento) e MONTH(data_vencimento),
 * que é o correto para a página de cartões (agrupa itens pela data de vencimento da fatura).
 * Já o FaturaService (página /faturas) filtra por mes_referencia/ano_referencia,
 * que agrupa pela competência da despesa. São visões complementares e intencionalmente diferentes.
 */
class CartaoFaturaService
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

        $total = $itens->where('pago', false)->sum('valor');
        $dataVencimento = sprintf('%04d-%02d-%02d', $ano, $mes, $cartao->dia_vencimento);

        return [
            'cartao' => [
                'id' => $cartao->id,
                'nome' => $cartao->nome_cartao,
                'ultimos_digitos' => $cartao->ultimos_digitos,
                'dia_vencimento' => $cartao->dia_vencimento,
                'bandeira' => $cartao->bandeira,
                'cor_cartao' => $cartao->cor_cartao ?? $cartao->conta?->instituicaoFinanceira?->cor_primaria ?? null,
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
                'mes_referencia' => $item->mes_referencia,
                'ano_referencia' => $item->ano_referencia,
                'recorrente' => (bool)$item->recorrente,
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
        DB::beginTransaction();

        try {
            $cartao = CartaoCredito::where('id', $cartaoId)
                ->where('user_id', $userId)
                ->firstOrFail();

            $fatura = $this->obterFaturaMes($cartaoId, $mes, $ano, $userId);

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
            // Usar mes_referencia/ano_referencia (competência) dos itens para a descrição
            $primeiroItem = reset($itensNaoPagos);
            $mesCompetencia = $primeiroItem['mes_referencia'] ?? $mes;
            $anoCompetencia = $primeiroItem['ano_referencia'] ?? $ano;
            $nomeMes = $this->getNomeMes($mesCompetencia);
            $tipoPagamento = $isPagamentoParcial ? 'Pagamento Parcial' : 'Pagamento';
            $descricaoFatura = sprintf(
                '%s Fatura %s •••• %s - %s/%04d',
                $tipoPagamento,
                $cartao->nome_cartao,
                $cartao->ultimos_digitos,
                $nomeMes,
                $anoCompetencia
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
                }
            } else {
                $itensParaPagar = $itensNaoPagos;
            }

            $qtdItensPagos = count($itensParaPagar);
            $observacao = $isPagamentoParcial
                ? sprintf('Pagamento parcial R$ %.2f - %d de %d item(s) - Fatura %02d/%04d', $totalPagar, $qtdItensPagos, count($itensNaoPagos), $mes, $ano)
                : sprintf('%d item(s) pago(s) - Fatura %02d/%04d', $qtdItensPagos, $mes, $ano);

            // Buscar categoria "Cartão de Crédito" do usuário para vincular ao lançamento
            $categoriaCartao = Categoria::where('user_id', $userId)
                ->where('nome', 'Cartão de Crédito')
                ->where('tipo', 'despesa')
                ->first();

            $lancamento = Lancamento::create([
                'user_id' => $userId,
                'conta_id' => $contaId,
                'categoria_id' => $categoriaCartao?->id,   // Vincula à categoria "Cartão de Crédito"
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

            // Marcar itens como pagos (apenas os que cabem no valor pago)
            foreach ($itensParaPagar as $itemData) {
                $item = FaturaCartaoItem::forUser($userId)->find($itemData['id']);
                if (!$item) continue;

                $item->pago = true;
                $item->data_pagamento = $dataPagamento;
                $item->save();

                $itensIds[] = $item->id;

                if ($item->fatura_id && !in_array($item->fatura_id, $faturasAfetadas)) {
                    $faturasAfetadas[] = $item->fatura_id;
                }
            }

            $this->atualizarStatusFaturas($faturasAfetadas, $userId);

            // Liberar limite do cartão (recalcula baseado nos itens não pagos)
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

            // Buscar categoria "Cartão de Crédito" do usuário para vincular ao lançamento
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

            // Marcar todos os itens como pagos
            foreach ($itens as $item) {
                $item->pago = true;
                $item->data_pagamento = $dataPagamento;
                $item->save();
            }

            $this->atualizarStatusFaturas($faturasAfetadas, $userId);

            // Liberar limite do cartão (recalcula baseado nos itens não pagos)
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
                $this->atualizarStatusFaturas([$faturaId], $userId);
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

        // Buscar lançamento de pagamento pelo origem_tipo (padrão criado por pagarFatura/pagarParcelas)
        $padraoObservacao = sprintf('Fatura %02d/%04d', $mes, $ano);

        $lancamentoPagamento = Lancamento::where('user_id', $userId)
            ->where('cartao_credito_id', $cartaoId)
            ->where('origem_tipo', 'pagamento_fatura')
            ->where('observacao', 'LIKE', "%{$padraoObservacao}%")
            ->orderBy('id', 'desc')
            ->first();

        if (!$lancamentoPagamento) {
            // Fallback: buscar pelo padrão antigo na descrição (dados migrados)
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
     * Desfazer pagamento de uma fatura completa
     */
    public function desfazerPagamentoFatura(int $cartaoId, int $mes, int $ano, int $userId): array
    {
        DB::beginTransaction();

        try {
            $cartao = CartaoCredito::where('id', $cartaoId)
                ->where('user_id', $userId)
                ->firstOrFail();

            // Buscar lançamento de pagamento de fatura pelo origem_tipo (mais confiável)
            // Observação contém: "X item(s) pago(s) - Fatura MM/YYYY"
            $padraoObservacao = sprintf('Fatura %02d/%04d', $mes, $ano);

            $lancamentosPagamento = Lancamento::where('user_id', $userId)
                ->where('cartao_credito_id', $cartaoId)
                ->where('origem_tipo', 'pagamento_fatura')
                ->where('observacao', 'LIKE', "%{$padraoObservacao}%")
                ->get();

            if ($lancamentosPagamento->isEmpty()) {
                // Fallback: buscar pelo padrão antigo na descrição
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

            $this->atualizarStatusFaturas($faturasAfetadas, $userId);

            foreach ($lancamentosPagamento as $pagamento) {
                $pagamento->delete();
            }

            // Recalcular limite do cartão baseado nos itens não pagos
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

                    $diaReal = min($cartao->dia_vencimento, (int) date('t', mktime(0, 0, 0, $mesVenc, 1, $anoVenc)));
                    $dataVencimento = new \DateTime("{$anoVenc}-{$mesVenc}-{$diaReal}");

                    // Se vencimento já passou neste mês, vai para o próximo
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
    public function obterResumoParcelamentos(int $cartaoId, int $mes, int $ano, ?int $userId = null): array
    {
        // Defense-in-depth: se userId fornecido, verificar ownership
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
        $conta = Conta::forUser($userId)->find($contaId);
        if (!$conta) {
            return 0;
        }

        $saldoInicial = (float) ($conta->saldo_inicial ?? 0);

        // Receitas que afetam caixa (apenas pagas, sem transferências)
        $receitas = Lancamento::where('conta_id', $contaId)
            ->where('user_id', $userId)
            ->where('tipo', 'receita')
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->where('eh_transferencia', 0)
            ->sum('valor');

        // Despesas que afetam caixa (apenas pagas, sem transferências)
        $despesas = Lancamento::where('conta_id', $contaId)
            ->where('user_id', $userId)
            ->where('tipo', 'despesa')
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->where('eh_transferencia', 0)
            ->sum('valor');

        // Transferências recebidas (pagas e que afetam caixa)
        $transfIn = Lancamento::where('conta_id_destino', $contaId)
            ->where('user_id', $userId)
            ->where('eh_transferencia', 1)
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->sum('valor');

        // Transferências enviadas (pagas e que afetam caixa)
        $transfOut = Lancamento::where('conta_id', $contaId)
            ->where('user_id', $userId)
            ->where('eh_transferencia', 1)
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->sum('valor');

        return $saldoInicial + $receitas - $despesas + $transfIn - $transfOut;
    }

    /**
     * Atualizar status de múltiplas faturas
     */
    private function atualizarStatusFaturas(array $faturaIds, int $userId): void
    {
        foreach ($faturaIds as $faturaId) {
            $fatura = Fatura::forUser($userId)->find($faturaId);
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
