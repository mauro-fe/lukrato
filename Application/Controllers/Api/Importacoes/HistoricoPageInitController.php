<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Importacoes;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Services\Importacao\ImportacoesHistoricoPageDataService;

class HistoricoPageInitController extends ApiController
{
    private readonly ImportacoesHistoricoPageDataService $pageDataService;

    public function __construct(?ImportacoesHistoricoPageDataService $pageDataService = null)
    {
        parent::__construct();
        $this->pageDataService = $this->resolveOrCreate($pageDataService, ImportacoesHistoricoPageDataService::class);
    }

    public function __invoke(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        $payload = $this->pageDataService->buildForUser($userId, [
            'conta_id' => $this->request->queryInt('conta_id', 0),
            'source_type' => $this->request->queryString('source_type', ''),
            'status' => $this->request->queryString('status', ''),
            'import_target' => $this->request->queryString('import_target', ''),
        ]);

        return Response::successResponse($payload, 'Inicialização do histórico de importações carregada com sucesso.');
    }
}
