<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Categoria;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\DTO\Requests\CreateSubcategoriaDTO;
use Application\DTO\Requests\UpdateSubcategoriaDTO;
use Application\Services\AI\Helpers\UserCategoryLoader;
use Application\Services\Categoria\SubcategoriaService;
use Application\Validators\SubcategoriaValidator;

class SubcategoriaController extends BaseController
{
    private SubcategoriaService $service;

    public function __construct(?SubcategoriaService $service = null)
    {
        parent::__construct();
        $this->service = $service ?? new SubcategoriaService();
    }

    /**
     * Lista subcategorias de uma categoria.
     * GET /api/categorias/{id}/subcategorias
     */
    public function index(mixed $categoriaId = null): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        $categoriaId = $this->resolveId($categoriaId);
        if ($categoriaId === null) {
            return Response::validationErrorResponse(['categoria_id' => 'ID da categoria inválido.']);
        }

        try {
            $result = $this->service->listByCategoria($categoriaId, $userId);

            return Response::successResponse($result);
        } catch (\DomainException $e) {
            return $this->notFoundFromThrowable($e, 'Categoria nao encontrada.');
        }
    }

    /**
     * Lista todas as categorias com subcategorias agrupadas.
     * GET /api/subcategorias/grouped
     */
    public function grouped(): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $result = $this->service->listAllGrouped($userId);

        return Response::successResponse($result);
    }

    /**
     * Cria uma subcategoria dentro de uma categoria.
     * POST /api/categorias/{id}/subcategorias
     */
    public function store(mixed $categoriaId = null): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $payload = $this->getRequestPayload();

        $categoriaId = $this->resolveId($categoriaId);
        if ($categoriaId === null) {
            return Response::validationErrorResponse(['categoria_id' => 'ID da categoria inválido.']);
        }

        $errors = SubcategoriaValidator::validateCreate($payload);
        if (!empty($errors)) {
            return Response::validationErrorResponse($errors);
        }

        try {
            $dto = CreateSubcategoriaDTO::fromRequest($userId, $categoriaId, $payload);
            $subcategoria = $this->service->create($dto);

            UserCategoryLoader::invalidate($userId);

            return Response::successResponse([
                'subcategoria' => $subcategoria->fresh(),
            ], 'Subcategoria criada com sucesso', 201);
        } catch (\DomainException $e) {
            $code = str_contains($e->getMessage(), 'Limite') ? 403 : 409;

            return $this->domainErrorResponse($e, 'Nao foi possivel criar a subcategoria.', $code);
        }
    }

    /**
     * Atualiza uma subcategoria.
     * PUT /api/subcategorias/{id}
     */
    public function update(mixed $id = null): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $payload = $this->getRequestPayload();

        $id = $this->resolveId($id, $payload);
        if ($id === null) {
            return Response::validationErrorResponse(['id' => 'ID inválido.']);
        }

        $errors = SubcategoriaValidator::validateUpdate($payload);
        if (!empty($errors)) {
            return Response::validationErrorResponse($errors);
        }

        try {
            $dto = UpdateSubcategoriaDTO::fromRequest($payload);
            $subcategoria = $this->service->update($id, $dto, $userId);

            UserCategoryLoader::invalidate($userId);

            return Response::successResponse($subcategoria);
        } catch (\DomainException $e) {
            $code = str_contains($e->getMessage(), 'não encontrada') ? 404 : 409;

            return $this->domainErrorResponse(
                $e,
                $code === 404 ? 'Subcategoria nao encontrada.' : 'Nao foi possivel atualizar a subcategoria.',
                $code,
                [],
                $code === 404 ? 'RESOURCE_NOT_FOUND' : null
            );
        }
    }

    /**
     * Exclui uma subcategoria.
     * DELETE /api/subcategorias/{id}
     */
    public function delete(mixed $id = null): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $payload = $this->getRequestPayload();

        $id = $this->resolveId($id, $payload);
        if ($id === null) {
            return Response::validationErrorResponse(['id' => 'ID inválido.']);
        }

        try {
            $this->service->delete($id, $userId);

            UserCategoryLoader::invalidate($userId);

            return Response::successResponse(['deleted' => true], 'Subcategoria excluída com sucesso');
        } catch (\DomainException $e) {
            return $this->notFoundFromThrowable($e, 'Subcategoria nao encontrada.');
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
