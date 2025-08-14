<?php

namespace Application\Middlewares;

use Application\Models\Admin;

class SysAdminMiddleware
{
    public static function handle($request)
    {
        if (!isset($_SESSION['admin_id'])) {
            header('Location: ' . BASE_URL . 'admin/login');
            exit;
        }

        $admin = Admin::find($_SESSION['admin_id']);

        if (!$admin || !$admin->isSysAdmin()) {
            $_SESSION['error'] = 'Acesso restrito.';
            header('Location: ' . BASE_URL . 'admin/login');
            exit;
        }
    }
}
