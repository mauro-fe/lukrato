<?php

declare(strict_types=1);

namespace Application\Controllers\Auth;

use Application\Controllers\BaseController;
use Application\Services\Auth\EmailVerificationService;
use Application\Models\Usuario;
use Application\Core\Response;
use Application\Services\LogService;

/**
 * Controller para verificação de email
 */
class EmailVerificationController extends BaseController
{
    private EmailVerificationService $verificationService;

    public function __construct()
    {
        parent::__construct();
        $this->verificationService = new EmailVerificationService();
    }

    /**
     * Verifica o email usando o token da URL
     */
    public function verify(): void
    {
        $token = $this->request->get('token', '');

        $result = $this->verificationService->verifyEmail($token);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            Response::redirectTo(BASE_URL . 'login');
            return;
        }

        // Se o token expirou, mostra opção de reenviar
        if (!empty($result['expired']) && !empty($result['user_id'])) {
            $_SESSION['verification_expired'] = true;
            $_SESSION['verification_user_id'] = $result['user_id'];
        }

        $_SESSION['error'] = $result['message'];
        Response::redirectTo(BASE_URL . 'login');
    }

    /**
     * Reenvia email de verificação
     */
    public function resend(): void
    {
        $isAjax = $this->request->isAjax();

        // Tenta buscar usuário por email ou pelo ID da sessão
        $email = $this->request->post('email', '');
        $userId = $_SESSION['verification_user_id'] ?? null;

        $user = null;

        if (!empty($email)) {
            $user = Usuario::whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->first();
        } elseif ($userId) {
            $user = Usuario::find($userId);
        }

        if (!$user) {
            if ($isAjax) {
                Response::error('Usuário não encontrado.', 404);
                return;
            }

            $_SESSION['error'] = 'Usuário não encontrado. Verifique o email informado.';
            Response::redirectTo(BASE_URL . 'login');
            return;
        }

        $result = $this->verificationService->resendVerificationEmail($user);

        // Limpa dados da sessão
        unset($_SESSION['verification_expired'], $_SESSION['verification_user_id']);

        if ($isAjax) {
            if ($result['success']) {
                Response::success(['message' => $result['message']]);
            } else {
                Response::error($result['message'], 429);
            }
            return;
        }

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        Response::redirectTo(BASE_URL . 'login');
    }

    /**
     * Exibe página de aviso para verificar email (para usuários não verificados)
     */
    public function notice(): void
    {
        // Se não tem usuário identificado, redireciona para login
        if (empty($_SESSION['unverified_email'])) {
            Response::redirectTo(BASE_URL . 'login');
            return;
        }

        $email = $_SESSION['unverified_email'];

        $this->render('admin/admins/verify_email', [
            'email' => $email,
            'message' => $_SESSION['verification_message'] ?? 'Por favor, verifique seu email antes de fazer login.',
        ]);
    }
}
