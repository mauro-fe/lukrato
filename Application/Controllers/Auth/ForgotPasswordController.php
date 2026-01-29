<?php

namespace Application\Controllers\Auth;

use Application\Controllers\BaseController;
use Application\Core\Exceptions\ValidationException;
use Application\Middlewares\CsrfMiddleware;
use Application\Services\Auth\PasswordResetService;
use Application\Repositories\PasswordResetRepositoryEloquent;
use Application\Services\Auth\MailPasswordResetNotification;
use Application\Services\Auth\SecureTokenGenerator;
use Application\Services\MailService;
use Application\Services\LogService;
use Throwable;

class ForgotPasswordController extends BaseController
{
    private PasswordResetService $service;

    public function __construct()
    {
        parent::__construct();

        // Montagem de dependências (injeção manual)
        $repository = new PasswordResetRepositoryEloquent();
        $tokenGen   = new SecureTokenGenerator(64);
        $notifier   = new MailPasswordResetNotification(new MailService());

        $this->service = new PasswordResetService(
            repository: $repository,
            tokenGenerator: $tokenGen,
            notifier: $notifier
        );
    }

    /**
     * Exibe formulário de "esqueci minha senha"
     */
    public function showRequestForm(): void
    {
        $this->render('admin/admins/forgot_password', [
            'error'      => $this->getError(),
            'success'    => $this->getSuccess(),
        ]);
    }

    /**
     * Recebe email e envia o link de recuperação
     */
    public function sendResetLink(): void
    {
        $isJson = $this->request->wantsJson() || $this->request->isAjax();

        try {
            CsrfMiddleware::handle($this->request, 'forgot_form');

            $email = $this->request->post('email', '');

            $this->service->requestReset($email);

            if ($isJson) {
                $this->ok([
                    'message' => 'Se o e-mail existir no sistema, enviaremos um link de recuperação.',
                ]);
                return;
            }

            $this->setSuccess('Se o e-mail existir no sistema, enviaremos um link de recuperação.');
            $this->redirect('recuperar-senha');
        } catch (ValidationException $e) {

            if ($isJson) {
                $this->fail($e->getMessage(), 422, $e->getErrors());
                return;
            }

            $this->setError($e->getMessage());
            $this->redirect('recuperar-senha');
        } catch (Throwable $e) {

            LogService::error('Erro no envio de reset password', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            if ($isJson) {
                $this->fail('Erro inesperado ao enviar o link.', 500);
                return;
            }

            $this->setError('Erro inesperado ao enviar o link.');
            $this->redirect('recuperar-senha');
        }
    }

    /**
     * Exibe formulário de redefinição de senha
     */
    public function showResetForm(): void
    {
        $token = $this->getQuery('token', '');

        $reset = null;

        if ($token !== '') {
            $reset = $this->service->getValidReset($token);
        }

        if (!$reset) {
            $this->setError('Token inválido ou expirado.');
            $this->redirect('recuperar-senha');
            return;
        }

        $this->render('admin/admins/reset_password', [
            'token'      => $token,
        ]);
    }

    /**
     * Processa redefinição de senha
     */
    public function resetPassword(): void
    {
        $isJson = $this->request->wantsJson() || $this->request->isAjax();

        try {
            CsrfMiddleware::handle($this->request, 'reset_form');

            $token    = $this->request->post('token', '');
            $password = $this->request->post('password', '');
            $confirm  = $this->request->post('password_confirmation', '');

            $this->service->resetPassword($token, $password, $confirm);

            if ($isJson) {
                $this->ok([
                    'message' => 'Senha redefinida com sucesso! Faça login.'
                ]);
                return;
            }

            $this->setSuccess('Senha redefinida com sucesso! Faça login.');
            $this->redirect('login');
        } catch (ValidationException $e) {

            if ($isJson) {
                $this->fail($e->getMessage(), 422, $e->getErrors());
                return;
            }

            $this->setError($e->getMessage());
            $this->redirect('resetar-senha?token=' . urlencode($token));
        } catch (Throwable $e) {

            LogService::error('Erro ao redefinir senha', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            if ($isJson) {
                $this->fail('Erro inesperado ao redefinir senha.', 500);
                return;
            }

            $this->setError('Erro inesperado ao redefinir senha.');
            $this->redirect('login');
        }
    }
}
