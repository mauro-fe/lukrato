<?php

namespace Application\Services\Auth;

use Application\DTO\Auth\RegistrationDTO;
use Application\Models\Usuario;
use Application\Services\Infrastructure\LogService;
use Application\Services\Referral\ReferralAntifraudService;
use Throwable;

class RegistrationHandler
{
    private RegistrationValidationStrategy $validationStrategy;
    private ReferralAntifraudService $antifraudService;

    public function __construct()
    {
        $this->validationStrategy = new RegistrationValidationStrategy();
        $this->antifraudService = new ReferralAntifraudService();
    }

    public function handle(RegistrationDTO $registration): array
    {
        try {
            $this->validationStrategy->validateRegistration($registration);

            $ip = $_SERVER['REMOTE_ADDR'] ?? null;

            // Verificação anti-fraude antes de criar conta
            $this->validateAntifraud($registration->email, $ip);

            $user = $this->createUser($registration, $ip);

            LogService::info('User registered', ['user_id' => $user->id]);

            return [
                'usuario' => $user,
                'user_id' => $user->id,
                'message' => 'Conta criada com sucesso! Verifique seu e-mail para ativar sua conta.',
                'redirect' => rtrim(BASE_URL, '/') . '/login',
                'requires_verification' => true,
            ];
        } catch (Throwable $e) {
            LogService::error('Registration failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Valida regras anti-fraude antes de permitir criar conta
     */
    private function validateAntifraud(string $email, ?string $ip): void
    {
        // 1. Verifica se usa email descartável
        if ($this->antifraudService->isDisposableEmail($email)) {
            LogService::warning('[RegistrationHandler] Tentativa de registro com email descartável', [
                'email_domain' => $this->antifraudService->getEmailDomain($email),
            ]);
            throw new \InvalidArgumentException('Por favor, use um email válido. Emails temporários não são permitidos.');
        }

        // 2. Verifica quarentena e limite de IP
        $canCreate = $this->antifraudService->canCreateAccount($email, $ip);
        if (!$canCreate['allowed']) {
            LogService::warning('[RegistrationHandler] Registro bloqueado por anti-fraude', [
                'reason' => $canCreate['reason'],
                'email_hash' => substr($this->antifraudService->hashEmail($email), 0, 8) . '...',
                'ip' => $ip,
            ]);
            throw new \InvalidArgumentException($canCreate['reason']);
        }
    }


    private function createUser(RegistrationDTO $registration, ?string $ip = null): Usuario
    {
        $user = new Usuario();
        $user->nome = $registration->name;
        $user->email = $registration->email;
        $user->senha = $registration->password;
        $user->registration_ip = $ip;
        $user->original_email_hash = $this->antifraudService->hashEmail($registration->email);
        $user->save();

        // Registra criação para tracking anti-fraude
        $this->antifraudService->onAccountCreated($user, $ip);

        return $user;
    }
}
