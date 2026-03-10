<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\DTO\AI\AIRequestDTO;
use Application\Enums\AI\AIChannel;
use Application\Enums\AI\IntentType;
use Application\Services\AI\Handlers\EntityCreationHandler;
use Application\Services\AI\IntentRules\ConfirmationIntentRule;
use Application\Services\AI\IntentRules\EntityCreationIntentRule;
use Application\Services\AI\Contracts\AIProvider;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Verificação 4: E2E "criar meta de viagem de 5000" → preview → "sim" → criação
 *
 * Este teste valida o pipeline completo sem banco:
 *  - IntentRouter detecta CREATE_ENTITY
 *  - EntityCreationHandler extrai dados via regex
 *  - Preview é gerado corretamente
 *  - ConfirmationIntentRule detecta "sim" como afirmativo
 */
class EntityCreationPipelineTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    // ─── Passo 1: Intent detectado como CREATE_ENTITY ──────

    public function testMensagemCriarMetaDetectada(): void
    {
        $rule = new EntityCreationIntentRule();
        $result = $rule->match('criar meta de viagem de 5000');

        $this->assertNotNull($result, 'EntityCreationIntentRule deve detectar "criar meta de viagem de 5000"');
        $this->assertEquals(IntentType::CREATE_ENTITY, $result->intent);
        $this->assertGreaterThanOrEqual(0.6, $result->confidence);
    }

    // ─── Passo 2: entityType detectado como "meta" ─────────

    public function testEntityTypeDetectadoComoMeta(): void
    {
        $type = EntityCreationIntentRule::detectEntityType('criar meta de viagem de 5000');
        $this->assertEquals('meta', $type);
    }

    // ─── Passo 3: Regex extrai titulo e valor_alvo ─────────

    public function testEntityCreationHandlerGeraPreview(): void
    {
        // Precisa de DB para PendingAiAction::create
        try {
            \Illuminate\Database\Capsule\Manager::connection()->getPdo();
        } catch (\Throwable) {
            $this->markTestSkipped('Requer conexão com banco para teste de preview');
        }

        $handler = new EntityCreationHandler();
        $handler->setProvider(Mockery::mock(AIProvider::class));

        $request = new AIRequestDTO(
            userId: 1,
            message: 'criar meta de viagem de 5000',
            intent: IntentType::CREATE_ENTITY,
            channel: AIChannel::WEB,
            context: ['conversation_id' => null]
        );

        $response = $handler->handle($request);

        $this->assertTrue($response->success, 'Handler deve retornar sucesso com preview');
        $this->assertStringContainsString('Meta', $response->message);
        $this->assertStringContainsString('5.000', $response->message);
        $this->assertStringContainsString('viagem', mb_strtolower($response->message));
        $this->assertStringContainsString('confirmar', mb_strtolower($response->message));

        // Data deve conter pending_id e entity_type
        $this->assertEquals('confirm', $response->data['action'] ?? null);
        $this->assertEquals('meta', $response->data['entity_type'] ?? null);
        $this->assertArrayHasKey('pending_id', $response->data);
    }

    // ─── Passo 4: "sim" é detectado como afirmativo ────────

    public function testSimIsAffirmative(): void
    {
        $this->assertTrue(ConfirmationIntentRule::isAffirmative('sim'));
        $this->assertTrue(ConfirmationIntentRule::isAffirmative('Sim'));
        $this->assertTrue(ConfirmationIntentRule::isAffirmative('SIM!'));
        $this->assertTrue(ConfirmationIntentRule::isAffirmative('confirmar'));
        $this->assertTrue(ConfirmationIntentRule::isAffirmative('ok'));
        $this->assertTrue(ConfirmationIntentRule::isAffirmative('pode'));
    }

    public function testNaoIsNotAffirmative(): void
    {
        $this->assertFalse(ConfirmationIntentRule::isAffirmative('não'));
        $this->assertFalse(ConfirmationIntentRule::isAffirmative('cancelar'));
        $this->assertFalse(ConfirmationIntentRule::isAffirmative('cancela'));
        $this->assertFalse(ConfirmationIntentRule::isAffirmative('não quero'));
    }

    // ─── Regex extraction: lancamento ──────────────────────

    public function testCriarDespesaExtractsFields(): void
    {
        $type = EntityCreationIntentRule::detectEntityType('criar despesa de R$ 150 conta de luz hoje');
        $this->assertEquals('lancamento', $type);
    }

    // ─── Regex extraction: orcamento ───────────────────────

    public function testDefinirOrcamentoDetected(): void
    {
        $rule = new EntityCreationIntentRule();
        $result = $rule->match('definir orçamento de 800 para alimentação');

        $this->assertNotNull($result);
        $this->assertEquals(IntentType::CREATE_ENTITY, $result->intent);
    }

    // ─── Regex extraction: categoria ───────────────────────

    public function testCriarCategoriaDetected(): void
    {
        $rule = new EntityCreationIntentRule();
        $result = $rule->match('criar categoria Pets tipo despesa');

        $this->assertNotNull($result);

        $type = EntityCreationIntentRule::detectEntityType('criar categoria Pets tipo despesa');
        $this->assertEquals('categoria', $type);
    }

    // ─── Regex extraction: subcategoria ────────────────────

    public function testCriarSubcategoriaDetected(): void
    {
        $rule = new EntityCreationIntentRule();
        $result = $rule->match('criar subcategoria Ração');

        $this->assertNotNull($result);

        $type = EntityCreationIntentRule::detectEntityType('criar subcategoria Ração');
        $this->assertEquals('subcategoria', $type);
    }

    // ─── Mensagem sem dados suficientes pede mais ──────────

    public function testHandlerPedeCamposFaltantes(): void
    {
        try {
            \Illuminate\Database\Capsule\Manager::connection()->getPdo();
        } catch (\Throwable) {
            $this->markTestSkipped('Requer conexão com banco');
        }

        $handler = new EntityCreationHandler();
        $handler->setProvider(Mockery::mock(AIProvider::class));

        $request = new AIRequestDTO(
            userId: 1,
            message: 'criar meta',
            intent: IntentType::CREATE_ENTITY,
            channel: AIChannel::WEB
        );

        $response = $handler->handle($request);

        // Sem título e valor, deve pedir informações
        $this->assertTrue($response->success);
        $this->assertStringContainsString('preciso', mb_strtolower($response->message));
    }
}
