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

            // Processa autenticação via Google
            $result = $this->googleAuthService->handleCallback($code);
            
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
                $this->redirect('login?new_google=1');
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

