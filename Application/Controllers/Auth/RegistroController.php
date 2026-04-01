<?php

declare(strict_types=1);

namespace Application\Controllers\Auth;

use Application\Controllers\WebController;
use Application\Core\Exceptions\ValidationException;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Enums\LogLevel;
use Application\Middlewares\CsrfMiddleware;
use Application\Services\Auth\AuthService;
use Application\Services\Auth\GoogleAuthService;
use Application\Services\Auth\RegistrationResponseHandler;
use Application\Services\Infrastructure\LogService;
use Application\Services\Infrastructure\TurnstileService;
use InvalidArgumentException;
use Throwable;

class RegistroController extends WebController
{
    private AuthService $authService;
    private GoogleAuthService $googleAuthService;
    private RegistrationResponseHandler $responseHandler;
    private ?TurnstileService $turnstile;

    public function __construct(
        ?AuthService $authService = null,
        ?GoogleAuthService $googleAuthService = null,
        ?RegistrationResponseHandler $responseHandler = null,
        ?TurnstileService $turnstile = null
    ) {
        parent::__construct();
        $this->authService = $authService ?? new AuthService();
        $this->googleAuthService = $googleAuthService ?? new GoogleAuthService();
        $this->responseHandler = $responseHandler ?? new RegistrationResponseHandler($this->request);
        $this->turnstile = $turnstile;
    }

    public function showForm(): Response
    {
        return $this->renderResponse('auth/register', [
            'errors' => $this->getSessionErrors(),
            'success' => $this->getSuccess(),
            'socialData' => $this->getSocialRegistrationData(),
        ]);
    }

    public function store(): Response
    {
        CsrfMiddleware::handle($this->request, 'register_form');
        $this->verifyTurnstileIfEnabled();

        $email = $this->request->postString('email', 'nao-informado');
        $socialData = $this->getSocialRegistrationData();
        $isGoogleRegistration = !empty($socialData) && ($socialData['provider'] ?? null) === 'google';

        try {
            $payload = $this->buildRegistrationPayload($isGoogleRegistration, $socialData);
            $result = $this->authService->register($payload);

            $this->clearSocialRegistrationData();

            if ($isGoogleRegistration) {
                return $this->handleGoogleRegistrationSuccess($result, $email);
            }

            $this->logRegistrationSuccess($email, $result, 'local');
            return $this->responseHandler->success($result, false);
        } catch (ValidationException $e) {
            return $this->handleValidationError($e, $email);
        } catch (Throwable $e) {
            return $this->handleRegistrationError($e, $email);
        }
    }

    private function buildRegistrationPayload(bool $isGoogle, ?array $socialData): array
    {
        $payload = [
            'name' => $this->request->postString('name', ''),
            'email' => $this->request->postString('email', ''),
            'referral_code' => $this->request->postString('referral_code', ''),
        ];

        if ($isGoogle) {
            $payload['google_id'] = $socialData['google_id'] ?? null;
            $payload['password'] = null;
            $payload['password_confirmation'] = null;
            $payload['provider'] = 'google';
        } else {
            $payload['password'] = $this->request->postString('password', '');
            $payload['password_confirmation'] = $this->request->postString('password_confirmation', '');
        }

        return $payload;
    }

    private function handleGoogleRegistrationSuccess(array $result, string $email): Response
    {
        $userId = $result['user_id'] ?? null;

        if ($userId) {
            $loginSuccess = $this->googleAuthService->loginAfterRegistration($userId, $email);

            if ($loginSuccess) {
                $this->logRegistrationSuccess($email, $result, 'google');
                return $this->responseHandler->success($result, true);
            }
        }

        LogService::warning('Login automatico falhou apos registro Google', ['email' => $email]);
        return $this->responseHandler->success($result, false);
    }

    private function logRegistrationSuccess(string $email, array $result, string $provider): void
    {
        LogService::info('Novo usuario registrado com sucesso.', [
            'email' => $email,
            'ip' => $this->request->ip() ?? 'unknown',
            'user_id' => $result['user_id'] ?? 'unknown',
            'provider' => $provider,
        ]);
    }

    private function handleValidationError(ValidationException $e, string $email): Response
    {
        LogService::persist(
            LogLevel::WARNING,
            LogCategory::AUTH,
            'Registro: falha de validacao',
            ['email' => $email, 'ip' => $this->request->ip() ?? 'unknown', 'errors' => $e->getErrors()]
        );

        return $this->responseHandler->validationError($e->getErrors());
    }

    private function handleRegistrationError(Throwable $e, string $email): Response
    {
        LogService::captureException($e, LogCategory::AUTH, [
            'action' => 'registro',
            'email' => $email,
            'ip' => $this->request->ip() ?? 'unknown',
        ]);

        return $this->responseHandler->generalError(
            $this->resolveRegistrationErrorMessage($e),
            $this->resolveRegistrationErrorStatus($e)
        );
    }

    private function resolveRegistrationErrorMessage(Throwable $e): string
    {
        if ($e instanceof InvalidArgumentException) {
            $message = trim($e->getMessage());
            if ($message !== '') {
                return $message;
            }
        }

        return 'Falha ao cadastrar. Tente novamente mais tarde.';
    }

    private function resolveRegistrationErrorStatus(Throwable $e): int
    {
        if (!$e instanceof InvalidArgumentException) {
            return 500;
        }

        $message = mb_strtolower(trim($e->getMessage()));

        if (str_contains($message, 'limite')) {
            return 429;
        }

        return 422;
    }

    private function getSessionErrors(): ?array
    {
        $errors = $this->pullSessionValue('form_errors');

        return is_array($errors) ? $errors : null;
    }

    private function verifyTurnstileIfEnabled(): void
    {
        if (!TurnstileService::isEnabled()) {
            return;
        }

        $token = $this->request->postString('cf-turnstile-response', '');
        $this->turnstileService()->verify($token, $this->request->ip() ?? 'unknown');
    }

    private function turnstileService(): TurnstileService
    {
        return $this->turnstile ??= new TurnstileService();
    }

    private function getSocialRegistrationData(): ?array
    {
        $socialData = $_SESSION['social_register'] ?? null;

        return is_array($socialData) ? $socialData : null;
    }

    private function clearSocialRegistrationData(): void
    {
        unset($_SESSION['social_register']);
    }
}
