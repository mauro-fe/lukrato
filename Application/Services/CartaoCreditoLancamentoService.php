<?php

namespace Application\Services;

use Application\Models\Lancamento;
use Application\Models\CartaoCredito;
use Application\Models\Parcelamento;
use Application\Models\FaturaCartaoItem;
use Application\Models\Fatura;
use Illuminate\Database\Capsule\Manager as DB;
use Exception;

class CartaoCreditoLancamentoService
{
    /**
     * Criar lançamento com cartão de crédito (parcelado ou à vista)
     * ATUALIZADO: Agora cria FaturaCartaoItem em vez de Lancamento direto
     * 
     * @param int $userId
     * @param array $data Dados do lançamento incluindo cartao_credito_id, eh_parcelado, total_parcelas
     * @return array ['success' => bool, 'itens' => array, 'message' => string]
     */
    public function criarLancamentoCartao(int $userId, array $data): array
    {
        try {
            DB::beginTransaction();

            $cartaoId = $data['cartao_credito_id'] ?? null;
            $ehParcelado = (bool)($data['eh_parcelado'] ?? false);
            $totalParcelas = (int)($data['total_parcelas'] ?? 1);
            $valorCompra = (float)($data['valor'] ?? 0);

            // Buscar cartão
            $cartao = CartaoCredito::where('id', $cartaoId)
                ->where('user_id', $userId)
                ->first();

            if (!$cartao) {
                return [
                    'success' => false,
                    'message' => 'Cartão de crédito não encontrado',
                ];
            }

            // VALIDAR LIMITE DISPONÍVEL
            if ($valorCompra > $cartao->limite_disponivel) {
                return [
                    'success' => false,
                    'message' => sprintf(
                        'Limite insuficiente. Disponível: R$ %.2f, Necessário: R$ %.2f',
                        $cartao->limite_disponivel,
                        $valorCompra
                    ),
                ];
            }

            // SEMPRE usar conta_id do cartão (conta de pagamento configurada)
            // Não aceitar conta_id externo para evitar inconsistências
            $contaId = $cartao->conta_id;

            LogService::info("[CARTAO] Dados recebidos", [
                'conta_id_cartao' => $cartao->conta_id ?? 'null',
                'cartao_id' => $cartaoId,
                'user_id' => $userId
            ]);

            $itens = [];

            if ($ehParcelado && $totalParcelas >= 2) {
                // Criar itens de fatura parcelados
                $itens = $this->criarLancamentoParcelado($userId, $data, $cartao, $contaId);
            } else {
                // Criar item de fatura à vista
                $itens[] = $this->criarLancamentoVista($userId, $data, $cartao, $contaId);
            }

            DB::commit();

            return [
                'success' => true,
                'itens' => $itens,
                'total_criados' => count($itens),
                'message' => $ehParcelado
                    ? "Compra parcelada em {$totalParcelas}x adicionada à fatura do cartão"
                    : 'Compra adicionada à fatura do cartão',
            ];
        } catch (Exception $e) {
            DB::rollBack();
            LogService::error("Erro ao criar item de fatura", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao criar item de fatura: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Criar lançamento à vista no cartão
     * ATUALIZADO: Busca ou cria fatura mensal (1 fatura por mês por cartão)
     * REFATORAÇÃO: mes_referencia agora usa mês da COMPRA (competência), não do vencimento
     * 
     * CORREÇÃO FINAL: Agora cria o Lancamento IMEDIATAMENTE no momento da compra:
     * - afeta_saldo = false (não afeta saldo da conta - só vai afetar no pagamento da fatura)
     * - afeta_competencia = true (conta como despesa do mês da compra)
     * - pago = false (pendente até pagar fatura)
     */
    private function criarLancamentoVista(int $userId, array $data, CartaoCredito $cartao, ?int $contaId): FaturaCartaoItem
    {
        $dataCompra = $data['data'] ?? date('Y-m-d');
        $vencimento = $this->calcularDataVencimento($dataCompra, $cartao->dia_vencimento, $cartao->dia_fechamento);

        // Extrair mês/ano da COMPRA para competência (não do vencimento)
        [$anoCompra, $mesCompra] = explode('-', $dataCompra);

        // Buscar ou criar fatura do mês de VENCIMENTO (onde a parcela será cobrada)
        $fatura = $this->buscarOuCriarFatura(
            $userId,
            $cartao->id,
            $vencimento['mes'],
            $vencimento['ano']
        );

        // ===============================================================
        // CORREÇÃO: Criar LANÇAMENTO no momento da COMPRA (não no pagamento)
        // Vincula conta_id para rastreabilidade, mas afeta_caixa = false
        // ===============================================================
        $lancamento = Lancamento::create([
            'user_id' => $userId,
            'conta_id' => $contaId,                    // Vincula conta para rastreabilidade
            'categoria_id' => $data['categoria_id'] ?? null,
            'cartao_credito_id' => $cartao->id,
            'tipo' => 'despesa',
            'valor' => $data['valor'],
            'descricao' => $data['descricao'],
            'data' => $dataCompra,                     // Data da compra
            'data_competencia' => $dataCompra,         // Competência = mês da compra
            'observacao' => sprintf(
                'Compra cartão %s •••• %s',
                $cartao->nome_cartao,
                $cartao->ultimos_digitos
            ),
            'pago' => false,                           // PENDENTE (não pago ainda)
            'data_pagamento' => null,
            // Campos de controle
            'afeta_competencia' => true,               // ✅ Conta nas despesas do mês da compra
            'afeta_caixa' => false,                    // ❌ NÃO afeta saldo (só quando pagar fatura)
            'origem_tipo' => 'cartao_credito',
        ]);

        LogService::info("[CARTAO] Lançamento criado no momento da compra (à vista)", [
            'lancamento_id' => $lancamento->id,
            'valor' => $data['valor'],
            'data_competencia' => $dataCompra,
            'afeta_competencia' => true,
            'afeta_caixa' => false,
            'pago' => false,
        ]);

        // Criar item de fatura vinculado à fatura mensal E ao lançamento
        $item = FaturaCartaoItem::create([
            'user_id' => $userId,
            'cartao_credito_id' => $cartao->id,
            'fatura_id' => $fatura->id,
            'lancamento_id' => $lancamento->id,        // NOVO: Vincular ao lançamento
            'descricao' => $data['descricao'],
            'valor' => $data['valor'],
            'data_compra' => $dataCompra,
            'data_vencimento' => $vencimento['data'],
            'categoria_id' => $data['categoria_id'] ?? null,
            'parcela_atual' => 1,
            'total_parcelas' => 1,
            // CORREÇÃO: mes_referencia = mês da COMPRA (competência), não do vencimento
            'mes_referencia' => (int) $mesCompra,
            'ano_referencia' => (int) $anoCompra,
            'pago' => false,
        ]);

        // Atualizar valor total da fatura
        $fatura->valor_total += $data['valor'];
        $fatura->save();

        // Atualizar limite disponível do cartão (reduz limite)
        $this->atualizarLimiteCartao($cartao->id, $data['valor'], 'debito');

        return $item;
    }

    /**
     * Criar lançamento parcelado
     * ATUALIZADO: Cada parcela vai para a fatura mensal correspondente
     * 
     * CORREÇÃO FINAL: Agora cria o Lancamento IMEDIATAMENTE para CADA parcela:
     * - afeta_saldo = false (não afeta saldo da conta - só vai afetar no pagamento da fatura)
     * - afeta_competencia = true (conta como despesa do mês da PARCELA)
     * - pago = false (pendente até pagar fatura)
     * 
     * IMPORTANTE: Cada parcela tem sua própria data de competência (mês do vencimento da parcela)
     * - Parcela 1 comprada em janeiro → competência janeiro
     * - Parcela 2 vence em fevereiro → competência fevereiro
     */
    private function criarLancamentoParcelado(int $userId, array $data, CartaoCredito $cartao, ?int $contaId): array
    {
        $itens = [];
        $valorTotal = $data['valor'];
        $totalParcelas = (int)$data['total_parcelas'];
        $valorParcela = round($valorTotal / $totalParcelas, 2);

        // Ajustar última parcela para compensar arredondamento
        $somaParcelasAnteriores = $valorParcela * ($totalParcelas - 1);
        $valorUltimaParcela = round($valorTotal - $somaParcelasAnteriores, 2);

        $dataCompra = $data['data'] ?? date('Y-m-d');

        // Criar cada parcela na fatura mensal correspondente
        for ($i = 1; $i <= $totalParcelas; $i++) {
            $vencimento = $this->calcularDataParcelaMes($dataCompra, $cartao->dia_vencimento, $cartao->dia_fechamento, $i - 1);
            $valorDessaParcela = ($i === $totalParcelas) ? $valorUltimaParcela : $valorParcela;

            // Buscar ou criar fatura do mês de VENCIMENTO da parcela
            $fatura = $this->buscarOuCriarFatura(
                $userId,
                $cartao->id,
                $vencimento['mes'],
                $vencimento['ano']
            );

            // ===============================================================
            // CORREÇÃO: Cada parcela tem:
            // - data = data de vencimento da parcela (fluxo de caixa)
            // - data_competencia = data da COMPRA original (quando a despesa aconteceu)
            // ===============================================================
            $descricaoParcela = $data['descricao'] . " ({$i}/{$totalParcelas})";
            $dataVencimentoParcela = $vencimento['data'];  // Data de vencimento desta parcela

            $lancamento = Lancamento::create([
                'user_id' => $userId,
                'conta_id' => $contaId,                    // Vincula conta para rastreabilidade
                'categoria_id' => $data['categoria_id'] ?? null,
                'cartao_credito_id' => $cartao->id,
                'tipo' => 'despesa',
                'valor' => $valorDessaParcela,
                'descricao' => $descricaoParcela,
                'data' => $dataVencimentoParcela,          // Vencimento: quando vai para a fatura
                'data_competencia' => $dataCompra,         // Competência: data da COMPRA original
                'observacao' => sprintf(
                    'Compra parcelada cartão %s •••• %s - Parcela %d/%d - Compra em %s',
                    $cartao->nome_cartao,
                    $cartao->ultimos_digitos,
                    $i,
                    $totalParcelas,
                    date('d/m/Y', strtotime($dataCompra))
                ),
                'pago' => false,                           // PENDENTE (não pago ainda)
                'data_pagamento' => null,
                // Campos de controle
                'afeta_competencia' => true,               // ✅ Conta nas despesas do mês da COMPRA
                'afeta_caixa' => false,                    // ❌ NÃO afeta saldo (só quando pagar fatura)
                'origem_tipo' => 'cartao_credito',
            ]);

            LogService::info("[CARTAO] Lançamento criado no momento da compra (parcelado {$i}/{$totalParcelas})", [
                'lancamento_id' => $lancamento->id,
                'fatura_id' => $fatura->id,
                'mes_ano_vencimento' => "{$vencimento['mes']}/{$vencimento['ano']}",
                'data_competencia' => $dataVencimentoParcela,
                'data_compra_original' => $dataCompra,
                'cartao_id' => $cartao->id,
                'valor' => $valorDessaParcela,
                'afeta_competencia' => true,
                'afeta_caixa' => false,
                'pago' => false,
            ]);

            // Extrair mês/ano do VENCIMENTO desta parcela (cada parcela vai para seu mês)
            $mesVencParcela = (int) date('n', strtotime($dataVencimentoParcela));
            $anoVencParcela = (int) date('Y', strtotime($dataVencimentoParcela));

            // Criar item de fatura vinculado à fatura mensal E ao lançamento
            $item = FaturaCartaoItem::create([
                'user_id' => $userId,
                'cartao_credito_id' => $cartao->id,
                'fatura_id' => $fatura->id,
                'lancamento_id' => $lancamento->id,        // NOVO: Vincular ao lançamento
                'descricao' => $descricaoParcela,
                'valor' => $valorDessaParcela,
                'data_compra' => $dataCompra,              // Data original da compra
                'data_vencimento' => $dataVencimentoParcela,
                'categoria_id' => $data['categoria_id'] ?? null,
                'parcela_atual' => $i,
                'total_parcelas' => $totalParcelas,
                // Para parcelados: mes_referencia = mês que a PARCELA vence (não a compra)
                // Parcela 1 vence em jan -> fatura jan, Parcela 2 vence em fev -> fatura fev, etc.
                'mes_referencia' => $mesVencParcela,
                'ano_referencia' => $anoVencParcela,
                'pago' => false,
            ]);

            // Atualizar valor total da fatura
            $fatura->valor_total += $valorDessaParcela;
            $fatura->save();

            $itens[] = $item;

            // Atualizar limite do cartão apenas na primeira parcela (reduz limite total)
            if ($i === 1) {
                $this->atualizarLimiteCartao($cartao->id, $valorTotal, 'debito');
            }
        }

        return $itens;
    }

    /**
     * Calcular data de vencimento da fatura
     * Se a compra foi ANTES do dia de fechamento, vence na fatura do mês atual
     * Se foi NO DIA de fechamento ou DEPOIS, vence na fatura do próximo mês
     * 
     * @return array ['data' => string, 'mes' => int, 'ano' => int]
     */
    private function calcularDataVencimento(string $dataCompra, int $diaVencimento, ?int $diaFechamento = null): array
    {
        $dataObj = new \DateTime($dataCompra);
        $mesAtual = (int)$dataObj->format('n');
        $anoAtual = (int)$dataObj->format('Y');
        $diaCompra = (int)$dataObj->format('j');

        // Se não informou dia de fechamento, considerar 5 dias antes do vencimento
        if ($diaFechamento === null) {
            $diaFechamento = max(1, $diaVencimento - 5);
        }

        // Se comprou NO DIA de fechamento ou DEPOIS, vai para o próximo mês
        if ($diaCompra >= $diaFechamento) {
            $mesVencimento = $mesAtual + 1;
            $anoVencimento = $anoAtual;

            if ($mesVencimento > 12) {
                $mesVencimento = 1;
                $anoVencimento++;
            }
        } else {
            // Comprou ANTES do fechamento - vence no mês atual
            $mesVencimento = $mesAtual;
            $anoVencimento = $anoAtual;
        }

        // Ajustar dia para o último dia do mês se necessário
        $ultimoDiaMes = (int)date('t', mktime(0, 0, 0, $mesVencimento, 1, $anoVencimento));
        $diaFinal = min($diaVencimento, $ultimoDiaMes);

        return [
            'data' => sprintf('%04d-%02d-%02d', $anoVencimento, $mesVencimento, $diaFinal),
            'mes' => $mesVencimento,
            'ano' => $anoVencimento,
        ];
    }

    /**
     * Calcular data de vencimento de uma parcela específica
     * 
     * @return array ['data' => string, 'mes' => int, 'ano' => int]
     */
    private function calcularDataParcelaMes(string $dataCompra, int $diaVencimento, ?int $diaFechamento, int $mesesAFrente): array
    {
        $vencimentoPrimeira = $this->calcularDataVencimento($dataCompra, $diaVencimento, $diaFechamento);

        $dataObj = new \DateTime($vencimentoPrimeira['data']);
        $dataObj->modify("+{$mesesAFrente} months");

        // Ajustar para o último dia do mês se necessário
        $mesAlvo = (int)$dataObj->format('n');
        $anoAlvo = (int)$dataObj->format('Y');
        $ultimoDiaMes = (int)date('t', mktime(0, 0, 0, $mesAlvo, 1, $anoAlvo));
        $diaFinal = min($diaVencimento, $ultimoDiaMes);

        return [
            'data' => sprintf('%04d-%02d-%02d', $anoAlvo, $mesAlvo, $diaFinal),
            'mes' => $mesAlvo,
            'ano' => $anoAlvo,
        ];
    }

    /**
     * Atualizar limite disponível do cartão
     * Usa o método atualizarLimiteDisponivel() do model que recalcula baseado nos itens não pagos
     */
    private function atualizarLimiteCartao(int $cartaoId, float $valor, string $operacao): void
    {
        $cartao = CartaoCredito::find($cartaoId);
        if (!$cartao) return;

        $limiteAnterior = $cartao->limite_disponivel;

        // Recalcular limite baseado nos itens de fatura não pagos (forma mais confiável)
        $cartao->atualizarLimiteDisponivel();

        // Recarregar o modelo para ter o valor atualizado
        $cartao->refresh();

        LogService::info("💳 [LIMITE] Limite atualizado", [
            'cartao_id' => $cartaoId,
            'operacao' => $operacao,
            'valor' => $valor,
            'limite_anterior' => $limiteAnterior,
            'limite_novo' => $cartao->limite_disponivel,
        ]);
    }

    /**
     * Cancelar parcelas futuras de um lançamento parcelado
     */
    public function cancelarParcelamento(int $parcelamentoId, int $userId): array
    {
        try {
            DB::beginTransaction();

            $parcelamento = Parcelamento::where('id', $parcelamentoId)
                ->where('user_id', $userId)
                ->first();

            if (!$parcelamento) {
                return [
                    'success' => false,
                    'message' => 'Parcelamento não encontrado'
                ];
            }

            if ($parcelamento->status !== 'ativo') {
                return [
                    'success' => false,
                    'message' => 'Parcelamento não está ativo'
                ];
            }

            $hoje = date('Y-m-d');

            // Buscar parcelas FUTURAS (não vencidas)
            $parcelasFuturas = Lancamento::where('parcelamento_id', $parcelamento->id)
                ->where('data', '>', $hoje)
                ->get();

            if ($parcelasFuturas->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Não existem parcelas futuras para cancelar'
                ];
            }

            // Calcular valor total a devolver ao limite
            $valorDevolver = $parcelasFuturas->sum('valor');

            // Devolver limite do cartão (uma vez só)
            if ($parcelamento->cartao_credito_id) {
                $this->atualizarLimiteCartao(
                    $parcelamento->cartao_credito_id,
                    $valorDevolver,
                    'credito'
                );
            }

            // Cancelar parcelas futuras (soft delete recomendado)
            foreach ($parcelasFuturas as $parcela) {
                $parcela->status = 'cancelado'; // ou usar soft delete
                $parcela->save();
            }

            // Atualizar status do parcelamento
            $parcelamento->status = 'parcial';
            $parcelamento->save();

            DB::commit();

            return [
                'success' => true,
                'parcelamento_id' => $parcelamento->id,
                'parcelas_canceladas' => $parcelasFuturas->count(),
                'valor_devolvido' => $valorDevolver,
                'message' => 'Parcelamento cancelado parcialmente com sucesso'
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            error_log('[CARTAO] Erro ao cancelar parcelamento: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao cancelar parcelamento'
            ];
        }
    }
    /**
     * Buscar ou criar fatura mensal (1 fatura por cartão por mês)
     */
    private function buscarOuCriarFatura(int $userId, int $cartaoId, int $mes, int $ano): Fatura
    {
        $descricao = "Fatura {$mes}/{$ano}";

        // Buscar fatura existente pela descrição padronizada
        $fatura = Fatura::where('user_id', $userId)
            ->where('cartao_credito_id', $cartaoId)
            ->where('descricao', $descricao)
            ->first();

        // Se não existe, criar nova fatura mensal
        if (!$fatura) {
            $fatura = Fatura::create([
                'user_id' => $userId,
                'cartao_credito_id' => $cartaoId,
                'descricao' => $descricao,
                'valor_total' => 0,
                'numero_parcelas' => 0,
                'data_compra' => date('Y-m-d'),
            ]);

            LogService::info('[CARTAO] Nova fatura mensal criada', [
                'fatura_id' => $fatura->id,
                'cartao_id' => $cartaoId,
                'mes' => $mes,
                'ano' => $ano,
            ]);
        }

        return $fatura;
    }
}
