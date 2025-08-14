<?php

namespace Application\Controllers\Admin;

use Application\Services\LogService;
use Application\Services\AuthService;
use Application\Services\AdminService;
use Application\Core\Exceptions\ValidationException;


class ProfileController extends AdminController
{
    private AuthService $authService;
    private AdminService $adminService;

    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
        $this->adminService = new AdminService();
    }

    public function view(string $username): void
    {
        if (!$this->hasPermissionForUser($username)) {
            return;
        }

        $this->renderAdmin('admin/profile/view', [
            'admin' => $this->admin,
        ]);
    }

    public function edit(string $username): void
    {
        if (!$this->hasPermissionForUser($username)) {
            return;
        }

        $this->renderAdmin('admin/profile/edit', [
            'admin' => $this->admin,
        ]);
    }

    public function update(string $username): void
    {
        $this->requirePost();

        if (!$this->hasPermissionForUserAjax($username)) {
            return;
        }

        try {
            $validated = $this->validateProfileData();
            $this->adminService->validateUniqueFields($this->adminId, $validated);

            $this->updateAdminProfile($validated);
            $this->updateSessionData($validated);

            $this->jsonSuccess('Perfil atualizado com sucesso!', [
                'redirect' => BASE_URL . 'admin/' . $validated['username'] . '/perfil'
            ]);
        } catch (ValidationException $e) {
            $this->handleValidationError($e, 'atualizar perfil');
        } catch (\Exception $e) {
            $this->handleGeneralError($e, 'atualizar perfil');
        }
    }

    public function updateField(): void
    {
        $this->requirePost();

        try {
            $campo = $this->request->get('campo');
            $valor = $this->request->get('valor');

            if (!$this->isValidField($campo)) {
                $this->jsonError('Campo inválido para atualização.', 400);
                return;
            }

            $validated = $this->validateFieldData($campo);
            $valorSanitizado = $validated[$campo];

            $this->adminService->validateUniqueField($this->adminId, $campo, $valorSanitizado);

            $this->updateSingleField($campo, $valorSanitizado);
            $this->updateSessionForField($campo, $valorSanitizado);

            $this->jsonSuccess('Campo atualizado com sucesso');
        } catch (ValidationException $e) {
            $this->handleFieldValidationError($e, $campo ?? 'N/A');
        } catch (\Exception $e) {
            $this->handleFieldGeneralError($e, $campo ?? 'N/A');
        }
    }

    public function editCredentials(string $username): void
    {
        if (!$this->hasPermissionForUser($username)) {
            return;
        }

        $this->renderAdmin('admin/admins/alterar-senha');
    }

    // public function updateCredentials(): void
    // {
    //     $this->requirePost();

    //     try {
    //         $validated = $this->validateCredentialsData();

    //         $this->authService->updatePassword(
    //             $this->adminId,
    //             $validated['current_password'],
    //             $validated['password']
    //         );

    //         $this->jsonSuccess('Senha atualizada com sucesso!', [
    //             'redirect' => BASE_URL . 'admin/' . $this->adminUsername . '/perfil'
    //         ]);
    //     } catch (ValidationException $e) {
    //         $this->handleValidationError($e, 'atualizar credenciais');
    //     } catch (\Exception $e) {
    //         $this->handleGeneralError($e, 'atualizar credenciais');
    //     }
    // }

    // Private helper methods

    private function hasPermissionForUser(string $username): bool
    {
        if ($username !== $this->adminUsername) {
            $this->setError('Acesso negado. Você não tem permissão para acessar este perfil.');
            $this->redirectToAdminHome();
            return false;
        }
        return true;
    }

    private function hasPermissionForUserAjax(string $username): bool
    {
        if ($username !== $this->adminUsername) {
            $this->jsonError('Acesso negado. Você não tem permissão para atualizar este perfil.', 403);
            return false;
        }
        return true;
    }

    private function validateProfileData(): array
    {
        return $this->request->validate([
            'username'      => 'required|alpha_numeric|min_len,4|max_len,50',
            'nome_completo' => 'required|max_len,100',
            'email'         => 'required|valid_email|max_len,100',
            'telefone'      => 'required|max_len,20',
            'nome_clinica'  => 'max_len,100',
            'razao_social'  => 'max_len,100',
            'cpf_cnpj'      => 'cpf_cnpj'
        ], [
            'username'      => 'trim',
            'nome_completo' => 'trim|sanitize_string',
            'email'         => 'trim|sanitize_email',
            'telefone'      => 'trim',
            'nome_clinica'  => 'trim|sanitize_string',
            'razao_social'  => 'trim|sanitize_string',
            'cpf_cnpj'      => 'trim'
        ]);
    }

    private function updateAdminProfile(array $validated): void
    {
        $this->admin->fill($validated);
        $this->admin->save();
    }

    private function updateSessionData(array $validated): void
    {
        if ($validated['username'] !== $this->adminUsername) {
            $_SESSION['admin_username'] = $validated['username'];
        }

        if (isset($validated['nome_clinica'])) {
            $_SESSION['nome_clinica'] = $validated['nome_clinica'];
        }
    }

    private function getPermittedFields(): array
    {
        return [
            'username' => ['rules' => 'required|alpha_numeric|min_len,4|max_len,30', 'filters' => 'trim'],
            'email' => ['rules' => 'required|valid_email|max_len,100', 'filters' => 'trim|sanitize_email'],
            'telefone' => ['rules' => 'required|max_len,20', 'filters' => 'trim|sanitize_string'],
            'nome_completo' => ['rules' => 'required|max_len,100', 'filters' => 'trim|sanitize_string'],
            'nome_clinica' => ['rules' => 'max_len,100', 'filters' => 'trim|sanitize_string'],
            'razao_social' => ['rules' => 'max_len,100', 'filters' => 'trim|sanitize_string'],
            'cnpj' => ['rules' => 'cnpj', 'filters' => 'trim|sanitize_string']
        ];
    }

    private function isValidField(string $campo): bool
    {
        if (!$campo) {
            return false;
        }

        $camposPermitidos = $this->getPermittedFields();
        return isset($camposPermitidos[$campo]);
    }

    private function validateFieldData(string $campo): array
    {
        $camposPermitidos = $this->getPermittedFields();

        return $this->request->validate(
            [$campo => $camposPermitidos[$campo]['rules']],
            [$campo => $camposPermitidos[$campo]['filters']]
        );
    }

    private function updateSingleField(string $campo, $valor): void
    {
        $this->admin->$campo = $valor;
        $this->admin->save();
    }

    private function updateSessionForField(string $campo, $valor): void
    {
        if ($campo === 'username') {
            $_SESSION['admin_username'] = $valor;
        } elseif ($campo === 'nome_clinica') {
            $_SESSION['nome_clinica'] = $valor;
        }
    }

    private function validateCredentialsData(): array
    {
        return $this->request->validate([
            'current_password' => 'required',
            'password' => 'required|min_len,8',
            'confirm_password' => 'required|equalsfield,password'
        ], [
            'current_password' => 'trim',
            'password' => 'trim',
            'confirm_password' => 'trim'
        ]);
    }

    private function handleValidationError(ValidationException $e, string $action): void
    {
        LogService::warning("Erro de validação ao $action", [
            'admin_id' => $this->adminId,
            'errors' => $e->getErrors(),
            'ip' => $this->request->ip()
        ]);

        $this->jsonError('Erro de validação', 422, $e->getErrors());
    }

    private function handleGeneralError(\Exception $e, string $action): void
    {
        LogService::error("Erro inesperado ao $action", [
            'admin_id' => $this->adminId,
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'ip' => $this->request->ip()
        ]);

        $this->jsonError("Erro ao $action: " . $e->getMessage());
    }

    private function handleFieldValidationError(ValidationException $e, string $campo): void
    {
        LogService::warning("Erro de validação ao atualizar campo específico", [
            'admin_id' => $this->adminId,
            'field' => $campo,
            'errors' => $e->getErrors(),
            'ip' => $this->request->ip()
        ]);

        $this->jsonError('Erro de validação: ' . implode(', ', $e->getErrors()), 422, $e->getErrors());
    }

    private function handleFieldGeneralError(\Exception $e, string $campo): void
    {
        LogService::error("Erro inesperado ao atualizar campo específico", [
            'admin_id' => $this->adminId,
            'field' => $campo,
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'ip' => $this->request->ip()
        ]);

        $this->jsonError('Erro ao atualizar campo: ' . $e->getMessage());
    }
}
