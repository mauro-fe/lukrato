<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;
use Application\Services\Importacao\ImportacoesHistoricoPageDataService;

class ImportacoesHistoricoController extends WebController
{
    private readonly ImportacoesHistoricoPageDataService $pageDataService;

    public function __construct(
        ?ImportacoesHistoricoPageDataService $pageDataService = null,
    ) {
        parent::__construct();
        $this->pageDataService = $this->resolveOrCreate($pageDataService, ImportacoesHistoricoPageDataService::class);
    }

    public function index(): Response
    {
        $userId = $this->requireUserId();
        $pageData = $this->pageDataService->buildForUser($userId, [
            'conta_id' => $this->getIntQuery('conta_id', 0),
            'source_type' => $this->getStringQuery('source_type', ''),
            'status' => $this->getStringQuery('status', ''),
            'import_target' => $this->getStringQuery('import_target', ''),
        ]);

        return $this->renderAdminResponse(
            'admin/importacoes/historico/index',
            array_merge([
                'pageTitle' => 'Historico de Importacoes',
                'subTitle' => 'Acompanhe lotes importados, status e resultado por conta',
            ], $pageData)
        );
    }
}
