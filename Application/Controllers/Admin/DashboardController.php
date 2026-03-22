<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Core\Response;

class DashboardController extends BaseController
{
    public function dashboard(): Response
    {
        $this->requireUserId();

        $showOnboardingCongrats = !empty($_SESSION['onboarding_just_completed']);
        if ($showOnboardingCongrats) {
            unset($_SESSION['onboarding_just_completed']);
        }

        return $this->renderResponse(
            'admin/dashboard/index',
            [
                'pageTitle' => 'Dashboard',
                'showOnboardingCongrats' => $showOnboardingCongrats,
                'showMonthSelector' => true,
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
