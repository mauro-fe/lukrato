<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Core\Response;
class OnboardingController extends BaseController
{
    public function index(): Response
    {
        $this->requireUserId();

        return $this->buildRedirectResponse('dashboard');
    }
}
