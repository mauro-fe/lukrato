<?php

namespace Application\Repositories;

use Application\Contracts\Auth\PasswordResetRepositoryInterface;
use Application\Models\PasswordReset;
use DateTimeImmutable;

class PasswordResetRepositoryEloquent implements PasswordResetRepositoryInterface
{
    public function deleteExistingTokens(string $email): void
    {
        PasswordReset::where('email', $email)->delete();
    }

    public function create(string $email, string $selector, string $tokenHash, string $expiresAt): PasswordReset
    {
        return PasswordReset::create([
            'email'      => $email,
            'selector'   => $selector,
            'token'      => null,
            'token_hash' => $tokenHash,
            'created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'expires_at' => $expiresAt,
            'used_at'    => null,
        ]);
    }

    public function findValidSelector(string $selector): ?PasswordReset
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        return PasswordReset::where('selector', $selector)
            ->whereNull('used_at')
            ->where('expires_at', '>=', $now)
            ->first();
    }

    public function findValidTokenHash(string $tokenHash): ?PasswordReset
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        return PasswordReset::where('token_hash', $tokenHash)
            ->whereNull('used_at')
            ->where('expires_at', '>=', $now)
            ->first();
    }

    public function markAsUsed(PasswordReset $reset): void
    {
        $reset->selector = null;
        $reset->token = null;
        $reset->token_hash = null;
        $reset->used_at = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $reset->save();
    }
}
