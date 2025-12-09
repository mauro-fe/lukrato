<?php

namespace Application\Controllers\Auth;

use Application\Controllers\BaseController;
use Application\Services\Auth\AuthService;
use Application\Core\Exceptions\ValidationException;
use Application\Services\LogService;
use Throwable;

class RegistroController extends BaseController
{
    private AuthService $authService;

    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
    }

    public function showForm(): void
    {
        $this->render('auth/register', [
            'errors' => $this->getSessionErrors(),
            'success' => $this->getSuccess()
        ]);
    }

    public function store(): void
    {
        $isAjax = $this->request->isAjax();

        $emailTentativa = $this->request->post('email', 'não-informado');

        try {
            $result = $this->authService->register([
                'name' => $this->request->post('name', ''),
                'email' => $emailTentativa,
                'password' => $this->request->post('password', ''),
                'password_confirmation' => $this->request->post('password_confirmation', '')
            ]);

            $this->respondToRegistrationSuccess($result, $isAjax, $emailTentativa);
        } catch (ValidationException $e) {
            $this->respondToValidationError($e, $isAjax, $emailTentativa);
        } catch (Throwable $e) {
            $this->respondToRegistrationError($e, $isAjax, $emailTentativa);
        }
    }

    private function getSessionErrors(): ?array
    {
        $errors = $_SESSION['form_errors'] ?? null;
        unset($_SESSION['form_errors']);
        return $errors;
    }

    private function respondToRegistrationSuccess(array $result, bool $isAjax, string $email): void
    {
        LogService::info('Novo usuário registrado com sucesso.', [
            'email' => $email,
            'ip' => $this->request->ip() ?? 'unknown',
            'user_id' => $result['user_id'] ?? 'unknown'
        ]);

        if ($isAjax) {
            $this->ok([
                'message' => $result['message'],
                'redirect' => $result['redirect']
            ], 201);
        } else {
            $this->setSuccess('Conta criada com sucesso! Você já pode fazer o login.');
            $this->redirect('login');
        }
    }

    private function respondToValidationError(ValidationException $e, bool $isAjax, string $email): void
    {
        LogService::warning('Falha de validação no registro.', [
            'email' => $email,
            'ip' => $this->request->ip() ?? 'unknown',
            'errors' => $e->getErrors()
        ]);

        if ($isAjax) {
            $this->fail($e->getMessage(), 422, $e->getErrors());
        } else {
            $_SESSION['form_errors'] = $e->getErrors();
            $this->redirect('register');
        }
    }

    private function respondToRegistrationError(Throwable $e, bool $isAjax, string $email): void
    {
        LogService::error('Exceção crítica ao tentar registrar usuário.', [
            'email' => $email,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        $message = 'Falha ao cadastrar. Tente novamente mais tarde.';

        if ($isAjax) {
            $this->fail($message, 500);
        } else {
            $this->setError($message);
            $this->redirect('register');
        }
    }
}
