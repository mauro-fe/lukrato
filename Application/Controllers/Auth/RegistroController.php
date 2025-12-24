<?php

declare(strict_types=1);

namespace Application\Controllers\Auth;

use Application\Controllers\BaseController;
use Application\Services\Auth\AuthService;
use Application\Services\Auth\GoogleAuthService;
use Application\Services\Auth\RegistrationResponseHandler;
use Application\Core\Exceptions\ValidationException;
use Application\Services\LogService;
use Throwable;

/**
 * Controller para registro de novos usuários
 */
class RegistroController extends BaseController
{
    private AuthService $authService;
    private GoogleAuthService $googleAuthService;
    private RegistrationResponseHandler $responseHandler;

    public function __construct(
        ?AuthService $authService = null,
        ?GoogleAuthService $googleAuthService = null
    ) {
        parent::__construct();
        $this->authService = $authService ?? new AuthService();
        $this->googleAuthService = $googleAuthService ?? new GoogleAuthService();
        $this->responseHandler = new RegistrationResponseHandler($this->request);
    }

    /**
     * Exibe formulário de registro
     */
    public function showForm(): void
    {
        $socialData = $_SESSION['social_register'] ?? null;

        $this->render('auth/register', [
            'errors' => $this->getSessionErrors(),
            'success' => $this->getSuccess(),
            'socialData' => $socialData,
        ]);
    }

    /**
     * Processa registro de novo usuário
     */
    public function store(): void
    {
        $email = $this->request->post('email', 'não-informado');
        $socialData = $_SESSION['social_register'] ?? null;
        $isGoogleRegistration = !empty($socialData) && ($socialData['provider'] ?? null) === 'google';

        try {
            $payload = $this->buildRegistrationPayload($isGoogleRegistration, $socialData);
            $result = $this->authService->register($payload);

            // Limpa dados temporários de social login
            unset($_SESSION['social_register']);

            // Login automático se for registro via Google
            if ($isGoogleRegistration) {
                $this->handleGoogleRegistrationSuccess($result, $email);
                return;
            }

            $this->logRegistrationSuccess($email, $result, 'local');
            $this->responseHandler->success($result, false);
        } catch (ValidationException $e) {
            $this->handleValidationError($e, $email);
        } catch (Throwable $e) {
            $this->handleRegistrationError($e, $email);
        }
    }

    /**
     * Constrói payload de registro baseado no tipo (local ou Google)
     */
    private function buildRegistrationPayload(bool $isGoogle, ?array $socialData): array
    {
        $payload = [
            'name' => $this->request->post('name', ''),
            'email' => $this->request->post('email', ''),
        ];

        if ($isGoogle) {
            $payload['google_id'] = $socialData['google_id'] ?? null;
            $payload['password'] = null;
            $payload['password_confirmation'] = null;
            $payload['provider'] = 'google';
        } else {
            $payload['password'] = $this->request->post('password', '');
            $payload['password_confirmation'] = $this->request->post('password_confirmation', '');
        }

        return $payload;
    }

    /**
     * Trata sucesso de registro via Google (com login automático)
     */
    private function handleGoogleRegistrationSuccess(array $result, string $email): void
    {
        $userId = $result['user_id'] ?? null;

        if ($userId) {
            $loginSuccess = $this->googleAuthService->loginAfterRegistration($userId, $email);

            if ($loginSuccess) {
                $this->logRegistrationSuccess($email, $result, 'google');
                $this->responseHandler->success($result, true);
                return;
            }
        }

        // Se falhou o login automático, redireciona para login manual
        LogService::warning('Login automático falhou após registro Google', ['email' => $email]);
        $this->responseHandler->success($result, false);
    }

    /**
     * Loga sucesso do registro
     */
    private function logRegistrationSuccess(string $email, array $result, string $provider): void
    {
        LogService::info('Novo usuário registrado com sucesso.', [
            'email' => $email,
            'ip' => $this->request->ip() ?? 'unknown',
            'user_id' => $result['user_id'] ?? 'unknown',
            'provider' => $provider,
        ]);
    }

    /**
     * Trata erros de validação
     */
    private function handleValidationError(ValidationException $e, string $email): void
    {
        LogService::warning('Falha de validação no registro.', [
            'email' => $email,
            'ip' => $this->request->ip() ?? 'unknown',
            'errors' => $e->getErrors(),
        ]);

        $this->responseHandler->validationError($e->getErrors());
    }

    /**
     * Trata erros gerais de registro
     */
    private function handleRegistrationError(Throwable $e, string $email): void
    {
        LogService::error('Exceção crítica ao tentar registrar usuário.', [
            'email' => $email,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        $this->responseHandler->generalError();
    }

    /**
     * Obtém erros da sessão
     */
    private function getSessionErrors(): ?array
    {
        $errors = $_SESSION['form_errors'] ?? null;
        unset($_SESSION['form_errors']);
        return $errors;
    }
}
