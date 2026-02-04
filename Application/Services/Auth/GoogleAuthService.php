<?php

declare(strict_types=1);

namespace Application\Services\Auth;

use Application\Models\Usuario;
use Application\Lib\Auth;
use Application\Services\LogService;
use Google_Client;
use Google\Service\Oauth2;
use Exception;
use RuntimeException;

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
     * Processa o callback do Google
     * @return array{usuario: Usuario, is_new: bool}
     * @throws Exception
     */
    public function handleCallback(string $code): array
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new Exception($token['error_description'] ?? $token['error']);
        }

        $this->client->setAccessToken($token);

        $userInfo = $this->getUserInfo();

        LogService::info('Login Google recebido', [
            'google_id' => $userInfo['id'],
            'email'     => $userInfo['email'],
        ]);

        return $this->processUser($userInfo);
    }

    private function getUserInfo(): array
    {
        $oauth = new Oauth2($this->client);
        $info = $oauth->userinfo->get();

        return [
            'id'      => $info->id,
            'name'    => $info->name ?? '',
            'email'   => $info->email,
            'picture' => $info->picture ?? '',
        ];
    }

    private function processUser(array $userInfo): array
    {
        $googleId = $userInfo['id'];
        $email    = trim(strtolower($userInfo['email']));
        $name     = $userInfo['name'];

        LogService::info('Processando usuário Google', [
            'google_id' => $googleId,
            'email' => $email,
            'name' => $name,
        ]);

        // Busca por google_id
        $usuario = Usuario::where('google_id', $googleId)->first();
        if ($usuario) {
            LogService::info('Usuário encontrado por google_id', ['usuario_id' => $usuario->id]);
            $this->updateUserName($usuario, $name);
            return ['usuario' => $usuario, 'is_new' => false];
        }

        // Busca por email (case-insensitive e com trim)
        $usuario = Usuario::whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();

        LogService::info('Busca por email', [
            'email_buscado' => $email,
            'encontrado' => $usuario ? 'SIM' : 'NÃO',
        ]);

        if ($usuario) {
            LogService::info('Usuário encontrado por email, vinculando google_id', ['usuario_id' => $usuario->id]);
            $usuario->google_id = $googleId;
            $usuario->nome = $usuario->nome ?: $name;
            $usuario->save();

            return ['usuario' => $usuario, 'is_new' => false];
        }

        // Usuário não existe - retorna para confirmação antes de criar
        LogService::info('Usuário não encontrado, solicitando confirmação', ['email' => $email]);

        return [
            'needs_confirmation' => true,
            'user_info' => $userInfo,
        ];
    }

    /**
     * Cria usuário a partir dos dados pendentes (após confirmação)
     */
    public function createUserFromPending(array $userInfo): Usuario
    {
        return $this->createUserFromGoogle($userInfo);
    }

    private function createUserFromGoogle(array $userInfo): Usuario
    {
        $randomPassword = bin2hex(random_bytes(16));

        $result = $this->authService->register([
            'name' => $userInfo['name'] ?: strtok($userInfo['email'], '@'),
            'email' => $userInfo['email'],
            'password' => $randomPassword,
            'password_confirmation' => $randomPassword,
            'google_id' => $userInfo['id'],
        ]);

        if (empty($result['user_id'])) {
            throw new Exception('Falha ao criar usuário via Google');
        }

        $usuario = Usuario::findOrFail($result['user_id']);

        // Marca email como verificado automaticamente (Google já verifica)
        if (!$usuario->hasVerifiedEmail()) {
            $usuario->markEmailAsVerified();
            LogService::info('Email marcado como verificado (registro via Google)', [
                'user_id' => $usuario->id,
            ]);
        }

        return $usuario;
    }

    private function updateUserName(Usuario $usuario, string $name): void
    {
        if ($name && $usuario->nome !== $name) {
            $usuario->nome = $name;
            $usuario->save();
        }
    }

    /**
     * Realiza login do usuário após autenticação Google
     */
    public function loginUser(Usuario $usuario, array $userInfo): void
    {
        // Armazena foto do Google na sessão
        if (!empty($userInfo['picture'])) {
            $_SESSION['google_user_picture'] = $userInfo['picture'];
        }

        // Marca email como verificado se ainda não estiver (Google verifica o email)
        if (!$usuario->hasVerifiedEmail()) {
            $usuario->markEmailAsVerified();
            LogService::info('Email marcado como verificado (login via Google)', [
                'user_id' => $usuario->id,
            ]);
        }

        // Usa o AuthService para fazer o login
        Auth::login($usuario);

        LogService::info('Login via Google realizado com sucesso', [
            'user_id' => $usuario->id,
            'email' => $usuario->email,
        ]);
    }

    /**
     * Realiza login automático após registro via Google
     */
    public function loginAfterRegistration(int $userId, string $email): bool
    {
        try {
            $usuario = Usuario::find($userId);
            
            if (!$usuario) {
                LogService::warning('Usuário não encontrado para login após registro', [
                    'user_id' => $userId,
                ]);
                return false;
            }

            Auth::login($usuario);

            LogService::info('Login automático após registro Google realizado', [
                'user_id' => $userId,
                'email' => $email,
            ]);

            return true;
        } catch (\Throwable $e) {
            LogService::error('Erro ao fazer login automático após registro', [
                'user_id' => $userId,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Cria cliente Google via ENV (produção-ready)
     */
    private function createGoogleClient(): Google_Client
    {
        if (
            empty($_ENV['GOOGLE_CLIENT_ID']) ||
            empty($_ENV['GOOGLE_CLIENT_SECRET']) ||
            empty($_ENV['GOOGLE_REDIRECT_URI'])
        ) {
            throw new RuntimeException('Google OAuth não configurado no .env');
        }

        $client = new Google_Client();
        $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
        $client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);

        $client->addScope('email');
        $client->addScope('profile');

        $client->setPrompt('select_account');
        $client->setAccessType('offline');

        return $client;
    }
}
