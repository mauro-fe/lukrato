<?php

declare(strict_types=1);

namespace Application\Services\Communication;

use Illuminate\Container\Container;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class CommunicationServiceProvider
{
    public function register(Container $container): void
    {
        if (!$container->bound(LoggerInterface::class)) {
            $container->singleton(
                LoggerInterface::class,
                static fn(): LoggerInterface => new NullLogger()
            );
        }
    }
}
