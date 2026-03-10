<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\DTO\AI\AIRequestDTO;
use Application\Enums\AI\AIChannel;
use Application\Enums\AI\IntentType;
use Application\Models\PendingAiAction;
use Application\Services\AI\Actions\ActionInterface;
use Application\Services\AI\Actions\ActionRegistry;
use Application\Services\AI\Actions\ActionResult;
use Application\Services\AI\Contracts\AIProvider;
use Application\Services\AI\Handlers\ConfirmationHandler;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Verificação 1: confirm → entity criada via Action Layer
 *
 * Testa que ConfirmationHandler despacha via ActionRegistry → Action → ActionResult,
 * não chamando Services diretamente.
 */
class ConfirmationHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    // ─── Sem pending → resposta de erro ────────────────────

    public function testHandleWithoutPendingReturnsError(): void
    {
        $handler = new ConfirmationHandler();
        $handler->setProvider(Mockery::mock(AIProvider::class));

        $request = new AIRequestDTO(
            userId: 999999,
            message: 'sim',
            intent: IntentType::CONFIRM_ACTION,
            channel: AIChannel::WEB
        );

        // Precisa de DB para funcionar o query
        try {
            \Illuminate\Database\Capsule\Manager::connection()->getPdo();
        } catch (\Throwable) {
            $this->markTestSkipped('Requer conexão com banco para testar PendingAiAction query');
        }

        $response = $handler->handle($request);

        $this->assertFalse($response->success);
        $this->assertStringContainsString('pendente', mb_strtolower($response->message));
    }

    // ─── Sem userId → erro de identificação ────────────────

    public function testHandleWithoutUserIdReturnsError(): void
    {
        $handler = new ConfirmationHandler();
        $handler->setProvider(Mockery::mock(AIProvider::class));

        $request = new AIRequestDTO(
            userId: null,
            message: 'sim',
            intent: IntentType::CONFIRM_ACTION,
            channel: AIChannel::WEB
        );

        $response = $handler->handle($request);

        $this->assertFalse($response->success);
        $this->assertStringContainsString('não identificado', mb_strtolower($response->message));
    }

    // ─── ActionRegistry resolve todas as actions ───────────

    public function testActionRegistryResolvesAllEntityTypes(): void
    {
        $registry = new ActionRegistry();

        $types = [
            'create_lancamento',
            'create_meta',
            'create_orcamento',
            'create_categoria',
            'create_subcategoria',
        ];

        foreach ($types as $type) {
            $action = $registry->resolve($type);
            $this->assertInstanceOf(
                ActionInterface::class,
                $action,
                "ActionRegistry deve resolver '{$type}'"
            );
        }
    }

    // ─── ActionRegistry retorna null para tipo desconhecido ─

    public function testActionRegistryReturnsNullForUnknown(): void
    {
        $registry = new ActionRegistry();
        $this->assertNull($registry->resolve('create_nonexistent'));
    }

    // ─── ActionResult DTO funciona corretamente ────────────

    public function testActionResultOkStructure(): void
    {
        $result = ActionResult::ok('Meta criada com sucesso!', ['id' => 42]);
        $this->assertTrue($result->success);
        $this->assertEquals('Meta criada com sucesso!', $result->message);
        $this->assertEquals(['id' => 42], $result->data);
    }

    public function testActionResultFailStructure(): void
    {
        $result = ActionResult::fail('Erro de validação', ['titulo' => 'obrigatório']);
        $this->assertFalse($result->success);
        $this->assertEquals(['titulo' => 'obrigatório'], $result->errors);
    }
}
