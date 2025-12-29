<?php

declare(strict_types=1);

namespace Application\Repositories;

use Application\Models\Categoria;
use Application\Enums\CategoriaTipo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Repository para operações com categorias.
 */
class CategoriaRepository extends BaseRepository
{
    /**
     * {@inheritdoc}
     */
    protected function getModelClass(): string
    {
        return Categoria::class;
    }

    /**
     * Busca categorias de um usuário específico (incluindo globais).
     * 
     * @param int $userId
     * @return Collection
     */
    public function findByUser(int $userId): Collection
    {
        return $this->query()
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', $userId);
            })
            ->orderBy('nome')
            ->get();
    }

    /**
     * Busca uma categoria específica de um usuário (ou global).
     * 
     * @param int $id
     * @param int $userId
     * @return Categoria|null
     */
    public function findByIdAndUser(int $id, int $userId): ?Categoria
    {
        return $this->query()
            ->where('id', $id)
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', $userId);
            })
            ->first();
    }

    /**
     * Busca uma categoria específica de um usuário ou lança exceção.
     * 
     * @param int $id
     * @param int $userId
     * @return Categoria
     * @throws ModelNotFoundException
     */
    public function findByIdAndUserOrFail(int $id, int $userId): Categoria
    {
        $categoria = $this->findByIdAndUser($id, $userId);

        if (!$categoria) {
            throw new ModelNotFoundException('Categoria não encontrada');
        }

        return $categoria;
    }

    /**
     * Busca apenas categorias próprias do usuário (não globais).
     * 
     * @param int $userId
     * @return Collection
     */
    public function findOwnByUser(int $userId): Collection
    {
        return $this->query()
            ->where('user_id', $userId)
            ->orderBy('nome')
            ->get();
    }

    /**
     * Busca categorias por tipo.
     * 
     * @param int $userId
     * @param CategoriaTipo $tipo
     * @return Collection
     */
    public function findByType(int $userId, CategoriaTipo $tipo): Collection
    {
        return $this->query()
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', $userId);
            })
            ->whereIn('tipo', [$tipo->value, CategoriaTipo::AMBAS->value])
            ->orderBy('nome')
            ->get();
    }

    /**
     * Busca categorias de receita.
     * 
     * @param int $userId
     * @return Collection
     */
    public function findReceitas(int $userId): Collection
    {
        return $this->findByType($userId, CategoriaTipo::RECEITA);
    }

    /**
     * Busca categorias de despesa.
     * 
     * @param int $userId
     * @return Collection
     */
    public function findDespesas(int $userId): Collection
    {
        return $this->findByType($userId, CategoriaTipo::DESPESA);
    }

    /**
     * Busca categorias globais (do sistema).
     * 
     * @return Collection
     */
    public function findGlobal(): Collection
    {
        return $this->query()
            ->whereNull('user_id')
            ->orderBy('nome')
            ->get();
    }

    /**
     * Cria uma nova categoria para um usuário.
     * 
     * @param int $userId
     * @param array $data
     * @return Categoria
     */
    public function createForUser(int $userId, array $data): Categoria
    {
        $data['user_id'] = $userId;
        return $this->create($data);
    }

    /**
     * Atualiza uma categoria própria do usuário.
     * 
     * @param int $id
     * @param int $userId
     * @param array $data
     * @return bool
     * @throws ModelNotFoundException
     */
    public function updateForUser(int $id, int $userId, array $data): bool
    {
        $categoria = $this->findOwnByIdAndUserOrFail($id, $userId);
        return $categoria->update($data);
    }

    /**
     * Busca uma categoria própria do usuário (não global).
     * 
     * @param int $id
     * @param int $userId
     * @return Categoria|null
     */
    public function findOwnByIdAndUser(int $id, int $userId): ?Categoria
    {
        return $this->query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Busca uma categoria própria do usuário ou lança exceção.
     * 
     * @param int $id
     * @param int $userId
     * @return Categoria
     * @throws ModelNotFoundException
     */
    public function findOwnByIdAndUserOrFail(int $id, int $userId): Categoria
    {
        $categoria = $this->findOwnByIdAndUser($id, $userId);

        if (!$categoria) {
            throw new ModelNotFoundException('Categoria não encontrada ou não pertence ao usuário');
        }

        return $categoria;
    }

    /**
     * Deleta uma categoria própria do usuário.
     * 
     * @param int $id
     * @param int $userId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function deleteForUser(int $id, int $userId): bool
    {
        $categoria = $this->findOwnByIdAndUserOrFail($id, $userId);
        return $categoria->delete();
    }

    /**
     * Verifica se já existe categoria com mesmo nome e tipo.
     * 
     * @param int $userId
     * @param string $nome
     * @param string $tipo
     * @param int|null $excludeId ID para excluir da verificação
     * @return bool
     */
    public function hasDuplicate(int $userId, string $nome, string $tipo, ?int $excludeId = null): bool
    {
        $nomeLower = mb_strtolower($nome);

        error_log("🔍 [DUPLICATE CHECK] UserID={$userId}, Nome='{$nome}', NomeLower='{$nomeLower}', Tipo='{$tipo}', ExcludeId=" . ($excludeId ?? 'null'));

        $query = $this->query()
            ->where('user_id', $userId)
            ->whereRaw('LOWER(nome) = ?', [$nomeLower])
            ->where('tipo', $tipo);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $exists = $query->exists();

        if ($exists) {
            $existing = $query->first();
            error_log("🔍 [DUPLICATE CHECK] Duplicata encontrada! ID={$existing->id}, Nome='{$existing->nome}'");
        } else {
            error_log("🔍 [DUPLICATE CHECK] Nenhuma duplicata encontrada");
        }

        return $exists;
    }

    /**
     * Verifica se uma categoria pertence a um usuário (ou é global).
     * 
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function belongsToUser(int $id, int $userId): bool
    {
        return $this->query()
            ->where('id', $id)
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', $userId);
            })
            ->exists();
    }

    /**
     * Verifica se categoria é global (do sistema).
     * 
     * @param int $id
     * @return bool
     */
    public function isGlobal(int $id): bool
    {
        return $this->query()
            ->where('id', $id)
            ->whereNull('user_id')
            ->exists();
    }

    /**
     * Conta categorias de um usuário por tipo.
     * 
     * @param int $userId
     * @param CategoriaTipo $tipo
     * @return int
     */
    public function countByType(int $userId, CategoriaTipo $tipo): int
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('tipo', $tipo->value)
            ->count();
    }

    /**
     * Conta total de categorias próprias do usuário.
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
     * Busca categorias mais usadas de um usuário.
     * 
     * @param int $userId
     * @param int $limit
     * @return Collection
     */
    public function findMostUsed(int $userId, int $limit = 10): Collection
    {
        return $this->query()
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', $userId);
            })
            ->withCount(['lancamentos' => function ($q) use ($userId) {
                $q->where('user_id', $userId);
            }])
            ->get()
            ->where('lancamentos_count', '>', 0)
            ->sortByDesc('lancamentos_count')
            ->take($limit);
    }

    /**
     * Busca categorias sem lançamentos.
     * 
     * @param int $userId
     * @return Collection
     */
    public function findUnused(int $userId): Collection
    {
        return $this->query()
            ->where('user_id', $userId)
            ->whereDoesntHave('lancamentos')
            ->orderBy('nome')
            ->get();
    }
}
