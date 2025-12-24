<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Models\Conta;

/**
 * Serviço para gerenciar saldo inicial de contas.
 * ATUALIZADO: Agora usa o campo saldo_inicial da tabela contas
 */
class SaldoInicialService
{
    /**
     * Atualiza o saldo inicial de uma conta.
     */
    public function createOrUpdate(
        int $userId,
        int $contaId,
        string $contaNome,
        float $saldoInicial,
        ?string $data = null
    ): void {
        $conta = Conta::where('id', $contaId)
            ->where('user_id', $userId)
            ->first();

        if (!$conta) {
            throw new \Exception('Conta não encontrada');
        }

        $conta->saldo_inicial = $saldoInicial;
        $conta->save();
    }

    /**
     * Remove o saldo inicial de uma conta (seta para 0).
     */
    public function delete(int $userId, int $contaId): void
    {
        $conta = Conta::where('id', $contaId)
            ->where('user_id', $userId)
            ->first();

        if ($conta) {
            $conta->saldo_inicial = 0;
            $conta->save();
        }
    }

    /**
     * Obtém o saldo inicial atual de uma conta.
     */
    public function getSaldo(int $userId, int $contaId): float
    {
        $conta = Conta::where('id', $contaId)
            ->where('user_id', $userId)
            ->first();

        return $conta ? (float) $conta->saldo_inicial : 0.0;
    }
}
