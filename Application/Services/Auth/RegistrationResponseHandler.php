<?php

declare(strict_types=1);

namespace Application\Services\Auth;

use Application\Core\Request;
use Application\Core\Response;

/**
 * Gerencia respostas de registro (sucesso e erro)
 */
class RegistrationResponseHandler
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Responde sucesso de registro
     */
    public function success(array $result, bool $isGoogleRegistration = false): void
    {
        $isAjax = $this->request->isAjax();
        $redirect = $isGoogleRegistration ? 'dashboard' : 'login';
        
        // Para registro normal, menciona a verificação de email
        $message = $isGoogleRegistration 
            ? 'Conta criada com Google e login realizado com sucesso!'
            : ($result['message'] ?? 'Conta criada com sucesso! Verifique seu e-mail para ativar sua conta.');

        if ($isAjax) {
            Response::success([
                'redirect' => $result['redirect'] ?? $redirect,
                'requires_verification' => !$isGoogleRegistration,
            ], $message, 201);
            return;
        }

        $successMessage = $isGoogleRegistration
            ? 'Conta criada com Google! Bem-vindo ao Lukrato.'
            : 'Conta criada com sucesso! Verifique seu e-mail para ativar sua conta.';

        $_SESSION['success'] = $successMessage;
        Response::redirectTo(BASE_URL . $redirect);
    }

    /**
     * Responde erro de validação
     */
    public function validationError(array $errors, string $defaultMessage = 'Corrija os dados do cadastro e tente novamente.'): void
    {
        $isAjax = $this->request->isAjax();
        $message = $errors['email'] ?? $defaultMessage;

        if ($isAjax) {
            Response::error($message, 422, $errors);
            return;
        }

        $_SESSION['register_errors'] = $errors;
        $_SESSION['auth_active_tab'] = 'register';
        $_SESSION['error'] = $message;
        Response::redirectTo(BASE_URL . 'login');
    }

    /**
     * Responde erro geral
     */
    public function generalError(string $message = 'Falha ao cadastrar. Tente novamente mais tarde.'): void
    {
        $isAjax = $this->request->isAjax();

        if ($isAjax) {
            Response::error($message, 500);
            return;
        }

        $_SESSION['error'] = $message;
        $_SESSION['auth_active_tab'] = 'register';
        Response::redirectTo(BASE_URL . 'login');
    }
}
