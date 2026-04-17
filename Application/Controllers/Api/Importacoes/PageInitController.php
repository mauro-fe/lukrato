<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Importacoes;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Services\Importacao\ImportacoesIndexPageDataService;

class PageInitController extends ApiController
{
    private readonly ImportacoesIndexPageDataService $pageDataService;

    public function __construct(?ImportacoesIndexPageDataService $pageDataService = null)
    {
        parent::__construct();
        $this->pageDataService = $this->resolveOrCreate($pageDataService, ImportacoesIndexPageDataService::class);
    }

    public function __invoke(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        $payload = $this->pageDataService->buildForUser($userId, [
            'import_target' => $this->request->queryString('import_target', 'conta'),
            'conta_id' => $this->request->queryInt('conta_id', 0),
            'cartao_id' => $this->request->queryInt('cartao_id', 0),
            'source_type' => $this->request->queryString('source_type', 'ofx'),
        ]);

        return Response::successResponse($payload, 'Inicialização de importações carregada com sucesso.');
    }
}
