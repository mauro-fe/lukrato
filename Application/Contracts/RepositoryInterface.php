<?php

declare(strict_types=1);

namespace Application\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface base para todos os repositories.
 * Define operações CRUD padrão.
 */
interface RepositoryInterface
{
    /**
     * Busca um registro por ID.
     * 
     * @param int $id
     * @return Model|null
     */
    public function find(int $id): ?Model;

    /**
     * Busca um registro por ID ou lança exceção.
     * 
     * @param int $id
     * @return Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Model;

    /**
     * Retorna todos os registros.
     * 
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Cria um novo registro.
     * 
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Atualiza um registro existente.
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Deleta um registro.
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Conta total de registros.
     * 
     * @return int
     */
    public function count(): int;
}
