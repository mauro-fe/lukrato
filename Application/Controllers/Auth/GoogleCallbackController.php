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

            // Se já está logado, redireciona

            if ($this->isAuthenticated()) {

                $this->redirect('dashboard');

                return;
            }



            // Verifica se recebeu o código de autorização

            $code = $this->getQuery('code');

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



            // Carrega a biblioteca do Google

            require_once BASE_PATH . '/vendor/autoload.php';



            // Cria o cliente Google

            $client = new \Google_Client();



            // Carrega as credenciais do arquivo JSON

            $credentialsPath = BASE_PATH . '/Application/Controllers/Auth/client_secret_2_941481750237-e5bnun64tunqirvmfa2ahs5l9cl1vf9e.apps.googleusercontent.com.json';

            $client->setAuthConfig($credentialsPath);



            // Define o URI de redirecionamento (mesmo do GoogleLoginController)

            $redirectUri = rtrim(BASE_URL, '/') . '/auth/google/callback';

            $client->setRedirectUri($redirectUri);



            // Log para debug

            LogService::info('Tentativa de callback Google', [

                'redirect_uri' => $redirectUri,

                'code_received' => !empty($code),

                'base_url' => BASE_URL

            ]);



            // Troca o código pelo token de acesso

            $token = $client->fetchAccessTokenWithAuthCode($code);



            if (isset($token['error'])) {

                throw new \Exception("Erro ao buscar token: " . $token['error_description']);
            }



            // Define o token de acesso

            $client->setAccessToken($token);



            // Obtém as informações do usuário

            $google_oauth = new Oauth2($client);

            $user_info = $google_oauth->userinfo->get();



            $google_id = $user_info->id;

            $nome_completo = $user_info->name;

            $email = $user_info->email;

            $foto_perfil = $user_info->picture;



            // Verifica se o usuário já existe na tabela usuarios por email

            $usuario = Usuario::where('email', $email)->first();



            if ($usuario) {

                // Usuário já existe, apenas atualiza o nome se necessário

                if (empty($usuario->nome) || $usuario->nome !== $nome_completo) {

                    $usuario->nome = $nome_completo;

                    $usuario->save();
                }
            } else {

                // Cria novo usuário

                $usuario = new Usuario();

                $usuario->nome = $nome_completo;

                $usuario->email = $email;

                $usuario->username = $this->generateUsername($email);

                $usuario->senha = ''; // Senha vazia para logins do Google

                $usuario->save();
            }



            // Faz login do usuário

            Auth::login($usuario);



            // Define informações adicionais na sessão

            $_SESSION['usuario_email'] = $email;

            $_SESSION['usuario_foto'] = $foto_perfil;

            $_SESSION['login_tipo'] = 'google';

            $_SESSION['google_id'] = $google_id;



            // Registra log de sucesso

            LogService::info('Login com Google realizado com sucesso', [

                'user_id' => $usuario->id,

                'email' => $email,

                'google_id' => $google_id

            ]);



            // Redireciona para dashboard

            $this->redirect('dashboard');
        } catch (\Exception $e) {

            LogService::error('Erro no callback do Google', [

                'message' => $e->getMessage(),

                'file' => $e->getFile(),

                'line' => $e->getLine(),

                'code' => $this->getQuery('code', 'N/A')

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

        $counter = 1;



        // Verifica se já existe e adiciona número se necessário

        while (Usuario::where('username', $username)->exists()) {

            $username = $base . $counter;

            $counter++;
        }



        return $username;
    }
}