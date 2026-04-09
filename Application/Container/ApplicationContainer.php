<?php

declare(strict_types=1);

namespace Application\Container;

use Application\Core\Request;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\Auth\AuthServiceProvider;
use Application\Services\Communication\CommunicationServiceProvider;
use Application\Services\Infrastructure\CacheService;
use Application\Services\User\PerfilServiceProvider;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Container\BindingResolutionException;

final class ApplicationContainer
{
    private static ?IlluminateContainer $container = null;
    /**
     * @var array<class-string, true>
     */
    private static array $registeredProviders = [];

    /**
     * @var list<class-string>
     */
    private const SERVICE_PROVIDERS = [
        AuthServiceProvider::class,
        CommunicationServiceProvider::class,
        PerfilServiceProvider::class,
    ];

    public static function bootstrap(): IlluminateContainer
    {
        if (self::$container instanceof IlluminateContainer) {
            return self::$container;
        }

        $container = new IlluminateContainer();
        self::$container = $container;
        self::$registeredProviders = [];
        IlluminateContainer::setInstance($container);

        self::registerCoreBindings($container);
        self::registerServiceProviders($container);

        return $container;
    }

    public static function getInstance(): ?IlluminateContainer
    {
        return self::$container;
    }

    public static function setInstance(IlluminateContainer $container): void
    {
        self::$container = $container;
        self::$registeredProviders = [];
        IlluminateContainer::setInstance($container);
    }

    public static function flush(): void
    {
        self::$container = null;
        self::$registeredProviders = [];
        IlluminateContainer::setInstance(null);
    }

    public static function ensureProviderRegistered(string $providerClass): IlluminateContainer
    {
        $container = self::$container ?? self::bootstrap();

        self::registerServiceProvider($container, $providerClass);

        return $container;
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

    public static function resolveOrNew(mixed $dependency, string $abstract, ?callable $factory = null): mixed
    {
        if ($dependency !== null) {
            return $dependency;
        }

        return self::tryMake($abstract) ?? ($factory ? $factory() : new $abstract());
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
            self::registerServiceProvider($container, $providerClass);
        }
    }

    private static function registerServiceProvider(IlluminateContainer $container, string $providerClass): void
    {
        if (isset(self::$registeredProviders[$providerClass]) || !class_exists($providerClass)) {
            return;
        }

        (new $providerClass())->register($container);
        self::$registeredProviders[$providerClass] = true;
    }
}
