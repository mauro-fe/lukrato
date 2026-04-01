<?php

declare(strict_types=1);

namespace Application\Services\Auth;

use Application\DTO\Auth\CredentialsDTO;
use Application\DTO\Auth\RegistrationDTO;
use Application\Models\Usuario;
use Application\Validators\PasswordStrengthValidator;
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
                'Falha na validacao de registro'
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

        $gump->run($this->registration->toArray());
    }

    private function validateFieldRules(): void
    {
        $gump = new GUMP();
        $gump->validation_rules([
            'nome' => 'max_len,150',
            'email' => 'required|valid_email|max_len,150',
            'senha' => 'required|min_len,8|max_len,72',
            'senha_confirmacao' => 'required|equalsfield,senha',
        ]);

        $result = $gump->run($this->registration->toArray());

        if ($result === false) {
            $this->errors = array_merge($this->errors, $this->mapGumpErrors($gump->get_errors_array()));
        }

        $this->validatePasswordStrength();
    }

    private function validatePasswordStrength(): void
    {
        $senha = $this->registration->password;

        if ($senha === '') {
            return;
        }

        $errors = PasswordStrengthValidator::validate($senha);
        foreach ($errors as $error) {
            $this->addError('password', $error);
        }
    }

    private function validateUniqueEmail(): void
    {
        $email = mb_strtolower(trim($this->registration->email));

        $exists = Usuario::where(function ($query) use ($email) {
            $query->whereRaw('LOWER(email) = ?', [$email])
                ->orWhereRaw('LOWER(pending_email) = ?', [$email]);
        })->exists();

        if ($exists) {
            $this->addError('email', 'E-mail ja cadastrado.');
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

    protected function performValidation(CredentialsDTO $credentials): void
    {
    }

    protected function getErrorMessage(): string
    {
        return '';
    }
}
