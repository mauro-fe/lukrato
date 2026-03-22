<?php

declare(strict_types=1);

namespace Application\Repositories;

use Application\Models\Meta;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class MetaRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return Meta::class;
    }

    /**
     * Busca todas as metas do usuário
     */
    public function findByUser(int $userId, ?string $status = null): Collection
    {
        $q = $this->query()->forUser($userId)->orderByDesc('created_at');

        if ($status) {
            $q->where('status', $status);
        }

        return $q->get();
    }

    /**
     * Busca meta por ID e user_id
     */
    public function findByIdAndUser(int $id, int $userId): ?Model
    {
        return $this->query()->where('id', $id)->where('user_id', $userId)->first();
    }

    /**
     * Cria meta para o usuário
     */
    public function createForUser(int $userId, array $data): Model
    {
        $data['user_id'] = $userId;

        return $this->create($data);
    }

    /**
     * Atualiza meta garantindo que pertence ao usuário
     */
    public function updateForUser(int $id, int $userId, array $data): bool
    {
        $meta = $this->findByIdAndUser($id, $userId);
        if (!$meta) {
            return false;
        }

        return $meta->update($data);
    }

    /**
     * Remove meta do usuário
     */
    public function deleteForUser(int $id, int $userId): bool
    {
        $meta = $this->findByIdAndUser($id, $userId);
        if (!$meta) {
            return false;
        }

        return $meta->delete();
    }

    /**
     * Conta metas ativas do usuário
     */
    public function countAtivas(int $userId): int
    {
        return $this->query()
            ->forUser($userId)
            ->whereNotIn('status', [Meta::STATUS_CANCELADA])
            ->count();
    }

    /**
     * Busca metas ativas ordenadas por prioridade
     */
    public function getAtivasOrdenadas(int $userId): Collection
    {
        return $this->query()
            ->forUser($userId)
            ->ativas()
            ->orderByRaw("FIELD(prioridade, 'alta', 'media', 'baixa')")
            ->get();
    }

    /**
     * Atualizar valor atual da meta
     */
    public function atualizarValor(int $id, int $userId, float $novoValor): bool
    {
        $meta = $this->findByIdAndUser($id, $userId);
        if (!$meta) return false;

        $meta->valor_atual = $novoValor;

        // Auto-concluir se atingiu o alvo
        if ($meta->valor_atual >= $meta->valor_alvo && $meta->status === Meta::STATUS_ATIVA) {
            $meta->status = Meta::STATUS_CONCLUIDA;
        }

        // Reverter se caiu abaixo do alvo (metas vinculadas a conta)
        if ($meta->valor_atual < $meta->valor_alvo && $meta->status === Meta::STATUS_CONCLUIDA && $meta->conta_id) {
            $meta->status = Meta::STATUS_ATIVA;
        }

        return $meta->save();
    }
}
