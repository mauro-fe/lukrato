<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Categoria;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\DTO\Requests\CreateSubcategoriaDTO;
use Application\DTO\Requests\UpdateSubcategoriaDTO;
use Application\Services\Categoria\SubcategoriaService;
use Application\Validators\SubcategoriaValidator;

/**
 * Controller REST para subcategorias.
 *
 * Endpoints:
 *   GET    /api/categorias/{id}/subcategorias
 *   POST   /api/categorias/{id}/subcategorias
 *   PUT    /api/subcategorias/{id}
 *   DELETE /api/subcategorias/{id}
 */
class SubcategoriaController extends BaseController
{
    private SubcategoriaService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new SubcategoriaService();
    }

    /**
     * Lista subcategorias de uma categoria.
     * GET /api/categorias/{id}/subcategorias
     */
    public function index(mixed $categoriaId = null): void
    {
        $this->requireAuthApi();

        $categoriaId = $this->resolveId($categoriaId);
        if ($categoriaId === null) {
            Response::validationError(['categoria_id' => 'ID da categoria inválido.']);
            return;
        }

        try {
            $result = $this->service->listByCategoria($categoriaId, $this->userId);
            Response::success($result);
        } catch (\DomainException $e) {
            Response::error($e->getMessage(), 404);
        }
    }

    /**
     * Lista todas as categorias com subcategorias agrupadas.
     * GET /api/subcategorias/grouped
     */
    public function grouped(): void
    {
        $this->requireAuthApi();

        $result = $this->service->listAllGrouped($this->userId);
        Response::success($result);
    }

    /**
     * Cria uma subcategoria dentro de uma categoria.
     * POST /api/categorias/{id}/subcategorias
     */
    public function store(mixed $categoriaId = null): void
    {
        $this->requireAuthApi();
        $payload = $this->getRequestPayload();

        $categoriaId = $this->resolveId($categoriaId);
        if ($categoriaId === null) {
            Response::validationError(['categoria_id' => 'ID da categoria inválido.']);
            return;
        }

        // Validar campos obrigatórios
        $errors = SubcategoriaValidator::validateCreate($payload);
        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        try {
            $dto = CreateSubcategoriaDTO::fromRequest($this->userId, $categoriaId, $payload);
            $subcategoria = $this->service->create($dto);

            Response::success([
                'subcategoria' => $subcategoria->fresh(),
            ], 'Subcategoria criada com sucesso', 201);
        } catch (\DomainException $e) {
            $code = str_contains($e->getMessage(), 'Limite') ? 403 : 409;
            Response::error($e->getMessage(), $code);
        }
    }

    /**
     * Atualiza uma subcategoria.
     * PUT /api/subcategorias/{id}
     */
    public function update(mixed $id = null): void
    {
        $this->requireAuthApi();
        $payload = $this->getRequestPayload();

        $id = $this->resolveId($id, $payload);
        if ($id === null) {
            Response::validationError(['id' => 'ID inválido.']);
            return;
        }

        // Validar campos
        $errors = SubcategoriaValidator::validateUpdate($payload);
        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        try {
            $dto = UpdateSubcategoriaDTO::fromRequest($payload);
            $subcategoria = $this->service->update($id, $dto, $this->userId);

            Response::success($subcategoria);
        } catch (\DomainException $e) {
            $code = str_contains($e->getMessage(), 'não encontrada') ? 404 : 409;
            Response::error($e->getMessage(), $code);
        }
    }

    /**
     * Exclui uma subcategoria.
     * DELETE /api/subcategorias/{id}
     */
    public function delete(mixed $id = null): void
    {
        $this->requireAuthApi();
        $payload = $this->getRequestPayload();

        $id = $this->resolveId($id, $payload);
        if ($id === null) {
            Response::validationError(['id' => 'ID inválido.']);
            return;
        }

        try {
            $this->service->delete($id, $this->userId);
            Response::success(['deleted' => true], 'Subcategoria excluída com sucesso');
        } catch (\DomainException $e) {
            Response::error($e->getMessage(), 404);
        }
    }

    /**
     * Resolve o ID a partir do parâmetro da rota ou do payload.
     */
    private function resolveId(mixed $routeParam, array $payload = []): ?int
    {
        $id = is_numeric($routeParam) ? (int) $routeParam : (int) ($payload['id'] ?? 0);
        return $id > 0 ? $id : null;
    }
}
