<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Importacoes;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Services\Importacao\ImportDeletionService;

class DeleteController extends ApiController
{
    private readonly ImportDeletionService $deletionService;

    public function __construct(
        ?ImportDeletionService $deletionService = null,
    ) {
        parent::__construct();
        $this->deletionService = $this->resolveOrCreate($deletionService, ImportDeletionService::class);
    }

    public function __invoke(int $id): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        try {
            $result = $this->deletionService->deleteBatchForUser($userId, $id);
        } catch (\Throwable $e) {
            return $this->internalErrorResponse($e, 'Erro ao excluir importacao.');
        }

        if (!(bool) ($result['success'] ?? false)) {
            return Response::errorResponse(
                (string) ($result['message'] ?? 'Nao foi possivel excluir a importacao.'),
                (int) ($result['status'] ?? 400),
                $result['errors'] ?? null
            );
        }

        return Response::successResponse(
            $result['data'] ?? null,
            (string) ($result['message'] ?? 'Importacao excluida com sucesso.'),
            (int) ($result['status'] ?? 200)
        );
    }
}
