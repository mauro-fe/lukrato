<?php

namespace Application\DTO\Auth;

class CredentialsDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $password
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            email: trim(strtolower($data['email'] ?? '')),
            password: $data['password'] ?? ''
        );
    }

    public function isEmpty(): bool
    {
        return empty($this->email) || empty($this->password);
    }
}
