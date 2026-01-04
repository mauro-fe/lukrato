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
     * Retorna as parcelas não pagas de um cartão em um mês específico
     * 
     * @param int $cartaoId
     * @param int $mes (1-12)
     * @param int $ano
     * @return array ['parcelas' => [...], 'total' => float, 'vencimento' => string]
     */
    public function obterFaturaMes(int $cartaoId, int $mes, int $ano): array
    {
        $cartao = CartaoCredito::findOrFail($cartaoId);

        // Busca TODAS as parcelas do cartão no mês (pagas e não pagas)
        $parcelas = Lancamento::where('cartao_credito_id', $cartaoId)
            ->where(function ($query) {
                // Lançamentos não parcelados OU parcelas de parcelamentos (ignora o pai)
                $query->where('eh_parcelado', false)
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('eh_parcelado', true)
                            ->whereNotNull('parcela_atual');
                    });
            })
            ->whereYear('data', $ano)
            ->whereMonth('data', $mes)
            ->orderBy('data')
            ->get();

        // Separar pagas e não pagas
        $parcelasPendentes = $parcelas->where('pago', false);
        $total = $parcelasPendentes->sum('valor');

        // Data de vencimento da fatura (dia do vencimento do cartão)
        $dataVencimento = sprintf('%04d-%02d-%02d', $ano, $mes, $cartao->dia_vencimento);

        return [
            'cartao' => [
                'id' => $cartao->id,
                'nome' => $cartao->nome_cartao,
                'ultimos_digitos' => $cartao->ultimos_digitos,
                'dia_vencimento' => $cartao->dia_vencimento,
            ],
            'parcelas' => $parcelas->map(function ($lancamento) {
                return [
                    'id' => $lancamento->id,
                    'descricao' => $lancamento->descricao,
                    'valor' => $lancamento->valor,
                    'data_vencimento' => $lancamento->data,
                    'parcela_atual' => $lancamento->parcela_atual ?? 1,
                    'total_parcelas' => $lancamento->total_parcelas ?? 1,
                    'pago' => $lancamento->pago,
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
     * Marca todas as parcelas como pagas e devolve o limite ao cartão
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

            if (empty($fatura['parcelas'])) {
                throw new \Exception('Não há parcelas para pagar neste mês.');
            }

            $totalPagar = $fatura['total'];

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
                    'Saldo insuficiente na conta para pagar a fatura. Disponível: R$ %.2f, Necessário: R$ %.2f',
                    $saldoAtual,
                    $totalPagar
                ));
            }

            // Marca todas as parcelas como pagas
            $parcelasIds = array_column($fatura['parcelas'], 'id');
            $dataPagamento = now()->format('Y-m-d');
            Lancamento::whereIn('id', $parcelasIds)->update([
                'pago' => true,
                'data_pagamento' => $dataPagamento
            ]);

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
                'data' => now()->format('Y-m-d'),
                'observacao' => sprintf(
                    'Pagamento automático de %d parcela(s) do cartão',
                    count($parcelasIds)
                ),
                'pago' => true, // Já marca como pago pois é o pagamento em si
                'data_pagamento' => $dataPagamento,
            ]);

            error_log("✅ [pagarFatura] Lançamento criado: ID={$lancamentoPagamento->id}");

            DB::commit();

            return [
                'success' => true,
                'message' => 'Fatura paga com sucesso!',
                'valor_pago' => $totalPagar,
                'parcelas_pagas' => count($parcelasIds),
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
     */
    public function pagarParcelas(int $cartaoId, array $parcelaIds, int $mes, int $ano, int $userId): array
    {
        DB::beginTransaction();

        try {
            $cartao = CartaoCredito::where('id', $cartaoId)
                ->where('user_id', $userId)
                ->firstOrFail();

            // Validar e buscar as parcelas selecionadas
            $parcelas = Lancamento::whereIn('id', $parcelaIds)
                ->where('user_id', $userId)
                ->where('cartao_credito_id', $cartaoId)
                ->where('pago', false)
                ->get();

            if ($parcelas->isEmpty()) {
                throw new \Exception('Nenhuma parcela válida encontrada para pagamento.');
            }

            $totalPagar = $parcelas->sum('valor');

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

            // Marca as parcelas selecionadas como pagas
            $dataPagamento = now()->format('Y-m-d');
            Lancamento::whereIn('id', $parcelaIds)->update([
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
    public function desfazerPagamentoParcela(int $parcelaId, int $userId): array
    {
        DB::beginTransaction();

        try {
            // Buscar a parcela
            $parcela = Lancamento::where('id', $parcelaId)
                ->where('user_id', $userId)
                ->whereNotNull('cartao_credito_id')
                ->where('pago', true)
                ->firstOrFail();

            $cartao = CartaoCredito::where('id', $parcela->cartao_credito_id)
                ->where('user_id', $userId)
                ->firstOrFail();

            error_log("🔄 [desfazerPagamentoParcela] Parcela ID={$parcelaId}, Cartão={$cartao->nome_cartao}, Valor=R$ {$parcela->valor}");

            // Buscar o lançamento de pagamento correspondente
            // O pagamento parcial tem descrição do tipo "Pagamento Fatura NOME •••• XXXX - MM/YYYY (X parcelas)"
            $mes = date('n', strtotime($parcela->data));
            $ano = date('Y', strtotime($parcela->data));
            $padraoDescricao = sprintf('- %02d/%04d', $mes, $ano);

            // Buscar TODOS os lançamentos de pagamento desse mês para verificar quantas parcelas foram pagas
            $lancamentosPagamento = Lancamento::where('user_id', $userId)
                ->whereNull('cartao_credito_id')
                ->where('tipo', 'despesa')
                ->where('descricao', 'LIKE', "Pagamento Fatura%{$cartao->nome_cartao}%{$padraoDescricao}%")
                ->get();

            if ($lancamentosPagamento->isEmpty()) {
                throw new \Exception('Pagamento não encontrado para esta parcela.');
            }

            // Se há mais de uma parcela paga no mesmo mês, precisamos ajustar
            $parcelasPagasNoMes = Lancamento::where('user_id', $userId)
                ->where('cartao_credito_id', $cartao->id)
                ->whereYear('data', $ano)
                ->whereMonth('data', $mes)
                ->where('pago', true)
                ->count();

            error_log("📊 [desfazerPagamentoParcela] Parcelas pagas no mês: {$parcelasPagasNoMes}");

            // Se é a última parcela paga do mês, deletar o lançamento de pagamento completamente
            if ($parcelasPagasNoMes === 1) {
                foreach ($lancamentosPagamento as $pagamento) {
                    error_log("🗑️ [desfazerPagamentoParcela] Deletando lançamento de pagamento ID={$pagamento->id}");
                    $pagamento->delete();
                }
            } else {
                // Se há mais parcelas pagas, reduzir o valor do lançamento de pagamento
                $lancamentoPagamento = $lancamentosPagamento->first();
                $novoValor = $lancamentoPagamento->valor - $parcela->valor;

                error_log("💰 [desfazerPagamentoParcela] Reduzindo pagamento de R$ {$lancamentoPagamento->valor} para R$ {$novoValor}");

                $lancamentoPagamento->valor = $novoValor;

                // Atualizar descrição para refletir nova quantidade de parcelas
                $novaQuantidade = $parcelasPagasNoMes - 1;
                $textoQuantidade = $novaQuantidade === 1 ? '1 parcela' : "{$novaQuantidade} parcelas";
                $lancamentoPagamento->descricao = preg_replace(
                    '/\(\d+ parcelas?\)/',
                    "({$textoQuantidade})",
                    $lancamentoPagamento->descricao
                );

                $lancamentoPagamento->save();
            }

            // Desmarcar a parcela como paga
            $parcela->pago = false;
            $parcela->data_pagamento = null;
            $parcela->save();

            // Reduzir limite disponível do cartão (a parcela volta a estar pendente)
            $cartao->limite_disponivel -= $parcela->valor;
            $cartao->save();

            DB::commit();

            error_log("✅ [desfazerPagamentoParcela] Concluído com sucesso");

            return [
                'success' => true,
                'message' => 'Pagamento da parcela desfeito com sucesso!',
                'valor_desfeito' => (float) $parcela->valor,
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
     * @param int $cartaoId
     * @param int $mes
     * @param int $ano
     * @param int $userId
     */
    private function desmarcarParcelasPagasFatura(int $cartaoId, int $mes, int $ano, int $userId): void
    {
        // Buscar lançamentos do cartão naquele mês que foram marcados como pagos
        $dataInicio = sprintf('%04d-%02d-01', $ano, $mes);
        $dataFim = date('Y-m-t', strtotime($dataInicio));

        Lancamento::where('user_id', $userId)
            ->where('cartao_credito_id', $cartaoId)
            ->whereBetween('data', [$dataInicio, $dataFim])
            ->where('pago', true)
            ->update([
                'pago' => false,
                'data_pagamento' => null
            ]);

        error_log("🔄 [desmarcarParcelas] Parcelas desmarcadas para o cartão {$cartaoId} em {$mes}/{$ano}");
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
