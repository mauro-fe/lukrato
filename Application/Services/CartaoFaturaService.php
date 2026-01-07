<?php

namespace Application\Services;

use Application\Models\Lancamento;
use Application\Models\CartaoCredito;
use Application\Models\Conta;
use Illuminate\Database\Capsule\Manager as DB;
use Application\Models\Categoria;

class CartaoFaturaService
{
    /**
     * Retorna histórico de faturas pagas de um cartão
     * 
     * @param int $cartaoId
     * @param int $limite Quantidade de meses anteriores (padrão: 12)
     * @return array
     */
    public function obterHistoricoFaturasPagas(int $cartaoId, int $limite = 12): array
    {
        $cartao = CartaoCredito::findOrFail($cartaoId);

        // Busca lançamentos pagos do cartão agrupados por mês
        $historico = Lancamento::where('cartao_credito_id', $cartaoId)
            ->where('pago', true)
            ->where(function ($query) {
                $query->where('eh_parcelado', false)
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('eh_parcelado', true)
                            ->whereNotNull('parcela_atual');
                    });
            })
            ->select(
                DB::raw('YEAR(data) as ano'),
                DB::raw('MONTH(data) as mes'),
                DB::raw('MAX(data_pagamento) as data_pagamento'),
                DB::raw('SUM(valor) as total'),
                DB::raw('COUNT(*) as quantidade_lancamentos')
            )
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
            'historico' => $historico->map(function ($item) use ($cartao) {
                return [
                    'mes' => $item->mes,
                    'ano' => $item->ano,
                    'mes_nome' => $this->getNomeMes($item->mes),
                    'total' => (float) $item->total,
                    'data_pagamento' => $item->data_pagamento,
                    'quantidade_lancamentos' => $item->quantidade_lancamentos,
                ];
            })->toArray()
        ];
    }

    /**
     * Retorna as parcelas/itens não pagos de um cartão em um mês específico
     * 
     * ATUALIZADO: Agora busca de faturas_cartao_itens ao invés de lancamentos
     * 
     * @param int $cartaoId
     * @param int $mes (1-12)
     * @param int $ano
     * @return array ['itens' => [...], 'total' => float, 'vencimento' => string]
     */
    public function obterFaturaMes(int $cartaoId, int $mes, int $ano): array
    {
        $cartao = CartaoCredito::findOrFail($cartaoId);

        // Busca itens de fatura do mês (pagos e não pagos)
        $itens = \Application\Models\FaturaCartaoItem::where('cartao_credito_id', $cartaoId)
            ->whereYear('data_vencimento', $ano)
            ->whereMonth('data_vencimento', $mes)
            ->orderBy('data_compra')
            ->get();

        // Separar pendentes
        $itensPendentes = $itens->where('pago', false);
        $total = $itensPendentes->sum('valor');

        // Data de vencimento da fatura (dia do vencimento do cartão)
        $dataVencimento = sprintf('%04d-%02d-%02d', $ano, $mes, $cartao->dia_vencimento);

        return [
            'cartao' => [
                'id' => $cartao->id,
                'nome' => $cartao->nome_cartao,
                'ultimos_digitos' => $cartao->ultimos_digitos,
                'dia_vencimento' => $cartao->dia_vencimento,
            ],
            'itens' => $itens->map(function ($item) {
                return [
                    'id' => $item->id,
                    'descricao' => $item->descricao,
                    'valor' => $item->valor,
                    'data_compra' => $item->data_compra,
                    'data_vencimento' => $item->data_vencimento,
                    'parcela_atual' => $item->parcela_atual ?? 1,
                    'total_parcelas' => $item->total_parcelas ?? 1,
                    'pago' => $item->pago,
                    'categoria_id' => $item->categoria_id,
                ];
            })->toArray(),
            'total' => $total,
            'vencimento' => $dataVencimento,
            'mes' => $mes,
            'ano' => $ano,
        ];
    }

    /**
     * Paga a fatura completa do mês
     * 
     * ATUALIZADO: Converte itens de fatura em lançamentos reais e marca como pago
     * 
     * @param int $cartaoId
     * @param int $mes
     * @param int $ano
     * @param int $userId
     * @return array
     */
    public function pagarFatura(int $cartaoId, int $mes, int $ano, int $userId): array
    {
        DB::beginTransaction();

        try {
            $cartao = CartaoCredito::where('id', $cartaoId)
                ->where('user_id', $userId)
                ->firstOrFail();

            // Busca fatura do mês
            $fatura = $this->obterFaturaMes($cartaoId, $mes, $ano);

            if (empty($fatura['itens'])) {
                throw new \Exception('Não há itens para pagar neste mês.');
            }

            // Filtrar apenas itens não pagos
            $itensNaoPagos = array_filter($fatura['itens'], fn($item) => !$item['pago']);

            if (empty($itensNaoPagos)) {
                throw new \Exception('Todos os itens desta fatura já foram pagos.');
            }

            $totalPagar = array_sum(array_column($itensNaoPagos, 'valor'));

            // Usa a conta vinculada ao cartão
            $contaId = $cartao->conta_id;

            if (!$contaId) {
                throw new \Exception('Cartão não está vinculado a nenhuma conta.');
            }

            // Valida se a conta existe e tem saldo suficiente
            $conta = Conta::where('id', $contaId)
                ->where('user_id', $userId)
                ->first();

            if (!$conta) {
                throw new \Exception(sprintf(
                    'Conta ID %d não encontrada ou não pertence ao usuário. Verifique o vínculo do cartão.',
                    $contaId
                ));
            }

            // Calcular saldo real da conta (exclui lançamentos de cartão)
            $saldoAtual = $conta->saldo_inicial +
                Lancamento::where('conta_id', $contaId)
                ->where('user_id', $userId)
                ->whereNull('cartao_credito_id')
                ->sum(DB::raw("CASE WHEN tipo = 'receita' THEN valor ELSE -valor END"));

            if ($saldoAtual < $totalPagar) {
                throw new \Exception(sprintf(
                    'Saldo insuficiente na conta para pagar a fatura. Disponível: R$ %.2f, Necessário: R$ %.2f',
                    $saldoAtual,
                    $totalPagar
                ));
            }

            $dataPagamento = now()->format('Y-m-d');
            $itensIds = array_column($itensNaoPagos, 'id');

            // Converter itens de fatura em lançamentos (compras de cartão já viram lançamentos na hora da compra)
            // MAS para parcelamentos, precisamos manter registro
            foreach ($itensNaoPagos as $itemData) {
                $item = \Application\Models\FaturaCartaoItem::find($itemData['id']);
                if (!$item) continue;

                // Marca item como pago
                $item->pago = true;
                $item->data_pagamento = $dataPagamento;
                $item->save();
            }

            // Devolve o limite ao cartão
            $cartao->limite_disponivel += $totalPagar;
            $cartao->save();

            // Obter categoria de Pagamento de Cartão
            $categoriaId = $this->obterCategoriaPagamentoCartao($userId);

            // Cria lançamento de DESPESA na conta (pagamento da fatura)
            $descricaoPagamento = sprintf(
                'Pagamento Fatura %s •••• %s - %02d/%04d',
                $cartao->nome_cartao,
                $cartao->ultimos_digitos,
                $mes,
                $ano
            );

            error_log("💳 [pagarFatura] Criando lançamento: '{$descricaoPagamento}'");

            $lancamentoPagamento = Lancamento::create([
                'user_id' => $userId,
                'conta_id' => $contaId,
                'categoria_id' => $categoriaId,
                'tipo' => 'despesa',
                'valor' => $totalPagar,
                'descricao' => $descricaoPagamento,
                'data' => $dataPagamento,
                'observacao' => sprintf(
                    'Pagamento automático de %d item(s) do cartão',
                    count($itensIds)
                ),
                'pago' => true,
                'data_pagamento' => $dataPagamento,
            ]);

            error_log("✅ [pagarFatura] Lançamento criado: ID={$lancamentoPagamento->id}");

            DB::commit();

            return [
                'success' => true,
                'message' => 'Fatura paga com sucesso!',
                'valor_pago' => $totalPagar,
                'itens_pagos' => count($itensIds),
                'novo_limite_disponivel' => $cartao->limite_disponivel,
                'lancamento_id' => $lancamentoPagamento->id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Pagar parcelas individuais selecionadas
     * 
     * ATUALIZADO: Busca itens de fatura ao invés de lançamentos
     */
    public function pagarParcelas(int $cartaoId, array $parcelaIds, int $mes, int $ano, int $userId): array
    {
        DB::beginTransaction();

        try {
            $cartao = CartaoCredito::where('id', $cartaoId)
                ->where('user_id', $userId)
                ->firstOrFail();

            // Validar e buscar os ITENS DE FATURA selecionados
            $itens = \Application\Models\FaturaCartaoItem::whereIn('id', $parcelaIds)
                ->where('user_id', $userId)
                ->where('cartao_credito_id', $cartaoId)
                ->where('pago', false)
                ->get();

            if ($itens->isEmpty()) {
                throw new \Exception('Nenhuma parcela válida encontrada para pagamento.');
            }

            $totalPagar = $itens->sum('valor');

            // Usa a conta vinculada ao cartão
            $contaId = $cartao->conta_id;

            if (!$contaId) {
                throw new \Exception('Cartão não está vinculado a nenhuma conta.');
            }

            // Valida se a conta existe e tem saldo suficiente
            $conta = Conta::where('id', $contaId)
                ->where('user_id', $userId)
                ->first();

            if (!$conta) {
                throw new \Exception(sprintf(
                    'Conta ID %d não encontrada ou não pertence ao usuário. Verifique o vínculo do cartão.',
                    $contaId
                ));
            }

            // Calcular saldo real da conta
            // Exclui TODOS os lançamentos de cartão de crédito pois:
            // - Não pagos: são compras futuras (virtual)
            // - Pagos: já foram contabilizados via lançamento "Pagamento Fatura"
            $saldoAtual = $conta->saldo_inicial +
                Lancamento::where('conta_id', $contaId)
                ->where('user_id', $userId)
                ->whereNull('cartao_credito_id')  // Apenas lançamentos normais (sem cartão)
                ->sum(DB::raw("CASE WHEN tipo = 'receita' THEN valor ELSE -valor END"));

            if ($saldoAtual < $totalPagar) {
                throw new \Exception(sprintf(
                    'Saldo insuficiente na conta para pagar as parcelas. Disponível: R$ %.2f, Necessário: R$ %.2f',
                    $saldoAtual,
                    $totalPagar
                ));
            }

            // Marca os ITENS DE FATURA selecionados como pagos
            $dataPagamento = now()->format('Y-m-d');
            \Application\Models\FaturaCartaoItem::whereIn('id', $parcelaIds)->update([
                'pago' => true,
                'data_pagamento' => $dataPagamento
            ]);

            // Devolve o limite ao cartão
            $cartao->limite_disponivel += $totalPagar;
            $cartao->save();

            // Obter categoria de Pagamento de Cartão
            $categoriaId = $this->obterCategoriaPagamentoCartao($userId);

            // Cria lançamento de DESPESA na conta (pagamento das parcelas)
            $descricaoParcelas = count($parcelaIds) === 1
                ? '1 parcela'
                : count($parcelaIds) . ' parcelas';

            $lancamentoPagamento = Lancamento::create([
                'user_id' => $userId,
                'conta_id' => $contaId,
                'categoria_id' => $categoriaId,
                'tipo' => 'despesa',
                'valor' => $totalPagar,
                'descricao' => sprintf(
                    'Pagamento Fatura %s •••• %s - %02d/%04d',
                    $cartao->nome_cartao,
                    $cartao->ultimos_digitos,
                    $mes,
                    $ano
                ),
                'data' => now()->format('Y-m-d'),
                'observacao' => sprintf(
                    'Pagamento parcial: %s selecionada(s)',
                    $descricaoParcelas
                ),
                'pago' => true,
                'data_pagamento' => $dataPagamento,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => sprintf('Pagamento realizado! %s paga(s) com sucesso.', ucfirst($descricaoParcelas)),
                'valor_pago' => $totalPagar,
                'parcelas_pagas' => count($parcelaIds),
                'novo_limite_disponivel' => $cartao->limite_disponivel,
                'lancamento_id' => $lancamentoPagamento->id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Desfazer pagamento de uma parcela específica
     * 
     * @param int $parcelaId ID da parcela (lançamento do cartão)
     * @param int $userId
     * @return array
     */
    /**
     * Desfazer pagamento de uma parcela individual
     * 
     * ATUALIZADO: Usa FaturaCartaoItem ao invés de Lancamento
     */
    public function desfazerPagamentoParcela(int $parcelaId, int $userId): array
    {
        DB::beginTransaction();

        try {
            // Buscar o ITEM DE FATURA
            $item = \Application\Models\FaturaCartaoItem::where('id', $parcelaId)
                ->where('user_id', $userId)
                ->whereNotNull('cartao_credito_id')
                ->where('pago', true)
                ->firstOrFail();

            $cartao = CartaoCredito::where('id', $item->cartao_credito_id)
                ->where('user_id', $userId)
                ->firstOrFail();

            error_log("🔄 [desfazerPagamentoParcela] Item ID={$parcelaId}, Cartão={$cartao->nome_cartao}, Valor=R$ {$item->valor}");

            // Buscar o lançamento de pagamento correspondente
            $mes = date('n', strtotime($item->data_vencimento));
            $ano = date('Y', strtotime($item->data_vencimento));
            $padraoDescricao = sprintf('- %02d/%04d', $mes, $ano);

            // Buscar TODOS os lançamentos de pagamento desse mês
            $lancamentosPagamento = Lancamento::where('user_id', $userId)
                ->whereNull('cartao_credito_id')
                ->where('tipo', 'despesa')
                ->where('descricao', 'LIKE', "Pagamento Fatura%{$cartao->nome_cartao}%{$padraoDescricao}%")
                ->get();

            if ($lancamentosPagamento->isEmpty()) {
                throw new \Exception('Pagamento não encontrado para esta parcela.');
            }

            // Contar quantos itens estão pagos no mesmo mês
            $itensPagosNoMes = \Application\Models\FaturaCartaoItem::where('user_id', $userId)
                ->where('cartao_credito_id', $cartao->id)
                ->whereYear('data_vencimento', $ano)
                ->whereMonth('data_vencimento', $mes)
                ->where('pago', true)
                ->count();

            error_log("📊 [desfazerPagamentoParcela] Itens pagos no mês: {$itensPagosNoMes}");

            // Se é o último item pago do mês, deletar o lançamento de pagamento completamente
            if ($itensPagosNoMes === 1) {
                foreach ($lancamentosPagamento as $pagamento) {
                    error_log("🗑️ [desfazerPagamentoParcela] Deletando lançamento de pagamento ID={$pagamento->id}");
                    $pagamento->delete();
                }
            } else {
                // Se há mais itens pagos, reduzir o valor do lançamento de pagamento
                $lancamentoPagamento = $lancamentosPagamento->first();
                $novoValor = $lancamentoPagamento->valor - $item->valor;

                error_log("💰 [desfazerPagamentoParcela] Reduzindo pagamento de R$ {$lancamentoPagamento->valor} para R$ {$novoValor}");

                $lancamentoPagamento->valor = $novoValor;

                // Atualizar descrição para refletir nova quantidade de itens
                $novaQuantidade = $itensPagosNoMes - 1;
                $textoQuantidade = $novaQuantidade === 1 ? '1 parcela' : "{$novaQuantidade} parcelas";
                $lancamentoPagamento->descricao = preg_replace(
                    '/\(\d+ parcelas?\)/',
                    "({$textoQuantidade})",
                    $lancamentoPagamento->descricao
                );

                $lancamentoPagamento->save();
            }

            // Desmarcar o item como pago
            $item->pago = false;
            $item->data_pagamento = null;
            $item->save();

            // Reduzir limite disponível do cartão (o item volta a estar pendente)
            $cartao->limite_disponivel -= $item->valor;
            $cartao->save();

            DB::commit();

            error_log("✅ [desfazerPagamentoParcela] Concluído com sucesso");

            return [
                'success' => true,
                'message' => 'Pagamento da parcela desfeito com sucesso!',
                'valor_desfeito' => (float) $item->valor,
                'novo_limite_disponivel' => (float) $cartao->limite_disponivel,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            error_log("❌ [desfazerPagamentoParcela] Erro: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retorna todos os meses que têm faturas pendentes para um cartão
     * 
     * @param int $cartaoId
     * @param int $userId
     * @return array
     */
    public function obterMesesComFaturasPendentes(int $cartaoId, int $userId): array
    {
        $parcelas = Lancamento::where('cartao_credito_id', $cartaoId)
            ->where('user_id', $userId)
            ->where('eh_parcelado', true)
            ->whereNotNull('parcela_atual')
            ->where('pago', false)
            ->select(DB::raw('YEAR(data) as ano, MONTH(data) as mes'))
            ->groupBy('ano', 'mes')
            ->orderBy('ano')
            ->orderBy('mes')
            ->get();

        return $parcelas->map(function ($item) {
            return [
                'ano' => $item->ano,
                'mes' => $item->mes,
            ];
        })->toArray();
    }

    /**
     * Retorna resumo dos parcelamentos ativos do cartão
     * Focado em contexto da fatura e próximos meses
     * 
     * @param int $cartaoId
     * @param int $mesAtual Mês da fatura (1-12)
     * @param int $anoAtual Ano da fatura
     * @return array
     */
    public function obterResumoParcelamentos(int $cartaoId, int $mesAtual = null, int $anoAtual = null): array
    {
        try {
            if (!$mesAtual) $mesAtual = (int) date('n');
            if (!$anoAtual) $anoAtual = (int) date('Y');

            error_log("📊 [obterResumoParcelamentos] Início - Cartão: {$cartaoId}, Mês: {$mesAtual}, Ano: {$anoAtual}");

            // Busca parcelamentos ativos de forma simples usando SQL direto
            $db = DB::connection()->getPdo();

            // Query simples para pegar parcelamentos únicos
            $sql = "SELECT 
                        parcelamento_id,
                        descricao,
                        MIN(parcela_atual) as parcela_atual,
                        MAX(total_parcelas) as total_parcelas,
                        valor as valor_parcela,
                        COUNT(CASE WHEN pago = 1 THEN 1 END) as pagas
                    FROM lancamentos
                    WHERE cartao_credito_id = :cartao_id
                        AND eh_parcelado = 1
                        AND parcelamento_id IS NOT NULL
                    GROUP BY parcelamento_id, descricao, valor
                    HAVING COUNT(CASE WHEN pago = 0 THEN 1 END) > 0
                    LIMIT 5";

            $stmt = $db->prepare($sql);
            $stmt->execute(['cartao_id' => $cartaoId]);
            $parcelamentos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $resumo = [];
            foreach ($parcelamentos as $p) {
                $resumo[] = [
                    'id' => (int) $p['parcelamento_id'],
                    'descricao' => $p['descricao'],
                    'parcela_atual' => (int) $p['pagas'] + 1, // Próxima parcela a pagar
                    'total_parcelas' => (int) $p['total_parcelas'],
                    'valor_parcela' => (float) $p['valor_parcela'],
                ];
            }

            // Projeções simplificadas
            $dataRef = new \DateTime(sprintf('%04d-%02d-01', $anoAtual, $mesAtual));
            $data3m = (clone $dataRef)->modify('+3 months')->format('Y-m-d');
            $data6m = (clone $dataRef)->modify('+6 months')->format('Y-m-d');

            $sql3m = "SELECT COALESCE(SUM(valor), 0) as total 
                      FROM lancamentos 
                      WHERE cartao_credito_id = :cartao_id 
                        AND eh_parcelado = 1 
                        AND pago = 0 
                        AND data < :data_limite";

            $stmt = $db->prepare($sql3m);
            $stmt->execute(['cartao_id' => $cartaoId, 'data_limite' => $data3m]);
            $valor3m = (float) $stmt->fetch(\PDO::FETCH_ASSOC)['total'];

            $stmt = $db->prepare($sql3m);
            $stmt->execute(['cartao_id' => $cartaoId, 'data_limite' => $data6m]);
            $valor6m = (float) $stmt->fetch(\PDO::FETCH_ASSOC)['total'];

            error_log("✅ [obterResumoParcelamentos] Sucesso - Total: " . count($resumo) . ", 3m: {$valor3m}, 6m: {$valor6m}");

            return [
                'total_parcelamentos' => count($resumo),
                'parcelamentos' => $resumo,
                'projecao' => [
                    'tres_meses' => $valor3m,
                    'seis_meses' => $valor6m,
                ],
            ];
        } catch (\Exception $e) {
            error_log("❌ [obterResumoParcelamentos] Erro: " . $e->getMessage());

            return [
                'total_parcelamentos' => 0,
                'parcelamentos' => [],
                'projecao' => [
                    'tres_meses' => 0.0,
                    'seis_meses' => 0.0,
                ],
            ];
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
                'cor' => '#e67e22', // Laranja
                'icone' => 'credit-card',
            ]);
        }

        return $categoria->id;
    }

    /**
     * Verificar se a fatura de um mês está paga
     * Considera paga APENAS se TODAS as parcelas do mês estão pagas
     * 
     * @param int $cartaoId
     * @param int $mes
     * @param int $ano
     * @param int $userId
     * @return array|null
     */
    public function faturaEstaPaga(int $cartaoId, int $mes, int $ano, int $userId): ?array
    {
        $cartao = CartaoCredito::where('id', $cartaoId)
            ->where('user_id', $userId)
            ->first();

        if (!$cartao) {
            error_log("❌ [faturaEstaPaga] Cartão não encontrado: ID={$cartaoId}, User={$userId}");
            return null;
        }

        // Buscar TODAS as parcelas do mês
        $dataInicio = sprintf('%04d-%02d-01', $ano, $mes);
        $dataFim = date('Y-m-t', strtotime($dataInicio));

        $totalParcelas = Lancamento::where('user_id', $userId)
            ->where('cartao_credito_id', $cartaoId)
            ->whereBetween('data', [$dataInicio, $dataFim])
            ->count();

        if ($totalParcelas === 0) {
            error_log("❌ [faturaEstaPaga] Nenhuma parcela encontrada no mês {$mes}/{$ano}");
            return null;
        }

        // Contar quantas estão pagas
        $parcelasPagas = Lancamento::where('user_id', $userId)
            ->where('cartao_credito_id', $cartaoId)
            ->whereBetween('data', [$dataInicio, $dataFim])
            ->where('pago', true)
            ->count();

        error_log("🔍 [faturaEstaPaga] Total: {$totalParcelas}, Pagas: {$parcelasPagas}");

        // Só considera paga se TODAS as parcelas foram pagas
        if ($parcelasPagas < $totalParcelas) {
            error_log("⚠️ [faturaEstaPaga] Fatura parcialmente paga ({$parcelasPagas}/{$totalParcelas})");
            return null;
        }

        // Buscar o lançamento de pagamento mais recente para pegar a data
        $padraoDescricao = sprintf('- %02d/%04d', $mes, $ano);
        $lancamentoPagamento = Lancamento::where('user_id', $userId)
            ->whereNull('cartao_credito_id')
            ->where('tipo', 'despesa')
            ->where('descricao', 'LIKE', "Pagamento Fatura%{$cartao->nome_cartao}%{$padraoDescricao}%")
            ->orderBy('id', 'desc')
            ->first();

        error_log("✅ [faturaEstaPaga] Fatura TOTALMENTE paga ({$parcelasPagas}/{$totalParcelas})");

        return [
            'pago' => true,
            'data_pagamento' => $lancamentoPagamento ? $lancamentoPagamento->data : null,
            'valor' => $lancamentoPagamento ? (float) $lancamentoPagamento->valor : 0,
            'lancamento_id' => $lancamentoPagamento ? $lancamentoPagamento->id : null,
        ];
    }

    /**
     * Desfazer pagamento de uma fatura
     * Deleta TODOS os lançamentos de pagamento do mês e desmarca TODAS as parcelas
     * 
     * @param int $cartaoId
     * @param int $mes
     * @param int $ano
     * @param int $userId
     * @return array
     */
    public function desfazerPagamentoFatura(int $cartaoId, int $mes, int $ano, int $userId): array
    {
        DB::beginTransaction();

        try {
            $cartao = CartaoCredito::where('id', $cartaoId)
                ->where('user_id', $userId)
                ->firstOrFail();

            error_log("🔄 [desfazerPagamento] Iniciando para cartão={$cartaoId}, mês={$mes}, ano={$ano}");

            // Buscar TODOS os lançamentos de pagamento da fatura deste mês
            // IMPORTANTE: O lançamento de pagamento NÃO tem cartao_credito_id porque é um lançamento da conta
            $padraoDescricao = sprintf('- %02d/%04d', $mes, $ano);

            $lancamentosPagamento = Lancamento::where('user_id', $userId)
                ->whereNull('cartao_credito_id')  // Lançamento normal da conta
                ->where('tipo', 'despesa')
                ->where('descricao', 'LIKE', "Pagamento Fatura%{$cartao->nome_cartao}%{$padraoDescricao}%")
                ->get();

            if ($lancamentosPagamento->isEmpty()) {
                error_log("❌ [desfazerPagamento] Nenhum pagamento encontrado");
                throw new \Exception('Nenhum pagamento encontrado para esta fatura.');
            }

            $totalPagamentos = $lancamentosPagamento->sum('valor');
            error_log("✅ [desfazerPagamento] Encontrados {$lancamentosPagamento->count()} pagamentos, total: R$ {$totalPagamentos}");

            // Desmarcar TODAS as parcelas do cartão como não pagas
            $this->desmarcarParcelasPagasFatura($cartao->id, $mes, $ano, $userId);

            // Reduzir limite disponível do cartão pelo total (a fatura volta a estar pendente)
            $cartao->limite_disponivel -= $totalPagamentos;
            $cartao->save();

            // Deletar TODOS os lançamentos de pagamento (isso restaura o saldo da conta)
            foreach ($lancamentosPagamento as $pagamento) {
                error_log("🗑️ Deletando lançamento ID={$pagamento->id}, Valor=R$ {$pagamento->valor}");
                $pagamento->delete();
            }

            DB::commit();

            error_log("🎉 [desfazerPagamento] Concluído com sucesso");

            return [
                'success' => true,
                'message' => 'Pagamento desfeito com sucesso! O saldo foi restaurado e as parcelas voltaram a ficar pendentes.',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            error_log("❌ [desfazerPagamento] Erro: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Desmarcar parcelas de uma fatura como não pagas
     * 
     * ATUALIZADO: Usa FaturaCartaoItem ao invés de Lancamento
     * 
     * @param int $cartaoId
     * @param int $mes
     * @param int $ano
     * @param int $userId
     */
    private function desmarcarParcelasPagasFatura(int $cartaoId, int $mes, int $ano, int $userId): void
    {
        // Buscar itens de fatura do cartão naquele mês que foram marcados como pagos
        \Application\Models\FaturaCartaoItem::where('user_id', $userId)
            ->where('cartao_credito_id', $cartaoId)
            ->whereYear('data_vencimento', $ano)
            ->whereMonth('data_vencimento', $mes)
            ->where('pago', true)
            ->update([
                'pago' => false,
                'data_pagamento' => null
            ]);

        error_log("🔄 [desmarcarParcelas] Itens desmarcados para o cartão {$cartaoId} em {$mes}/{$ano}");
    }

    /**
     * Helper para obter nome do mês
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

    /**
     * Verificar faturas que vencerão nos próximos N dias
     */
    public function verificarVencimentosProximos(int $userId, int $diasAlerta = 7): array
    {
        try {
            $dataHoje = new \DateTime();
            $dataLimite = (clone $dataHoje)->modify("+{$diasAlerta} days");

            // Busca todos os cartões ativos do usuário
            $cartoes = CartaoCredito::where('user_id', $userId)
                ->where('ativo', true)
                ->get();

            $alertas = [];

            foreach ($cartoes as $cartao) {
                try {
                    // Calcula data de vencimento da fatura atual
                    $mesAtual = (int) $dataHoje->format('n');
                    $anoAtual = (int) $dataHoje->format('Y');

                    $dataVencimento = \DateTime::createFromFormat(
                        'Y-n-j',
                        "{$anoAtual}-{$mesAtual}-{$cartao->dia_vencimento}"
                    );

                    if (!$dataVencimento) {
                        continue;
                    }

                    // Se o vencimento já passou este mês, considera o próximo mês
                    if ($dataVencimento < $dataHoje) {
                        $dataVencimento->modify('+1 month');
                        $mesAtual = (int) $dataVencimento->format('n');
                        $anoAtual = (int) $dataVencimento->format('Y');
                    }

                    // Verifica se está dentro do prazo de alerta
                    if ($dataVencimento <= $dataLimite && $dataVencimento >= $dataHoje) {
                        // Verifica diretamente se há lançamentos não pagos neste mês (mais simples e seguro)
                        $totalFatura = Lancamento::where('cartao_credito_id', $cartao->id)
                            ->where('pago', false)
                            ->where(function ($query) {
                                $query->where('eh_parcelado', false)
                                    ->orWhere(function ($subQuery) {
                                        $subQuery->where('eh_parcelado', true)
                                            ->whereNotNull('parcela_atual');
                                    });
                            })
                            ->whereYear('data', $anoAtual)
                            ->whereMonth('data', $mesAtual)
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
                    // Log do erro mas continua processando outros cartões
                    error_log("Erro ao verificar vencimento do cartão {$cartao->id}: " . $e->getMessage());
                    continue;
                }
            }

            return $alertas;
        } catch (\Exception $e) {
            error_log("Erro geral em verificarVencimentosProximos: " . $e->getMessage());
            return [];
        }
    }
}
