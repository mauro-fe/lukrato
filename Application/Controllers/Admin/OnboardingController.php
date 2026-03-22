<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\Admin\OnboardingAdminViewService;

class OnboardingController extends BaseController
{
    public function __construct(
        private readonly ?OnboardingAdminViewService $viewService = null
    ) {
        parent::__construct();
    }

    /**
     * Exibe o onboarding V2 (multi-step)
     * Fluxo: Welcome -> Goal -> Account -> Transaction -> Success
     */
    public function index(): Response
    {
        $viewData = $this->viewService()->buildViewData($this->requireUser());

        if (isset($viewData['redirect']) && is_string($viewData['redirect'])) {
            return $this->buildRedirectResponse($viewData['redirect']);
        }

        return $this->renderResponse('admin/onboarding/v2/index', $viewData);
    }

    private function viewService(): OnboardingAdminViewService
    {
        return $this->viewService ?? new OnboardingAdminViewService();
    }
}
