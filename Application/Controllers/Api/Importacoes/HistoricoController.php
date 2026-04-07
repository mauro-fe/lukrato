<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Importacoes;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Services\Importacao\ImportHistoryService;

class HistoricoController extends ApiController
{
    private readonly ImportHistoryService $historyService;

    public function __construct(
        ?ImportHistoryService $historyService = null,
    ) {
        parent::__construct();
        $this->historyService = $this->resolveOrCreate($historyService, ImportHistoryService::class);
    }

    public function __invoke(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        $items = $this->historyService->listForUser($userId, [
            'conta_id' => $this->request->queryInt('conta_id', 0),
            'source_type' => $this->request->queryString('source_type', ''),
            'status' => $this->request->queryString('status', ''),
            'import_target' => $this->request->queryString('import_target', ''),
        ], $this->request->queryInt('limit', 100));

        return Response::successResponse([
            'items' => $items,
            'total' => count($items),
        ], 'Historico carregado com sucesso.');
    }
}
