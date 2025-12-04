<?php
// Application/DTOs/Auth/RegistrationDTO.php
namespace Application\DTOs\Auth;

class RegistrationDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly string $passwordConfirmation
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            email: strtolower(trim((string) ($data['email'] ?? ''))),
            password: (string) ($data['password'] ?? ''),
            passwordConfirmation: (string) ($data['password_confirmation'] ?? '')
        );
    }

    public function toArray(): array
    {
        return [
            'nome' => $this->name,
            'email' => $this->email,
            'senha' => $this->password,
            'senha_confirmacao' => $this->passwordConfirmation,
        ];
    }
}
