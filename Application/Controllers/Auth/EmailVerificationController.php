<?php

declare(strict_types=1);

namespace Application\Controllers\Auth;

use Application\Config\AuthRuntimeConfig;
use Application\Controllers\WebController;
use Application\Core\Exceptions\ValidationException;
use Application\Core\Response;
use Application\Middlewares\CsrfMiddleware;
use Application\Models\Usuario;
use Application\Repositories\UsuarioRepository;
use Application\Services\Auth\EmailVerificationService;
use Application\Services\Infrastructure\CacheService;

class EmailVerificationController extends WebController
{
    private EmailVerificationService $verificationService;
    private UsuarioRepository $usuarioRepo;
    private AuthRuntimeConfig $runtimeConfig;

    public function __construct(
        ?EmailVerificationService $verificationService = null,
        ?CacheService $cache = null,
        ?UsuarioRepository $usuarioRepo = null,
        ?AuthRuntimeConfig $runtimeConfig = null
    ) {
        parent::__construct(cache: $cache);
        $this->verificationService = $this->resolveOrCreate($verificationService, EmailVerificationService::class);
        $this->usuarioRepo = $this->resolveOrCreate($usuarioRepo, UsuarioRepository::class);
        $this->runtimeConfig = $this->resolveOrCreate($runtimeConfig, AuthRuntimeConfig::class);
    }

    public function verify(): Response
    {
        $isJson = $this->request->wantsJson() || $this->request->isAjax();
        $token = $this->request->get('token', '');
        $selector = $this->request->get('selector', '');
        $validator = $this->request->get('validator', '');
        $result = $this->verificationService->verifyEmail($token, $selector, $validator);

        if (!empty($result['success'])) {
            unset($_SESSION['unverified_email'], $_SESSION['verification_expired'], $_SESSION['verification_user_id']);
        }

        if (!empty($result['expired']) && !empty($result['user_id'])) {
            $_SESSION['verification_expired'] = true;
            $_SESSION['verification_user_id'] = $result['user_id'];
        }

        if ($result['success']) {
            if ($isJson) {
                return $this->ok([
                    'message' => $result['message'],
                    'redirect' => $this->runtimeConfig->loginUrl(),
                ]);
            }

            $_SESSION['success'] = $result['message'];

            return $this->buildRedirectResponse($this->runtimeConfig->loginUrl());
        }

        if ($isJson) {
            $status = !empty($result['expired']) ? 410 : 422;
            $errors = [
                'redirect' => $this->runtimeConfig->loginUrl(),
            ];

            if (!empty($result['expired'])) {
                $errors['expired'] = true;
            }

            if (!empty($result['user_id'])) {
                $errors['user_id'] = $result['user_id'];
            }

            return $this->fail($result['message'], $status, $errors);
        }

        $_SESSION['error'] = $result['message'];

        return $this->buildRedirectResponse($this->runtimeConfig->loginUrl());
    }

    public function noticeData(): Response
    {
        $email = trim((string) ($_SESSION['unverified_email'] ?? ''));
        $expired = !empty($_SESSION['verification_expired']);
        $userId = $_SESSION['verification_user_id'] ?? null;

        if ($email === '' && !$expired && empty($userId)) {
            return $this->fail('Nenhum aviso de verificacao pendente encontrado.', 404, [
                'redirect' => $this->runtimeConfig->loginUrl(),
            ]);
        }

        $message = $expired
            ? 'Seu link de verificacao expirou. Solicite um novo email para continuar.'
            : 'Por favor, verifique seu email antes de fazer login.';

        return $this->ok([
            'message' => 'Aviso de verificacao carregado com sucesso.',
            'notice' => [
                'email' => $email,
                'message' => $message,
                'expired' => $expired,
            ],
            'actions' => [
                'login_url' => $this->runtimeConfig->loginUrl(),
                'resend_url' => $this->runtimeConfig->emailResendUrl(),
            ],
        ]);
    }

    public function resend(): Response
    {
        $isAjax = $this->request->isAjax();

        try {
            CsrfMiddleware::handle($this->request, 'verify_email_form');
        } catch (ValidationException) {
            CsrfMiddleware::handle($this->request, 'login_form');
        }

        try {
            $ip = $this->request->ip() ?? 'unknown';
            $this->cache->checkRateLimit('email-verification-resend:' . $ip, 3, 60);
        } catch (ValidationException $e) {
            if ($isAjax) {
                return Response::errorResponse('Muitas tentativas. Aguarde 1 minuto e tente novamente.', 429, $e->getErrors());
            }

            $_SESSION['error'] = 'Muitas tentativas. Aguarde 1 minuto e tente novamente.';

            return $this->buildRedirectResponse($this->runtimeConfig->loginUrl());
        }

        $email = $this->request->post('email', '');
        $userId = $_SESSION['verification_user_id'] ?? null;
        $user = null;

        if ($email !== '') {
            $user = $this->usuarioRepo->findByEmailOrPending($email);
        } elseif ($userId) {
            $user = Usuario::find($userId);
        }

        if (!$user) {
            if ($isAjax) {
                return Response::errorResponse('Usuario nao encontrado.', 404);
            }

            $_SESSION['error'] = 'Usuario nao encontrado. Verifique o email informado.';

            return $this->buildRedirectResponse($this->runtimeConfig->loginUrl());
        }

        $result = $this->verificationService->resendVerificationEmail($user);

        unset($_SESSION['verification_expired'], $_SESSION['verification_user_id']);

        if ($isAjax) {
            if ($result['success']) {
                return Response::successResponse(['message' => $result['message']]);
            }

            return Response::errorResponse($result['message'], 429);
        }

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        return $this->buildRedirectResponse($this->runtimeConfig->loginUrl());
    }

    public function notice(): Response
    {
        $token = trim((string) $this->request->get('token', ''));
        $selector = trim((string) $this->request->get('selector', ''));
        $validator = trim((string) $this->request->get('validator', ''));
        $hasVerificationCredentials = $token !== '' || ($selector !== '' && $validator !== '');
        $hasNoticeState = !empty($_SESSION['unverified_email'])
            || !empty($_SESSION['verification_expired'])
            || !empty($_SESSION['verification_user_id']);

        if (!$hasNoticeState && !$hasVerificationCredentials) {
            return $this->buildRedirectResponse($this->runtimeConfig->loginUrl());
        }

        if ($this->runtimeConfig->hasConfiguredVerifyEmailNoticeUrl()) {
            return $this->buildRedirectResponse($this->runtimeConfig->verifyEmailPageUrl($token, $selector, $validator));
        }

        return $this->renderResponse('admin/auth/verify-email', [
            'verifyEmailUrl' => $this->runtimeConfig->emailVerifyUrl(),
            'emailNoticeUrl' => $this->runtimeConfig->emailNoticeUrl(),
            'resendVerificationUrl' => $this->runtimeConfig->emailResendUrl(),
            'loginUrl' => $this->runtimeConfig->loginUrl(),
        ]);
    }
}
