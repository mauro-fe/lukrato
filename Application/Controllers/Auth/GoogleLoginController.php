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

            LogService::info('Iniciando login com Google OAuth', [
                'client_id'    => $_ENV['GOOGLE_CLIENT_ID'] ?? null,
                'redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'] ?? null,
                'auth_url'     => $authUrl,
            ]);

            header('Location: ' . $authUrl);
            exit;
        } catch (Exception $e) {
            LogService::error('Erro ao iniciar login com Google', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            $this->setError('Não foi possível conectar ao Google. Tente novamente.');
            $this->redirect('login');
        }
    }
}
