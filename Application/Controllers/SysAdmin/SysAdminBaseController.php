<?php

namespace Application\Controllers\SysAdmin;

use Application\Controllers\BaseController;
use Application\Lib\Auth;
use Application\Services\LogService;

/**
 * Controlador base para funcionalidades de super administrador
 */
abstract class SysAdminBaseController extends BaseController
{
    protected ?int $adminId = null;

    public function __construct()
    {
        parent::__construct();
        $this->validateSysAdminAccess();
    }

    /**
     * Valida acesso de super administrador
     */
    private function validateSysAdminAccess(): void
    {
        $admin = Auth::user();

        if (!$this->isValidSysAdmin($admin)) {
            $this->handleUnauthorizedAccess($admin);
        }

        $this->adminId = $admin->id;
    }

    /**
     * Verifica se o usuário é um super administrador válido
     */
    private function isValidSysAdmin($admin): bool
    {
        return $admin && $admin->isSysAdmin();
    }

    /**
     * Trata acesso não autorizado
     */
    private function handleUnauthorizedAccess($admin): void
    {
        LogService::warning('Acesso negado ao painel SysAdmin', [
            'admin_id' => $admin->id ?? null,
            'ip' => $this->request->ip()
        ]);

        $this->setError('Acesso restrito.');
        $this->redirect('admin/' . ($_SESSION['admin_username'] ?? 'admin') . '/dashboard');
        exit;
    }

    /**
     * Renderiza views do painel SysAdmin
     */
    protected function renderSysAdmin(string $viewPath, array $data = []): void
    {
        $data['isSysAdmin'] = true;

        $this->render(
            'sys_admin/' . ltrim($viewPath, '/'),
            $data,
            'admin/home/header',
            'admin/footer'
        );
    }
}
