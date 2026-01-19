<?php

namespace Application\Controllers;

use Application\Services\GamificationService;

class GamificationController extends BaseController
{
    private GamificationService $gamificationService;

    public function __construct()
    {
        parent::__construct();
        $this->gamificationService = new GamificationService();
    }

    public function index(): void
    {
        $this->requireAuth();
        $user = \Application\Lib\Auth::user();
        $isPro = $user ? $user->isPro() : false;

        $this->render(
            'admin/gamification/index',
            [
                'pageTitle' => 'Gamificação - Lukrato',
                'isPro' => $isPro,
                'currentUser' => $user
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
