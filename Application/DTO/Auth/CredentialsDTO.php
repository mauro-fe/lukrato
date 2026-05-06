<?php

namespace Application\DTO\Auth;

class CredentialsDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $password
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromRequest(array $data): self
    {
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        return new self(
            email: is_scalar($email) ? trim(strtolower((string) $email)) : '',
            password: is_scalar($password) ? (string) $password : ''
        );
    }

    public function isEmpty(): bool
    {
        return empty($this->email) || empty($this->password);
    }
}
