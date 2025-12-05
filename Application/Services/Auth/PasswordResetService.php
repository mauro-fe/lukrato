<?php

namespace Application\Services\Auth;

use Application\Contracts\Auth\PasswordResetRepositoryInterface;
use Application\Contracts\Auth\TokenGeneratorInterface;
use Application\Contracts\Auth\PasswordResetNotificationInterface;
use Application\Core\Exceptions\ValidationException;
use Application\Models\Usuario;
use DateTimeImmutable;

class PasswordResetService
{
    private PasswordResetRepositoryInterface $repository;
    private TokenGeneratorInterface $tokenGenerator;
    private PasswordResetNotificationInterface $notifier;

    public function __construct(
        PasswordResetRepositoryInterface $repository,
        TokenGeneratorInterface $tokenGenerator,
        PasswordResetNotificationInterface $notifier
    ) {
        $this->repository     = $repository;
        $this->tokenGenerator = $tokenGenerator;
        $this->notifier       = $notifier;
    }

    public function requestReset(string $email): void
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            throw new ValidationException(['email' => 'Informe o e-mail.'], 'E-mail obrigatório');
        }

        $usuario = Usuario::whereRaw('LOWER(email) = ?', [$email])->first();

        // Mesmo fluxo silencioso
        if (!$usuario) {
            return;
        }

        // Bloqueia conta só-Google
        $semSenhaLocal = ($usuario->senha === null || $usuario->senha === '');
        $temGoogle     = !empty($usuario->google_id ?? null);

        if ($semSenhaLocal && $temGoogle) {
            throw new ValidationException(
                ['email' => 'Esta conta utiliza login com Google.'],
                'Conta vinculada ao Google'
            );
        }

        // Gera token
        $token     = $this->tokenGenerator->generate();
        $expiresAt = (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');

        // Limpa tokens antigos + salva novo
        $this->repository->deleteExistingTokens($usuario->email);
        $this->repository->create($usuario->email, $token, $expiresAt);

        // Gera link e envia notificação
        $resetLink = rtrim(BASE_URL, '/') . '/resetar-senha?token=' . urlencode($token);
        $this->notifier->send($usuario->email, $usuario->nome, $resetLink);
    }

    public function getValidReset(string $token)
    {
        return $this->repository->findValidToken($token);
    }

    public function resetPassword(string $token, string $newPass, string $confirm): void
    {
        if ($token === '') {
            throw new ValidationException(['token' => 'Token ausente.']);
        }

        if ($newPass !== $confirm) {
            throw new ValidationException(['password_confirmation' => 'Senhas não conferem.']);
        }

        if (strlen($newPass) < 8) {
            throw new ValidationException(['password' => 'A senha deve ter ao menos 8 caracteres.']);
        }

        $reset = $this->repository->findValidToken($token);

        if (!$reset) {
            throw new ValidationException(['token' => 'Token inválido ou expirado.']);
        }

        $usuario = Usuario::where('email', $reset->email)->first();

        if (!$usuario) {
            throw new ValidationException(['email' => 'Usuário não encontrado.']);
        }

        $usuario->senha = password_hash($newPass, PASSWORD_DEFAULT);
        $usuario->save();

        $this->repository->markAsUsed($reset);
    }
}