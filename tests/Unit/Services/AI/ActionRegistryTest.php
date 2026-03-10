<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Services\AI\Actions\ActionRegistry;
use Application\Services\AI\Actions\ActionInterface;
use Application\Services\AI\Actions\ActionResult;
use PHPUnit\Framework\TestCase;

class ActionRegistryTest extends TestCase
{
    private ActionRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new ActionRegistry();
    }

    // ─── Verificação 1: confirm → entity criada via Action ─

    public function testResolvesCreateLancamento(): void
    {
        $action = $this->registry->resolve('create_lancamento');
        $this->assertInstanceOf(ActionInterface::class, $action);
    }

    public function testResolvesCreateMeta(): void
    {
        $action = $this->registry->resolve('create_meta');
        $this->assertInstanceOf(ActionInterface::class, $action);
    }

    public function testResolvesCreateOrcamento(): void
    {
        $action = $this->registry->resolve('create_orcamento');
        $this->assertInstanceOf(ActionInterface::class, $action);
    }

    public function testResolvesCreateCategoria(): void
    {
        $action = $this->registry->resolve('create_categoria');
        $this->assertInstanceOf(ActionInterface::class, $action);
    }

    public function testResolvesCreateSubcategoria(): void
    {
        $action = $this->registry->resolve('create_subcategoria');
        $this->assertInstanceOf(ActionInterface::class, $action);
    }

    public function testReturnsNullForUnknownAction(): void
    {
        $this->assertNull($this->registry->resolve('create_unknown'));
    }

    public function testHasReturnsTrueForRegistered(): void
    {
        $this->assertTrue($this->registry->has('create_meta'));
    }

    public function testHasReturnsFalseForUnknown(): void
    {
        $this->assertFalse($this->registry->has('create_foo'));
    }

    // ─── ActionResult DTO ──────────────────────────────────

    public function testActionResultOk(): void
    {
        $result = ActionResult::ok('Criado!', ['id' => 1]);
        $this->assertTrue($result->success);
        $this->assertEquals('Criado!', $result->message);
        $this->assertEquals(['id' => 1], $result->data);
        $this->assertEmpty($result->errors);
    }

    public function testActionResultFail(): void
    {
        $result = ActionResult::fail('Erro X', ['campo' => 'obrigatório']);
        $this->assertFalse($result->success);
        $this->assertEquals('Erro X', $result->message);
        $this->assertEmpty($result->data);
        $this->assertEquals(['campo' => 'obrigatório'], $result->errors);
    }
}
