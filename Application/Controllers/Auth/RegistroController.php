<?php

// Application/Controllers/Auth/RegistroController.php
namespace Application\Controllers\Auth;

use Application\Controllers\BaseController;
use Application\Services\Auth\AuthService;
use Application\DTOs\Auth\RegistrationDTO;
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

    /**
     * Exibe o formulário de registro.
     */
    public function showForm(): void
    {
        $this->render('auth/register', [
            'errors' => $this->getSessionErrors(),
            'success' => $this->getSuccess()
        ]);
    }

    /**
     * Processa o registro de novo usuário.
     */
    public function store(): void
    {
        $isAjax = $this->request->isAjax();

        // Capturamos o email aqui apenas para contexto de log (sem senha!)
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

    // --- Métodos Auxiliares Privados ---

    /**
     * Obtém erros da sessão.
     */
    private function getSessionErrors(): ?array
    {
        $errors = $_SESSION['form_errors'] ?? null;
        unset($_SESSION['form_errors']);
        return $errors;
    }

    /**
     * Responde ao sucesso do registro e LOGA.
     */
    private function respondToRegistrationSuccess(array $result, bool $isAjax, string $email): void
    {
        // LOG DE SUCESSO (INFO)
        LogService::info('Novo usuário registrado com sucesso.', [
            'email' => $email,
            'ip' => $this->request->ip() ?? 'unknown',
            'user_id' => $result['user_id'] ?? 'unknown' // Supondo que o service retorne o ID
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

    /**
     * Responde a erro de validação e LOGA.
     */
    private function respondToValidationError(ValidationException $e, bool $isAjax, string $email): void
    {
        // LOG DE AVISO (WARNING) - Útil para detectar spam ou UX ruim
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

    /**
     * Responde a erro geral de registro e LOGA DETALHADO.
     */
    private function respondToRegistrationError(Throwable $e, bool $isAjax, string $email): void
    {
        // LOG DE ERRO CRÍTICO (ERROR) - Com stack trace para debug
        LogService::error('Exceção crítica ao tentar registrar usuário.', [
            'email' => $email,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString() // Ajuda muito no debug
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
