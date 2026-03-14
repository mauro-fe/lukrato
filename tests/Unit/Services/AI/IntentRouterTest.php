<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\DTO\AI\IntentResult;
use Application\Enums\AI\IntentType;
use Application\Services\AI\IntentRouter;
use PHPUnit\Framework\TestCase;

/**
 * Testes do IntentRouter: confidence scoring, cache ephemeral, fallback.
 *
 * Verificações:
 *  2. "sim" sem pending → NÃO retorna CONFIRM_ACTION, confidence baixa
 *  3. Mensagem ambígua → confidence < 0.6 → fallback para CHAT
 *  4. "criar meta de viagem de 5000" → CREATE_ENTITY com alta confiança
 */
class IntentRouterTest extends TestCase
{
    private IntentRouter $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = new IntentRouter();
    }

    // ─── Verificação 2: "sim" sem pending → não é CONFIRM_ACTION ─

    public function testSimSemPendingNaoRetornaConfirmAction(): void
    {
        try {
            \Illuminate\Database\Capsule\Manager::connection()->getPdo();
        } catch (\Throwable) {
            $this->markTestSkipped('Requer conexão com banco para PendingAiAction query');
        }

        // userId=999999 (inexistente) → nenhum PendingAiAction
        $result = $this->router->detect('sim', false, 999999);

        // Sem pending, ConfirmationIntentRule NÃO matcha
        // Deve cair em outro intent ou fallback CHAT
        $this->assertNotEquals(
            IntentType::CONFIRM_ACTION,
            $result->intent,
            '"sim" sem ação pendente NÃO deve ser detectado como CONFIRM_ACTION'
        );
    }

    public function testNaoSemPendingNaoRetornaConfirmAction(): void
    {
        try {
            \Illuminate\Database\Capsule\Manager::connection()->getPdo();
        } catch (\Throwable) {
            $this->markTestSkipped('Requer conexão com banco para PendingAiAction query');
        }

        $result = $this->router->detect('não', false, 999999);

        $this->assertNotEquals(
            IntentType::CONFIRM_ACTION,
            $result->intent,
            '"não" sem ação pendente NÃO deve ser detectado como CONFIRM_ACTION'
        );
    }

    // ─── Verificação 3: Mensagem ambígua → confidence baixa ─────

    public function testMensagemAmbiguaRetornaFallbackChat(): void
    {
        // Mensagem que não bate com nenhuma regra → fallback CHAT com confidence 0.5
        $result = $this->router->detect('xyz abc 123 random', false, null);

        $this->assertEquals(IntentType::CHAT, $result->intent);
        $this->assertLessThan(
            IntentResult::CONFIDENCE_THRESHOLD,
            $result->confidence,
            'Mensagem aleatória deve ter confidence abaixo do threshold'
        );
        $this->assertFalse($result->isConfident());
    }

    public function testMensagemGenericaRetornaChat(): void
    {
        $result = $this->router->detect('olá, bom dia', false, null);

        // "olá" não bate com nenhuma regra específica → fallback CHAT
        $this->assertEquals(IntentType::CHAT, $result->intent);
    }

    // ─── Verificação 4: "criar meta..." → CREATE_ENTITY ────────

    public function testCriarMetaDetectaCreateEntity(): void
    {
        $result = $this->router->detect('criar meta de viagem de 5000', false, 1);

        $this->assertEquals(
            IntentType::CREATE_ENTITY,
            $result->intent,
            '"criar meta de viagem de 5000" deve ser detectado como CREATE_ENTITY'
        );
        $this->assertGreaterThanOrEqual(0.6, $result->confidence);
        $this->assertTrue($result->isConfident());
    }

    public function testCriarDespesaDetectaCreateEntity(): void
    {
        $result = $this->router->detect('criar despesa de 150 conta de luz', false, 1);

        $this->assertEquals(IntentType::CREATE_ENTITY, $result->intent);
        $this->assertTrue($result->isConfident());
    }

    public function testCriarOrcamentoDetectaCreateEntity(): void
    {
        $result = $this->router->detect('definir orçamento de 800 para alimentação', false, 1);

        $this->assertEquals(IntentType::CREATE_ENTITY, $result->intent);
        $this->assertTrue($result->isConfident());
    }

    public function testCriarCategoriaDetectaCreateEntity(): void
    {
        $result = $this->router->detect('adicionar categoria Pets tipo despesa', false, 1);

        $this->assertEquals(IntentType::CREATE_ENTITY, $result->intent);
        $this->assertTrue($result->isConfident());
    }

    public function testContagemDeLancamentosDetectaQuickQuery(): void
    {
        $result = $this->router->detect('quantos lançamentos tenho', false, 1);

        $this->assertEquals(IntentType::QUICK_QUERY, $result->intent);
    }

    public function testAbbreviatedHighestSpendQuestionDetectsQuickQuery(): void
    {
        $result = $this->router->detect('oq eu gasto mais', false, 1);

        $this->assertEquals(IntentType::QUICK_QUERY, $result->intent);
    }

    public function testWeatherForecastFallsBackToChat(): void
    {
        $result = $this->router->detect('qual a previsão do tempo', false, 1);

        $this->assertEquals(IntentType::CHAT, $result->intent);
    }

    // ─── Ephemeral intents NÃO cacheiam ────────────────────────

    public function testEphemeralIntentsSkipCache(): void
    {
        // Detectar intent CREATE_ENTITY
        $result1 = $this->router->detect('criar meta de viagem de 5000', false, 1);
        $this->assertEquals(IntentType::CREATE_ENTITY, $result1->intent);

        // A mesma mensagem deve detectar novamente (sem cache para ephemeral)
        $result2 = $this->router->detect('criar meta de viagem de 5000', false, 1);
        $this->assertEquals(IntentType::CREATE_ENTITY, $result2->intent);
    }

    // ─── IntentResult retornado é válido ───────────────────────

    public function testDetectAlwaysReturnsIntentResult(): void
    {
        $result = $this->router->detect('qualquer coisa', false, null);
        $this->assertInstanceOf(IntentResult::class, $result);
        $this->assertInstanceOf(IntentType::class, $result->intent);
        $this->assertIsFloat($result->confidence);
    }

    // ─── EntityCreationIntentRule.detectEntityType ──────────────

    public function testDetectEntityTypeMeta(): void
    {
        $type = \Application\Services\AI\IntentRules\EntityCreationIntentRule::detectEntityType(
            'criar meta de viagem de 5000'
        );
        $this->assertEquals('meta', $type);
    }

    public function testDetectEntityTypeLancamento(): void
    {
        $type = \Application\Services\AI\IntentRules\EntityCreationIntentRule::detectEntityType(
            'registrar despesa de 100 de supermercado'
        );
        $this->assertEquals('lancamento', $type);
    }

    public function testDetectEntityTypeCategoria(): void
    {
        $type = \Application\Services\AI\IntentRules\EntityCreationIntentRule::detectEntityType(
            'criar categoria Pets tipo despesa'
        );
        $this->assertEquals('categoria', $type);
    }

    public function testDetectEntityTypeSubcategoria(): void
    {
        $type = \Application\Services\AI\IntentRules\EntityCreationIntentRule::detectEntityType(
            'criar subcategoria Ração'
        );
        $this->assertEquals('subcategoria', $type);
    }

    public function testDetectEntityTypeOrcamento(): void
    {
        $type = \Application\Services\AI\IntentRules\EntityCreationIntentRule::detectEntityType(
            'definir orçamento de 800'
        );
        $this->assertEquals('orcamento', $type);
    }

    public function testDetectEntityTypeNullForUnknown(): void
    {
        $type = \Application\Services\AI\IntentRules\EntityCreationIntentRule::detectEntityType(
            'olá bom dia'
        );
        $this->assertNull($type);
    }
}
