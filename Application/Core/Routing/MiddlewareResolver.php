<?php

declare(strict_types=1);

namespace Application\Core\Routing;

use Application\Container\ApplicationContainer;
use Application\Core\Request;
use Application\Services\Infrastructure\CacheService;
use Illuminate\Contracts\Container\BindingResolutionException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use Throwable;

class MiddlewareResolver
{
    private static ?array $registry = null;

    public static function reset(): void
    {
        self::$registry = null;
    }

    /** @throws Throwable */
    public function execute(array $middlewareNames, Request $request): void
    {
        if ($middlewareNames === []) {
            return;
        }

        ApplicationContainer::bindRequest($request);

        $registry = $this->middlewareRegistry();

        foreach ($middlewareNames as $name) {
            if (!isset($registry[$name])) {
                throw new \RuntimeException("Middleware '{$name}' não está registrado.");
            }

            $this->invokeMiddleware($registry[$name], $request);
        }
    }

    /**
     * @return array<string, class-string>
     */
    private function middlewareRegistry(): array
    {
        /** @var array<string, class-string> $registry */
        $registry = self::$registry ??= require BASE_PATH . '/Application/Middlewares/RegistryMiddleware.php';

        return $registry;
    }

    /** @throws Throwable */
    private function invokeMiddleware(string $middlewareClass, Request $request): void
    {
        if (!class_exists($middlewareClass)) {
            throw new \RuntimeException("Middleware '{$middlewareClass}' não encontrado.");
        }

        $handleMethod = new ReflectionMethod($middlewareClass, 'handle');
        $arguments = $this->buildMiddlewareArguments($middlewareClass, $handleMethod, $request);

        if ($handleMethod->isStatic()) {
            $handleMethod->invokeArgs(null, $arguments);
            return;
        }

        $instance = $this->instantiateMiddleware(new ReflectionClass($middlewareClass));
        $handleMethod->invokeArgs($instance, $arguments);
    }

    /**
     * @return array<int, mixed>
     */
    private function buildMiddlewareArguments(
        string $middlewareClass,
        ReflectionMethod $handleMethod,
        Request $request
    ): array {
        $arguments = [];

        foreach ($handleMethod->getParameters() as $parameter) {
            if ($this->acceptsRequest($parameter)) {
                $arguments[] = $request;
                continue;
            }

            if ($this->acceptsString($parameter) && $parameter->getName() === 'identifier') {
                $arguments[] = $this->resolveIdentifier($middlewareClass, $request);
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            if ($parameter->allowsNull()) {
                $arguments[] = null;
                continue;
            }

            throw new \RuntimeException(
                "Nao foi possivel resolver o argumento '{$parameter->getName()}' do middleware '{$middlewareClass}'."
            );
        }

        return $arguments;
    }

    private function instantiateMiddleware(ReflectionClass $reflectionClass): object
    {
        $container = ApplicationContainer::getInstance();

        if ($container !== null) {
            try {
                return $container->make($reflectionClass->getName());
            } catch (BindingResolutionException) {
                // Mantem o resolvedor legado enquanto a cobertura de bindings ainda e parcial.
            }
        }

        $constructor = $reflectionClass->getConstructor();

        if ($constructor === null) {
            return $reflectionClass->newInstance();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (
                $type instanceof ReflectionNamedType
                && !$type->isBuiltin()
                && is_a($type->getName(), CacheService::class, true)
            ) {
                $arguments[] = new CacheService();
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            if ($parameter->allowsNull()) {
                $arguments[] = null;
                continue;
            }

            throw new \RuntimeException(
                "Nao foi possivel instanciar o middleware '{$reflectionClass->getName()}': argumento '{$parameter->getName()}' nao resolvido."
            );
        }

        return $reflectionClass->newInstanceArgs($arguments);
    }

    private function acceptsRequest(ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return false;
        }

        return is_a($type->getName(), Request::class, true);
    }

    private function acceptsString(ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType) {
            return $type->isBuiltin() && $type->getName() === 'string';
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $unionType) {
                if ($unionType instanceof ReflectionNamedType && $unionType->isBuiltin() && $unionType->getName() === 'string') {
                    return true;
                }
            }
        }

        return false;
    }

    private function resolveIdentifier(string $middlewareClass, Request $request): string
    {
        if (method_exists($middlewareClass, 'getIdentifier')) {
            $identifier = $middlewareClass::getIdentifier($request);

            if (is_string($identifier) && $identifier !== '') {
                return $identifier;
            }
        }

        $ip = $request->ip();

        return $ip !== '' ? $ip : 'unknown';
    }
}
