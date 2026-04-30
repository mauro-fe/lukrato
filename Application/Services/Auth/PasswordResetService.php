<?php

declare(strict_types=1);

namespace Application\Services\Auth;

use Application\Config\AuthRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Contracts\Auth\PasswordResetNotificationInterface;
use Application\Contracts\Auth\PasswordResetRepositoryInterface;
use Application\Contracts\Auth\TokenGeneratorInterface;
use Application\Core\Exceptions\ValidationException;
use Application\Models\PasswordReset;
use Application\Models\Usuario;
use Application\Validators\PasswordStrengthValidator;
use DateTimeImmutable;
use Illuminate\Container\Container as IlluminateContainer;

class PasswordResetService
{
    private PasswordResetRepositoryInterface $repository;
    private TokenGeneratorInterface $tokenGenerator;
    private PasswordResetNotificationInterface $notifier;
    private TokenPairService $tokenPairService;
    private AuthRuntimeConfig $runtimeConfig;

    public function __construct(
        ?PasswordResetRepositoryInterface $repository = null,
        ?TokenGeneratorInterface $tokenGenerator = null,
        ?PasswordResetNotificationInterface $notifier = null,
        ?TokenPairService $tokenPairService = null,
        ?AuthRuntimeConfig $runtimeConfig = null
    ) {
        $container = $this->authContainer();

        $this->repository = $repository ?? $container->make(PasswordResetRepositoryInterface::class);
        $this->tokenGenerator = $tokenGenerator ?? $container->make(TokenGeneratorInterface::class);
        $this->notifier = $notifier ?? $container->make(PasswordResetNotificationInterface::class);
        $this->tokenPairService = ApplicationContainer::resolveOrNew($tokenPairService, TokenPairService::class);
        $this->runtimeConfig = ApplicationContainer::resolveOrNew($runtimeConfig, AuthRuntimeConfig::class);
    }

    public function requestReset(string $email): void
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            throw new ValidationException(['email' => 'Informe o e-mail.'], 'E-mail obrigatorio');
        }

        $usuario = Usuario::whereRaw('LOWER(email) = ?', [$email])->first();

        if (!$usuario) {
            return;
        }

        $semSenhaLocal = ($usuario->senha === null || $usuario->senha === '');
        $temGoogle = !empty($usuario->google_id ?? null);

        if ($semSenhaLocal && $temGoogle) {
            throw new ValidationException(
                ['email' => 'Esta conta utiliza login com Google.'],
                'Conta vinculada ao Google'
            );
        }

        $recentToken = PasswordReset::where('email', $usuario->email)
            ->where('created_at', '>=', (new DateTimeImmutable('-2 minutes'))->format('Y-m-d H:i:s'))
            ->whereNull('used_at')
            ->exists();

        if ($recentToken) {
            return;
        }

        $credentials = $this->issueResetCredentials();
        $expiresAt = (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');

        $this->repository->deleteExistingTokens($usuario->email);
        $this->repository->create(
            $usuario->email,
            $credentials['selector'],
            $credentials['token_hash'],
            $expiresAt
        );

        $resetLink = $this->runtimeConfig->resetPasswordUrl('', $credentials['selector'], $credentials['validator']);

        $this->notifier->send($usuario->email, $usuario->nome, $resetLink);
    }

    public function getValidReset(string $token = '', string $selector = '', string $validator = ''): ?PasswordReset
    {
        return $this->resolveValidReset($token, $selector, $validator);
    }

    public function resetPassword(
        string $token,
        string $newPass,
        string $confirm,
        string $selector = '',
        string $validator = ''
    ): void {
        if ($token === '' && ($selector === '' || $validator === '')) {
            throw new ValidationException(['token' => 'Token ausente.']);
        }

        if ($newPass !== $confirm) {
            throw new ValidationException(['password_confirmation' => 'Senhas nao conferem.']);
        }

        $passwordErrors = PasswordStrengthValidator::validate($newPass);
        if (!empty($passwordErrors)) {
            throw new ValidationException(['password' => implode(' ', $passwordErrors)]);
        }

        $reset = $this->resolveValidReset($token, $selector, $validator);

        if (!$reset) {
            throw new ValidationException(['token' => 'Token inválido ou expirado.']);
        }

        $usuario = Usuario::where('email', $reset->email)->first();

        if (!$usuario) {
            throw new ValidationException(['email' => 'Usuario nao encontrado.']);
        }

        $usuario->senha = password_hash($newPass, PASSWORD_DEFAULT);
        $usuario->save();

        $this->repository->markAsUsed($reset);
    }

    /**
     * @return array{selector:string,validator:string,token_hash:string}
     */
    private function issueResetCredentials(): array
    {
        $validator = $this->tokenGenerator->generate();
        $selector = bin2hex(random_bytes(8));

        return [
            'selector' => $selector,
            'validator' => $validator,
            'token_hash' => $this->tokenPairService->hashValidator($validator),
        ];
    }

    private function resolveValidReset(string $token, string $selector, string $validator): ?PasswordReset
    {
        if ($selector !== '' && $validator !== '') {
            $reset = $this->repository->findValidSelector($selector);

            if (!$reset || !$this->tokenPairService->matches($validator, $reset->token_hash ?? null)) {
                return null;
            }

            return $reset;
        }

        if ($token === '') {
            return null;
        }

        return $this->repository->findValidTokenHash($this->tokenPairService->hashValidator($token));
    }

    private function authContainer(): IlluminateContainer
    {
        return ApplicationContainer::ensureProviderRegistered(AuthServiceProvider::class);
    }
}
