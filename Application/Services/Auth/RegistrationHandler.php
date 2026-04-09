<?php

declare(strict_types=1);

namespace Application\Services\Auth;

use Application\Container\ApplicationContainer;
use Application\Core\Request;
use Application\DTO\Auth\RegistrationDTO;
use Application\Models\Usuario;
use Application\Services\Infrastructure\LogService;
use Application\Services\Referral\ReferralAntifraudService;
use Throwable;

class RegistrationHandler
{
    private RegistrationValidationStrategy $validationStrategy;
    private ReferralAntifraudService $antifraudService;
    private Request $request;

    public function __construct(
        ?RegistrationValidationStrategy $validationStrategy = null,
        ?ReferralAntifraudService $antifraudService = null,
        ?Request $request = null
    ) {
        $this->validationStrategy = ApplicationContainer::resolveOrNew(
            $validationStrategy,
            RegistrationValidationStrategy::class
        );
        $this->antifraudService = ApplicationContainer::resolveOrNew(
            $antifraudService,
            ReferralAntifraudService::class
        );
        $this->request = ApplicationContainer::resolveOrNew($request, Request::class);
    }

    public function handle(RegistrationDTO $registration): array
    {
        try {
            $this->validationStrategy->validateRegistration($registration);

            $ip = $this->request->ip();

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
        $displayName = trim($registration->name);

        $user = new Usuario();
        // Mantemos string vazia quando o cadastro não pede nome.
        // Isso evita quebrar em bancos onde `usuarios.nome` ainda está NOT NULL
        // e preserva o prompt posterior de "como prefere ser chamado?" no dashboard.
        $user->nome = $displayName;
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
