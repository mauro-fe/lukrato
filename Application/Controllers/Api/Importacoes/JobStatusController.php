<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Importacoes;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Services\Importacao\ImportQueueService;

class JobStatusController extends ApiController
{
    private readonly ImportQueueService $queueService;

    public function __construct(
        ?ImportQueueService $queueService = null,
    ) {
        parent::__construct();
        $this->queueService = $this->resolveOrCreate($queueService, ImportQueueService::class);
    }

    public function __invoke(int $id): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        $payload = $this->queueService->getStatusForUser($userId, $id);
        if ($payload === null) {
            return Response::errorResponse('Job de importação não encontrado.', 404);
        }

        return Response::successResponse($payload, 'Status do job carregado com sucesso.');
    }
}
