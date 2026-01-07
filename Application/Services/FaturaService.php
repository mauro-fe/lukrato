<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Models\Fatura;
use Application\Models\FaturaCartaoItem;
use Application\Models\CartaoCredito;
use Application\Models\Lancamento;
use Exception;
use Illuminate\Support\Collection;

/**
 * Service para gerenciar faturas de cartão de crédito
 */
class FaturaService
{
    /**
     * Listar todas as faturas do usuário
     */
    public function listar(int $usuarioId, ?int $cartaoId = null, ?string $status = null, ?int $mes = null, ?int $ano = null): array
    {
        try {
            $query = Fatura::where('user_id', $usuarioId)
                ->with(['cartaoCredito', 'itens']);

            if ($cartaoId) {
                $query->where('cartao_credito_id', $cartaoId);
            }

            // Filtrar por mês/ano se fornecido
            if ($mes && $ano) {
                $query->whereYear('data_compra', $ano)
                    ->whereMonth('data_compra', $mes);
            }

            $faturas = $query->orderBy('data_compra', 'desc')->get();

            // Filtrar por status se fornecido
            if ($status) {
                $faturas = $faturas->filter(function ($fatura) use ($status) {
                    $faturaStatus = $fatura->isPaga() ? 'paga' : 'ativa';
                    if ($status === 'ativo') {
                        return $faturaStatus === 'ativa';
                    }
                    if ($status === 'concluido') {
                        return $faturaStatus === 'paga';
                    }
                    return true;
                });
            }

            return $faturas->map(function ($fatura) {
                $itensPagos = $fatura->itens->where('pago', 1)->count();
                $totalItens = $fatura->itens->count();
                $progresso = $fatura->progresso;

                // Determinar status baseado no progresso
                if ($progresso === 0) {
                    $status = 'pendente';
                } elseif ($progresso === 100) {
                    $status = 'paga';
                } else {
                    $status = 'parcial';
                }

                return [
                    'id' => $fatura->id,
                    'descricao' => $fatura->descricao,
                    'valor_total' => $fatura->valor_total,
                    'numero_parcelas' => $fatura->numero_parcelas,
                    'valor_parcela' => $fatura->valor_parcela,
                    'data_compra' => $fatura->data_compra->format('Y-m-d'),
                    'cartao' => [
                        'id' => $fatura->cartaoCredito->id,
                        'nome' => $fatura->cartaoCredito->nome,
                        'bandeira' => $fatura->cartaoCredito->bandeira,
                    ],
                    'parcelas_pagas' => $itensPagos,
                    'parcelas_pendentes' => $totalItens - $itensPagos,
                    'progresso' => $progresso,
                    'status' => $status,
                ];
            })->toArray();
        } catch (Exception $e) {
            error_log("Erro ao listar faturas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar fatura por ID
     */
    public function buscar(int $faturaId, int $usuarioId): ?array
    {
        try {
            $fatura = Fatura::where('id', $faturaId)
                ->where('user_id', $usuarioId)
                ->with(['cartaoCredito', 'itens'])
                ->first();

            if (!$fatura) {
                return null;
            }

            $parcelas = $fatura->itens->map(function ($item) {
                return [
                    'id' => $item->id,
                    'numero_parcela' => $item->parcela_atual,
                    'total_parcelas' => $item->total_parcelas,
                    'valor' => $item->valor,
                    'descricao' => $item->descricao,
                    'data_vencimento' => $item->data_vencimento->format('Y-m-d'),
                    'mes_referencia' => $item->mes_referencia,
                    'ano_referencia' => $item->ano_referencia,
                    'pago' => $item->pago,
                    'data_pagamento' => $item->data_pagamento?->format('Y-m-d'),
                ];
            })->sortBy('mes_referencia')->values()->toArray();

            $itensPagos = $fatura->itens->where('pago', 1)->count();
            $totalItens = $fatura->itens->count();

            $progresso = $fatura->progresso;
            if ($progresso === 0) {
                $status = 'pendente';
            } elseif ($progresso === 100) {
                $status = 'paga';
            } else {
                $status = 'parcial';
            }

            return [
                'id' => $fatura->id,
                'descricao' => $fatura->descricao,
                'valor_total' => $fatura->valor_total,
                'numero_parcelas' => $fatura->numero_parcelas,
                'data_compra' => $fatura->data_compra->format('Y-m-d'),
                'cartao' => [
                    'id' => $fatura->cartaoCredito->id,
                    'nome' => $fatura->cartaoCredito->nome,
                    'bandeira' => $fatura->cartaoCredito->bandeira,
                ],
                'parcelas' => $parcelas,
                'parcelas_pagas' => $itensPagos,
                'parcelas_pendentes' => $totalItens - $itensPagos,
                'progresso' => $progresso,
                'status' => $status,
            ];
        } catch (Exception $e) {
            error_log("Erro ao buscar fatura {$faturaId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Criar nova fatura com parcelas
     */
    public function criar(array $dados): ?int
    {
        try {
            // Validar dados básicos
            $this->validarDados($dados);

            // Buscar cartão
            $cartao = CartaoCredito::find($dados['cartao_credito_id']);
            if (!$cartao) {
                throw new Exception("Cartão não encontrado");
            }

            // Criar fatura
            $fatura = Fatura::create([
                'user_id' => $dados['user_id'],
                'cartao_credito_id' => $dados['cartao_credito_id'],
                'descricao' => $dados['descricao'],
                'valor_total' => $dados['valor_total'],
                'numero_parcelas' => $dados['numero_parcelas'],
                'data_compra' => $dados['data_compra'],
            ]);

            // Calcular valor da parcela
            $valorParcela = round($dados['valor_total'] / $dados['numero_parcelas'], 2);

            // Ajustar última parcela para cobrir diferença de arredondamento
            $somaParcelas = $valorParcela * ($dados['numero_parcelas'] - 1);
            $ultimaParcela = $dados['valor_total'] - $somaParcelas;

            // Criar itens (parcelas)
            $dataCompra = new \DateTime($dados['data_compra']);
            $diaCompra = (int) $dataCompra->format('d');
            $mesCompra = (int) $dataCompra->format('m');
            $anoCompra = (int) $dataCompra->format('Y');

            for ($i = 1; $i <= $dados['numero_parcelas']; $i++) {
                $valor = ($i === $dados['numero_parcelas']) ? $ultimaParcela : $valorParcela;

                // Calcular data de vencimento
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
                    'descricao' => $dados['descricao'],
                    'valor' => $valor,
                    'data_compra' => $dados['data_compra'],
                    'data_vencimento' => $vencimento['data'],
                    'categoria_id' => $dados['categoria_id'] ?? null,
                    'numero_parcela' => $i,
                    'total_parcelas' => $dados['numero_parcelas'],
                    'mes_referencia' => $vencimento['mes'],
                    'ano_referencia' => $vencimento['ano'],
                    'pago' => 0,
                ]);
            }

            return $fatura->id;
        } catch (Exception $e) {
            error_log("Erro ao criar fatura: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Cancelar fatura (remover fatura e todos os itens não pagos)
     */
    public function cancelar(int $faturaId, int $usuarioId): bool
    {
        try {
            $fatura = Fatura::where('id', $faturaId)
                ->where('user_id', $usuarioId)
                ->first();

            if (!$fatura) {
                throw new Exception("Fatura não encontrada");
            }

            // Verificar se tem parcelas pagas
            $itensPagos = FaturaCartaoItem::where('fatura_id', $faturaId)
                ->where('pago', 1)
                ->count();

            if ($itensPagos > 0) {
                throw new Exception("Não é possível cancelar fatura com parcelas já pagas");
            }

            // Remover itens pendentes
            FaturaCartaoItem::where('fatura_id', $faturaId)->delete();

            // Remover fatura
            $fatura->delete();

            return true;
        } catch (Exception $e) {
            error_log("Erro ao cancelar fatura {$faturaId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validar dados de entrada
     */
    private function validarDados(array $dados): void
    {
        if (empty($dados['user_id'])) {
            throw new Exception("Usuário não informado");
        }

        if (empty($dados['cartao_credito_id'])) {
            throw new Exception("Cartão não informado");
        }

        if (empty($dados['descricao'])) {
            throw new Exception("Descrição não informada");
        }

        if (empty($dados['valor_total']) || $dados['valor_total'] <= 0) {
            throw new Exception("Valor inválido");
        }

        if (empty($dados['numero_parcelas']) || $dados['numero_parcelas'] < 1) {
            throw new Exception("Número de parcelas inválido");
        }

        if (empty($dados['data_compra'])) {
            throw new Exception("Data da compra não informada");
        }
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
        // Se a compra foi feita no dia do fechamento ou depois, vai para o próximo mês
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

        $dataVencimento = sprintf('%04d-%02d-%02d', $anoVencimento, $mesVencimento, $diaFinal);

        return [
            'data' => $dataVencimento,
            'mes' => $mesVencimento,
            'ano' => $anoVencimento,
        ];
    }

    /**
     * Marcar item da fatura como pago/pendente
     */
    public function toggleItemPago(int $faturaId, int $itemId, int $usuarioId, bool $pago): bool
    {
        try {
            $item = FaturaCartaoItem::where('id', $itemId)
                ->where('fatura_id', $faturaId)
                ->where('user_id', $usuarioId)
                ->with(['cartaoCredito'])
                ->first();

            if (!$item) {
                return false;
            }

            // Se está marcando como pago, criar lançamento
            if ($pago && !$item->lancamento_id) {
                // Garantir que o valor está no formato correto (float com 2 casas decimais)
                $valorFormatado = (float) number_format((float) $item->valor, 2, '.', '');

                // Verificar se o cartão tem conta vinculada
                if (!$item->cartaoCredito->conta_id) {
                    error_log("Cartão {$item->cartaoCredito->id} não tem conta vinculada");
                    return false;
                }

                $lancamento = Lancamento::create([
                    'user_id' => $usuarioId,
                    'tipo' => 'despesa',
                    'valor' => $valorFormatado,
                    'data' => $item->data_vencimento ? $item->data_vencimento->format('Y-m-d') : now()->format('Y-m-d'),
                    'descricao' => $item->descricao,
                    'categoria_id' => $item->categoria_id,
                    'conta_id' => $item->cartaoCredito->conta_id,
                    'pago' => true,
                    'data_pagamento' => now()->format('Y-m-d'),
                    'observacao' => 'Pagamento de fatura - ' . $item->cartaoCredito->nome
                ]);

                $item->lancamento_id = $lancamento->id;
            }
            // Se está desmarcando, remover o lançamento
            elseif (!$pago && $item->lancamento_id) {
                Lancamento::where('id', $item->lancamento_id)->delete();
                $item->lancamento_id = null;
            }

            $item->pago = $pago;
            $item->data_pagamento = $pago ? now() : null;
            $item->save();

            return true;
        } catch (Exception $e) {
            error_log("Erro ao atualizar item da fatura: " . $e->getMessage());
            return false;
        }
    }
}
