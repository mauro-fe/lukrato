<?php

namespace Application\Controllers;

use Application\Core\Response;
use Application\Services\Gamification\GamificationService;

class GamificationController extends WebController
{
    private GamificationService $gamificationService;

    public function __construct()
    {
        parent::__construct();
        $this->gamificationService = new GamificationService();
    }

    public function index(): Response
    {
        $user = $this->requireUser();
        $isPro = $user ? $user->isPro() : false;

        return $this->renderResponse(
            'admin/gamification/index',
            [
                'pageTitle' => 'Gamificação - Lukrato',
                'isPro' => $isPro,
                'currentUser' => $user,
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
