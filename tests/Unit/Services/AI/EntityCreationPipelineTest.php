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

    // ─── Regex extraction: lançamento ──────────────────────

    public function testCriarDespesaExtractsFields(): void
    {
        $type = EntityCreationIntentRule::detectEntityType('criar despesa de R$ 150 conta de luz hoje');
        $this->assertEquals('lancamento', $type);
    }

    public function testExtractLancamentoNoCartaoPreencheDescricaoPadrao(): void
    {
        $handler = new EntityCreationHandler();

        $result = $this->extractLancamento($handler, 'lança 30 no cartão de crédito');

        $this->assertEquals('despesa', $result['tipo'] ?? null);
        $this->assertEqualsWithDelta(30.0, (float) ($result['valor'] ?? 0), 0.01);
        $this->assertEquals('cartao_credito', $result['forma_pagamento'] ?? null);
        $this->assertEquals('Compra no Cartão', $result['descricao'] ?? null);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $result['data'] ?? '');
    }

    public function testExtractLancamentoNaFaturaDoNubankExtraiCartao(): void
    {
        $handler = new EntityCreationHandler();

        $result = $this->extractLancamento($handler, 'lança 30 na fatura do nubank');

        $this->assertEquals('cartao_credito', $result['forma_pagamento'] ?? null);
        $this->assertEquals('Nubank', $result['nome_cartao'] ?? null);
        $this->assertEquals('Nubank', $result['_cartao_nome'] ?? null);
        $this->assertEquals('Compra no Nubank', $result['descricao'] ?? null);
    }

    public function testExtractLancamentoRemoveContextoDoMercadoDaDescricao(): void
    {
        $handler = new EntityCreationHandler();

        $result = $this->extractLancamento($handler, 'registre 30 com produto de limpeza no mercado');

        $this->assertEquals('Produto De Limpeza', $result['descricao'] ?? null);
        $this->assertEquals('Mercado', $result['categoria_contexto'] ?? null);
    }

    public function testExtractLancamentoStructuredCommaSeparatedFormat(): void
    {
        $handler = new EntityCreationHandler();

        $result = $this->extractLancamento($handler, 'Receita, comida, 30, hoje');

        $this->assertEquals('receita', $result['tipo'] ?? null);
        $this->assertEqualsWithDelta(30.0, (float) ($result['valor'] ?? 0), 0.01);
        $this->assertEquals('Comida', $result['descricao'] ?? null);
        $this->assertEquals(date('Y-m-d'), $result['data'] ?? null);
    }

    // ─── Regex extraction: orcamento ───────────────────────

    public function testDefinirOrcamentoDetected(): void
    {
        $rule = new EntityCreationIntentRule();
        $result = $rule->match('definir orçamento de 800 para alimentação');

        $this->assertNotNull($result);
        $this->assertEquals(IntentType::CREATE_ENTITY, $result->intent);
    }

    public function testExtractOrcamentoCapturaCategoriaSugerida(): void
    {
        $handler = new EntityCreationHandler();

        $result = $this->extractOrcamento($handler, 'definir orçamento de 800 para alimentação');

        $this->assertEqualsWithDelta(800.0, (float) ($result['valor_limite'] ?? 0), 0.01);
        $this->assertEquals('alimentação', mb_strtolower((string) ($result['categoria_sugerida'] ?? '')));
    }

    public function testOrcamentoAgoraExigeCategoriaComoCampoFaltante(): void
    {
        $handler = new EntityCreationHandler();

        $missing = $this->getMissingFields($handler, [
            'valor_limite' => 400,
            'mes' => (int) date('m'),
            'ano' => (int) date('Y'),
        ], 'orcamento');

        $this->assertContains('categoria_id', $missing);
        $this->assertNotContains('valor_limite', $missing);
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

    public function testHandlerNaoAceitaValorZeroInventadoPelaIA(): void
    {
        $provider = Mockery::mock(AIProvider::class);
        $provider->shouldReceive('chatWithTools')
            ->once()
            ->andReturn([
                'valor' => 0,
                'descricao' => 'gasto',
            ]);

        $handler = new EntityCreationHandler();
        $handler->setProvider($provider);

        $request = new AIRequestDTO(
            userId: 1,
            message: 'quero registrar um gasto',
            intent: IntentType::CREATE_ENTITY,
            channel: AIChannel::WEB
        );

        $response = $handler->handle($request);

        $this->assertTrue($response->success);
        $this->assertEquals('missing_fields', $response->data['action'] ?? null);
        $this->assertContains('valor', $response->data['missing'] ?? []);
        $this->assertContains('descricao', $response->data['missing'] ?? []);
        $this->assertStringContainsString('valor', mb_strtolower($response->message));
        $this->assertStringContainsString('descri', mb_strtolower($response->message));
    }

    private function extractLancamento(EntityCreationHandler $handler, string $message): array
    {
        $method = new \ReflectionMethod($handler, 'extractLancamento');
        $method->setAccessible(true);

        /** @var array $result */
        $result = $method->invoke($handler, $message);

        return $result;
    }

    private function extractOrcamento(EntityCreationHandler $handler, string $message): array
    {
        $method = new \ReflectionMethod($handler, 'extractOrcamento');
        $method->setAccessible(true);

        /** @var array $result */
        $result = $method->invoke($handler, $message);

        return $result;
    }

    private function getMissingFields(EntityCreationHandler $handler, array $data, string $entityType): array
    {
        $method = new \ReflectionMethod($handler, 'getMissingFields');
        $method->setAccessible(true);

        /** @var array $result */
        $result = $method->invoke($handler, $data, $entityType);

        return $result;
    }
}
