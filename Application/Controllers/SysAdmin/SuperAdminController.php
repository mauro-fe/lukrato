<?php

namespace Application\Controllers\SysAdmin;

use Application\Controllers\WebController;
use Application\Core\Response;
use Application\Models\Usuario;

class SuperAdminController extends WebController
{
    public function index(): Response
    {
        $this->requireAdminUser();

        $totalUsers = Usuario::count();
        $totalAdmins = Usuario::where('is_admin', 1)->count();
        $newToday = Usuario::whereDate('created_at', date('Y-m-d'))->count();
        $recentUsers = Usuario::orderByDesc('id')
            ->limit(8)
            ->get(['id', 'nome', 'email', 'is_admin', 'created_at']);

        return $this->renderResponse(
            'admin/sysadmin/index',
            [
                'pageTitle' => 'Área Restrita do Dono',
                'subTitle' => 'Conteúdo exclusivo para administradores',
                'skipPlanLimits' => true,
                'metrics' => [
                    'totalUsers' => $totalUsers,
                    'totalAdmins' => $totalAdmins,
                    'newToday' => $newToday,
                ],
                'recentUsers' => $recentUsers,
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
