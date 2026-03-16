<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Services\AI\Rules\CategoryRuleEngine;
use PHPUnit\Framework\TestCase;

class CategoryRuleEngineTest extends TestCase
{
    public function testSpecificPhraseWinsOverGenericMarketKeyword(): void
    {
        $result = CategoryRuleEngine::match('produto de limpeza no mercado');

        $this->assertNotNull($result);
        $this->assertEquals('Moradia', $result['categoria'] ?? null);
        $this->assertEquals('Limpeza', $result['subcategoria'] ?? null);
    }

    public function testMercadoWithoutSpecificItemRemainsSupermercado(): void
    {
        $result = CategoryRuleEngine::match('mercado');

        $this->assertNotNull($result);
        $this->assertEquals('Alimentação', $result['categoria'] ?? null);
        $this->assertEquals('Supermercado', $result['subcategoria'] ?? null);
    }
}
