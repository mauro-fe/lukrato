<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Categoria;

use PHPUnit\Framework\TestCase;

class SubcategoriaServiceCompositionGuardTest extends TestCase
{
    public function testSubcategoriaServiceDoesNotInstantiateDependenciesInlineInConstructor(): void
    {
        $content = (string) file_get_contents('Application/Services/Categoria/SubcategoriaService.php');

        $this->assertDoesNotMatchRegularExpression(
            '/function\s+__construct\s*\((?:(?!\)\s*\{).)*=\s*new\s+[\\\w]+/s',
            $content,
            'Construtor não deve usar default inline com new: Application/Services/Categoria/SubcategoriaService.php'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/function\s+__construct\s*\([^)]*\)\s*\{[\s\S]*?\?\?=?\s*new\s+[\\\w]+/s',
            $content,
            'Construtor não deve montar dependência inline com new: Application/Services/Categoria/SubcategoriaService.php'
        );
    }
}
