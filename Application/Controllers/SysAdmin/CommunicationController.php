<?php

declare(strict_types=1);

namespace Application\Controllers\SysAdmin;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\Admin\CommunicationAdminViewService;

class CommunicationController extends BaseController
{
    public function __construct(
        private readonly CommunicationAdminViewService $viewService = new CommunicationAdminViewService()
    ) {
        parent::__construct();
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
