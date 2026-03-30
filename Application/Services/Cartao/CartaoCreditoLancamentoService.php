<?php

namespace Application\Services\Cartao;

use Application\Models\Lancamento;
use Application\Models\CartaoCredito;
use Application\Models\Parcelamento;
use Application\Models\FaturaCartaoItem;
use Application\Models\Fatura;
use Illuminate\Database\Capsule\Manager as DB;
use Exception;
use Application\Services\Infrastructure\LogService;

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
        $vencimento = $this->calcularDataVencimento($dataCompra, $cartao->dia_vencimento, $cartao->dia_fechamento);

        // Calcular competência (mês da fatura que fechou, não do vencimento)
        $competencia = $this->calcularCompetencia($dataCompra, $cartao->dia_fechamento);

        // Buscar ou criar fatura do mês de VENCIMENTO (onde a parcela será cobrada)
        $fatura = $this->buscarOuCriarFatura(
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
        $valorCompra = $this->moneyString($data['valor'] ?? 0);

        $valorCompra = $this->moneyString($data['valor'] ?? 0);

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
        $this->incrementarValorFatura($fatura, $valorCompra);

        // Atualizar limite disponível do cartão (reduz limite)
        $this->atualizarLimiteCartao($cartao->id, $data['valor'], $userId, 'debito');

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
        $vencimento = $this->calcularDataVencimento($dataCompra, $cartao->dia_vencimento, $cartao->dia_fechamento);
        $competencia = $this->calcularCompetencia($dataCompra, $cartao->dia_fechamento);
        $valorCompra = $this->moneyString($data['valor'] ?? 0);

        $fatura = $this->buscarOuCriarFatura(
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
        $this->incrementarValorFatura($fatura, $valorCompra);

        // Atualizar limite disponível do cartão
        $this->atualizarLimiteCartao($cartao->id, $data['valor'], $userId, 'debito');

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
        $competenciaBase = $this->calcularCompetencia($dataCompra, $cartao->dia_fechamento);

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

            $valorParcelaFormatado = $this->moneyString($valorDessaParcela);

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
            $this->incrementarValorFatura($fatura, $valorParcelaFormatado);

            $itens[] = $item;

            // Atualizar limite do cartão apenas na primeira parcela (reduz limite total)
            if ($i === 1) {
                $this->atualizarLimiteCartao($cartao->id, $valorTotal, $userId, 'debito');
            }
        }

        return $itens;
    }

    /**
     * Calcular mês/ano de competência (mês da compra)
     * A competência é SEMPRE o mês em que a compra foi feita.
     * O dia de fechamento afeta apenas o VENCIMENTO, não a competência.
     * 
     * @return array ['mes' => int, 'ano' => int]
     */
    private function calcularCompetencia(string $dataCompra, ?int $diaFechamento): array
    {
        $dataObj = new \DateTime($dataCompra);

        // Competência = mês da compra (sempre)
        return [
            'mes' => (int)$dataObj->format('n'),
            'ano' => (int)$dataObj->format('Y'),
        ];
    }

    /**
     * Calcular data de vencimento da fatura
     * 
     * Lógica:
     * 1. Determinar mês de fechamento:
     *    - Se comprou ANTES do dia de fechamento: fatura fecha no mês da compra
     *    - Se comprou NO DIA ou DEPOIS do fechamento: fatura fecha no mês seguinte
     * 2. Determinar mês de vencimento:
     *    - Se dia_vencimento > dia_fechamento: vencimento no MESMO mês do fechamento
     *    - Se dia_vencimento <= dia_fechamento: vencimento no mês SEGUINTE ao fechamento
     * 
     * Exemplo: fecha=2, vence=10
     *   Compra em 01/02 → fecha em 02/02, vence em 10/02
     *   Compra em 02/02 → fecha em 02/03, vence em 10/03
     * 
     * Exemplo: fecha=25, vence=5
     *   Compra em 24/01 → fecha em 25/01, vence em 05/02
     *   Compra em 25/01 → fecha em 25/02, vence em 05/03
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

        // PASSO 1: Determinar mês de fechamento da fatura
        // Se comprou NO DIA do fechamento ou depois, entra na fatura que fecha no próximo mês
        if ($diaCompra >= $diaFechamento) {
            $mesFechamento = $mesAtual + 1;
            $anoFechamento = $anoAtual;
            if ($mesFechamento > 12) {
                $mesFechamento -= 12;
                $anoFechamento++;
            }
        } else {
            $mesFechamento = $mesAtual;
            $anoFechamento = $anoAtual;
        }

        // PASSO 2: Determinar mês de vencimento
        // Se dia_vencimento > dia_fechamento: vencimento no MESMO mês do fechamento
        // Se dia_vencimento <= dia_fechamento: vencimento no mês SEGUINTE ao fechamento
        if ($diaVencimento > $diaFechamento) {
            $mesVencimento = $mesFechamento;
            $anoVencimento = $anoFechamento;
        } else {
            $mesVencimento = $mesFechamento + 1;
            $anoVencimento = $anoFechamento;
            if ($mesVencimento > 12) {
                $mesVencimento -= 12;
                $anoVencimento++;
            }
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
    private function atualizarLimiteCartao(int $cartaoId, float $valor, int $userId, string $operacao): void
    {
        $cartao = CartaoCredito::forUser($userId)->find($cartaoId);
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
            $totalCanceladas = 0;
            $valorDevolver = 0;

            // Buscar parcelas futuras em Lancamento (parcelamentos sem cartão)
            $parcelasFuturasLanc = Lancamento::where('parcelamento_id', $parcelamento->id)
                ->where('data', '>', $hoje)
                ->get();

            if ($parcelasFuturasLanc->isNotEmpty()) {
                $valorDevolver += $parcelasFuturasLanc->sum('valor');
                foreach ($parcelasFuturasLanc as $parcela) {
                    $parcela->cancelado_em = now();
                    $parcela->save();
                }
                $totalCanceladas += $parcelasFuturasLanc->count();
            }

            // Buscar parcelas futuras em FaturaCartaoItem (parcelamentos com cartão de crédito)
            $parcelasFuturasItem = FaturaCartaoItem::where('user_id', $userId)
                ->where('cartao_credito_id', $parcelamento->cartao_credito_id)
                ->where('eh_parcelado', true)
                ->where('descricao', 'LIKE', '%' . ($parcelamento->descricao ?? '') . '%')
                ->where('item_pai_id', $parcelamento->id)
                ->whereNull('cancelado_em')
                ->where(function ($q) use ($hoje) {
                    $q->where('data_compra', '>', $hoje)
                        ->orWhere('data_vencimento', '>', $hoje);
                })
                ->where('pago', false)
                ->get();

            // Fallback: buscar por parcelamento_id (caso exista coluna de referência)
            if ($parcelasFuturasItem->isEmpty() && $parcelamento->cartao_credito_id) {
                $parcelasFuturasItem = FaturaCartaoItem::where('user_id', $userId)
                    ->where('cartao_credito_id', $parcelamento->cartao_credito_id)
                    ->where('eh_parcelado', true)
                    ->whereNull('cancelado_em')
                    ->where('pago', false)
                    ->whereRaw("parcela_atual > 1")
                    ->where('total_parcelas', $parcelamento->numero_parcelas ?? 0)
                    ->get();
            }

            if ($parcelasFuturasItem->isNotEmpty()) {
                $valorDevolver += $parcelasFuturasItem->sum('valor');
                foreach ($parcelasFuturasItem as $item) {
                    $item->cancelado_em = now();
                    $item->save();
                }
                $totalCanceladas += $parcelasFuturasItem->count();
            }

            if ($totalCanceladas === 0) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Não existem parcelas futuras para cancelar'
                ];
            }

            // Devolver limite do cartão (uma vez só)
            if ($parcelamento->cartao_credito_id && $valorDevolver > 0) {
                $this->atualizarLimiteCartao(
                    $parcelamento->cartao_credito_id,
                    $valorDevolver,
                    $userId,
                    'credito'
                );
            }

            // Atualizar status do parcelamento
            $parcelamento->status = 'parcial';
            $parcelamento->save();

            DB::commit();

            return [
                'success' => true,
                'parcelamento_id' => $parcelamento->id,
                'parcelas_canceladas' => $totalCanceladas,
                'valor_devolvido' => $valorDevolver,
                'message' => 'Parcelamento cancelado parcialmente com sucesso'
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            \Application\Services\Infrastructure\LogService::safeErrorLog('[CARTAO] Erro ao cancelar parcelamento: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao cancelar parcelamento'
            ];
        }
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
        try {
            DB::beginTransaction();

            $cartaoId = $data['cartao_credito_id'] ?? null;
            $valorEstorno = abs((float)($data['valor'] ?? 0)); // Garantir positivo
            $descricao = $data['descricao'] ?? 'Estorno';
            $dataEstorno = $data['data'] ?? date('Y-m-d');
            $categoriaId = $data['categoria_id'] ?? null;

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

            // Determinar mês/ano de referência (fatura onde o estorno será creditado)
            // Primeiro verificar se foi passado pelo usuário
            $mesReferencia = $data['mes_referencia'] ?? null;
            $anoReferencia = $data['ano_referencia'] ?? null;

            // Se não foi passado, calcular pela data
            if (!$mesReferencia || !$anoReferencia) {
                $dataObj = new \DateTime($dataEstorno);
                $diaFechamento = (int)$cartao->dia_fechamento;
                $diaCompra = (int)$dataObj->format('d');
                $mesCompra = (int)$dataObj->format('m');
                $anoCompra = (int)$dataObj->format('Y');

                // Mesma lógica de fechamento de fatura (>= para consistência)
                if ($diaCompra >= $diaFechamento) {
                    $mesReferencia = $mesCompra + 1;
                    $anoReferencia = $anoCompra;
                    if ($mesReferencia > 12) {
                        $mesReferencia = 1;
                        $anoReferencia++;
                    }
                } else {
                    $mesReferencia = $mesCompra;
                    $anoReferencia = $anoCompra;
                }
            }

            // Calcular data de vencimento baseado na relação entre dia_vencimento e dia_fechamento
            $diaVencimento = (int)$cartao->dia_vencimento;
            if ($diaVencimento > $diaFechamento) {
                // Vencimento no MESMO mês do fechamento
                $mesVencimento = $mesReferencia;
                $anoVencimento = $anoReferencia;
            } else {
                // Vencimento no mês SEGUINTE ao fechamento
                $mesVencimento = $mesReferencia + 1;
                $anoVencimento = $anoReferencia;
                if ($mesVencimento > 12) {
                    $mesVencimento = 1;
                    $anoVencimento++;
                }
            }
            $ultimoDiaMes = (int)date('t', mktime(0, 0, 0, $mesVencimento, 1, $anoVencimento));
            $dataVencimento = sprintf('%04d-%02d-%02d', $anoVencimento, $mesVencimento, min($diaVencimento, $ultimoDiaMes));

            // Buscar ou criar fatura do mês de vencimento
            $fatura = $this->buscarOuCriarFatura($userId, $cartaoId, $mesVencimento, $anoVencimento);

            $valorEstornoFormatado = $this->moneyString(-$valorEstorno);

            // Criar item de fatura como ESTORNO (valor negativo para abater da fatura)
            $item = FaturaCartaoItem::create([
                'user_id' => $userId,
                'cartao_credito_id' => $cartaoId,
                'fatura_id' => $fatura->id,
                'descricao' => '↩️ ' . $descricao,
                'valor' => $valorEstornoFormatado, // NEGATIVO para abater da fatura
                'tipo' => 'estorno',
                'data_compra' => $dataEstorno,
                'data_vencimento' => $dataVencimento,
                'mes_referencia' => $mesReferencia,
                'ano_referencia' => $anoReferencia,
                'categoria_id' => $categoriaId,
                'eh_parcelado' => false,
                'parcela_atual' => 1,
                'total_parcelas' => 1,
                'pago' => true, // Estorno já está creditado
                'data_pagamento' => $dataEstorno,
            ]);

            // Atualizar valor total da fatura (subtrair o estorno)
            $novoTotal = max(0, (float) $fatura->valor_total - $valorEstorno);
            $fatura->update(['valor_total' => $this->moneyString($novoTotal)]);

            // Atualizar limite disponível do cartão
            $cartao->atualizarLimiteDisponivel();

            DB::commit();

            LogService::info('[CARTAO] Estorno criado', [
                'item_id' => $item->id,
                'cartao_id' => $cartaoId,
                'valor' => -$valorEstorno,
                'mes_referencia' => $mesReferencia,
                'ano_referencia' => $anoReferencia,
                'novo_limite_disponivel' => $cartao->limite_disponivel,
            ]);

            return [
                'success' => true,
                'item' => $item,
                'message' => 'Estorno adicionado à fatura do cartão',
            ];
        } catch (Exception $e) {
            DB::rollBack();
            LogService::error("Erro ao criar estorno de cartão", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao criar estorno: ' . $e->getMessage(),
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
                'valor_total' => $this->moneyString(0),
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

    private function incrementarValorFatura(Fatura $fatura, float|int|string $valor): void
    {
        $novoTotal = (float) $fatura->valor_total + (float) $valor;
        $fatura->valor_total = $this->moneyString($novoTotal);
        $fatura->save();
    }

    private function moneyString(float|int|string|null $valor): string
    {
        return number_format((float) ($valor ?? 0), 2, '.', '');
    }
}
