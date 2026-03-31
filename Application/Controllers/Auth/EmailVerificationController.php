<?php

declare(strict_types=1);

namespace Application\Controllers\Auth;

use Application\Controllers\BaseController;
use Application\Core\Exceptions\ValidationException;
use Application\Core\Response;
use Application\Middlewares\CsrfMiddleware;
use Application\Models\Usuario;
use Application\Repositories\UsuarioRepository;
use Application\Services\Auth\EmailVerificationService;
use Application\Services\Infrastructure\CacheService;

class EmailVerificationController extends BaseController
{
    private EmailVerificationService $verificationService;
    private UsuarioRepository $usuarioRepo;

    public function __construct(
        ?EmailVerificationService $verificationService = null,
        ?CacheService $cache = null,
        ?UsuarioRepository $usuarioRepo = null
    )
    {
        parent::__construct(cache: $cache);
        $this->verificationService = $verificationService ?? new EmailVerificationService();
        $this->usuarioRepo = $usuarioRepo ?? new UsuarioRepository();
    }

    public function verify(): Response
    {
        $token = $this->request->get('token', '');
        $selector = $this->request->get('selector', '');
        $validator = $this->request->get('validator', '');
        $result = $this->verificationService->verifyEmail($token, $selector, $validator);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];

            return $this->buildRedirectResponse('login');
        }

        if (!empty($result['expired']) && !empty($result['user_id'])) {
            $_SESSION['verification_expired'] = true;
            $_SESSION['verification_user_id'] = $result['user_id'];
        }

        $_SESSION['error'] = $result['message'];

        return $this->buildRedirectResponse('login');
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

            return $this->buildRedirectResponse('login');
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

            return $this->buildRedirectResponse('login');
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

        return $this->buildRedirectResponse('login');
    }

    public function notice(): Response
    {
        if (empty($_SESSION['unverified_email'])) {
            return $this->buildRedirectResponse('login');
        }

        return $this->renderResponse('admin/auth/verify-email', [
            'email' => $_SESSION['unverified_email'],
            'message' => 'Por favor, verifique seu email antes de fazer login.',
        ]);
    }
}
