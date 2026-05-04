<?php

declare(strict_types=1);

namespace Application\Controllers;

use Application\Core\Response;

class GamificationController extends WebController
{
    public function index(): Response
    {
        $user = $this->requireUser();
        $isPro = $user->plan()->isPro();

        return $this->renderAdminResponse(
            'admin/gamification/index',
            [
                'pageTitle' => 'Gamificação - Lukrato',
                'isPro' => $isPro,
                'currentUser' => $user,
            ]
        );
    }
}
