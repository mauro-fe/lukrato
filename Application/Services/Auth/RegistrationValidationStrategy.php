<?php

// Application/Services/Auth/RegistrationValidationStrategy.php
namespace Application\Services\Auth;

use Application\DTOs\Auth\RegistrationDTO;
use Application\DTOs\Auth\CredentialsDTO;
use Application\Models\Usuario;
use GUMP;

class RegistrationValidationStrategy extends AbstractValidationStrategy
{
    private RegistrationDTO $registration;

    public function validateRegistration(RegistrationDTO $registration): void
    {
        $this->registration = $registration;
        $this->errors = [];

        $this->validateFormat();
        $this->validateFieldRules();
        $this->validateUniqueEmail();

        if (!empty($this->errors)) {
            throw new \Application\Core\Exceptions\ValidationException(
                $this->errors,
                'Falha na validação de registro'
            );
        }
    }

    private function validateFormat(): void
    {
        $gump = new GUMP();
        $gump->filter_rules([
            'nome' => 'trim|sanitize_string',
            'email' => 'trim|sanitize_email',
        ]);

        $filtered = $gump->run($this->registration->toArray());
    }

    private function validateFieldRules(): void
    {
        $gump = new GUMP();
        $gump->validation_rules([
            'nome' => 'required|min_len,3|max_len,150',
            'email' => 'required|valid_email|max_len,150',
            'senha' => 'required|min_len,8|max_len,72',
            'senha_confirmacao' => 'required|equalsfield,senha',
        ]);

        $result = $gump->run($this->registration->toArray());

        if ($result === false) {
            $this->errors = array_merge($this->errors, $this->mapGumpErrors($gump->get_errors_array()));
        }
    }

    private function validateUniqueEmail(): void
    {
        if (Usuario::where('email', $this->registration->email)->exists()) {
            $this->addError('email', 'E-mail já cadastrado.');
        }
    }

    private function mapGumpErrors(array $errors): array
    {
        return [
            'name' => $errors['nome'] ?? null,
            'email' => $errors['email'] ?? null,
            'password' => $errors['senha'] ?? null,
            'password_confirmation' => $errors['senha_confirmacao'] ?? null,
        ];
    }

    protected function performValidation(CredentialsDTO $credentials): void {}
    protected function getErrorMessage(): string
    {
        return '';
    }
}
