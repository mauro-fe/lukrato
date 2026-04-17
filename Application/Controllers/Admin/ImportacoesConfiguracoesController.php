<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;
use Application\Services\Importacao\ImportacoesConfiguracoesPageDataService;

class ImportacoesConfiguracoesController extends WebController
{
    private readonly ImportacoesConfiguracoesPageDataService $pageDataService;

    public function __construct(
        ?ImportacoesConfiguracoesPageDataService $pageDataService = null,
    ) {
        parent::__construct();
        $this->pageDataService = $this->resolveOrCreate($pageDataService, ImportacoesConfiguracoesPageDataService::class);
    }

    public function index(): Response
    {
        $userId = $this->requireUserId();
        $pageData = $this->pageDataService->buildForUser($userId, [
            'conta_id' => $this->getIntQuery('conta_id', 0),
        ]);

        return $this->renderAdminResponse(
            'admin/importacoes/configuracoes/index',
            array_merge([
                'pageTitle' => 'Configurações de Importação',
                'subTitle' => 'Defina perfil por conta para OFX agora e CSV na mesma base',
            ], $pageData)
        );
    }
}
