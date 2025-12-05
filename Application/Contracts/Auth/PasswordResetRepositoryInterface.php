<?php

namespace Application\Contracts\Auth;

use Application\Models\PasswordReset;

interface PasswordResetRepositoryInterface
{
    public function deleteExistingTokens(string $email): void;

    public function create(string $email, string $token, string $expiresAt): PasswordReset;

    public function findValidToken(string $token): ?PasswordReset;

    public function markAsUsed(PasswordReset $reset): void;
}