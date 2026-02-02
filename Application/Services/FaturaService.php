<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Models\Fatura;
use Application\Models\FaturaCartaoItem;
use Application\Models\CartaoCredito;
use Application\Models\Lancamento;
use Application\Services\LogService;
use Exception;
use InvalidArgumentException;
use Illuminate\Database\Capsule\Manager as DB;
use DateTime;

/**
 * Service para gerenciar faturas de cartão de crédito
 */
class FaturaService
{
    private const STATUS_PENDENTE = 'pendente';
    private const STATUS_PARCIAL = 'parcial';
    private const STATUS_PAGA = 'paga';
    private const STATUS_CANCELADO = 'cancelado';

    private const VALOR_MINIMO = 0.01;
    private const PARCELAS_MINIMAS = 1;
    private const PARCELAS_MAXIMAS = 120;

    /**
     * Listar todas as faturas do usuário
     */
    public function listar(
        int $usuarioId,
        ?int $cartaoId = null,
        ?string $status = null,
        ?int $mes = null,
        ?int $ano = null
    ): array {
        try {
            $query = Fatura::where('user_id', $usuarioId)
                ->with(['cartaoCredito', 'itens']);

            if ($cartaoId) {
                $query->where('cartao_credito_id', $cartaoId);
            }

            // Filtrar por status diretamente no banco (mais performático)
            if ($status) {
                $query->where('status', $status);
            }

            // Filtrar por mês/ano de referência (busca faturas que tenham itens nesse mês)
            if ($mes && $ano) {
                $query->whereHas('itens', function ($q) use ($mes, $ano) {
                    $q->where('mes_referencia', $mes)
                        ->where('ano_referencia', $ano);
                });
            } elseif ($ano) {
                // Apenas ano: busca faturas com itens nesse ano
                $query->whereHas('itens', function ($q) use ($ano) {
                    $q->where('ano_referencia', $ano);
                });
            }

            $faturas = $query->orderBy('data_compra', 'desc')->get();

            return $faturas->map(function ($fatura) {
                return $this->formatarFaturaListagem($fatura);
            })->toArray();
        } catch (Exception $e) {
            LogService::error("Erro ao listar faturas", [
                'usuario_id' => $usuarioId,
                'error' => $e->getMessage()
            ]);
            throw new Exception("Erro ao listar faturas: " . $e->getMessage());
        }
    }

    /**
     * Obter anos disponíveis das faturas do usuário
     */
    public function obterAnosDisponiveis(int $usuarioId): array
    {
        try {
            // Buscar anos únicos dos itens de faturas do usuário
            $anos = FaturaCartaoItem::whereHas('fatura', function ($q) use ($usuarioId) {
                $q->where('user_id', $usuarioId);
            })
                ->select('ano_referencia')
                ->distinct()
                ->pluck('ano_referencia')
                ->filter()
                ->sort()
                ->values()
                ->toArray();

            // Se não houver anos, retorna o ano atual
            if (empty($anos)) {
                return [date('Y')];
            }

            return $anos;
        } catch (Exception $e) {
            LogService::error("Erro ao obter anos disponíveis", [
                'usuario_id' => $usuarioId,
                'error' => $e->getMessage()
            ]);
            return [date('Y')];
        }
    }

    /**
     * Buscar fatura por ID com todos os detalhes
     */
    public function buscar(int $faturaId, int $usuarioId): ?array
    {
        try {
            $fatura = Fatura::where('id', $faturaId)
                ->where('user_id', $usuarioId)
                ->with(['cartaoCredito', 'itens' => function ($query) {
                    $query->orderBy('mes_referencia')
                        ->orderBy('ano_referencia');
                }])
                ->first();

            if (!$fatura) {
                return null;
            }

            return $this->formatarFaturaDetalhada($fatura);
        } catch (Exception $e) {
            LogService::error("Erro ao buscar fatura", [
                'fatura_id' => $faturaId,
                'usuario_id' => $usuarioId,
                'error' => $e->getMessage()
            ]);
            throw new Exception("Erro ao buscar fatura: " . $e->getMessage());
        }
    }

    /**
     * Criar nova fatura com parcelas
     */
    public function criar(array $dados): ?int
    {
        DB::beginTransaction();

        try {
            // Validar dados
            $this->validarDadosCriacao($dados);

            // Buscar e validar cartão
            $cartao = $this->buscarCartaoValidado($dados['cartao_credito_id'], $dados['user_id']);

            // Criar fatura
            $fatura = $this->criarFatura($dados);

            // Criar itens (parcelas)
            $this->criarItensFatura($fatura, $dados, $cartao);

            DB::commit();

            LogService::info("Fatura criada com sucesso", [
                'fatura_id' => $fatura->id,
                'usuario_id' => $dados['user_id']
            ]);

            return $fatura->id;
        } catch (Exception $e) {
            DB::rollBack();

            LogService::error("Erro ao criar fatura", [
                'dados' => $dados,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Cancelar fatura (apenas se não tiver parcelas pagas)
     */
    public function cancelar(int $faturaId, int $usuarioId): bool
    {
        DB::beginTransaction();

        try {
            $fatura = Fatura::where('id', $faturaId)
                ->where('user_id', $usuarioId)
                ->with('itens')
                ->first();

            if (!$fatura) {
                throw new Exception("Fatura não encontrada");
            }

            // Verificar se tem parcelas pagas
            $itensPagos = $fatura->itens->where('pago', 1)->count();

            if ($itensPagos > 0) {
                throw new InvalidArgumentException(
                    "Não é possível cancelar fatura com {$itensPagos} parcela(s) já paga(s)"
                );
            }

            // Remover itens pendentes
            FaturaCartaoItem::where('fatura_id', $faturaId)
                ->where('pago', 0)
                ->delete();

            // Remover fatura
            $fatura->delete();

            DB::commit();

            LogService::info("Fatura cancelada", [
                'fatura_id' => $faturaId,
                'usuario_id' => $usuarioId
            ]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            LogService::error("Erro ao cancelar fatura", [
                'fatura_id' => $faturaId,
                'usuario_id' => $usuarioId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Marcar item da fatura como pago/pendente
     */
    public function toggleItemPago(int $faturaId, int $itemId, int $usuarioId, bool $pago): bool
    {
        DB::beginTransaction();

        try {
            $item = FaturaCartaoItem::where('id', $itemId)
                ->where('fatura_id', $faturaId)
                ->where('user_id', $usuarioId)
                ->with('cartaoCredito')
                ->first();

            if (!$item) {
                throw new Exception("Item não encontrado");
            }

            if ($pago) {
                $this->marcarItemComoPago($item, $usuarioId);
            } else {
                $this->desmarcarItemPago($item);
            }

            DB::commit();

            LogService::info("Item de fatura atualizado", [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'pago' => $pago
            ]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            LogService::error("Erro ao atualizar item da fatura", [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Atualizar item individual da fatura (descrição e valor)
     */
    public function atualizarItem(int $faturaId, int $itemId, int $usuarioId, array $dados): bool
    {
        DB::beginTransaction();

        try {
            $item = FaturaCartaoItem::where('id', $itemId)
                ->where('fatura_id', $faturaId)
                ->where('user_id', $usuarioId)
                ->with(['cartaoCredito', 'fatura'])
                ->first();

            if (!$item) {
                throw new Exception("Item não encontrado");
            }

            $valorAntigo = $item->valor;
            $diferencaValor = 0;

            // Atualizar descrição se fornecida
            if (isset($dados['descricao']) && !empty(trim($dados['descricao']))) {
                $item->descricao = trim($dados['descricao']);
            }

            // Atualizar valor se fornecido
            if (isset($dados['valor']) && is_numeric($dados['valor']) && $dados['valor'] > 0) {
                $novoValor = (float) $dados['valor'];
                $diferencaValor = $novoValor - $valorAntigo;
                $item->valor = $novoValor;
            }

            $item->save();

            // Atualizar valor total da fatura
            if ($diferencaValor !== 0 && $item->fatura) {
                $item->fatura->valor_total = FaturaCartaoItem::where('fatura_id', $faturaId)->sum('valor');
                $item->fatura->save();
            }

            // Atualizar limite do cartão se valor mudou
            if ($diferencaValor !== 0 && $item->cartaoCredito) {
                $item->cartaoCredito->atualizarLimiteDisponivel();
            }

            DB::commit();

            LogService::info("Item de fatura atualizado", [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'usuario_id' => $usuarioId,
                'descricao' => $dados['descricao'] ?? null,
                'valor_antigo' => $valorAntigo,
                'valor_novo' => $dados['valor'] ?? null
            ]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            LogService::error("Erro ao atualizar item da fatura", [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Excluir item individual da fatura
     */
    public function excluirItem(int $faturaId, int $itemId, int $usuarioId): bool
    {
        DB::beginTransaction();

        try {
            $item = FaturaCartaoItem::where('id', $itemId)
                ->where('fatura_id', $faturaId)
                ->where('user_id', $usuarioId)
                ->with('cartaoCredito')
                ->first();

            if (!$item) {
                throw new Exception("Item não encontrado");
            }

            // Não permitir excluir item já pago
            if ($item->pago) {
                throw new InvalidArgumentException(
                    "Não é possível excluir um item já pago. Desfaça o pagamento primeiro."
                );
            }

            $cartao = $item->cartaoCredito;
            $faturaId = $item->fatura_id;

            // Excluir o item
            $item->delete();

            // Atualizar limite do cartão (liberar limite)
            if ($cartao) {
                $cartao->atualizarLimiteDisponivel();
            }

            // Atualizar status da fatura
            if ($faturaId) {
                $fatura = Fatura::find($faturaId);
                if ($fatura) {
                    // Verificar se ainda tem itens
                    $itensRestantes = FaturaCartaoItem::where('fatura_id', $faturaId)->count();

                    if ($itensRestantes === 0) {
                        // Se não tem mais itens, excluir a fatura também
                        $fatura->delete();
                    } else {
                        // Atualizar valor total e status
                        $novoTotal = FaturaCartaoItem::where('fatura_id', $faturaId)->sum('valor');
                        $fatura->valor_total = $novoTotal;
                        $fatura->save();
                        $fatura->atualizarStatus();
                    }
                }
            }

            DB::commit();

            LogService::info("Item de fatura excluído", [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'usuario_id' => $usuarioId
            ]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            LogService::error("Erro ao excluir item da fatura", [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    // ============================================================================
    // MÉTODOS PRIVADOS - VALIDAÇÃO
    // ============================================================================

    /**
     * Validar dados de criação de fatura
     */
    private function validarDadosCriacao(array $dados): void
    {
        $erros = [];

        if (empty($dados['user_id'])) {
            $erros[] = "Usuário não informado";
        }

        if (empty($dados['cartao_credito_id'])) {
            $erros[] = "Cartão não informado";
        }

        if (empty($dados['descricao']) || strlen(trim($dados['descricao'])) < 3) {
            $erros[] = "Descrição inválida (mínimo 3 caracteres)";
        }

        if (empty($dados['valor_total']) || $dados['valor_total'] < self::VALOR_MINIMO) {
            $erros[] = sprintf("Valor inválido (mínimo R$ %.2f)", self::VALOR_MINIMO);
        }

        if (
            empty($dados['numero_parcelas']) ||
            $dados['numero_parcelas'] < self::PARCELAS_MINIMAS ||
            $dados['numero_parcelas'] > self::PARCELAS_MAXIMAS
        ) {
            $erros[] = sprintf(
                "Número de parcelas inválido (entre %d e %d)",
                self::PARCELAS_MINIMAS,
                self::PARCELAS_MAXIMAS
            );
        }

        if (empty($dados['data_compra']) || !$this->validarData($dados['data_compra'])) {
            $erros[] = "Data da compra inválida";
        }

        if (!empty($erros)) {
            throw new InvalidArgumentException(implode("; ", $erros));
        }
    }

    /**
     * Validar formato de data (Y-m-d)
     */
    private function validarData(string $data): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $data);
        return $d && $d->format('Y-m-d') === $data;
    }

    /**
     * Buscar cartão e validar se pertence ao usuário
     */
    private function buscarCartaoValidado(int $cartaoId, int $usuarioId): CartaoCredito
    {
        $cartao = CartaoCredito::where('id', $cartaoId)
            ->where('user_id', $usuarioId)
            ->first();

        if (!$cartao) {
            throw new InvalidArgumentException("Cartão não encontrado ou não pertence ao usuário");
        }

        if (empty($cartao->dia_vencimento) || empty($cartao->dia_fechamento)) {
            throw new InvalidArgumentException("Cartão sem configuração de vencimento/fechamento");
        }

        return $cartao;
    }

    // ============================================================================
    // MÉTODOS PRIVADOS - CRIAÇÃO
    // ============================================================================

    /**
     * Criar registro da fatura
     */
    private function criarFatura(array $dados): Fatura
    {
        return Fatura::create([
            'user_id' => $dados['user_id'],
            'cartao_credito_id' => $dados['cartao_credito_id'],
            'descricao' => trim($dados['descricao']),
            'valor_total' => round((float) $dados['valor_total'], 2),
            'numero_parcelas' => (int) $dados['numero_parcelas'],
            'data_compra' => $dados['data_compra'],
            'status' => Fatura::STATUS_PENDENTE,
        ]);
    }

    /**
     * Criar itens (parcelas) da fatura
     */
    private function criarItensFatura(Fatura $fatura, array $dados, CartaoCredito $cartao): void
    {
        $valorTotal = round((float) $dados['valor_total'], 2);
        $numeroParcelas = (int) $dados['numero_parcelas'];

        // Calcular valores das parcelas
        $valoresParcelas = $this->calcularValoresParcelas($valorTotal, $numeroParcelas);

        // Parsear data de compra
        $dataCompra = new DateTime($dados['data_compra']);
        $diaCompra = (int) $dataCompra->format('d');
        $mesCompra = (int) $dataCompra->format('m');
        $anoCompra = (int) $dataCompra->format('Y');

        // Criar cada parcela
        for ($i = 1; $i <= $numeroParcelas; $i++) {
            $vencimento = $this->calcularDataVencimento(
                $diaCompra,
                $mesCompra,
                $anoCompra,
                $i,
                $cartao->dia_vencimento,
                $cartao->dia_fechamento
            );

            FaturaCartaoItem::create([
                'user_id' => $dados['user_id'],
                'cartao_credito_id' => $dados['cartao_credito_id'],
                'fatura_id' => $fatura->id,
                'descricao' => trim($dados['descricao']),
                'valor' => $valoresParcelas[$i - 1],
                'data_compra' => $dados['data_compra'],
                'data_vencimento' => $vencimento['data'],
                'categoria_id' => $dados['categoria_id'] ?? null,
                'numero_parcela' => $i,
                'total_parcelas' => $numeroParcelas,
                'mes_referencia' => $vencimento['mes'],
                'ano_referencia' => $vencimento['ano'],
                'pago' => false,
            ]);
        }
    }

    /**
     * Calcular valores das parcelas com ajuste de arredondamento
     */
    private function calcularValoresParcelas(float $valorTotal, int $numeroParcelas): array
    {
        $valorParcela = round($valorTotal / $numeroParcelas, 2);
        $valores = array_fill(0, $numeroParcelas, $valorParcela);

        // Ajustar última parcela para cobrir diferença de arredondamento
        $soma = $valorParcela * ($numeroParcelas - 1);
        $valores[$numeroParcelas - 1] = round($valorTotal - $soma, 2);

        // Garantir que a soma bate exatamente
        $somaTotal = array_sum($valores);
        if (abs($somaTotal - $valorTotal) > 0.01) {
            throw new Exception("Erro no cálculo das parcelas");
        }

        return $valores;
    }

    /**
     * Calcular data de vencimento da parcela
     */
    private function calcularDataVencimento(
        int $diaCompra,
        int $mesCompra,
        int $anoCompra,
        int $numeroParcela,
        int $diaVencimento,
        int $diaFechamento
    ): array {
        // Se a compra foi feita após o fechamento, vai para o próximo mês
        $mesReferencia = $mesCompra;
        $anoReferencia = $anoCompra;

        if ($diaCompra >= $diaFechamento) {
            $mesReferencia++;
            if ($mesReferencia > 12) {
                $mesReferencia = 1;
                $anoReferencia++;
            }
        }

        // Adicionar os meses da parcela
        $mesVencimento = $mesReferencia + ($numeroParcela - 1);
        $anoVencimento = $anoReferencia;

        while ($mesVencimento > 12) {
            $mesVencimento -= 12;
            $anoVencimento++;
        }

        // Ajustar dia se não existir no mês
        $ultimoDiaMes = (int) date('t', mktime(0, 0, 0, $mesVencimento, 1, $anoVencimento));
        $diaFinal = min($diaVencimento, $ultimoDiaMes);

        return [
            'data' => sprintf('%04d-%02d-%02d', $anoVencimento, $mesVencimento, $diaFinal),
            'mes' => $mesVencimento,
            'ano' => $anoVencimento,
        ];
    }

    // ============================================================================
    // MÉTODOS PRIVADOS - PAGAMENTO
    // ============================================================================

    /**
     * Marcar item como pago e ATUALIZAR lançamento existente
     * 
     * CORREÇÃO FINAL: Não cria mais lançamentos aqui!
     * - Lançamentos já foram criados no momento da COMPRA (CartaoCreditoLancamentoService)
     * - Aqui apenas: atualiza lançamentos existentes (pago=true, afeta_caixa=true)
     */
    private function marcarItemComoPago(FaturaCartaoItem $item, int $usuarioId): void
    {
        // Se já está pago, não fazer nada
        if ($item->pago) {
            return;
        }

        // Verificar se cartão tem conta vinculada
        if (!$item->cartaoCredito) {
            throw new InvalidArgumentException("Cartão não encontrado");
        }

        if (!$item->cartaoCredito->conta_id) {
            throw new InvalidArgumentException(
                "Cartão '{$item->cartaoCredito->nome}' não tem conta vinculada"
            );
        }

        $dataPagamento = now()->format('Y-m-d');

        // Verificar se o item já tem lançamento vinculado
        if ($item->lancamento_id) {
            // ATUALIZAR lançamento existente (não criar novo!)
            $lancamento = Lancamento::find($item->lancamento_id);
            if ($lancamento) {
                $lancamento->update([
                    'pago' => true,
                    'data_pagamento' => $dataPagamento,
                    'afeta_caixa' => true,  // Agora sim afeta o saldo!
                    'observacao' => sprintf(
                        'Pagamento de fatura - %s (Parcela %d/%d) - pago em %s',
                        $item->cartaoCredito->nome ?? $item->cartaoCredito->bandeira ?? 'Cartão',
                        $item->parcela_atual ?? 1,
                        $item->total_parcelas ?? 1,
                        date('d/m/Y', strtotime($dataPagamento))
                    ),
                ]);
            }
        } else {
            // Fallback: criar lançamento se não existir (dados antigos migrados)
            $valorFormatado = round((float) $item->valor, 2);
            $dataCompra = $item->data_compra ? $item->data_compra->format('Y-m-d') : $dataPagamento;

            $lancamento = Lancamento::create([
                'user_id' => $usuarioId,
                'tipo' => 'despesa',
                'valor' => $valorFormatado,
                'data' => $dataCompra,                     // Data da compra original
                'data_competencia' => $dataCompra,         // Competência: mês da compra
                'descricao' => $item->descricao ?: 'Pagamento de fatura',
                'categoria_id' => $item->categoria_id,
                'conta_id' => $item->cartaoCredito->conta_id,
                'cartao_credito_id' => $item->cartao_credito_id,
                'pago' => true,
                'data_pagamento' => $dataPagamento,
                'observacao' => sprintf(
                    'Pagamento de fatura - %s (Parcela %d/%d) (migrado)',
                    $item->cartaoCredito->nome ?? $item->cartaoCredito->bandeira ?? 'Cartão',
                    $item->parcela_atual ?? 1,
                    $item->total_parcelas ?? 1
                ),
                // Campos de controle
                'afeta_competencia' => true,
                'afeta_caixa' => true,
                'origem_tipo' => 'cartao_credito',
            ]);

            $item->lancamento_id = $lancamento->id;
        }

        $item->pago = true;
        $item->data_pagamento = now();
        $item->save();

        // Atualizar limite do cartão de crédito (liberar limite)
        if ($item->cartaoCredito) {
            $item->cartaoCredito->atualizarLimiteDisponivel();
        }

        // Atualizar status da fatura
        if ($item->fatura_id) {
            $fatura = Fatura::find($item->fatura_id);
            if ($fatura) {
                $fatura->atualizarStatus();
            }
        }
    }

    /**
     * Desmarcar item como pago e reverter lançamento
     */
    private function desmarcarItemPago(FaturaCartaoItem $item): void
    {
        // Se já está pendente, não fazer nada
        if (!$item->pago) {
            return;
        }

        // Reverter lançamento se existir (não deletar!)
        if ($item->lancamento_id) {
            $lancamento = Lancamento::find($item->lancamento_id);
            if ($lancamento) {
                // Marcar como não pago e remover efeito no caixa
                $lancamento->update([
                    'pago' => false,
                    'data_pagamento' => null,
                    'afeta_caixa' => false,  // Não afeta mais o saldo!
                    'observacao' => sprintf(
                        'Pagamento revertido - %s (Parcela %d/%d)',
                        $item->descricao ?? 'Item de fatura',
                        $item->parcela_atual ?? 1,
                        $item->total_parcelas ?? 1
                    ),
                ]);
            }
        }

        $item->pago = false;
        $item->data_pagamento = null;
        $item->save();

        // Atualizar limite do cartão de crédito (consumir limite novamente)
        if ($item->cartaoCredito) {
            $item->cartaoCredito->atualizarLimiteDisponivel();
        }

        // Atualizar status da fatura
        if ($item->fatura_id) {
            $fatura = Fatura::find($item->fatura_id);
            if ($fatura) {
                $fatura->atualizarStatus();
            }
        }
    }

    // ============================================================================
    // MÉTODOS PRIVADOS - FORMATAÇÃO
    // ============================================================================

    /**
     * Filtrar faturas por status
     */
    private function filtrarPorStatus($faturas, string $status)
    {
        return $faturas->filter(function ($fatura) use ($status) {
            $progresso = $fatura->progresso;

            switch ($status) {
                case self::STATUS_PENDENTE:
                case 'ativo':
                    return $progresso === 0;

                case self::STATUS_PARCIAL:
                    return $progresso > 0 && $progresso < 100;

                case self::STATUS_PAGA:
                case 'concluido':
                    return $progresso >= 100;

                case self::STATUS_CANCELADO:
                    return false; // Faturas canceladas são deletadas

                default:
                    return true;
            }
        });
    }

    /**
     * Formatar fatura para listagem
     */
    private function formatarFaturaListagem(Fatura $fatura): array
    {
        $itensPagos = $fatura->itens->where('pago', 1)->count();
        $totalItens = $fatura->itens->count();
        $progresso = $fatura->progresso;

        // Calcular valor pendente (apenas itens não pagos)
        // Nota: usa 'valor' que é o campo real na tabela faturas_cartao_itens
        $valorPendente = $fatura->itens->where('pago', 0)->sum('valor');

        // Próxima parcela pendente (por mês/ano)
        $primeiroItemPendente = $fatura->itens->where('pago', 0)
            ->sortBy(function ($item) {
                return $item->ano_referencia * 100 + $item->mes_referencia;
            })
            ->first();

        // Obter meses únicos de referência dos itens desta fatura
        $mesesReferencia = $fatura->itens
            ->map(function ($item) {
                return [
                    'mes' => $item->mes_referencia,
                    'ano' => $item->ano_referencia,
                ];
            })
            ->unique(function ($item) {
                return $item['ano'] . '-' . $item['mes'];
            })
            ->sortBy(function ($item) {
                return $item['ano'] * 100 + $item['mes'];
            })
            ->values()
            ->toArray();

        // Primeiro mês de referência (para exibição principal)
        $primeiroMesRef = $fatura->itens->sortBy(function ($item) {
            return $item->ano_referencia * 100 + $item->mes_referencia;
        })->first();

        return [
            'id' => $fatura->id,
            'descricao' => $fatura->descricao,
            'valor_total' => round($valorPendente, 2),
            'numero_parcelas' => $fatura->numero_parcelas,
            'valor_parcela' => $fatura->valor_parcela,
            'data_compra' => $fatura->data_compra->format('Y-m-d'),
            // Mês/ano de referência (primeiro mês onde aparece)
            'mes_referencia' => $primeiroMesRef ? $primeiroMesRef->mes_referencia : null,
            'ano_referencia' => $primeiroMesRef ? $primeiroMesRef->ano_referencia : null,
            // Lista de todos os meses onde este parcelamento aparece
            'meses_referencia' => $mesesReferencia,
            'proxima_parcela' => $primeiroItemPendente ? [
                'numero' => $primeiroItemPendente->parcela_atual,
                'mes' => $primeiroItemPendente->mes_referencia,
                'ano' => $primeiroItemPendente->ano_referencia,
            ] : null,
            'cartao' => $this->formatarCartao($fatura->cartaoCredito),
            'parcelas_pagas' => $itensPagos,
            'parcelas_pendentes' => $totalItens - $itensPagos,
            'progresso' => round($progresso, 2),
            'status' => $fatura->status ?? $this->determinarStatus($progresso),
        ];
    }

    /**
     * Formatar fatura detalhada
     */
    private function formatarFaturaDetalhada(Fatura $fatura): array
    {
        $parcelas = $fatura->itens->map(function ($item) use ($fatura) {
            return [
                'id' => $item->id,
                'numero_parcela' => $item->parcela_atual,
                'total_parcelas' => $item->total_parcelas ?? $fatura->numero_parcelas,
                'valor_parcela' => round((float) $item->valor, 2),
                'descricao' => $item->descricao ?? $fatura->descricao,
                'mes_referencia' => $item->mes_referencia,
                'ano_referencia' => $item->ano_referencia,
                'pago' => (bool) $item->pago,
                'data_pagamento' => $item->data_pagamento?->format('Y-m-d'),
            ];
        })->values()->toArray();

        $itensPagos = $fatura->itens->where('pago', 1)->count();
        $totalItens = $fatura->itens->count();
        // Nota: usa 'valor' que é o campo real na tabela faturas_cartao_itens
        $valorPendente = $fatura->itens->where('pago', 0)->sum('valor');
        $progresso = $fatura->progresso;

        return [
            'id' => $fatura->id,
            'descricao' => $fatura->descricao,
            'valor_total' => round((float) $valorPendente, 2),
            'valor_original' => round((float) $fatura->valor_total, 2),
            'numero_parcelas' => $fatura->numero_parcelas,
            'data_compra' => $fatura->data_compra->format('Y-m-d'),
            'cartao' => $this->formatarCartao($fatura->cartaoCredito),
            'parcelas' => $parcelas,
            'parcelas_pagas' => $itensPagos,
            'parcelas_pendentes' => $totalItens - $itensPagos,
            'progresso' => round($progresso, 2),
            'status' => $fatura->status ?? $this->determinarStatus($progresso),
        ];
    }

    /**
     * Formatar dados do cartão
     */
    private function formatarCartao(CartaoCredito $cartao): array
    {
        return [
            'id' => $cartao->id,
            'nome' => $cartao->nome_cartao ?? $cartao->bandeira,
            'bandeira' => $cartao->bandeira,
            'ultimos_digitos' => $cartao->ultimos_digitos ?? '',
        ];
    }

    /**
     * Determinar status baseado no progresso
     */
    private function determinarStatus(float $progresso): string
    {
        if ($progresso === 0.0) {
            return self::STATUS_PENDENTE;
        } elseif ($progresso >= 100.0) {
            return self::STATUS_PAGA;
        } else {
            return self::STATUS_PARCIAL;
        }
    }
}
