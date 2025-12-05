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

    public function create(string $email, string $token, string $expiresAt): PasswordReset
    {
        return PasswordReset::create([
            'email'      => $email,
            'token'      => $token,
            'created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'expires_at' => $expiresAt,
            'used_at'    => null,
        ]);
    }

    public function findValidToken(string $token): ?PasswordReset
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        return PasswordReset::where('token', $token)
            ->whereNull('used_at')
            ->where('expires_at', '>=', $now)
            ->first();
    }

    public function markAsUsed(PasswordReset $reset): void
    {
        $reset->used_at = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $reset->save();
    }
}