<?php

declare(strict_types=1);

namespace Application\Repositories;

use Application\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Classe base abstrata para repositories.
 * Implementa funcionalidades comuns.
 *
 * @template TModel of Model
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * Relacionamentos para eager loading na próxima query.
     */
    protected array $eagerLoad = [];

    /**
     * Retorna o nome da classe do model.
     * 
     * @return class-string<TModel>
     */
    abstract protected function getModelClass(): string;

    /**
     * Define relacionamentos para eager loading na próxima query.
     *
     * @param array $relations
     * @return static
     */
    public function with(array $relations): static
    {
        $this->eagerLoad = $relations;
        return $this;
    }

    /**
     * @template T of Model
     * @param class-string<T> $modelClass
     * @return Builder<T>
     */
    private function newQueryFor(string $modelClass): Builder
    {
        /** @var Builder<T> $query */
        $query = $modelClass::query();
        return $query;
    }

    /**
     * Cria uma nova query builder instance.
     * Aplica eager loading se definido.
     * 
     * @return Builder<TModel>
     */
    protected function query(): Builder
    {
        $query = $this->newQueryFor($this->getModelClass());

        if (!empty($this->eagerLoad)) {
            $query->with($this->eagerLoad);
            $this->eagerLoad = [];
        }

        return $query;
    }

    /**
     * {@inheritdoc}
     *
     * @return TModel|null
     */
    public function find(int $id): ?Model
    {
        return $this->query()->find($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return TModel
     */
    public function findOrFail(int $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return Collection<int, Model>
     */
    public function all(): Collection
    {
        return $this->query()->get();
    }

    /**
     * {@inheritdoc}
     *
     * @return TModel
     */
    public function create(array $data): Model
    {
        return $this->query()->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): bool
    {
        $model = $this->findOrFail($id);
        return $model->update($data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        $model = $this->findOrFail($id);
        return $model->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->query()->count();
    }

    /**
     * Busca registro por ID verificando pertencimento ao usuário.
     * Usar em vez de find() para models com user_id.
     *
     * @param int $id
     * @param int $userId
     * @return TModel|null
     */
    public function findForUser(int $id, int $userId): ?Model
    {
        return $this->query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Busca registros com paginação.
     * 
     * @param int $perPage
     * @param int $page
     * @return Collection<int, Model>
     */
    public function paginate(int $perPage = 15, int $page = 1): Collection
    {
        return $this->query()
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();
    }

    /**
     * Busca registros com condições específicas.
     * 
     * @param array $conditions
     * @return Collection<int, Model>
     */
    public function findWhere(array $conditions): Collection
    {
        $query = $this->query();

        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->get();
    }

    /**
     * Busca um único registro com condições.
     * 
     * @param array $conditions
     * @return TModel|null
     */
    public function findOneWhere(array $conditions): ?Model
    {
        $query = $this->query();

        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }

        return $query->first();
    }
}
