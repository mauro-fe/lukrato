<?php

namespace Application\Controllers\Auth;

use Application\Controllers\BaseController;
use Application\Services\LogService;

class GoogleLoginController extends BaseController
{
    public function login(): void
    {
        // Se já estiver logado, redireciona
        if ($this->isAuthenticated()) {
            $this->redirect('dashboard');
            return;
        }

        try {
            // Carrega a biblioteca do Google
            require_once BASE_PATH . '/vendor/autoload.php';

            // Caminho absoluto do JSON de credenciais
            $credentialsPath = BASE_PATH . '/Application/Controllers/Auth/client_secret_2_941481750237-e5bnun64tunqirvmfa2ahs5l9cl1vf9e.apps.googleusercontent.com.json';

            if (!file_exists($credentialsPath) || !filesize($credentialsPath)) {
                throw new \Exception('Arquivo de credenciais do Google ausente ou vazio.');
            }

            // Cria o cliente Google
            $client = new \Google_Client();

            $client->setAuthConfig($credentialsPath);

            // Define o URI de redirecionamento
            $redirectUri = rtrim(BASE_URL, '/') . '/auth/google/callback';
            $client->setRedirectUri($redirectUri);

            // Define os escopos
            $client->addScope('email');
            $client->addScope('profile');

            // Gera a URL de autenticação e redireciona
            $authUrl = $client->createAuthUrl();

            header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
            exit();
        } catch (\Exception $e) {
            LogService::error('Erro ao iniciar login com Google', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            $this->setError('Erro ao redirecionar para o Google: ' . $e->getMessage());
            $this->redirect('login');
            return;
        }
    }
}
