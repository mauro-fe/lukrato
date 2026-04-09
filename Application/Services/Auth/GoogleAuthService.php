<?php

declare(strict_types=1);

namespace Application\Services\Auth;

use Application\Config\AuthRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Enums\LogCategory;
use Application\Models\Usuario;
use Application\Services\Communication\MailService;
use Application\Services\Infrastructure\LogService;
use Application\Validators\PasswordStrengthValidator;
use Exception;
use Google_Client;
use Google\Service\Oauth2;
use RuntimeException;

/**
 * Servico para autenticacao via Google OAuth2
 */
class GoogleAuthService
{
    private Google_Client $client;
    private AuthService $authService;
    private MailService $mailService;
    private SessionManager $sessionManager;
    private AuthRuntimeConfig $runtimeConfig;

    public function __construct(
        ?Google_Client $client = null,
        ?AuthService $authService = null,
        ?MailService $mailService = null,
        ?SessionManager $sessionManager = null,
        ?AuthRuntimeConfig $runtimeConfig = null
    ) {
        $this->runtimeConfig = ApplicationContainer::resolveOrNew($runtimeConfig, AuthRuntimeConfig::class);
        $this->client = ApplicationContainer::resolveOrNew(
            $client,
            Google_Client::class,
            fn(): Google_Client => $this->createGoogleClient()
        );
        $this->authService = ApplicationContainer::resolveOrNew($authService, AuthService::class);
        $this->mailService = ApplicationContainer::resolveOrNew($mailService, MailService::class);
        $this->sessionManager = ApplicationContainer::resolveOrNew($sessionManager, SessionManager::class);
    }

    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    /**
     * @return array{usuario: Usuario, is_new: bool}|array{needs_confirmation: bool, user_info: array<string, string>}
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
            'email' => $userInfo['email'],
        ]);

        return $this->processUser($userInfo);
    }

    /**
     * @return array{id: string, name: string, email: string, picture: string}
     */
    private function getUserInfo(): array
    {
        $oauth = new Oauth2($this->client);
        $info = $oauth->userinfo->get();

        return [
            'id' => (string) $info->id,
            'name' => (string) ($info->name ?? ''),
            'email' => (string) $info->email,
            'picture' => (string) ($info->picture ?? ''),
        ];
    }

    /**
     * @param array{id: string, name: string, email: string, picture: string} $userInfo
     * @return array{usuario: Usuario, is_new: bool}|array{needs_confirmation: bool, user_info: array<string, string>}
     */
    private function processUser(array $userInfo): array
    {
        $googleId = $userInfo['id'];
        $email = mb_strtolower(trim($userInfo['email']));
        $name = $userInfo['name'];

        LogService::info('Processando usuario Google', [
            'google_id' => $googleId,
            'email' => $email,
            'name' => $name,
        ]);

        // 1) Busca por google_id
        $usuario = Usuario::where('google_id', $googleId)->first();
        if ($usuario) {
            LogService::info('Usuario encontrado por google_id', ['usuario_id' => $usuario->id]);
            $this->concludePendingEmailChangeIfMatchesGoogle($usuario, $email);
            $this->updateUserName($usuario, $name);
            return ['usuario' => $usuario, 'is_new' => false];
        }

        // 2) Busca por email principal
        $usuario = Usuario::whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();
        if ($usuario) {
            LogService::info('Usuario encontrado por email, vinculando google_id', ['usuario_id' => $usuario->id]);
            $usuario->google_id = $googleId;
            if ($usuario->nome === '' || $usuario->nome === null) {
                $usuario->nome = $name;
            }
            $usuario->save();
            return ['usuario' => $usuario, 'is_new' => false];
        }

        // 3) Busca por email pendente para concluir troca automaticamente
        $usuario = Usuario::whereRaw('LOWER(TRIM(pending_email)) = ?', [$email])->first();
        if ($usuario) {
            LogService::info('Usuario encontrado por pending_email', ['usuario_id' => $usuario->id]);
            $usuario->google_id = $googleId;
            if ($usuario->nome === '' || $usuario->nome === null) {
                $usuario->nome = $name;
            }
            $this->concludePendingEmailChangeIfMatchesGoogle($usuario, $email);
            return ['usuario' => $usuario, 'is_new' => false];
        }

        // 4) Nao existe: solicitar confirmacao para criar conta nova
        LogService::info('Usuario nao encontrado, solicitando confirmacao', ['email' => $email]);

        return [
            'needs_confirmation' => true,
            'user_info' => $userInfo,
        ];
    }

    /**
     * Cria usuario a partir dos dados pendentes (apos confirmacao)
     */
    public function createUserFromPending(array $userInfo, string $referralCode = ''): Usuario
    {
        return $this->createUserFromGoogle($userInfo, $referralCode);
    }

    private function createUserFromGoogle(array $userInfo, string $referralCode = ''): Usuario
    {
        $randomPassword = PasswordStrengthValidator::generateSecureRandom(32);

        $registerData = [
            'name' => $userInfo['name'] ?: strtok($userInfo['email'], '@'),
            'email' => $userInfo['email'],
            'password' => $randomPassword,
            'password_confirmation' => $randomPassword,
            'google_id' => $userInfo['id'],
            'skip_email_verification' => true,
        ];

        if ($referralCode !== '') {
            $registerData['referral_code'] = $referralCode;
            LogService::info('Codigo de indicacao aplicado no registro via Google', [
                'email' => $userInfo['email'],
                'referral_code' => $referralCode,
            ]);
        }

        $result = $this->authService->register($registerData);

        if (empty($result['user_id'])) {
            throw new Exception('Falha ao criar usuario via Google');
        }

        $usuario = Usuario::findOrFail((int) $result['user_id']);

        // Registro novo via Google pode marcar verificado automaticamente.
        if (!$usuario->hasVerifiedEmail()) {
            $usuario->markEmailAsVerified();
            LogService::info('Email marcado como verificado (registro via Google)', [
                'user_id' => $usuario->id,
            ]);
        }

        try {
            $this->mailService->sendWelcomeEmail($usuario->email, $usuario->nome ?? 'Usuario');
            LogService::info('Email de boas-vindas enviado (registro via Google)', [
                'user_id' => $usuario->id,
                'email' => $usuario->email,
            ]);
        } catch (\Throwable $e) {
            LogService::captureException($e, LogCategory::NOTIFICATION, [
                'action' => 'enviar_welcome_email_google',
                'user_id' => $usuario->id,
                'email' => $usuario->email,
            ]);
        }

        return $usuario;
    }

    private function updateUserName(Usuario $usuario, string $name): void
    {
        if ($name !== '' && $usuario->nome !== $name) {
            $usuario->nome = $name;
            $usuario->save();
        }
    }

    private function concludePendingEmailChangeIfMatchesGoogle(Usuario $usuario, string $googleEmail): bool
    {
        $pendingEmail = mb_strtolower(trim((string) ($usuario->pending_email ?? '')));
        $normalizedGoogleEmail = mb_strtolower(trim($googleEmail));

        if ($pendingEmail === '' || $pendingEmail !== $normalizedGoogleEmail) {
            return false;
        }

        $usuario->email = $normalizedGoogleEmail;
        $usuario->pending_email = null;
        $usuario->email_verified_at = $usuario->email_verified_at ?? now();
        $usuario->email_verification_token = null;
        $usuario->email_verification_selector = null;
        $usuario->email_verification_token_hash = null;
        $usuario->email_verification_expires_at = null;
        $usuario->email_verification_sent_at = null;
        $usuario->email_verification_reminder_sent_at = null;
        $usuario->save();

        LogService::info('Troca de email pendente concluida via Google', [
            'user_id' => $usuario->id,
            'new_email' => $normalizedGoogleEmail,
        ]);

        return true;
    }

    /**
     * Realiza login do usuario apos autenticacao Google
     */
    public function loginUser(Usuario $usuario, array $userInfo): void
    {
        if (!$usuario->hasVerifiedEmail()) {
            $usuario->markEmailAsVerified();
            LogService::info('Email marcado como verificado (login via Google)', [
                'user_id' => $usuario->id,
            ]);
        }

        $this->sessionManager->createSession($usuario, false);

        if (!empty($userInfo['picture'])) {
            $_SESSION['google_user_picture'] = $userInfo['picture'];
        }

        LogService::info('Login via Google realizado com sucesso', [
            'user_id' => $usuario->id,
            'email' => $usuario->email,
        ]);
    }

    public function loginAfterRegistration(int $userId, string $email): bool
    {
        try {
            $usuario = Usuario::find($userId);
            if (!$usuario) {
                LogService::warning('Usuario nao encontrado para login apos registro', [
                    'user_id' => $userId,
                ]);
                return false;
            }

            $this->sessionManager->createSession($usuario, false);

            LogService::info('Login automatico apos registro Google realizado', [
                'user_id' => $userId,
                'email' => $email,
            ]);

            return true;
        } catch (\Throwable $e) {
            LogService::captureException($e, LogCategory::AUTH, [
                'action' => 'login_after_registration',
                'user_id' => $userId,
                'email' => $email,
            ]);
            return false;
        }
    }

    private function createGoogleClient(): Google_Client
    {
        if (!$this->runtimeConfig->hasGoogleOauthCredentials()) {
            throw new RuntimeException('Google OAuth nao configurado no .env');
        }

        $client = new Google_Client();
        $client->setClientId($this->runtimeConfig->googleClientId());
        $client->setClientSecret($this->runtimeConfig->googleClientSecret());
        $client->setRedirectUri($this->runtimeConfig->googleRedirectUri());

        $client->addScope('email');
        $client->addScope('profile');
        $client->setPrompt('select_account');
        $client->setAccessType('offline');

        return $client;
    }
}
