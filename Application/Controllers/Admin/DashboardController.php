<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;

class DashboardController extends BaseController
{

    public function dashboard(): void
    {
        // Check if onboarding was just completed (session flag)
        $showOnboardingCongrats = !empty($_SESSION['onboarding_just_completed']);
        if ($showOnboardingCongrats) {
            unset($_SESSION['onboarding_just_completed']);
        }

        $data = [
            'pageTitle' => 'Dashboard',
            'showOnboardingCongrats' => $showOnboardingCongrats,
            'showMonthSelector' => true,
        ];

        $this->render(
            'admin/dashboard/index',
            $data,
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
