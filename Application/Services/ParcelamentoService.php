<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Models\Parcelamento;
use Application\Models\Lancamento;
use Application\Repositories\ParcelamentoRepository;
use Illuminate\Database\Capsule\Manager as DB;
use Exception;

/**
 * Serviço para gerenciar parcelamentos
 */
class ParcelamentoService
{
    private ParcelamentoRepository $repository;

    public function __construct(?ParcelamentoRepository $repository = null)
    {
        $this->repository = $repository ?? new ParcelamentoRepository();
    }

    /**
     * Cria um novo parcelamento com todas as suas parcelas
     * 
     * @param int $usuarioId
     * @param array $data [descricao, valor_total, numero_parcelas, categoria_id, conta_id, tipo, data_criacao]
     * @return array ['success' => bool, 'parcelamento' => Parcelamento|null, 'message' => string]
     */
    public function criar(int $usuarioId, array $data): array
    {
        try {
            DB::beginTransaction();

            // Validações básicas
            $numeroParcelas = (int)($data['numero_parcelas'] ?? 1);
            if ($numeroParcelas < 2) {
                return [
                    'success' => false,
                    'message' => 'Número de parcelas deve ser maior que 1',
                ];
            }

            $valorTotal = (float)($data['valor_total'] ?? 0);
            if ($valorTotal <= 0) {
                return [
                    'success' => false,
                    'message' => 'Valor total deve ser maior que zero',
                ];
            }

            // Preparar dados do parcelamento
            $dadosParcelamento = [
                'user_id' => $usuarioId,
                'descricao' => $data['descricao'] ?? 'Parcelamento',
                'valor_total' => $valorTotal,
                'numero_parcelas' => $numeroParcelas,
                'categoria_id' => $data['categoria_id'] ?? null,
                'conta_id' => $data['conta_id'] ?? null,
                'tipo' => $data['tipo'] ?? 'saida', // entrada ou saida
                'data_criacao' => $data['data_criacao'] ?? date('Y-m-d'),
                'status' => Parcelamento::STATUS_ATIVO,
            ];

            // Criar parcelamento e parcelas
            $parcelamento = $this->repository->createWithParcelas($dadosParcelamento);

            DB::commit();

            return [
                'success' => true,
                'parcelamento' => $parcelamento,
                'parcelas' => $parcelamento->lancamentos,
                'message' => "Parcelamento criado com {$numeroParcelas} parcelas",
            ];
        } catch (Exception $e) {
            DB::rollBack();
            error_log("Erro ao criar parcelamento: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao criar parcelamento: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Cancela um parcelamento e remove parcelas não pagas
     */
    public function cancelar(int $parcelamentoId, int $usuarioId): array
    {
        try {
            DB::beginTransaction();

            $parcelamento = Parcelamento::where('id', $parcelamentoId)
                ->where('user_id', $usuarioId)
                ->first();

            if (!$parcelamento) {
                return [
                    'success' => false,
                    'message' => 'Parcelamento não encontrado',
                ];
            }

            if ($parcelamento->status === Parcelamento::STATUS_CANCELADO) {
                return [
                    'success' => false,
                    'message' => 'Parcelamento já está cancelado',
                ];
            }

            $this->repository->cancelar($parcelamentoId);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Parcelamento cancelado com sucesso',
            ];
        } catch (Exception $e) {
            DB::rollBack();
            error_log("Erro ao cancelar parcelamento: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao cancelar parcelamento: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Marca uma parcela como paga e atualiza o parcelamento
     */
    public function marcarParcelaPaga(int $lancamentoId, int $usuarioId, bool $pago = true): array
    {
        try {
            DB::beginTransaction();

            $lancamento = Lancamento::where('id', $lancamentoId)
                ->where('user_id', $usuarioId)
                ->whereNotNull('parcelamento_id')
                ->first();

            if (!$lancamento) {
                return [
                    'success' => false,
                    'message' => 'Parcela não encontrada',
                ];
            }

            // Atualizar status da parcela
            $lancamento->update(['pago' => $pago]);

            // Atualizar contador do parcelamento
            $this->repository->atualizarParcelasPagas($lancamento->parcelamento_id);

            DB::commit();

            return [
                'success' => true,
                'lancamento' => $lancamento,
                'message' => $pago ? 'Parcela marcada como paga' : 'Parcela marcada como não paga',
            ];
        } catch (Exception $e) {
            DB::rollBack();
            error_log("Erro ao atualizar parcela: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao atualizar parcela: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Lista parcelamentos de um usuário
     */
    public function listar(int $usuarioId, ?string $status = null, ?int $mes = null, ?int $ano = null): array
    {
        try {
            $query = Parcelamento::where('user_id', $usuarioId);

            // Filtro por status
            if ($status) {
                $query->where('status', $status);
            }

            // Filtro por mês e ano baseado na data_criacao
            if ($mes && $ano) {
                $query->whereYear('data_criacao', $ano)
                    ->whereMonth('data_criacao', $mes);
            }

            $parcelamentos = $query->with(['lancamentos' => function ($query) {
                $query->orderBy('data', 'asc');
            }])
                ->orderBy('data_criacao', 'desc')
                ->get();

            return [
                'success' => true,
                'parcelamentos' => $parcelamentos,
            ];
        } catch (Exception $e) {
            error_log("Erro ao listar parcelamentos: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao listar parcelamentos: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Busca um parcelamento com suas parcelas
     */
    public function buscar(int $parcelamentoId, int $usuarioId): array
    {
        try {
            $parcelamento = Parcelamento::where('id', $parcelamentoId)
                ->where('user_id', $usuarioId)
                ->with(['lancamentos', 'categoria', 'conta'])
                ->first();

            if (!$parcelamento) {
                return [
                    'success' => false,
                    'message' => 'Parcelamento não encontrado',
                ];
            }

            return [
                'success' => true,
                'parcelamento' => $parcelamento,
            ];
        } catch (Exception $e) {
            error_log("Erro ao buscar parcelamento: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao buscar parcelamento: ' . $e->getMessage(),
            ];
        }
    }
}
