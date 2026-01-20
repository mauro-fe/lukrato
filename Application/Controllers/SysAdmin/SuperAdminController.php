<?php

namespace Application\Controllers\SysAdmin;

use Application\Controllers\BaseController;
use Application\Lib\Auth;
use Application\Models\Usuario;

class SuperAdminController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $user = Auth::user();

        // Usar comparação não-estrita para funcionar com string "1" ou int 1
        if (!$user || $user->is_admin != 1) {
            $this->redirect('login');
            return;
        }

        $totalUsers   = Usuario::count();
        $totalAdmins  = Usuario::where('is_admin', 1)->count();
        $newToday     = Usuario::whereDate('created_at', date('Y-m-d'))->count();
        $recentUsers  = Usuario::orderByDesc('id')
            ->limit(8)
            ->get(['id', 'nome', 'email', 'is_admin', 'created_at']);

        $this->render(
            'sysAdmin/index',
            [
                'pageTitle' => 'Area Restrita do Dono',
                'subTitle' => 'Conteudo exclusivo para administradores',
                'metrics' => [
                    'totalUsers'  => $totalUsers,
                    'totalAdmins' => $totalAdmins,
                    'newToday'    => $newToday,
                ],
                'recentUsers' => $recentUsers,
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
