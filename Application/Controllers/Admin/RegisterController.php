<?php

namespace Application\Controllers\Admin;

use Application\Controllers\Admin\AdminController;
use Application\Models\Admin;
use Application\Lib\Helpers;
use Application\Services\LogService;
use Application\Core\Exceptions\ValidationException;
use Application\Controllers\BaseController;
use Application\Lib\Auth;
use Application\Services\AuthService;



class RegisterController extends AdminController
{
    public function __construct()
    {
        // Não chama parent::__construct() para evitar autenticação automática
        // Chama apenas o construtor do BaseController para ter Request e Response
        BaseController::__construct();
    }
    public function showRegisterForm(): void
    {
        $admin = Auth::user();

        if (!$admin || !$admin->isSysAdmin()) {
            $this->redirect('admin/login');
            return;
        }

        $this->renderRegisterForm();
    }


    public function processRegister(): void
    {
        file_put_contents('debug_ajax.txt', $this->request->isAjax() ? 'SIM' : 'NÃO');
        $this->requirePost();

        try {
            $validated = $this->validateRegistrationData();

            // ✅ Validação da força da senha (sem usar GUMP regex)
            (new AuthService())->validatePassword($validated['password']);

            // Agora sim, hash
            $validated['password'] = password_hash($validated['password'], PASSWORD_DEFAULT);

            $this->validateUniqueFields($validated);


            $admin = new Admin($validated);
            if (!$admin->save()) {
                throw new \Exception('Erro ao salvar administrador no banco de dados.');
            }



            unset($_SESSION['form_data']);

            // Se for AJAX, responde com JSON
            if ($this->request->isAjax()) {
                file_put_contents('debug_ajax_raw.txt', print_r($_POST, true));
                $this->jsonSuccess('Conta criada com sucesso!', [
                    'redirect' => BASE_URL . 'admin/login'
                ]);
                return;
            }

            // Se não for AJAX, redireciona normalmente
            $this->redirect('admin/login');
        } catch (ValidationException $e) {
            $message = $e->getMessage();

            if ($this->request->isAjax()) {
                $this->jsonError('Erro ao validar os dados.', 422, $e->getErrors());
                return;
            }

            $_SESSION['error'] = $message;
            $this->redirect('admin/novo');
        } catch (\Exception $e) {

            if ($this->request->isAjax()) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro inesperado: ' . $e->getMessage()
                ]);
                exit;
            }
            file_put_contents('debug_erro.txt', $e);

            $_SESSION['error'] = 'Erro inesperado.';
            $this->redirect('admin/novo');
        }
    }

    // Private helper methods

    private function renderRegisterForm(): void
    {
        $data = [
            'error' => $this->getError(),
            'success' => $this->getSuccess(),
            'form_data' => $_SESSION['form_data'] ?? []
        ];

        unset($_SESSION['form_data']);

        $this->render('admin/admins/register', $data, null, 'admin/footer');
    }

    private function storeFormDataInSession(): void
    {
        $_SESSION['form_data'] = $this->request->all();
    }

    private function validateRegistrationData(): array
    {
        return $this->request->validate([
            'username'          => 'required|alpha_numeric|min_len,4|max_len,50',
            'nome_completo'     => 'required|max_len,100',
            'email'             => 'required|valid_email|max_len,100',
            'telefone'          => 'required|max_len,20',
            'password' => 'required|min_len,8',

            'confirm_password'  => 'required|equalsfield,password',
            'nome_clinica'      => 'required|max_len,100',
            'cpf_cnpj'          => 'required|cpf_cnpj' // <- aqui é a mudança principal
        ], [
            'username'          => 'trim',
            'nome_completo'     => 'trim|sanitize_string',
            'email'             => 'trim|sanitize_email',
            'telefone'          => 'trim|sanitize_string',
            'password'          => 'trim',
            'confirm_password'  => 'trim',
            'nome_clinica'      => 'trim|sanitize_string',
            'razao_social'      => 'trim|sanitize_string',
            'cpf_cnpj'          => 'trim|sanitize_string'
        ]);
    }




    private function validateUniqueFields(array $validated): void
    {
        $errors = [];

        $errors = array_merge($errors, $this->validateUniqueUsername($validated['username']));
        $errors = array_merge($errors, $this->validateUniqueEmail($validated['email']));
        $errors = array_merge($errors, $this->validateUniqueCnpj($validated['cpf_cnpj']));


        if (!empty($errors)) {
            throw new ValidationException($errors, 422);
        }
    }

    private function validateUniqueUsername(string $username): array
    {
        if (!Admin::where('username', $username)->exists()) {
            return [];
        }

        $suggestion = $this->generateUsernameSuggestion($username);
        return ['username' => "Este nome de usuário já está em uso. Tente '{$suggestion}'."];
    }

    private function validateUniqueEmail(string $email): array
    {
        if (!Admin::where('email', $email)->exists()) {
            return [];
        }

        return ['email' => 'Este e-mail já está cadastrado.'];
    }
    private function validateUniqueCnpj(string $cpfCnpj): array
    {
        if (!Admin::where('cpf_cnpj', $cpfCnpj)->exists()) {
            return [];
        }

        return ['cpf_cnpj' => 'Este CPF ou CNPJ já está cadastrado.'];
    }


    private function generateUsernameSuggestion(string $username): string
    {
        $base = preg_replace('/[^a-z0-9]/i', '', $username);
        return $base . rand(10, 99);
    }

    private function createNewAdmin(array $validated): Admin
    {
        $adminData = $this->buildAdminData($validated);
        return Admin::create($adminData);
    }

    private function buildAdminData(array $validated): array
    {
        $slug = $this->generateUniqueSlug($validated['nome_clinica']);

        return [
            'username' => $validated['username'],
            'nome_completo' => $validated['nome_completo'],
            'email' => $validated['email'],
            'telefone' => $validated['telefone'],
            'password' => $validated['password'], // Hasheado automaticamente
            'nome_clinica' => $validated['nome_clinica'],
            'slug_clinica' => $slug,
            'razao_social' => $validated['razao_social'],
            'cpf_cnpj' => $validated['cpf_cnpj'],
            'ativo' => true,
            'password_changed_at' => now()
        ];
    }

    private function generateUniqueSlug(string $baseSlug): string
    {
        $slug = Helpers::slugify($baseSlug);
        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }

    private function slugExists(string $slug): bool
    {
        return Admin::where('slug_clinica', $slug)->exists();
    }

    private function handleSuccessfulRegistration(Admin $admin): void
    {
        $this->clearFormDataFromSession();
        $this->logSuccessfulRegistration($admin);
        $this->setSuccessMessage();
        $this->redirect('admin/login');
    }

    private function clearFormDataFromSession(): void
    {
        unset($_SESSION['form_data']);
    }

    private function logSuccessfulRegistration(Admin $admin): void
    {
        LogService::info("Novo admin registrado", [
            'admin_id' => $admin->id,
            'username' => $admin->username,
            'email' => $admin->email,
            'ip' => $this->request->ip()
        ]);
    }

    private function setSuccessMessage(): void
    {
        $this->setSuccess('Administrador cadastrado com sucesso! Faça login para continuar.');
    }

    private function handleValidationError(ValidationException $e): void
    {
        $this->logValidationError($e);

        $this->render('admin/admins/register', [
            'error' => Helpers::formatErrorHtml($e->getErrors()),
            'form_data' => $this->request->all()
        ]);
    }

    private function handleRegistrationError(\Exception $e): void
    {
        $this->logRegistrationError($e);

        $this->render('admin/admins/register', [
            'error' => 'Erro ao cadastrar administrador: ' . $e->getMessage(),
            'form_data' => $this->request->all()
        ]);
    }


    private function logValidationError(ValidationException $e): void
    {
        LogService::warning("Erro de validação no registro de admin", [
            'errors' => $e->getErrors(),
            'ip' => $this->request->ip(),
            'form_data' => $this->request->all()
        ]);
    }

    private function logRegistrationError(\Exception $e): void
    {
        LogService::error("Erro inesperado no registro de admin", [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'ip' => $this->request->ip(),
            'form_data' => $this->request->all()
        ]);
    }

    private function redirectToRegisterForm(): void
    {
        $this->redirect('admin/novo');
    }
}
