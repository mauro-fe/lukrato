<?php

declare(strict_types=1);

namespace Application\Contracts;

use Application\DTO\Requests\CreateSubcategoriaDTO;
use Application\DTO\Requests\UpdateSubcategoriaDTO;
use Application\Models\Categoria;

/**
 * Interface para o serviço de subcategorias.
 */
interface SubcategoriaServiceInterface
{
    /**
     * Lista subcategorias de uma categoria específica.
     *
     * @param int $categoriaId
     * @param int $userId
     * @return array
     */
    public function listByCategoria(int $categoriaId, int $userId): array;

    /**
     * Lista todas as categorias com suas subcategorias agrupadas.
     *
     * @param int $userId
     * @return array
     */
    public function listAllGrouped(int $userId): array;

    /**
     * Cria uma nova subcategoria.
     *
     * @param CreateSubcategoriaDTO $dto
     * @return Categoria
     */
    public function create(CreateSubcategoriaDTO $dto): Categoria;

    /**
     * Atualiza uma subcategoria existente.
     *
     * @param int $id
     * @param UpdateSubcategoriaDTO $dto
     * @param int $userId
     * @return Categoria
     */
    public function update(int $id, UpdateSubcategoriaDTO $dto, int $userId): Categoria;

    /**
     * Exclui uma subcategoria.
     *
     * @param int $id
     * @param int $userId
     * @return void
     */
    public function delete(int $id, int $userId): void;
}
