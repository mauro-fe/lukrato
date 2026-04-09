<?php

declare(strict_types=1);

namespace Application\Services\Auth;

use Application\Contracts\Auth\PasswordResetNotificationInterface;
use Application\Contracts\Auth\PasswordResetRepositoryInterface;
use Application\Contracts\Auth\TokenGeneratorInterface;
use Application\Repositories\PasswordResetRepositoryEloquent;

class AuthServiceProvider
{
    public function register($container): void
    {
        if (!$container->bound(PasswordResetRepositoryInterface::class)) {
            $container->singleton(
                PasswordResetRepositoryInterface::class,
                static fn(): PasswordResetRepositoryInterface => new PasswordResetRepositoryEloquent()
            );
        }

        if (!$container->bound(TokenGeneratorInterface::class)) {
            $container->singleton(
                TokenGeneratorInterface::class,
                static fn(): TokenGeneratorInterface => new SecureTokenGenerator()
            );
        }

        if (!$container->bound(PasswordResetNotificationInterface::class)) {
            $container->singleton(
                PasswordResetNotificationInterface::class,
                static fn(): PasswordResetNotificationInterface => new MailPasswordResetNotification()
            );
        }
    }
}
