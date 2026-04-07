<?php

declare(strict_types=1);

namespace Application\Controllers\SysAdmin;

use Application\Controllers\WebController;
use Application\Core\Response;
use Application\Services\Admin\CommunicationAdminViewService;

class CommunicationController extends WebController
{
    private readonly CommunicationAdminViewService $viewService;

    public function __construct(
        ?CommunicationAdminViewService $viewService = null
    ) {
        parent::__construct();
        $this->viewService = $this->resolveOrCreate($viewService, CommunicationAdminViewService::class);
    }

    public function index(): Response
    {
        $this->requireAdminUser();

        return $this->renderResponse(
            'admin/sysadmin/communications',
            $this->viewService->buildViewData(),
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
