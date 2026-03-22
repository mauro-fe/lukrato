<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Application\Core\Request;
use Application\Core\Routing\MiddlewareResolver;
use Application\Services\Infrastructure\CacheService;
use PHPUnit\Framework\TestCase;

class MiddlewareResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MiddlewareResolver::reset();
    }

    protected function tearDown(): void
    {
        MiddlewareResolver::reset();

        parent::tearDown();
    }

    public function testExecuteRunsInstanceMiddlewareWithResolvedDependencies(): void
    {
        MiddlewareResolverInstanceStub::reset();
        $this->setMiddlewareRegistry(['test.instance' => MiddlewareResolverInstanceStub::class]);

        $resolver = new MiddlewareResolver();
        $request = new Request([
            'REQUEST_METHOD' => 'POST',
            'REMOTE_ADDR' => '198.51.100.25',
        ]);

        $resolver->execute(['test.instance'], $request);

        $this->assertTrue(MiddlewareResolverInstanceStub::$constructedWithCacheService);
        $this->assertSame([
            'method' => 'POST',
            'identifier' => 'resolver:198.51.100.25',
            'endpoint' => 'global',
        ], MiddlewareResolverInstanceStub::$calls[0] ?? null);
    }

    public function testExecuteThrowsForUnknownMiddleware(): void
    {
        $resolver = new MiddlewareResolver();
        $request = new Request(['REQUEST_METHOD' => 'GET']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Middleware 'inexistente' não está registrado.");

        $resolver->execute(['inexistente'], $request);
    }

    public function testLegacyCoreMiddlewareResolverRemainsCompatible(): void
    {
        $legacyResolver = new \Application\Core\MiddlewareResolver();

        $this->assertInstanceOf(MiddlewareResolver::class, $legacyResolver);
    }

    private function setMiddlewareRegistry(array $registry): void
    {
        $property = new \ReflectionProperty(MiddlewareResolver::class, 'registry');
        $property->setAccessible(true);
        $property->setValue(null, $registry);
    }
}

final class MiddlewareResolverInstanceStub
{
    public static bool $constructedWithCacheService = false;

    /** @var array<int, array<string, string>> */
    public static array $calls = [];

    public function __construct(CacheService $cacheService, ?int $maxAttempts = null)
    {
        self::$constructedWithCacheService = $cacheService instanceof CacheService;
    }

    public function handle(Request $request, string $identifier, string $endpoint = 'global'): void
    {
        self::$calls[] = [
            'method' => $request->method(),
            'identifier' => $identifier,
            'endpoint' => $endpoint,
        ];
    }

    public static function getIdentifier(Request $request): string
    {
        return 'resolver:' . $request->ip();
    }

    public static function reset(): void
    {
        self::$constructedWithCacheService = false;
        self::$calls = [];
    }
}
