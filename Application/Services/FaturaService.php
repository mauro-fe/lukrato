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

            // Filtrar por mês/ano de referência dos itens
            // Mostra faturas que tenham itens nesse mês/ano de competência
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
     * Retorna todos os anos onde há itens de fatura
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
     * Buscar item individual da fatura
     */
    public function buscarItem(int $itemId, int $usuarioId): ?array
    {
        try {
            $item = FaturaCartaoItem::where('id', $itemId)
                ->where('user_id', $usuarioId)
                ->first();

            if (!$item) {
                return null;
            }

            return [
                'id' => $item->id,
                'fatura_id' => $item->fatura_id,
                'descricao' => $item->descricao,
                'valor' => $item->valor,
                'pago' => (bool) $item->pago,
                'parcela_atual' => $item->parcela_atual,
                'total_parcelas' => $item->total_parcelas,
                'mes_referencia' => $item->mes_referencia,
                'ano_referencia' => $item->ano_referencia,
            ];
        } catch (Exception $e) {
            LogService::error("Erro ao buscar item", [
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Excluir item individual da fatura
     */
    public function excluirItem(int $faturaId, int $itemId, int $usuarioId): array
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

            return ['success' => true, 'message' => 'Item excluído com sucesso'];
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        } catch (Exception $e) {
            DB::rollBack();

            LogService::error("Erro ao excluir item da fatura", [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'message' => 'Erro ao excluir item'];
        }
    }

    /**
     * Excluir parcelamento completo (todas as parcelas de uma compra)
     */
    public function excluirParcelamento(int $itemId, int $usuarioId): array
    {
        DB::beginTransaction();

        try {
            LogService::info("Iniciando exclusão de parcelamento", [
                'item_id' => $itemId,
                'usuario_id' => $usuarioId
            ]);

            // Buscar o item para descobrir a fatura relacionada
            $item = FaturaCartaoItem::where('id', $itemId)
                ->where('user_id', $usuarioId)
                ->with('cartaoCredito')
                ->first();

            if (!$item) {
                LogService::warning("Item não encontrado para exclusão de parcelamento", [
                    'item_id' => $itemId,
                    'usuario_id' => $usuarioId
                ]);
                return ['success' => false, 'message' => 'Item não encontrado'];
            }

            LogService::info("Item encontrado para exclusão", [
                'item_id' => $itemId,
                'item_pai_id' => $item->item_pai_id,
                'total_parcelas' => $item->total_parcelas,
                'descricao' => $item->descricao
            ]);

            // Buscar todos os itens do mesmo parcelamento
            $itensParcelamento = FaturaCartaoItem::where('user_id', $usuarioId);

            if ($item->item_pai_id) {
                // Se tem item_pai_id, buscar todos com mesmo pai ou o próprio pai
                $itensParcelamento->where(function ($q) use ($item) {
                    $q->where('item_pai_id', $item->item_pai_id)
                        ->orWhere('id', $item->item_pai_id);
                });
            } else {
                // Se não tem item_pai_id, verificar se é pai ou usar descrição
                $filhos = FaturaCartaoItem::where('item_pai_id', $item->id)->count();
                if ($filhos > 0) {
                    // É um pai, buscar todos os filhos + ele mesmo
                    $itensParcelamento->where(function ($q) use ($item) {
                        $q->where('item_pai_id', $item->id)
                            ->orWhere('id', $item->id);
                    });
                } elseif ($item->total_parcelas > 1) {
                    // Parcelamento sem item_pai_id (dados antigos)
                    // Identificar pela descrição base (sem número da parcela)
                    $descricaoBase = preg_replace('/\s*\(\d+\/\d+\)\s*$/', '', $item->descricao);
                    
                    $itensParcelamento->where('cartao_credito_id', $item->cartao_credito_id)
                        ->where('total_parcelas', $item->total_parcelas)
                        ->where('data_compra', $item->data_compra)
                        ->where(function ($q) use ($descricaoBase, $item) {
                            // Descrição igual ou descrição base igual (para parcelas com número)
                            $q->where('descricao', 'LIKE', $descricaoBase . ' (%/%)')
                              ->orWhere('descricao', $descricaoBase);
                        });
                    
                    LogService::info("Buscando parcelamento por descrição base", [
                        'descricao_base' => $descricaoBase,
                        'cartao_id' => $item->cartao_credito_id,
                        'total_parcelas' => $item->total_parcelas,
                        'data_compra' => $item->data_compra
                    ]);
                } else {
                    // Item avulso, só ele
                    $itensParcelamento->where('id', $item->id);
                }
            }

            $itens = $itensParcelamento->get();

            // Verificar se algum item já foi pago
            $itensPagos = $itens->where('pago', true)->count();
            if ($itensPagos > 0) {
                return [
                    'success' => false,
                    'message' => "Não é possível excluir: {$itensPagos} parcela(s) já foi(foram) paga(s). Desfaça o pagamento primeiro."
                ];
            }

            $totalExcluidos = $itens->count();
            $faturasAfetadas = $itens->pluck('fatura_id')->unique()->filter()->toArray();
            $cartao = $item->cartaoCredito;

            // Excluir todos os itens
            FaturaCartaoItem::whereIn('id', $itens->pluck('id'))->delete();

            // Atualizar limite do cartão
            if ($cartao) {
                $cartao->atualizarLimiteDisponivel();
            }

            // Atualizar faturas afetadas
            foreach ($faturasAfetadas as $faturaId) {
                $fatura = Fatura::find($faturaId);
                if ($fatura) {
                    $itensRestantes = FaturaCartaoItem::where('fatura_id', $faturaId)->count();
                    if ($itensRestantes === 0) {
                        $fatura->delete();
                    } else {
                        $novoTotal = FaturaCartaoItem::where('fatura_id', $faturaId)->sum('valor');
                        $fatura->valor_total = $novoTotal;
                        $fatura->save();
                        $fatura->atualizarStatus();
                    }
                }
            }

            DB::commit();

            LogService::info("Parcelamento excluído", [
                'item_inicial_id' => $itemId,
                'total_excluidos' => $totalExcluidos,
                'usuario_id' => $usuarioId
            ]);

            return [
                'success' => true,
                'message' => $totalExcluidos > 1
                    ? "Parcelamento excluído ({$totalExcluidos} parcelas)"
                    : 'Item excluído com sucesso',
                'itens_excluidos' => $totalExcluidos
            ];
        } catch (Exception $e) {
            DB::rollBack();

            LogService::error("Erro ao excluir parcelamento", [
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'message' => 'Erro ao excluir parcelamento'];
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

        $itemPaiId = null; // ID da primeira parcela para vincular as demais

        // Calcular competência base (mês da fatura que fechou)
        $competenciaBase = $this->calcularCompetenciaFatura($diaCompra, $mesCompra, $anoCompra, $cartao->dia_fechamento);

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

            // Calcular competência desta parcela (avança mês a partir da competência base)
            $mesCompetencia = $competenciaBase['mes'] + ($i - 1);
            $anoCompetencia = $competenciaBase['ano'];
            while ($mesCompetencia > 12) {
                $mesCompetencia -= 12;
                $anoCompetencia++;
            }

            $item = FaturaCartaoItem::create([
                'user_id' => $dados['user_id'],
                'cartao_credito_id' => $dados['cartao_credito_id'],
                'fatura_id' => $fatura->id,
                'descricao' => trim($dados['descricao']),
                'valor' => $valoresParcelas[$i - 1],
                'data_compra' => $dados['data_compra'],
                'data_vencimento' => $vencimento['data'],
                'categoria_id' => $dados['categoria_id'] ?? null,
                'parcela_atual' => $i,
                'total_parcelas' => $numeroParcelas,
                'mes_referencia' => $mesCompetencia,
                'ano_referencia' => $anoCompetencia,
                'pago' => false,
                'item_pai_id' => $itemPaiId,
            ]);

            // Guardar ID da primeira parcela para vincular as demais
            if ($i === 1) {
                $itemPaiId = $item->id;
            }
        }
    }

    /**
     * Calcular mês/ano de competência (mês da fatura que fechou)
     * Se comprou ANTES do fechamento: competência = mês atual
     * Se comprou NO DIA ou DEPOIS do fechamento: competência = próximo mês
     */
    private function calcularCompetenciaFatura(int $diaCompra, int $mesCompra, int $anoCompra, int $diaFechamento): array
    {
        if ($diaCompra >= $diaFechamento) {
            // Comprou no dia do fechamento ou depois - entra na próxima fatura
            $mesCompetencia = $mesCompra + 1;
            $anoCompetencia = $anoCompra;

            if ($mesCompetencia > 12) {
                $mesCompetencia = 1;
                $anoCompetencia++;
            }
        } else {
            // Comprou ANTES do fechamento - entra na fatura do mês atual
            $mesCompetencia = $mesCompra;
            $anoCompetencia = $anoCompra;
        }

        return [
            'mes' => $mesCompetencia,
            'ano' => $anoCompetencia,
        ];
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
     * CORRIGIDO: O vencimento é SEMPRE no mês seguinte ao fechamento da fatura
     */
    private function calcularDataVencimento(
        int $diaCompra,
        int $mesCompra,
        int $anoCompra,
        int $numeroParcela,
        int $diaVencimento,
        int $diaFechamento
    ): array {
        // Calcular mês de competência (quando a fatura fecha)
        $mesCompetencia = $mesCompra;
        $anoCompetencia = $anoCompra;

        if ($diaCompra >= $diaFechamento) {
            // Comprou no dia do fechamento ou depois - entra na próxima fatura
            $mesCompetencia++;
            if ($mesCompetencia > 12) {
                $mesCompetencia = 1;
                $anoCompetencia++;
            }
        }

        // O vencimento é SEMPRE no mês seguinte à competência
        // + os meses adicionais da parcela
        $mesVencimento = $mesCompetencia + $numeroParcela; // +1 para vencimento + (parcela-1) para meses adicionais
        $anoVencimento = $anoCompetencia;

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
        $valorPendenteItens = $fatura->itens->where('pago', 0)->sum('valor');

        // Estornos têm valor negativo e já estão pagos
        // Precisamos somar o valor dos estornos para abater do total
        $valorEstornos = $fatura->itens->where('tipo', 'estorno')->sum('valor'); // Já é negativo

        // Valor total a pagar = itens pendentes + estornos (que são negativos, então abate)
        $valorPendente = $valorPendenteItens + $valorEstornos;
        // Garantir que não fique negativo
        $valorPendente = max(0, $valorPendente);

        // Separar despesas de estornos para o resumo
        $totalDespesas = $fatura->itens->where('tipo', '!=', 'estorno')->sum('valor');
        $totalEstornos = abs($fatura->itens->where('tipo', 'estorno')->sum('valor'));

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
            'total_despesas' => round((float) $totalDespesas, 2),
            'total_estornos' => round((float) $totalEstornos, 2),
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
                'tipo' => $item->tipo ?? 'despesa', // 'despesa' ou 'estorno'
            ];
        })->values()->toArray();

        $itensPagos = $fatura->itens->where('pago', 1)->count();
        $totalItens = $fatura->itens->count();

        // Calcular valor pendente (apenas itens não pagos)
        $valorPendenteItens = $fatura->itens->where('pago', 0)->sum('valor');

        // Estornos têm valor negativo e já estão pagos
        // Precisamos somar o valor dos estornos para abater do total
        $valorEstornos = $fatura->itens->where('tipo', 'estorno')->sum('valor'); // Já é negativo

        // Valor total a pagar = itens pendentes + estornos (que são negativos, então abate)
        $valorPendente = $valorPendenteItens + $valorEstornos;
        // Garantir que não fique negativo
        $valorPendente = max(0, $valorPendente);

        $progresso = $fatura->progresso;

        // Separar despesas de estornos para o resumo
        $totalDespesas = $fatura->itens->where('tipo', '!=', 'estorno')->sum('valor');
        $totalEstornos = abs($fatura->itens->where('tipo', 'estorno')->sum('valor'));

        // Extrair mês/ano da descrição (ex: "Fatura 9/2026")
        $mesReferencia = null;
        $anoReferencia = null;
        if (preg_match('/(\d{1,2})\/(\d{4})/', $fatura->descricao, $matches)) {
            $mesReferencia = (int)$matches[1];
            $anoReferencia = (int)$matches[2];
        }

        // Calcular data de vencimento baseado no mês da fatura e dia de vencimento do cartão
        $dataVencimento = null;
        if ($mesReferencia && $anoReferencia && $fatura->cartaoCredito) {
            $diaVencimento = $fatura->cartaoCredito->dia_vencimento ?? 1;
            $dataVencimento = sprintf('%04d-%02d-%02d', $anoReferencia, $mesReferencia, $diaVencimento);
        }

        return [
            'id' => $fatura->id,
            'descricao' => $fatura->descricao,
            'valor_total' => round((float) $valorPendente, 2),
            'valor_original' => round((float) $fatura->valor_total, 2),
            'total_despesas' => round((float) $totalDespesas, 2),
            'total_estornos' => round((float) $totalEstornos, 2),
            'numero_parcelas' => $fatura->numero_parcelas,
            'data_compra' => $fatura->data_compra->format('Y-m-d'),
            'data_vencimento' => $dataVencimento,
            'mes_referencia' => $mesReferencia,
            'ano_referencia' => $anoReferencia,
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
