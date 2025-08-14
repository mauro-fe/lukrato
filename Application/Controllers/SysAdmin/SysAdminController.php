<?php

namespace Application\Controllers\SysAdmin;

use Application\Models\Admin;
use Application\Services\LogService;

/**
 * Controlador principal do painel de super administrador
 */
class SysAdminController extends SysAdminBaseController
{
    /**
     * Exibe dashboard do super administrador
     */
    public function index(): void
    {
        $this->renderSysAdmin('dashboard');
    }

    /**
     * Lista todos os administradores (exceto super admins)
     */
    public function admins(): void
    {
        $admins = Admin::naoSysAdmin()->get();

        $this->renderSysAdmin('admins', [
            'admins' => $admins
        ]);
    }

    /**
     * Autoriza um administrador
     */
    public function autorizar(int $id): void
    {
        try {
            $admin = $this->findAdmin($id);

            if (!$admin) {
                $this->setError('Administrador não encontrado.');
                $this->redirect('sysadmin/admins');
                return;
            }

            if ($this->authorizeAdmin($admin)) {
                $this->logAdminAction('autorizado', $admin);
                $this->setSuccess('Administrador autorizado com sucesso!');
            } else {
                $this->setError('Administrador já está ativo.');
            }
        } catch (\Exception $e) {
            $this->handleActionError('autorizar', $id, $e);
        }

        $this->redirect('sysadmin/admins');
    }

    /**
     * Bloqueia um administrador
     */
    public function bloquear(int $id): void
    {
        try {
            $admin = $this->findAdmin($id);

            if (!$admin) {
                $this->setError('Administrador não encontrado.');
                $this->redirect('sysadmin/admins');
                return;
            }

            if ($this->blockAdmin($admin)) {
                $this->logAdminAction('bloqueado', $admin);
                $this->setSuccess('Administrador bloqueado com sucesso!');
            } else {
                $this->setError('Administrador já está inativo.');
            }
        } catch (\Exception $e) {
            $this->handleActionError('bloquear', $id, $e);
        }

        $this->redirect('sysadmin/admins');
    }

    /**
     * Exibe formulário de edição de administrador
     */
    public function editar(int $id): void
    {
        $admin = $this->findAdmin($id);

        if (!$admin) {
            $this->setError('Administrador não encontrado.');
            $this->redirect('sysadmin/admins');
            return;
        }

        $this->renderSysAdmin('editar_admin', [
            'admin' => $admin
        ]);
    }

    /**
     * Salva alterações do administrador
     */
    public function salvar(int $id): void
    {
        try {
            $admin = $this->findAdmin($id);

            if (!$admin) {
                $this->setError('Administrador não encontrado.');
                $this->redirect('sysadmin/admins');
                return;
            }

            $this->updateAdminData($admin);
            $this->logAdminAction('editado', $admin);

            $this->setSuccess('Administrador atualizado com sucesso.');
        } catch (\Exception $e) {
            $this->handleUpdateError($id, $e);
        }

        $this->redirect('sysadmin/admins');
    }

    /**
     * Busca administrador por ID
     */
    private function findAdmin(int $id): ?Admin
    {
        return Admin::find($id);
    }

    /**
     * Autoriza um administrador (ativa)
     */
    private function authorizeAdmin(Admin $admin): bool
    {
        if ($admin->ativo) {
            return false;
        }

        $admin->ativo = 1;
        $admin->save();
        return true;
    }

    /**
     * Bloqueia um administrador (desativa)
     */
    private function blockAdmin(Admin $admin): bool
    {
        if (!$admin->ativo) {
            return false;
        }

        $admin->ativo = 0;
        $admin->save();
        return true;
    }

    /**
     * Atualiza dados do administrador
     */
    private function updateAdminData(Admin $admin): void
    {
        $this->validateUpdateData();

        $admin->username = $_POST['username'];
        $admin->email = $_POST['email'];
        $admin->telefone = $_POST['telefone'] ?? '';
        $admin->save();
    }

    /**
     * Valida dados de atualização
     */
    private function validateUpdateData(): void
    {
        if (empty($_POST['username'])) {
            throw new \Exception('Nome de usuário é obrigatório.');
        }

        if (empty($_POST['email'])) {
            throw new \Exception('Email é obrigatório.');
        }

        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Email inválido.');
        }
    }

    /**
     * Registra ação realizada no administrador
     */
    private function logAdminAction(string $action, Admin $admin): void
    {
        LogService::info("Administrador {$action} pelo SysAdmin", [
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'sysadmin_id' => $this->adminId,
            'ip' => $this->request->ip()
        ]);
    }

    /**
     * Trata erros em ações de autorização/bloqueio
     */
    private function handleActionError(string $action, int $id, \Exception $e): void
    {
        LogService::error("Erro ao {$action} administrador", [
            'admin_id' => $id,
            'sysadmin_id' => $this->adminId,
            'exception' => $e->getMessage(),
            'ip' => $this->request->ip()
        ]);

        $this->setError("Erro ao {$action} administrador. Tente novamente.");
    }

    /**
     * Trata erros na atualização de dados
     */
    private function handleUpdateError(int $id, \Exception $e): void
    {
        LogService::error("Erro ao atualizar administrador", [
            'admin_id' => $id,
            'sysadmin_id' => $this->adminId,
            'exception' => $e->getMessage(),
            'form_data' => $_POST,
            'ip' => $this->request->ip()
        ]);

        $this->setError($e->getMessage() ?: 'Erro ao atualizar administrador. Tente novamente.');
    }
}
