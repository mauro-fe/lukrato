<?php

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;
class OnboardingController extends WebController
{
    public function index(): Response
    {
        $this->requireUserId();

        return $this->buildRedirectResponse('dashboard');
    }
}
