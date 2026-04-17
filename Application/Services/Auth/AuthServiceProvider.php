<?php

declare(strict_types=1);

namespace Application\Services\Auth;

use Application\Config\AuthRuntimeConfig;
use Application\Contracts\Auth\PasswordResetNotificationInterface;
use Application\Contracts\Auth\PasswordResetRepositoryInterface;
use Application\Contracts\Auth\TokenGeneratorInterface;
use Application\Repositories\PasswordResetRepositoryEloquent;
use Google_Client;
use RuntimeException;

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

        if (!$container->bound(Google_Client::class)) {
            $container->singleton(
                Google_Client::class,
                static function ($container): Google_Client {
                    $runtimeConfig = $container->bound(AuthRuntimeConfig::class)
                        ? $container->make(AuthRuntimeConfig::class)
                        : new AuthRuntimeConfig();

                    if (!$runtimeConfig->hasGoogleOauthCredentials()) {
                        throw new RuntimeException('Google OAuth nao configurado no .env');
                    }

                    $client = new Google_Client();
                    $client->setClientId($runtimeConfig->googleClientId());
                    $client->setClientSecret($runtimeConfig->googleClientSecret());
                    $client->setRedirectUri($runtimeConfig->googleRedirectUri());
                    $client->addScope('email');
                    $client->addScope('profile');
                    $client->setPrompt('select_account');
                    $client->setAccessType('offline');

                    return $client;
                }
            );
        }
    }
}
