<?php

namespace Application\Controllers\Auth;

use Application\Controllers\WebController;
use Application\Core\Exceptions\ValidationException;
use Application\Core\Response;
use Application\Middlewares\CsrfMiddleware;
use Application\Repositories\PasswordResetRepositoryEloquent;
use Application\Services\Auth\MailPasswordResetNotification;
use Application\Services\Auth\PasswordResetService;
use Application\Services\Auth\SecureTokenGenerator;
use Application\Services\Communication\MailService;
use Application\Services\Infrastructure\CacheService;
use Application\Services\Infrastructure\LogService;
use Throwable;

class ForgotPasswordController extends WebController
{
    private PasswordResetService $service;

    public function __construct(?PasswordResetService $service = null, ?CacheService $cache = null)
    {
        parent::__construct(cache: $cache);

        if ($service !== null) {
            $this->service = $service;
            return;
        }

        $repository = new PasswordResetRepositoryEloquent();
        $tokenGen = new SecureTokenGenerator(64);
        $notifier = new MailPasswordResetNotification(new MailService());

        $this->service = new PasswordResetService(
            repository: $repository,
            tokenGenerator: $tokenGen,
            notifier: $notifier
        );
    }

    public function showRequestForm(): Response
    {
        return $this->renderResponse('admin/auth/forgot-password', [
            'error' => $this->getError(),
            'success' => $this->getSuccess(),
        ]);
    }

    public function sendResetLink(): Response
    {
        $isJson = $this->request->wantsJson() || $this->request->isAjax();

        try {
            CsrfMiddleware::handle($this->request, 'forgot_form');
            $this->applyRateLimit('password-reset-request', 3, 60);

            $email = $this->request->post('email', '');
            $this->service->requestReset($email);

            if ($isJson) {
                return $this->ok([
                    'message' => 'Se o e-mail existir no sistema, enviaremos um link de recuperacao.',
                ]);
            }

            $this->setSuccess('Se o e-mail existir no sistema, enviaremos um link de recuperacao.');
            return $this->buildRedirectResponse('recuperar-senha');
        } catch (ValidationException $e) {
            if ($this->isRateLimitException($e)) {
                if ($isJson) {
                    return $this->fail('Muitas tentativas. Aguarde 1 minuto e tente novamente.', 429, $e->getErrors());
                }

                $this->setError('Muitas tentativas. Aguarde 1 minuto e tente novamente.');
                return $this->buildRedirectResponse('recuperar-senha');
            }

            if ($isJson) {
                return $this->fail($this->validationMessage($e), 422, $e->getErrors());
            }

            $this->setError($this->validationMessage($e));
            return $this->buildRedirectResponse('recuperar-senha');
        } catch (Throwable $e) {
            LogService::error('Erro no envio de reset password', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            if ($isJson) {
                return $this->fail('Erro inesperado ao enviar o link.', 500);
            }

            $this->setError('Erro inesperado ao enviar o link.');
            return $this->buildRedirectResponse('recuperar-senha');
        }
    }

    public function showResetForm(): Response
    {
        $token = $this->getStringQuery('token');
        $selector = $this->getStringQuery('selector');
        $validator = $this->getStringQuery('validator');
        $reset = null;

        if ($token !== '' || ($selector !== '' && $validator !== '')) {
            $reset = $this->service->getValidReset($token, $selector, $validator);
        }

        if (!$reset) {
            $this->setError('Token invalido ou expirado.');
            return $this->buildRedirectResponse('recuperar-senha');
        }

        return $this->renderResponse('admin/auth/reset-password', [
            'token' => $token,
            'selector' => $selector,
            'validator' => $validator,
        ]);
    }

    public function resetPassword(): Response
    {
        $isJson = $this->request->wantsJson() || $this->request->isAjax();
        $token = '';
        $selector = '';
        $validator = '';

        try {
            CsrfMiddleware::handle($this->request, 'reset_form');
            $this->applyRateLimit('password-reset-submit', 3, 60);

            $token = $this->request->post('token', '');
            $selector = $this->request->post('selector', '');
            $validator = $this->request->post('validator', '');
            $password = $this->request->post('password', '');
            $confirm = $this->request->post('password_confirmation', '');

            $this->service->resetPassword($token, $password, $confirm, $selector, $validator);

            if ($isJson) {
                return $this->ok([
                    'message' => 'Senha redefinida com sucesso! Faca login.',
                ]);
            }

            $this->setSuccess('Senha redefinida com sucesso! Faca login.');
            return $this->buildRedirectResponse('login');
        } catch (ValidationException $e) {
            if ($this->isRateLimitException($e)) {
                if ($isJson) {
                    return $this->fail('Muitas tentativas. Aguarde 1 minuto e tente novamente.', 429, $e->getErrors());
                }

                $this->setError('Muitas tentativas. Aguarde 1 minuto e tente novamente.');
                return $this->buildRedirectResponse($this->buildResetPasswordUrl($token, $selector, $validator));
            }

            if ($isJson) {
                return $this->fail($this->validationMessage($e), 422, $e->getErrors());
            }

            $this->setError($this->validationMessage($e));
            return $this->buildRedirectResponse($this->buildResetPasswordUrl($token, $selector, $validator));
        } catch (Throwable $e) {
            LogService::error('Erro ao redefinir senha', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            if ($isJson) {
                return $this->fail('Erro inesperado ao redefinir senha.', 500);
            }

            $this->setError('Erro inesperado ao redefinir senha.');
            return $this->buildRedirectResponse('login');
        }
    }

    private function buildResetPasswordUrl(string $token, string $selector, string $validator): string
    {
        if ($selector !== '' && $validator !== '') {
            return 'resetar-senha?selector=' . urlencode($selector) . '&validator=' . urlencode($validator);
        }

        return 'resetar-senha?token=' . urlencode($token);
    }

    private function applyRateLimit(string $action, int $limit, int $seconds): void
    {
        $this->cache->checkRateLimit(
            $action . ':' . ($this->request->ip() ?? 'unknown'),
            $limit,
            $seconds
        );
    }

    private function isRateLimitException(ValidationException $e): bool
    {
        return isset($e->getErrors()['rate_limit']) || $e->getCode() === 429;
    }

    private function validationMessage(ValidationException $e): string
    {
        foreach ($e->getErrors() as $value) {
            if (is_array($value)) {
                foreach ($value as $message) {
                    $message = trim((string) $message);
                    if ($message !== '') {
                        return $message;
                    }
                }

                continue;
            }

            $message = trim((string) $value);
            if ($message !== '') {
                return $message;
            }
        }

        return 'Dados invalidos.';
    }
}
