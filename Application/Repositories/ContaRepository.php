<?php

declare(strict_types=1);

namespace Application\Repositories;

use Application\Models\Conta;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Repository para operações com contas.
 */
class ContaRepository extends BaseRepository
{
    /**
     * {@inheritdoc}
     */
    protected function getModelClass(): string
    {
        return Conta::class;
    }

    /**
     * Busca contas de um usuário específico.
     * 
     * @param int $userId
     * @param bool $onlyActive Retornar apenas ativas
     * @return Collection
     */
    public function findByUser(int $userId, bool $onlyActive = false): Collection
    {
        $query = $this->query()->where('user_id', $userId);

        if ($onlyActive) {
            $query->where('ativo', 1);
        }

        return $query->orderBy('nome')->get();
    }

    /**
     * Busca uma conta específica de um usuário.
     * 
     * @param int $id
     * @param int $userId
     * @return Conta|null
     */
    public function findByIdAndUser(int $id, int $userId): ?Conta
    {
        return $this->query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Busca uma conta específica de um usuário ou lança exceção.
     * 
     * @param int $id
     * @param int $userId
     * @return Conta
     * @throws ModelNotFoundException
     */
    public function findByIdAndUserOrFail(int $id, int $userId): Conta
    {
        $conta = $this->findByIdAndUser($id, $userId);
        
        if (!$conta) {
            throw new ModelNotFoundException('Conta não encontrada');
        }
        
        return $conta;
    }

    /**
     * Busca contas ativas de um usuário.
     * 
     * @param int $userId
     * @return Collection
     */
    public function findActive(int $userId): Collection
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('ativo', 1)
            ->orderBy('nome')
            ->get();
    }

    /**
     * Busca contas arquivadas de um usuário.
     * 
     * @param int $userId
     * @return Collection
     */
    public function findArchived(int $userId): Collection
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('ativo', 0)
            ->orderBy('nome')
            ->get();
    }

    /**
     * Busca contas por moeda.
     * 
     * @param int $userId
     * @param string $moeda
     * @return Collection
     */
    public function findByMoeda(int $userId, string $moeda): Collection
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('moeda', $moeda)
            ->orderBy('nome')
            ->get();
    }

    /**
     * Cria uma nova conta.
     * 
     * @param int $userId
     * @param array $data
     * @return Conta
     */
    public function createForUser(int $userId, array $data): Conta
    {
        $data['user_id'] = $userId;
        $data['ativo'] = $data['ativo'] ?? 1;
        
        return $this->create($data);
    }

    /**
     * Atualiza uma conta de um usuário.
     * 
     * @param int $id
     * @param int $userId
     * @param array $data
     * @return bool
     * @throws ModelNotFoundException
     */
    public function updateForUser(int $id, int $userId, array $data): bool
    {
        $conta = $this->findByIdAndUserOrFail($id, $userId);
        return $conta->update($data);
    }

    /**
     * Arquiva uma conta (soft delete).
     * 
     * @param int $id
     * @param int $userId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function archive(int $id, int $userId): bool
    {
        $conta = $this->findByIdAndUserOrFail($id, $userId);
        $conta->ativo = 0;
        return $conta->save();
    }

    /**
     * Restaura uma conta arquivada.
     * 
     * @param int $id
     * @param int $userId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function restore(int $id, int $userId): bool
    {
        $conta = $this->findByIdAndUserOrFail($id, $userId);
        $conta->ativo = 1;
        return $conta->save();
    }

    /**
     * Deleta permanentemente uma conta de um usuário.
     * 
     * @param int $id
     * @param int $userId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function deleteForUser(int $id, int $userId): bool
    {
        $conta = $this->findByIdAndUserOrFail($id, $userId);
        return $conta->delete();
    }

    /**
     * Conta total de contas ativas de um usuário.
     * 
     * @param int $userId
     * @return int
     */
    public function countActive(int $userId): int
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('ativo', 1)
            ->count();
    }

    /**
     * Conta total de contas de um usuário (ativas e arquivadas).
     * 
     * @param int $userId
     * @return int
     */
    public function countByUser(int $userId): int
    {
        return $this->query()
            ->where('user_id', $userId)
            ->count();
    }

    /**
     * Verifica se uma conta pertence a um usuário.
     * 
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function belongsToUser(int $id, int $userId): bool
    {
        return $this->query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Verifica se usuário já tem uma conta com o mesmo nome.
     * 
     * @param int $userId
     * @param string $nome
     * @param int|null $excludeId ID para excluir da verificação (útil em updates)
     * @return bool
     */
    public function hasDuplicateName(int $userId, string $nome, ?int $excludeId = null): bool
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->whereRaw('LOWER(nome) = ?', [mb_strtolower($nome)]);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Busca contas com seus saldos (carrega relacionamento).
     * 
     * @param int $userId
     * @param bool $onlyActive
     * @return Collection
     */
    public function findWithLancamentos(int $userId, bool $onlyActive = true): Collection
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->with(['lancamentos' => function ($q) {
                $q->orderBy('data', 'desc');
            }]);

        if ($onlyActive) {
            $query->where('ativo', 1);
        }

        return $query->orderBy('nome')->get();
    }

    /**
     * Busca IDs de todas as contas de um usuário.
     * 
     * @param int $userId
     * @param bool $onlyActive
     * @return array
     */
    public function getIdsByUser(int $userId, bool $onlyActive = false): array
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->select('id');

        if ($onlyActive) {
            $query->where('ativo', 1);
        }

        return $query->pluck('id')->toArray();
    }
}
