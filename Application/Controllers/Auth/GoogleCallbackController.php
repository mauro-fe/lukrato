<?php

declare(strict_types=1);

namespace Application\Controllers\Auth;

use Application\Controllers\BaseController;
use Application\Services\Auth\GoogleAuthService;
use Application\Services\LogService;
use Exception;
use Throwable;

/**
 * Controller para processar callback do Google OAuth
 */
class GoogleCallbackController extends BaseController
{
    private GoogleAuthService $googleAuthService;

    public function __construct(?GoogleAuthService $googleAuthService = null)
    {
        parent::__construct();
        $this->googleAuthService = $googleAuthService ?? new GoogleAuthService();
    }

    public function callback(): void
    {
        try {
            LogService::info('Google callback iniciado', [
                'query_params' => $_GET,
                'env_redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'] ?? 'NÃO DEFINIDO',
                'base_url' => BASE_URL,
            ]);

            // Se já estiver logado, redireciona para dashboard
            if ($this->isAuthenticated()) {
                $this->redirect('dashboard');
                return;
            }

            // Valida parâmetros do callback
            $this->validateCallbackParams();

            $code = $this->getQuery('code');

            LogService::info('Processando callback do Google', [
                'redirect_uri' => rtrim(BASE_URL, '/') . '/auth/google/callback',
                'base_url' => BASE_URL,
                'code_received' => !empty($code),
            ]);

            // Processa autenticação via Google (verifica se usuário existe)
            $result = $this->googleAuthService->handleCallback($code);

            // Se precisa confirmar criação de conta
            if ($result['needs_confirmation'] ?? false) {
                $_SESSION['google_pending_user'] = $result['user_info'];
                $this->redirect('auth/google/confirm-page');
                return;
            }

            $usuario = $result['usuario'];
            $isNew = $result['is_new'];

            // Obtém dados do usuário para sessão
            $userInfo = [
                'id' => $usuario->google_id,
                'email' => $usuario->email,
                'picture' => $_SESSION['google_user_picture'] ?? null,
            ];

            // Realiza login
            $this->googleAuthService->loginUser($usuario, $userInfo);

            // Redireciona
            if ($isNew) {
                $this->redirect('dashboard?welcome=1');
            } else {
                $this->redirect('dashboard');
            }
        } catch (Exception $e) {
            $this->handleCallbackError($e);
        } catch (Throwable $e) {
            $this->handleCallbackError($e);
        }
    }

    /**
     * Exibe página de confirmação para criar conta
     */
    public function confirmPage(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('dashboard');
            return;
        }

        if (empty($_SESSION['google_pending_user'])) {
            $this->redirect('login');
            return;
        }

        require BASE_PATH . '/views/site/auth/google-confirm.php';
    }

    /**
     * Confirma criação da conta via Google
     */
    public function confirm(): void
    {
        try {
            if ($this->isAuthenticated()) {
                $this->redirect('dashboard');
                return;
            }

            $pendingUser = $_SESSION['google_pending_user'] ?? null;
            if (!$pendingUser) {
                $this->setError('Sessão expirada. Tente novamente.');
                $this->redirect('login');
                return;
            }

            // Cria o usuário
            $usuario = $this->googleAuthService->createUserFromPending($pendingUser);

            // Limpa dados pendentes
            unset($_SESSION['google_pending_user']);

            // Faz login
            $userInfo = [
                'id' => $usuario->google_id,
                'email' => $usuario->email,
                'picture' => $pendingUser['picture'] ?? null,
            ];
            $this->googleAuthService->loginUser($usuario, $userInfo);

            LogService::info('Conta criada via Google após confirmação', [
                'user_id' => $usuario->id,
                'email' => $usuario->email,
            ]);

            $this->redirect('dashboard?welcome=1');
        } catch (Exception $e) {
            LogService::error('Erro ao confirmar criação de conta Google', [
                'message' => $e->getMessage(),
            ]);
            $this->setError('Erro ao criar conta: ' . $e->getMessage());
            $this->redirect('login');
        }
    }

    /**
     * Cancela criação da conta via Google
     */
    public function cancel(): void
    {
        unset($_SESSION['google_pending_user']);
        $this->setError('Cadastro cancelado.');
        $this->redirect('login');
    }

    /**
     * Valida parâmetros recebidos no callback
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

    /**
     * Trata erros do callback
     */
    private function handleCallbackError(Throwable $e): void
    {
        LogService::error('Erro no callback do Google', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'code' => $this->getQuery('code', 'N/A'),
        ]);

        $this->setError('Erro ao processar login com Google: ' . $e->getMessage());
        $this->redirect('login');
    }
}
