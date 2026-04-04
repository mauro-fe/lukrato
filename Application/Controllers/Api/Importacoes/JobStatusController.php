<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Importacoes;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Services\Importacao\ImportQueueService;

class JobStatusController extends ApiController
{
    public function __construct(
        private readonly ImportQueueService $queueService = new ImportQueueService(),
    ) {
        parent::__construct();
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
