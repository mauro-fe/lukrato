<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;
use Application\Services\Importacao\ImportacoesIndexPageDataService;

class ImportacoesController extends WebController
{
    private readonly ImportacoesIndexPageDataService $pageDataService;

    public function __construct(
        ?ImportacoesIndexPageDataService $pageDataService = null,
    ) {
        parent::__construct();
        $this->pageDataService = $this->resolveOrCreate($pageDataService, ImportacoesIndexPageDataService::class);
    }

    public function index(): Response
    {
        $userId = $this->requireUserId();
        $pageData = $this->pageDataService->buildForUser($userId, [
            'import_target' => $this->getStringQuery('import_target', 'conta'),
            'conta_id' => $this->getIntQuery('conta_id', 0),
            'cartao_id' => $this->getIntQuery('cartao_id', 0),
            'source_type' => $this->getStringQuery('source_type', 'ofx'),
        ]);

        return $this->renderAdminResponse(
            'admin/importacoes/index',
            array_merge([
                'pageTitle' => 'Importações',
                'subTitle' => 'Importe arquivos financeiros com preview e confirmação real',
            ], $pageData)
        );
    }
}
