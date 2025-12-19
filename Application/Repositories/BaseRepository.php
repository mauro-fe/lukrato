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
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * @var Model
     */
    protected Model $model;

    /**
     * Retorna o nome da classe do model.
     * 
     * @return string
     */
    abstract protected function getModelClass(): string;

    /**
     * Construtor - instancia o model.
     */
    public function __construct()
    {
        $modelClass = $this->getModelClass();
        $this->model = new $modelClass();
    }

    /**
     * Cria uma nova query builder instance.
     * 
     * @return Builder
     */
    protected function query(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function find(int $id): ?Model
    {
        return $this->query()->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findOrFail(int $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function all(): Collection
    {
        return $this->query()->get();
    }

    /**
     * {@inheritdoc}
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
     * Busca registros com paginação.
     * 
     * @param int $perPage
     * @param int $page
     * @return Collection
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
     * @return Collection
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
     * @return Model|null
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
