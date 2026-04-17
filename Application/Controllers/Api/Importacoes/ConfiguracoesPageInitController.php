<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Importacoes;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Services\Importacao\ImportacoesConfiguracoesPageDataService;

class ConfiguracoesPageInitController extends ApiController
{
    private readonly ImportacoesConfiguracoesPageDataService $pageDataService;

    public function __construct(?ImportacoesConfiguracoesPageDataService $pageDataService = null)
    {
        parent::__construct();
        $this->pageDataService = $this->resolveOrCreate($pageDataService, ImportacoesConfiguracoesPageDataService::class);
    }

    public function __invoke(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        $payload = $this->pageDataService->buildForUser($userId, [
            'conta_id' => $this->request->queryInt('conta_id', 0),
        ]);

        return Response::successResponse($payload, 'Inicialização de configurações de importação carregada com sucesso.');
    }
}
