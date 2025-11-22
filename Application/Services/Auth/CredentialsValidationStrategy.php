<?php

// Application/Services/Auth/CredentialsValidationStrategy.php
namespace Application\Services\Auth;

use Application\DTOs\Auth\CredentialsDTO;
use Application\Models\Usuario;

class CredentialsValidationStrategy extends AbstractValidationStrategy
{
    protected function performValidation(CredentialsDTO $credentials): void
    {
        if ($credentials->isEmpty()) {
            $this->addError('email', 'Preencha e-mail e senha.');
            return;
        }

        $usuario = Usuario::whereRaw('LOWER(email) = ?', [$credentials->email])->first();

        if (!$usuario || !password_verify($credentials->password, $usuario->senha)) {
            $this->addError('credentials', 'E-mail ou senha inválidos.');
        }
    }

    protected function getErrorMessage(): string
    {
        return 'Falha na validação de credenciais';
    }
}
