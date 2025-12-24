<?php

namespace Application\Repositories;

use Application\Models\Agendamento;
use Illuminate\Support\Collection;

class AgendamentoRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return Agendamento::class;
    }

    /**
     * Busca agendamentos por usuário
     */
    public function findByUser(int $userId): Collection
    {
        return Agendamento::where('user_id', $userId)
            ->orderBy('data_vencimento', 'asc')
            ->get();
    }

    /**
     * Busca agendamentos pendentes de um usuário
     */
    public function findPendentes(int $userId): Collection
    {
        return Agendamento::where('user_id', $userId)
            ->where('status', 'pendente')
            ->orderBy('data_vencimento', 'asc')
            ->get();
    }

    /**
     * Busca agendamentos vencidos até hoje
     */
    public function findVencidosAteHoje(int $userId): Collection
    {
        return Agendamento::where('user_id', $userId)
            ->where('status', 'pendente')
            ->where('data_vencimento', '<=', date('Y-m-d'))
            ->orderBy('data_vencimento', 'asc')
            ->get();
    }

    /**
     * Busca agendamentos por período
     */
    public function findPorPeriodo(int $userId, string $inicio, string $fim): Collection
    {
        return Agendamento::where('user_id', $userId)
            ->whereBetween('data_vencimento', [$inicio, $fim])
            ->orderBy('data_vencimento', 'asc')
            ->get();
    }

    /**
     * Busca agendamentos concluídos
     */
    public function findConcluidos(int $userId): Collection
    {
        return Agendamento::where('user_id', $userId)
            ->where('status', 'concluido')
            ->orderBy('data_pagamento', 'desc')
            ->get();
    }

    /**
     * Busca agendamentos cancelados
     */
    public function findCancelados(int $userId): Collection
    {
        return Agendamento::where('user_id', $userId)
            ->where('status', 'cancelado')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Busca agendamentos por tipo
     */
    public function findByTipo(int $userId, string $tipo): Collection
    {
        return Agendamento::where('user_id', $userId)
            ->where('tipo', $tipo)
            ->orderBy('data_vencimento', 'asc')
            ->get();
    }

    /**
     * Busca agendamentos por recorrência
     */
    public function findByRecorrencia(int $userId, string $recorrencia): Collection
    {
        return Agendamento::where('user_id', $userId)
            ->where('recorrencia', $recorrencia)
            ->where('status', 'pendente')
            ->orderBy('data_vencimento', 'asc')
            ->get();
    }

    /**
     * Busca agendamento por ID e usuário
     */
    public function findByIdAndUser(int $id, int $userId): ?Agendamento
    {
        return Agendamento::where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Conta agendamentos pendentes de um usuário
     */
    public function countPendentes(int $userId): int
    {
        return Agendamento::where('user_id', $userId)
            ->where('status', 'pendente')
            ->count();
    }

    /**
     * Verifica se o agendamento pertence ao usuário
     */
    public function belongsToUser(int $id, int $userId): bool
    {
        return Agendamento::where('id', $id)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Atualiza status do agendamento
     */
    public function updateStatus(int $id, string $status): bool
    {
        return Agendamento::where('id', $id)
            ->update(['status' => $status]);
    }

    /**
     * Marca agendamento como concluído
     */
    public function marcarConcluido(int $id, string $dataPagamento): bool
    {
        return Agendamento::where('id', $id)
            ->update([
                'status' => 'concluido',
                'data_pagamento' => $dataPagamento,
            ]);
    }

    /**
     * Marca agendamento como cancelado
     */
    public function cancelar(int $id): bool
    {
        return $this->updateStatus($id, 'cancelado');
    }
}
