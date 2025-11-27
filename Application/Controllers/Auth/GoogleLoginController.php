<?php



namespace Application\Controllers\Auth;



use Application\Controllers\BaseController;



class GoogleLoginController extends BaseController

{

    public function login(): void

    {

        // Se já está logado, redireciona

        if ($this->isAuthenticated()) {

            $this->redirect('dashboard');

            return;
        }



        // Carrega a biblioteca do Google

        require_once BASE_PATH . '/vendor/autoload.php';



        // Cria o cliente Google

        $client = new \Google_Client();



        // Carrega as credenciais do arquivo JSON

        // O caminho correto, partindo da raiz do projeto (BASE_PATH)
        $credentialsPath = BASE_PATH . '/Application/Controllers/Auth/client_secret_2_941481750237-e5bnun64tunqirvmfa2ahs5l9cl1vf9e.apps.googleusercontent.com.json';
        $client->setAuthConfig($credentialsPath);



        // Define o URI de redirecionamento

        $redirectUri = rtrim(BASE_URL, '/') . '/auth/google/callback';

        $client->setRedirectUri($redirectUri);



        // Define os escopos

        $client->addScope('email');

        $client->addScope('profile');



        // Gera a URL de autenticação e redireciona

        $auth_url = $client->createAuthUrl();

        header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));

        exit();
    }
}