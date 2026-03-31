<?php

declare(strict_types=1);

namespace Application\Controllers\Auth;

use Application\Controllers\WebController;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Services\Auth\GoogleAuthService;
use Application\Services\Infrastructure\LogService;
use Exception;
use Throwable;

class GoogleCallbackController extends WebController
{
    private GoogleAuthService $googleAuthService;

    public function __construct(?GoogleAuthService $googleAuthService = null)
    {
        parent::__construct();
        $this->googleAuthService = $googleAuthService ?? new GoogleAuthService();
    }

    public function callback(): Response
    {
        try {
            LogService::info('Google callback iniciado', [
                'query_params' => $_GET,
                'env_redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'] ?? 'NAO DEFINIDO',
                'base_url' => BASE_URL,
            ]);

            if ($this->isAuthenticated()) {
                return $this->buildRedirectResponse('dashboard');
            }

            $this->validateCallbackParams();
            $code = $this->getQuery('code');

            LogService::info('Processando callback do Google', [
                'redirect_uri' => rtrim(BASE_URL, '/') . '/auth/google/callback',
                'base_url' => BASE_URL,
                'code_received' => !empty($code),
            ]);

            $result = $this->googleAuthService->handleCallback($code);

            if ($result['needs_confirmation'] ?? false) {
                $_SESSION['google_pending_user'] = $result['user_info'];
                return $this->buildRedirectResponse('auth/google/confirm-page');
            }

            $usuario = $result['usuario'];
            $isNew = $result['is_new'];

            $userInfo = [
                'id' => $usuario->google_id,
                'email' => $usuario->email,
                'picture' => $_SESSION['google_user_picture'] ?? null,
            ];

            $this->googleAuthService->loginUser($usuario, $userInfo);

            $intended = $_SESSION['login_intended'] ?? '';
            unset($_SESSION['login_intended']);

            if ($isNew) {
                return $this->buildRedirectResponse('dashboard?welcome=1');
            }

            if ($intended !== '' && preg_match('#^[a-zA-Z0-9/_\-]+$#', $intended)) {
                return $this->buildRedirectResponse($intended);
            }

            return $this->buildRedirectResponse('dashboard');
        } catch (Exception $e) {
            return $this->handleCallbackError($e);
        } catch (Throwable $e) {
            return $this->handleCallbackError($e);
        }
    }

    public function confirmPage(): Response
    {
        if ($this->isAuthenticated()) {
            return $this->buildRedirectResponse('dashboard');
        }

        $googleData = $_SESSION['google_pending_user'] ?? null;
        if (!$googleData) {
            return $this->buildRedirectResponse('login');
        }

        return $this->renderResponse('site/auth/google-confirm', [
            'googleData' => $googleData,
        ]);
    }

    public function confirm(): Response
    {
        try {
            if ($this->isAuthenticated()) {
                return $this->buildRedirectResponse('dashboard');
            }

            $pendingUser = $_SESSION['google_pending_user'] ?? null;
            if (!$pendingUser) {
                $this->setError('Sessão expirada. Tente novamente.');
                return $this->buildRedirectResponse('login');
            }

            $referralCode = $_SESSION['pending_referral_code'] ?? '';
            $usuario = $this->googleAuthService->createUserFromPending($pendingUser, $referralCode);

            unset($_SESSION['google_pending_user'], $_SESSION['pending_referral_code']);

            $userInfo = [
                'id' => $usuario->google_id,
                'email' => $usuario->email,
                'picture' => $pendingUser['picture'] ?? null,
            ];
            $this->googleAuthService->loginUser($usuario, $userInfo);

            unset($_SESSION['login_intended']);

            LogService::info('Conta criada via Google após confirmação', [
                'user_id' => $usuario->id,
                'email' => $usuario->email,
            ]);

            return $this->buildRedirectResponse('dashboard?welcome=1');
        } catch (Exception $e) {
            LogService::captureException($e, LogCategory::AUTH, [
                'action' => 'google_confirm_account',
            ]);
            $this->setError($this->internalErrorMessage(
                $e,
                'Erro ao criar conta com Google. Tente novamente.',
                ['action' => 'google_confirm_account']
            ));

            return $this->buildRedirectResponse('login');
        }
    }

    public function cancel(): Response
    {
        unset($_SESSION['google_pending_user']);
        $this->setError('Cadastro cancelado.');

        return $this->buildRedirectResponse('login');
    }

    /**
     * @throws Exception
     */
    private function validateCallbackParams(): void
    {
        $error = $this->getQuery('error');

        if ($error) {
            $errorDescription = $this->getQuery('error_description', 'Erro desconhecido');

            LogService::error('Erro no callback do Google - OAuth error', [
                'error' => $error,
                'error_description' => $errorDescription,
            ]);

            throw new Exception("Erro na autenticação com Google: {$error}");
        }

        $code = $this->getQuery('code');

        if (!$code) {
            throw new Exception('Nenhum código de autorização recebido');
        }
    }

    private function handleCallbackError(Throwable $e): Response
    {
        LogService::captureException($e, LogCategory::AUTH, [
            'action' => 'google_callback',
            'code' => $this->getQuery('code', 'N/A'),
        ]);

        $this->setError($this->internalErrorMessage(
            $e,
            'Erro ao processar login com Google. Tente novamente.',
            ['action' => 'google_callback']
        ));

        return $this->buildRedirectResponse('login');
    }
}
