<?php

declare(strict_types=1);

namespace Application\Services\Communication;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class CommunicationServiceProvider
{
    public function register($container): void
    {
        if (!$container->bound(LoggerInterface::class)) {
            $container->singleton(
                LoggerInterface::class,
                static fn(): LoggerInterface => new NullLogger()
            );
        }
    }
}
