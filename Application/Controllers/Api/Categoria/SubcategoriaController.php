<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Categoria;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\DTO\Requests\CreateSubcategoriaDTO;
use Application\DTO\Requests\UpdateSubcategoriaDTO;
use Application\Services\AI\Helpers\UserCategoryLoader;
use Application\Services\Categoria\SubcategoriaService;
use Application\Validators\SubcategoriaValidator;

class SubcategoriaController extends ApiController
{
    private SubcategoriaService $service;

    public function __construct(?SubcategoriaService $service = null)
    {
        parent::__construct();
        $this->service = $this->resolveOrCreate($service, SubcategoriaService::class);
    }

    /**
     * Lista subcategorias de uma categoria.
     * GET /api/categorias/{id}/subcategorias
     */
    public function index(mixed $categoriaId = null): Response
    {
        $userId = $this->userId();

        $categoriaId = $this->resolveId($categoriaId);
        if ($categoriaId === null) {
            return $this->invalidIdResponse('categoria_id', 'ID da categoria inválido.');
        }

        return $this->successOrNotFound(
            fn(): mixed => $this->service->listByCategoria($categoriaId, $userId),
            'Categoria não encontrada.'
        );
    }

    /**
     * Lista todas as categorias com subcategorias agrupadas.
     * GET /api/subcategorias/grouped
     */
    public function grouped(): Response
    {
        $userId = $this->userId();
        $result = $this->service->listAllGrouped($userId);

        return Response::successResponse($result);
    }

    /**
     * Cria uma subcategoria dentro de uma categoria.
     * POST /api/categorias/{id}/subcategorias
     */
    public function store(mixed $categoriaId = null): Response
    {
        $userId = $this->userId();
        $payload = $this->getRequestPayload();

        $categoriaId = $this->resolveId($categoriaId);
        if ($categoriaId === null) {
            return $this->invalidIdResponse('categoria_id', 'ID da categoria inválido.');
        }

        $errors = SubcategoriaValidator::validateCreate($payload);
        if (!empty($errors)) {
            return Response::validationErrorResponse($errors);
        }

        try {
            $dto = CreateSubcategoriaDTO::fromRequest($userId, $categoriaId, $payload);
            $subcategoria = $this->service->create($dto);

            $this->invalidateUserCategories($userId);

            return Response::successResponse([
                'subcategoria' => $subcategoria->fresh(),
            ], 'Subcategoria criada com sucesso', 201);
        } catch (\DomainException $e) {
            $code = $this->domainStatusFromMessage($e, 409, ['limite' => 403]);

            return $this->domainErrorResponse($e, 'Não foi possivel criar a subcategoria.', $code);
        }
    }

    /**
     * Atualiza uma subcategoria.
     * PUT /api/subcategorias/{id}
     */
    public function update(mixed $id = null): Response
    {
        $userId = $this->userId();
        $payload = $this->getRequestPayload();

        $id = $this->resolveId($id, $payload);
        if ($id === null) {
            return $this->invalidIdResponse('id', 'ID inválido.');
        }

        $errors = SubcategoriaValidator::validateUpdate($payload);
        if (!empty($errors)) {
            return Response::validationErrorResponse($errors);
        }

        try {
            $dto = UpdateSubcategoriaDTO::fromRequest($payload);
            $subcategoria = $this->service->update($id, $dto, $userId);

            $this->invalidateUserCategories($userId);

            return Response::successResponse($subcategoria);
        } catch (\DomainException $e) {
            $code = $this->domainStatusFromMessage($e, 409, [
                'não encontrada' => 404,
                'nao encontrada' => 404,
            ]);

            return $this->domainErrorResponse(
                $e,
                $code === 404 ? 'Subcategoria não encontrada.' : 'Não foi possível atualizar a subcategoria.',
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
        $userId = $this->userId();
        $payload = $this->getRequestPayload();

        $id = $this->resolveId($id, $payload);
        if ($id === null) {
            return $this->invalidIdResponse('id', 'ID inválido.');
        }

        return $this->successOrNotFound(function () use ($id, $userId): mixed {
            $this->service->delete($id, $userId);
            $this->invalidateUserCategories($userId);

            return ['deleted' => true];
        }, 'Subcategoria não encontrada.', 'Subcategoria excluída com sucesso');
    }

    /**
     * Resolve o ID a partir do parâmetro da rota ou do payload.
     */
    private function resolveId(mixed $routeParam, array $payload = []): ?int
    {
        $id = is_numeric($routeParam) ? (int) $routeParam : (int) ($payload['id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    private function userId(): int
    {
        return $this->requireApiUserIdOrFail();
    }

    private function invalidIdResponse(string $field, string $message): Response
    {
        return Response::validationErrorResponse([$field => $message]);
    }

    private function invalidateUserCategories(int $userId): void
    {
        UserCategoryLoader::invalidate($userId);
    }

    /**
     * @param callable():mixed $resolver
     */
    private function successOrNotFound(
        callable $resolver,
        string $fallbackMessage,
        string $successMessage = 'Success'
    ): Response {
        try {
            return Response::successResponse($resolver(), $successMessage);
        } catch (\DomainException $e) {
            return $this->notFoundFromThrowable($e, $fallbackMessage);
        }
    }

    /**
     * @param array<string, int> $needleToStatus
     */
    private function domainStatusFromMessage(\DomainException $e, int $defaultStatus, array $needleToStatus): int
    {
        $message = strtolower($e->getMessage());

        foreach ($needleToStatus as $needle => $status) {
            if (str_contains($message, strtolower($needle))) {
                return $status;
            }
        }

        return $defaultStatus;
    }
}
