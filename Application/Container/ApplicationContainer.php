<?php

declare(strict_types=1);

namespace Application\Container;

use Application\Core\Request;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\Infrastructure\CacheService;
use Application\Services\User\PerfilServiceProvider;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Container\BindingResolutionException;

final class ApplicationContainer
{
    private static ?IlluminateContainer $container = null;

    /**
     * @var list<class-string>
     */
    private const SERVICE_PROVIDERS = [
        PerfilServiceProvider::class,
    ];

    public static function bootstrap(): IlluminateContainer
    {
        if (self::$container instanceof IlluminateContainer) {
            return self::$container;
        }

        $container = new IlluminateContainer();
        IlluminateContainer::setInstance($container);

        self::registerCoreBindings($container);
        self::registerServiceProviders($container);

        return self::$container = $container;
    }

    public static function getInstance(): ?IlluminateContainer
    {
        return self::$container;
    }

    public static function setInstance(IlluminateContainer $container): void
    {
        self::$container = $container;
        IlluminateContainer::setInstance($container);
    }

    public static function flush(): void
    {
        self::$container = null;
        IlluminateContainer::setInstance(null);
    }

    public static function bindRequest(Request $request): void
    {
        if (!(self::$container instanceof IlluminateContainer)) {
            return;
        }

        self::$container->instance(Request::class, $request);
    }

    public static function tryMake(string $abstract): mixed
    {
        if (!(self::$container instanceof IlluminateContainer)) {
            return null;
        }

        try {
            return self::$container->make($abstract);
        } catch (BindingResolutionException) {
            return null;
        }
    }

    private static function registerCoreBindings(IlluminateContainer $container): void
    {
        $container->bind(Auth::class, static fn(): Auth => new Auth());
        $container->bind(Request::class, static fn(): Request => new Request());
        $container->bind(Response::class, static fn(): Response => new Response());
        $container->singleton(CacheService::class, static fn(): CacheService => new CacheService());
    }

    private static function registerServiceProviders(IlluminateContainer $container): void
    {
        foreach (self::SERVICE_PROVIDERS as $providerClass) {
            if (!class_exists($providerClass)) {
                continue;
            }

            (new $providerClass())->register($container);
        }
    }
}
