<?php

namespace Application\Controllers\Auth;

use Application\Controllers\BaseController;
use Application\Models\Usuario;
use Application\Lib\Auth;
use Application\Services\Auth\AuthService;
use Application\Services\LogService;
use Google\Service\Oauth2;
use Throwable;

class GoogleCallbackController extends BaseController
{
    public function callback(): void
    {
        try {
            // Se já estiver logado, só vai pro dashboard
            if ($this->isAuthenticated()) {
                $this->redirect('dashboard');
                return;
            }

            $code  = $this->getQuery('code');
            $error = $this->getQuery('error');

            if ($error) {
                LogService::error('Erro no callback do Google - OAuth error', [
                    'error'             => $error,
                    'error_description' => $this->getQuery('error_description', 'N/A')
                ]);
                $this->setError('Erro na autenticação com Google: ' . $error);
                $this->redirect('login');
                return;
            }

            if (!$code) {
                $this->setError('Erro: Nenhum código de autorização recebido.');
                $this->redirect('login');
                return;
            }

            // --- CLIENTE GOOGLE ---

            $client = new \Google_Client();

            $credentialsPath = BASE_PATH . '/Application/Controllers/Auth/client_secret_2_941481750237-e5bnun64tunqirvmfa2ahs5l9cl1vf9e.apps.googleusercontent.com.json';
            $client->setAuthConfig($credentialsPath);

            $redirectUri = rtrim(BASE_URL, '/') . '/auth/google/callback';
            $client->setRedirectUri($redirectUri);

            LogService::info('Tentativa de callback Google', [
                'redirect_uri'  => $redirectUri,
                'code_received' => !empty($code),
                'base_url'      => BASE_URL
            ]);

            // --- TROCA CÓDIGO POR TOKEN ---

            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                throw new \Exception("Erro ao buscar token: " . $token['error_description']);
            }

            $client->setAccessToken($token);

            // --- PEGA DADOS DO USUÁRIO NO GOOGLE ---

            $google_oauth = new Oauth2($client);
            $user_info    = $google_oauth->userinfo->get();

            $google_id     = $user_info->id;
            $nome_completo = $user_info->name;
            $email         = $user_info->email;
            $foto_perfil   = $user_info->picture;

            // ---------------------------------------------------------------------
            // 1) TENTA ACHAR USUÁRIO POR google_id (CONTA JÁ VINCULADA)
            // ---------------------------------------------------------------------

            $usuario = Usuario::where('google_id', $google_id)->first();

            if ($usuario) {
                // opcional: atualiza nome
                if (!empty($nome_completo) && $usuario->nome !== $nome_completo) {
                    $usuario->nome = $nome_completo;
                    $usuario->save();
                }

                Auth::login($usuario);
                $this->setGoogleSessionData($usuario, $email, $foto_perfil, $google_id);

                LogService::info('Login com Google realizado (google_id já vinculado)', [
                    'user_id'   => $usuario->id,
                    'email'     => $email,
                    'google_id' => $google_id
                ]);

                $this->redirect('dashboard');
                return;
            }

            // ---------------------------------------------------------------------
            // 2) NÃO TEM google_id. TENTA ACHAR POR EMAIL (CONTA LOCAL EXISTENTE)
            // ---------------------------------------------------------------------

            $usuario = Usuario::where('email', $email)->first();

            if ($usuario) {
                // VINCULA o Google a essa conta existente
                $usuario->google_id = $google_id;

                if (empty($usuario->nome) && !empty($nome_completo)) {
                    $usuario->nome = $nome_completo;
                }

                $usuario->save();

                Auth::login($usuario);
                $this->setGoogleSessionData($usuario, $email, $foto_perfil, $google_id);

                LogService::info('Conta existente vinculada ao Google com sucesso.', [
                    'user_id'   => $usuario->id,
                    'email'     => $email,
                    'google_id' => $google_id
                ]);

                $this->redirect('dashboard');
                return;
            }

            // ---------------------------------------------------------------------
            // 3) NENHUM USUÁRIO COM ESTE EMAIL → CRIAR NOVO VIA AuthService E LOGAR
            // ---------------------------------------------------------------------

            $authService = new AuthService(); // supondo construtor flexível

            // Senha aleatória forte para satisfazer a validação
            $randomPassword = bin2hex(random_bytes(16)); // 32 caracteres hex

            $payload = [
                'name'                  => $nome_completo ?: strtok($email, '@'),
                'email'                 => $email,
                'password'              => $randomPassword,
                'password_confirmation' => $randomPassword,
                'google_id'             => $google_id,
                'provider'              => 'google', // só pra log/controle interno se vc usar
            ];

            $result = $authService->register($payload);

            $userId  = $result['user_id'] ?? null;
            $usuario = null;

            if ($userId) {
                $usuario = Usuario::find($userId);
            }

            if (!$usuario) {
                $usuario = Usuario::where('email', $email)->first();
            }

            if (!$usuario) {
                throw new \Exception('Usuário não pôde ser criado via Google.');
            }

            Auth::login($usuario);
            $this->setGoogleSessionData($usuario, $email, $foto_perfil, $google_id);

            LogService::info('Novo usuário criado e logado via Google.', [
                'user_id'   => $usuario->id,
                'email'     => $email,
                'google_id' => $google_id
            ]);

            $this->redirect('login?new_google=1');
            return;
        } catch (Throwable $e) {
            LogService::error('Erro no callback do Google', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'code'    => $this->getQuery('code', 'N/A')
            ]);

            $this->setError('Erro ao processar login com Google: ' . $e->getMessage());
            $this->redirect('login');
        }
    }

    /**
     * Centraliza o que você joga na sessão depois do login Google
     */
    private function setGoogleSessionData(Usuario $usuario, string $email, ?string $foto_perfil, string $google_id): void
    {
        $_SESSION['usuario_email'] = $email;
        $_SESSION['usuario_foto']  = $foto_perfil;
        $_SESSION['login_tipo']    = 'google';
        $_SESSION['google_id']     = $google_id;
    }
}
