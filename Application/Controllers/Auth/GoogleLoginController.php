<?php

declare(strict_types=1);

namespace Application\Controllers\Auth;

use Application\Controllers\BaseController;
use Application\Services\Auth\GoogleAuthService;
use Application\Services\LogService;
use Exception;

/**
 * Controller para iniciar login com Google OAuth
 */
class GoogleLoginController extends BaseController
{
    private GoogleAuthService $googleAuthService;

    public function __construct(?GoogleAuthService $googleAuthService = null)
    {
        parent::__construct();
        $this->googleAuthService = $googleAuthService ?? new GoogleAuthService();
    }

    public function login(): void
    {
        // Se já estiver logado, redireciona para dashboard
        if ($this->isAuthenticated()) {
            $this->redirect('dashboard');
            return;
        }

        try {
            $authUrl = $this->googleAuthService->getAuthUrl();
            
            LogService::info('Redirecionando para login do Google', [
                'redirect_uri' => rtrim(BASE_URL, '/') . '/auth/google/callback',
            ]);

            header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
            exit();
        } catch (Exception $e) {
            LogService::error('Erro ao iniciar login com Google', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $this->setError('Erro ao redirecionar para o Google: ' . $e->getMessage());
            $this->redirect('login');
        }
    }
}

