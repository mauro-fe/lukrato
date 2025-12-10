<?php

namespace Application\Controllers\Auth;

use Application\Controllers\BaseController;
use Application\Models\Usuario;
use Application\Lib\Auth;
use Application\Services\LogService;
use Google\Service\Oauth2;

class GoogleCallbackController extends BaseController
{
    public function callback(): void
    {
        try {
            if ($this->isAuthenticated()) {
                $this->redirect('dashboard');
                return;
            }

            $code  = $this->getQuery('code');
            $error = $this->getQuery('error');

            if ($error) {
                LogService::error('Erro no callback do Google - OAuth error', [
                    'error' => $error,
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
                // Atualiza nome se quiser mantê-lo mais recente
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

                // Se o nome estiver vazio, atualiza
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

                // Opcional: mensagem amigável
                $this->setSuccess('Conectamos sua conta ao Google. Agora você pode entrar com um clique em "Entrar com Google".');

                $this->redirect('dashboard');
                return;
            }

            // ---------------------------------------------------------------------
            // 3) NÃO EXISTE NENHUM USUÁRIO COM ESTE EMAIL → ENVIAR PARA CADASTRO
            // ---------------------------------------------------------------------

            // Guarda dados do Google na sessão para completar o cadastro
            $_SESSION['social_register'] = [
                'provider'   => 'google',
                'google_id'  => $google_id,
                'nome'       => $nome_completo,
                'email'      => $email,
                'foto'       => $foto_perfil,
            ];

            LogService::info('Iniciando cadastro via Google (novo usuário)', [
                'email'     => $email,
                'google_id' => $google_id
            ]);

            $this->setSuccess('Encontramos sua conta do Google. Complete seu cadastro para começar a usar o Lukrato.');

            // Redireciona para a página de cadastro normal
            $this->redirect('login');
            return;

        } catch (\Exception $e) {
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
     * Gera um username único baseado no email
     */
    private function generateUsername(string $email): string
    {
        $base = strtolower(trim(explode('@', $email)[0]));
        $base = preg_replace('/[^a-z0-9_]/', '', $base);

        if (strlen($base) < 3) {
            $base = 'user' . $base;
        }

        $username = $base;
        $counter  = 1;

        while (Usuario::where('username', $username)->exists()) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
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
