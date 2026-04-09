<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Infrastructure;

use PHPUnit\Framework\TestCase;

class CacheServiceEnvBoundaryTest extends TestCase
{
    public function testCacheServiceDoesNotReadEnvironmentDirectly(): void
    {
        $contents = file_get_contents('Application/Services/Infrastructure/CacheService.php');

        $this->assertIsString($contents, 'Nao foi possivel ler Application/Services/Infrastructure/CacheService.php');
        $this->assertDoesNotMatchRegularExpression(
            '/\$_ENV|getenv\s*\(/i',
            $contents,
            'CacheService nao deve ler ambiente diretamente.'
        );
    }
}
