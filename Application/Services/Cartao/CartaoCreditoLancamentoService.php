<?php

declare(strict_types=1);

namespace Application\Services\Cartao;

use Application\Container\ApplicationContainer;
use Application\Models\CartaoCredito;
use Application\Models\FaturaCartaoItem;
use Illuminate\Database\Capsule\Manager as DB;
use Exception;
use Application\Services\Infrastructure\LogService;

class CartaoCreditoLancamentoService
{
    private CartaoBillingDateService $billingDateService;
    private CartaoFaturaSupportService $faturaSupportService;
    private CartaoLimitUpdaterService $limitUpdaterService;
    private CartaoPostSaleService $postSaleService;

    public function __construct(
        ?CartaoBillingDateService $billingDateService = null,
        ?CartaoFaturaSupportService $faturaSupportService = null,
        ?CartaoLimitUpdaterService $limitUpdaterService = null,
        ?CartaoPostSaleService $postSaleService = null
    ) {
        $this->billingDateService = ApplicationContainer::resolveOrNew($billingDateService, CartaoBillingDateService::class);
        $this->faturaSupportService = ApplicationContainer::resolveOrNew($faturaSupportService, CartaoFaturaSupportService::class);
        $this->limitUpdaterService = ApplicationContainer::resolveOrNew($limitUpdaterService, CartaoLimitUpdaterService::class);
        $this->postSaleService = ApplicationContainer::resolveOrNew(
            $postSaleService,
            CartaoPostSaleService::class,
            fn(): CartaoPostSaleService => new CartaoPostSaleService(
                $this->faturaSupportService,
                $this->limitUpdaterService
            )
        );
    }

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
            } elseif (!empty($data['recorrente'])) {
                // Criar item de fatura recorrente (assinatura)
                $itens[] = $this->criarLancamentoRecorrente($userId, $data, $cartao, $contaId);
            } else {
                // Criar item de fatura à vista
                $itens[] = $this->criarLancamentoVista($userId, $data, $cartao, $contaId);
            }

            DB::commit();

            $isRecorrente = !empty($data['recorrente']);

            return [
                'success' => true,
                'itens' => $itens,
                'total_criados' => count($itens),
                'message' => $isRecorrente
                    ? 'Assinatura recorrente adicionada à fatura do cartão'
                    : ($ehParcelado
                        ? "Compra parcelada em {$totalParcelas}x adicionada à fatura do cartão"
                        : 'Compra adicionada à fatura do cartão'),
            ];
        } catch (Exception $e) {
            DB::rollBack();
            LogService::error("Erro ao criar item de fatura", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao criar item de fatura.',
            ];
        }
    }

    /**
     * Criar lançamento à vista no cartão
     * REFATORADO: Agora cria APENAS FaturaCartaoItem, SEM criar Lancamento
     * 
     * O lançamento único será criado apenas quando a fatura for paga
     * (um lançamento por pagamento de fatura, não por item)
     */
    private function criarLancamentoVista(int $userId, array $data, CartaoCredito $cartao, ?int $contaId): FaturaCartaoItem
    {
        $dataCompra = $data['data'] ?? date('Y-m-d');
        $vencimento = $this->billingDateService->calcularDataVencimento($dataCompra, $cartao->dia_vencimento, $cartao->dia_fechamento);

        // Calcular competência (mês da fatura que fechou, não do vencimento)
        $competencia = $this->billingDateService->calcularCompetencia($dataCompra, $cartao->dia_fechamento);

        // Buscar ou criar fatura do mês de VENCIMENTO (onde a parcela será cobrada)
        $fatura = $this->faturaSupportService->buscarOuCriarFatura(
            $userId,
            $cartao->id,
            $vencimento['mes'],
            $vencimento['ano']
        );

        // ===============================================================
        // REFATORADO: NÃO cria mais lançamento individual
        // O lançamento será criado no pagamento da fatura (único por fatura)
        // ===============================================================

        // Criar item de fatura vinculado à fatura mensal (SEM lancamento_id)
        $valorCompra = $this->faturaSupportService->moneyString($data['valor'] ?? 0);

        $item = FaturaCartaoItem::create([
            'user_id' => $userId,
            'cartao_credito_id' => $cartao->id,
            'fatura_id' => $fatura->id,
            'lancamento_id' => null,                   // NÃO vincula a lançamento
            'descricao' => $data['descricao'],
            'valor' => $valorCompra,
            'data_compra' => $dataCompra,
            'data_vencimento' => $vencimento['data'],
            'categoria_id' => $data['categoria_id'] ?? null,
            'parcela_atual' => 1,
            'total_parcelas' => 1,
            'mes_referencia' => $competencia['mes'],
            'ano_referencia' => $competencia['ano'],
            'pago' => false,
        ]);

        LogService::info("[CARTAO] Item de fatura criado (à vista) - SEM lançamento individual", [
            'item_id' => $item->id,
            'fatura_id' => $fatura->id,
            'valor' => $valorCompra,
            'mes_referencia' => $competencia['mes'],
        ]);

        // Atualizar valor total da fatura
        $this->faturaSupportService->incrementarValorFatura($fatura, $valorCompra);

        // Atualizar limite disponível do cartão (reduz limite)
        $this->limitUpdaterService->atualizarLimiteCartao($cartao->id, $data['valor'], $userId, 'debito');

        return $item;
    }

    /**
     * Criar item de fatura recorrente (assinatura) no cartão
     * 
     * Cria o primeiro item com recorrente=true e recorrencia_pai_id=NULL.
     * Os próximos meses serão gerados automaticamente pelo cron (RecorrenciaCartaoService).
     * 
     * Exemplo: Spotify R$21,90/mês → cria 1 item agora, cron gera os próximos.
     */
    private function criarLancamentoRecorrente(int $userId, array $data, CartaoCredito $cartao, ?int $contaId): FaturaCartaoItem
    {
        $dataCompra = $data['data'] ?? date('Y-m-d');
        $vencimento = $this->billingDateService->calcularDataVencimento($dataCompra, $cartao->dia_vencimento, $cartao->dia_fechamento);
        $competencia = $this->billingDateService->calcularCompetencia($dataCompra, $cartao->dia_fechamento);
        $valorCompra = $this->faturaSupportService->moneyString($data['valor'] ?? 0);

        $fatura = $this->faturaSupportService->buscarOuCriarFatura(
            $userId,
            $cartao->id,
            $vencimento['mes'],
            $vencimento['ano']
        );

        // Validar e sanitizar recorrencia_fim
        $recorrenciaFim = $data['recorrencia_fim'] ?? null;
        if ($recorrenciaFim !== null) {
            if (!is_string($recorrenciaFim) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $recorrenciaFim)) {
                $recorrenciaFim = null; // Formato inválido — ignorar
                LogService::warning('[CARTAO] recorrencia_fim com formato inválido ignorado', [
                    'valor_recebido' => $data['recorrencia_fim'],
                    'user_id' => $userId,
                ]);
            }
        }

        // Validar frequência
        $recorrenciaFreq = $data['recorrencia_freq'] ?? 'mensal';
        $freqValidas = ['semanal', 'quinzenal', 'mensal', 'bimestral', 'trimestral', 'semestral', 'anual'];
        if (!in_array($recorrenciaFreq, $freqValidas, true)) {
            $recorrenciaFreq = 'mensal';
        }

        $item = FaturaCartaoItem::create([
            'user_id' => $userId,
            'cartao_credito_id' => $cartao->id,
            'fatura_id' => $fatura->id,
            'lancamento_id' => null,
            'descricao' => $data['descricao'],
            'valor' => $valorCompra,
            'data_compra' => $dataCompra,
            'data_vencimento' => $vencimento['data'],
            'categoria_id' => $data['categoria_id'] ?? null,
            'parcela_atual' => 1,
            'total_parcelas' => 1,
            'mes_referencia' => $competencia['mes'],
            'ano_referencia' => $competencia['ano'],
            'pago' => false,
            // Campos de recorrência
            'recorrente' => true,
            'recorrencia_freq' => $recorrenciaFreq,
            'recorrencia_fim' => $recorrenciaFim,
            'recorrencia_pai_id' => null, // Este é o item pai (original)
        ]);

        LogService::info("[CARTAO] Item de fatura recorrente criado (assinatura)", [
            'item_id' => $item->id,
            'fatura_id' => $fatura->id,
            'valor' => $valorCompra,
            'freq' => $data['recorrencia_freq'] ?? 'mensal',
            'descricao' => $data['descricao'],
        ]);

        // Atualizar valor total da fatura
        $this->faturaSupportService->incrementarValorFatura($fatura, $valorCompra);

        // Atualizar limite disponível do cartão
        $this->limitUpdaterService->atualizarLimiteCartao($cartao->id, $data['valor'], $userId, 'debito');

        return $item;
    }

    /**
     * Criar lançamento parcelado
     * REFATORADO: Agora cria APENAS FaturaCartaoItem para cada parcela, SEM criar Lancamento
     * 
     * O lançamento único será criado apenas quando a fatura for paga
     * (um lançamento por pagamento de fatura, não por parcela)
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
        $itemPaiId = null; // ID da primeira parcela para vincular as demais

        // Calcular competência da primeira parcela (mês da fatura que fechou)
        $competenciaBase = $this->billingDateService->calcularCompetencia($dataCompra, $cartao->dia_fechamento);

        // Criar cada parcela na fatura mensal correspondente
        for ($i = 1; $i <= $totalParcelas; $i++) {
            $vencimento = $this->billingDateService->calcularDataParcelaMes($dataCompra, $cartao->dia_vencimento, $cartao->dia_fechamento, $i - 1);
            $valorDessaParcela = ($i === $totalParcelas) ? $valorUltimaParcela : $valorParcela;

            // Buscar ou criar fatura do mês de VENCIMENTO da parcela
            $fatura = $this->faturaSupportService->buscarOuCriarFatura(
                $userId,
                $cartao->id,
                $vencimento['mes'],
                $vencimento['ano']
            );

            // ===============================================================
            // REFATORADO: NÃO cria mais lançamento individual por parcela
            // O lançamento será criado no pagamento da fatura (único por fatura)
            // ===============================================================
            $descricaoParcela = $data['descricao'] . " ({$i}/{$totalParcelas})";
            $dataVencimentoParcela = $vencimento['data'];

            // Calcular competência desta parcela (avança mês a partir da competência base)
            $mesCompetencia = $competenciaBase['mes'] + ($i - 1);
            $anoCompetencia = $competenciaBase['ano'];
            while ($mesCompetencia > 12) {
                $mesCompetencia -= 12;
                $anoCompetencia++;
            }

            $valorParcelaFormatado = $this->faturaSupportService->moneyString($valorDessaParcela);

            // Criar item de fatura (SEM lancamento_id)
            $item = FaturaCartaoItem::create([
                'user_id' => $userId,
                'cartao_credito_id' => $cartao->id,
                'fatura_id' => $fatura->id,
                'lancamento_id' => null,                   // NÃO vincula a lançamento
                'descricao' => $descricaoParcela,
                'valor' => $valorParcelaFormatado,
                'data_compra' => $dataCompra,
                'data_vencimento' => $dataVencimentoParcela,
                'categoria_id' => $data['categoria_id'] ?? null,
                'parcela_atual' => $i,
                'total_parcelas' => $totalParcelas,
                'mes_referencia' => $mesCompetencia,
                'ano_referencia' => $anoCompetencia,
                'pago' => false,
                'item_pai_id' => $itemPaiId,              // Vincula à primeira parcela
            ]);

            // Guardar o ID da primeira parcela para vincular as demais
            if ($i === 1) {
                $itemPaiId = $item->id;
            }

            LogService::info("[CARTAO] Item de fatura criado (parcela {$i}/{$totalParcelas}) - SEM lançamento individual", [
                'item_id' => $item->id,
                'fatura_id' => $fatura->id,
                'mes_ano_vencimento' => "{$vencimento['mes']}/{$vencimento['ano']}",
                'mes_referencia' => $mesCompetencia,
                'valor' => $valorDessaParcela,
                'item_pai_id' => $itemPaiId,
            ]);

            // Atualizar valor total da fatura
            $this->faturaSupportService->incrementarValorFatura($fatura, $valorParcelaFormatado);

            $itens[] = $item;

            // Atualizar limite do cartão apenas na primeira parcela (reduz limite total)
            if ($i === 1) {
                $this->limitUpdaterService->atualizarLimiteCartao($cartao->id, $valorTotal, $userId, 'debito');
            }
        }

        return $itens;
    }

    public function cancelarParcelamento(int $parcelamentoId, int $userId): array
    {
        return $this->postSaleService->cancelarParcelamento($parcelamentoId, $userId);
    }

    /**
     * Criar estorno de cartão de crédito
     * Estornos são itens de fatura com valor negativo (crédito na fatura)
     *
     * @param int $userId
     * @param array $data Dados do estorno incluindo cartao_credito_id
     * @return array ['success' => bool, 'item' => FaturaCartaoItem, 'message' => string]
     */
    public function criarEstornoCartao(int $userId, array $data): array
    {
        return $this->postSaleService->criarEstornoCartao($userId, $data);
    }
}
