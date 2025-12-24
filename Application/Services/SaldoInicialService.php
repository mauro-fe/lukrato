<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Models\Lancamento;
use Application\Enums\LancamentoTipo;

/**
 * Serviço para gerenciar saldo inicial de contas.
 */
class SaldoInicialService
{
    /**
     * Cria ou atualiza o lançamento de saldo inicial de uma conta.
     */
    public function createOrUpdate(
        int $userId,
        int $contaId,
        string $contaNome,
        float $saldoInicial,
        ?string $data = null
    ): void {
        $data = $data ?? date('Y-m-d');

        // Buscar lançamento de saldo inicial existente
        $lancamento = Lancamento::where('user_id', $userId)
            ->where('conta_id', $contaId)
            ->where('eh_transferencia', 0)
            ->where('eh_saldo_inicial', 1)
            ->first();

        // Se saldo for zero, remover lançamento existente
        if (abs($saldoInicial) <= 0.00001) {
            if ($lancamento) {
                $lancamento->delete();
            }
            return;
        }

        // Preparar dados do lançamento
        $isReceita = $saldoInicial >= 0;
        $payload = [
            'user_id' => $userId,
            'tipo' => $isReceita ? LancamentoTipo::RECEITA->value : LancamentoTipo::DESPESA->value,
            'data' => $lancamento?->data?->format('Y-m-d') ?? $data,
            'categoria_id' => null,
            'conta_id' => $contaId,
            'conta_id_destino' => null,
            'descricao' => 'Saldo inicial da conta ' . $contaNome,
            'observacao' => null,
            'valor' => abs($saldoInicial),
            'eh_transferencia' => 0,
            'eh_saldo_inicial' => 1,
        ];

        // Atualizar ou criar lançamento
        if ($lancamento) {
            $lancamento->fill($payload)->save();
        } else {
            Lancamento::create($payload);
        }
    }

    /**
     * Remove o lançamento de saldo inicial de uma conta.
     */
    public function delete(int $userId, int $contaId): void
    {
        Lancamento::where('user_id', $userId)
            ->where('conta_id', $contaId)
            ->where('eh_transferencia', 0)
            ->where('eh_saldo_inicial', 1)
            ->delete();
    }

    /**
     * Obtém o saldo inicial atual de uma conta.
     */
    public function getSaldo(int $userId, int $contaId): float
    {
        $lancamento = Lancamento::where('user_id', $userId)
            ->where('conta_id', $contaId)
            ->where('eh_transferencia', 0)
            ->where('eh_saldo_inicial', 1)
            ->first();

        if (!$lancamento) {
            return 0.0;
        }

        $valor = (float) $lancamento->valor;
        return $lancamento->tipo === LancamentoTipo::RECEITA->value ? $valor : -$valor;
    }
}
