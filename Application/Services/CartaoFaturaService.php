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

        // Busca lançamentos do cartão no mês que não foram pagos ainda
        // Inclui tanto lançamentos simples quanto parcelas de parcelamentos
        $parcelas = Lancamento::where('cartao_credito_id', $cartaoId)
            ->where('pago', false)
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

        $total = $parcelas->sum('valor');

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
                ->firstOrFail();

            $saldoAtual = $conta->saldo_inicial +
                Lancamento::where('conta_id', $contaId)
                ->where('user_id', $userId)
                ->sum(DB::raw("CASE WHEN tipo = 'receita' THEN valor ELSE -valor END"));

            if ($saldoAtual < $totalPagar) {
                throw new \Exception('Saldo insuficiente na conta para pagar a fatura.');
            }

            // Marca todas as parcelas como pagas
            $parcelasIds = array_column($fatura['parcelas'], 'id');
            Lancamento::whereIn('id', $parcelasIds)->update(['pago' => true]);

            // Devolve o limite ao cartão
            $cartao->limite_disponivel += $totalPagar;
            $cartao->save();

            // Obter categoria de Pagamento de Cartão
            $categoriaId = $this->obterCategoriaPagamentoCartao($userId);

            // Cria lançamento de DESPESA na conta (pagamento da fatura)
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
                    'Pagamento automático de %d parcela(s) do cartão',
                    count($parcelasIds)
                ),
                'pago' => true, // Já marca como pago pois é o pagamento em si
            ]);

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
            $db = \Illuminate\Database\Capsule\Manager::connection()->getPdo();

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
