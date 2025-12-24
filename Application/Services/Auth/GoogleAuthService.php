<?php

declare(strict_types=1);

namespace Application\Services\Auth;

use Application\Models\Usuario;
use Application\Lib\Auth;
use Application\Services\LogService;
use Google_Client;
use Google\Service\Oauth2;
use Exception;

/**
 * Serviço para autenticação via Google OAuth2
 */
class GoogleAuthService
{
    private Google_Client $client;
    private AuthService $authService;

    public function __construct(?Google_Client $client = null, ?AuthService $authService = null)
    {
        $this->client = $client ?? $this->createGoogleClient();
        $this->authService = $authService ?? new AuthService();
    }

    /**
     * Gera URL de autenticação do Google
     */
    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    /**
     * Processa o código de autorização recebido do Google
     * @return array{usuario: Usuario, is_new: bool}
     * @throws Exception
     */
    public function handleCallback(string $code): array
    {
        // Troca código por token
        $token = $this->client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new Exception("Erro ao buscar token: " . ($token['error_description'] ?? $token['error']));
        }

        $this->client->setAccessToken($token);

        // Obtém dados do usuário
        $userInfo = $this->getUserInfo();

        LogService::info('Dados recebidos do Google', [
            'google_id' => $userInfo['id'],
            'email' => $userInfo['email'],
        ]);

        // Processa usuário (vincula ou cria)
        return $this->processUser($userInfo);
    }

    /**
     * Obtém informações do usuário do Google
     * @return array{id: string, name: string, email: string, picture: string}
     */
    private function getUserInfo(): array
    {
        $oauth = new Oauth2($this->client);
        $info = $oauth->userinfo->get();

        return [
            'id' => $info->id,
            'name' => $info->name ?? '',
            'email' => $info->email,
            'picture' => $info->picture ?? '',
        ];
    }

    /**
     * Processa usuário: vincula conta existente ou cria nova
     * @return array{usuario: Usuario, is_new: bool}
     * @throws Exception
     */
    private function processUser(array $userInfo): array
    {
        $googleId = $userInfo['id'];
        $email = $userInfo['email'];
        $name = $userInfo['name'];

        // 1. Busca por google_id (conta já vinculada)
        $usuario = Usuario::where('google_id', $googleId)->first();
        if ($usuario) {
            $this->updateUserName($usuario, $name);
            
            LogService::info('Login com Google (conta já vinculada)', [
                'user_id' => $usuario->id,
                'email' => $email,
            ]);

            return ['usuario' => $usuario, 'is_new' => false];
        }

        // 2. Busca por email (vincula conta local existente)
        $usuario = Usuario::where('email', $email)->first();
        if ($usuario) {
            $usuario->google_id = $googleId;
            if (empty($usuario->nome) && !empty($name)) {
                $usuario->nome = $name;
            }
            $usuario->save();

            LogService::info('Conta local vinculada ao Google', [
                'user_id' => $usuario->id,
                'email' => $email,
            ]);

            return ['usuario' => $usuario, 'is_new' => false];
        }

        // 3. Cria novo usuário
        $usuario = $this->createUserFromGoogle($userInfo);

        LogService::info('Novo usuário criado via Google', [
            'user_id' => $usuario->id,
            'email' => $email,
        ]);

        return ['usuario' => $usuario, 'is_new' => true];
    }

    /**
     * Cria novo usuário a partir de dados do Google
     * @throws Exception
     */
    private function createUserFromGoogle(array $userInfo): Usuario
    {
        $randomPassword = bin2hex(random_bytes(16));

        $payload = [
            'name' => $userInfo['name'] ?: strtok($userInfo['email'], '@'),
            'email' => $userInfo['email'],
            'password' => $randomPassword,
            'password_confirmation' => $randomPassword,
            'google_id' => $userInfo['id'],
        ];

        $result = $this->authService->register($payload);
        $userId = $result['user_id'] ?? null;

        if (!$userId) {
            throw new Exception('Falha ao criar usuário via Google');
        }

        $usuario = Usuario::find($userId);
        if (!$usuario) {
            throw new Exception('Usuário não encontrado após criação');
        }

        return $usuario;
    }

    /**
     * Atualiza nome do usuário se necessário
     */
    private function updateUserName(Usuario $usuario, string $name): void
    {
        if (!empty($name) && $usuario->nome !== $name) {
            $usuario->nome = $name;
            $usuario->save();
        }
    }

    /**
     * Realiza login do usuário e configura sessão
     */
    public function loginUser(Usuario $usuario, array $userInfo): void
    {
        Auth::login($usuario);

        $_SESSION['usuario_email'] = $userInfo['email'];
        $_SESSION['usuario_foto'] = $userInfo['picture'] ?? null;
        $_SESSION['login_tipo'] = 'google';
        $_SESSION['google_id'] = $userInfo['id'];
    }

    /**
     * Realiza login após registro via Google
     */
    public function loginAfterRegistration(int $userId, string $email): bool
    {
        $usuario = Usuario::find($userId);

        // Fallback: busca por email
        if (!$usuario) {
            $usuario = Usuario::where('email', $email)->first();
        }

        if (!$usuario) {
            return false;
        }

        Auth::login($usuario);
        return true;
    }

    /**
     * Cria e configura cliente Google
     */
    private function createGoogleClient(): Google_Client
    {
        $credentialsPath = $this->getCredentialsPath();

        if (!file_exists($credentialsPath) || !filesize($credentialsPath)) {
            throw new Exception('Arquivo de credenciais do Google ausente ou vazio.');
        }

        $client = new Google_Client();
        $client->setAuthConfig($credentialsPath);
        $client->setRedirectUri($this->getRedirectUri());
        $client->addScope('email');
        $client->addScope('profile');

        return $client;
    }

    /**
     * Obtém caminho do arquivo de credenciais
     */
    private function getCredentialsPath(): string
    {
        // Tenta variável de ambiente primeiro
        $envPath = $_ENV['GOOGLE_CREDENTIALS_PATH'] ?? null;
        if ($envPath && file_exists($envPath)) {
            return $envPath;
        }

        // Fallback para arquivo padrão
        return BASE_PATH . '/config/google_credentials.json';
    }

    /**
     * Obtém URI de redirecionamento
     */
    private function getRedirectUri(): string
    {
        return rtrim(BASE_URL, '/') . '/auth/google/callback';
    }
}
