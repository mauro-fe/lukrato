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
    public function success(array $result, bool $isGoogleRegistration = false): Response
    {
        $isAjax = $this->request->isAjax();
        $redirect = $isGoogleRegistration ? 'dashboard' : 'login';
        
        // Para registro normal, menciona a verificação de email
        $message = $isGoogleRegistration 
            ? 'Conta criada com Google e login realizado com sucesso!'
            : ($result['message'] ?? 'Conta criada com sucesso! Verifique seu e-mail para ativar sua conta.');

        if ($isAjax) {
            return Response::successResponse([
                'redirect' => $result['redirect'] ?? $redirect,
                'requires_verification' => !$isGoogleRegistration,
            ], $message, 201);
        }

        $successMessage = $isGoogleRegistration
            ? 'Conta criada com Google! Bem-vindo ao Lukrato.'
            : 'Conta criada com sucesso! Verifique seu e-mail para ativar sua conta.';

        $_SESSION['success'] = $successMessage;

        return Response::redirectResponse(BASE_URL . $redirect);
    }

    /**
     * Responde erro de validação
     */
    public function validationError(array $errors, string $defaultMessage = 'Corrija os dados do cadastro e tente novamente.'): Response
    {
        $isAjax = $this->request->isAjax();
        $message = $errors['email'] ?? $defaultMessage;
        $code = $this->resolveValidationErrorCode($errors, $message);

        if ($isAjax) {
            return Response::errorResponse($message, 422, $errors, $code);
        }

        $_SESSION['register_errors'] = $errors;
        $_SESSION['auth_active_tab'] = 'register';
        $_SESSION['error'] = $message;

        return Response::redirectResponse(BASE_URL . 'login');
    }

    /**
     * Responde erro geral
     */
    public function generalError(
        string $message = 'Falha ao cadastrar. Tente novamente mais tarde.',
        int $statusCode = 500
    ): Response
    {
        $isAjax = $this->request->isAjax();

        if ($isAjax) {
            return Response::errorResponse($message, $statusCode);
        }

        $_SESSION['error'] = $message;
        $_SESSION['auth_active_tab'] = 'register';

        return Response::redirectResponse(BASE_URL . 'login');
    }

    private function resolveValidationErrorCode(array $errors, string $message): ?string
    {
        $emailError = $errors['email'] ?? $message;
        $normalized = mb_strtolower((string) $emailError);

        if (str_contains($normalized, 'já cadastrado') || str_contains($normalized, 'ja cadastrado') || str_contains($normalized, 'já existe') || str_contains($normalized, 'ja existe')) {
            return 'EMAIL_ALREADY_EXISTS';
        }

        return null;
    }
}
