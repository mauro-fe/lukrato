<?php

namespace Application\Contracts\Auth;

use Application\Models\PasswordReset;

interface PasswordResetRepositoryInterface
{
    public function deleteExistingTokens(string $email): void;

    public function create(string $email, string $selector, string $tokenHash, string $expiresAt): PasswordReset;

    public function findValidSelector(string $selector): ?PasswordReset;

    public function findValidTokenHash(string $tokenHash): ?PasswordReset;

    public function markAsUsed(PasswordReset $reset): void;
}
