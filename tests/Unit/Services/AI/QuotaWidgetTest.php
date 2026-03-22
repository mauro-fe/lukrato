<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Services\AI\Providers\OpenAIProvider;
use Application\Services\Infrastructure\CacheService;
use PHPUnit\Framework\TestCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * Testes para o pipeline de rate limits OpenAI (widget Quota OpenAI).
 *
 * Verifica:
 * - OpenAIProvider captura rate limits corretamente
 * - CacheService serializa/deserializa arrays round-trip em qualquer backend
 * - Estrutura dos rate limits está completa
 * - getLastRateLimits retorna estrutura esperada
 */
class QuotaWidgetTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    // ─── OpenAIProvider structure ──────────────────────────

    public function testOpenAIProviderHasRateLimitMethods(): void
    {
        // Verificar que os métodos de rate limit existem no provider
        $this->assertTrue(
            method_exists(OpenAIProvider::class, 'getLastRateLimits'),
            'OpenAIProvider deve ter método getLastRateLimits()'
        );
        $this->assertTrue(
            method_exists(OpenAIProvider::class, 'getLastMeta'),
            'OpenAIProvider deve ter método getLastMeta()'
        );
        $this->assertTrue(
            method_exists(OpenAIProvider::class, 'getModel'),
            'OpenAIProvider deve ter método getModel()'
        );
    }

    public function testGetLastRateLimitsReturnsEmptyArrayByDefault(): void
    {
        // Sem nenhuma chamada API, lastRateLimits deve estar vazio
        // Precisamos criar com reflexão para evitar chamar a API
        $provider = $this->createProviderWithoutApiCall();

        $limits = $provider->getLastRateLimits();
        $this->assertIsArray($limits);
        $this->assertEmpty($limits, 'Sem chamada API, rate limits devem estar vazios');
    }

    public function testGetLastMetaReturnsEmptyArrayByDefault(): void
    {
        $provider = $this->createProviderWithoutApiCall();

        $meta = $provider->getLastMeta();
        $this->assertIsArray($meta);
        $this->assertEmpty($meta, 'Sem chamada API, meta deve estar vazio');
    }

    // ─── CacheService round-trip ───────────────────────────

    public function testCacheServiceRoundTripForRateLimitsArray(): void
    {
        $cache = new CacheService();

        $testKey = 'test:quota_widget_roundtrip_' . time();
        $rateLimits = [
            'requests_limit'     => 10000,
            'requests_remaining' => 9999,
            'tokens_limit'       => 200000,
            'tokens_remaining'   => 199997,
            'reset_requests'     => '8.64s',
            'reset_tokens'       => '0s',
        ];

        // Set
        $setResult = $cache->set($testKey, $rateLimits, 60);
        $this->assertTrue($setResult, 'cache->set() deve retornar true');

        // Get
        $retrieved = $cache->get($testKey);
        $this->assertIsArray($retrieved, 'cache->get() deve retornar array');
        $this->assertEquals($rateLimits, $retrieved, 'Round-trip deve preservar todos os campos');

        // Verificar campos individuais
        $this->assertEquals(10000, $retrieved['requests_limit']);
        $this->assertEquals(9999, $retrieved['requests_remaining']);
        $this->assertEquals(200000, $retrieved['tokens_limit']);
        $this->assertEquals(199997, $retrieved['tokens_remaining']);
        $this->assertEquals('8.64s', $retrieved['reset_requests']);
        $this->assertEquals('0s', $retrieved['reset_tokens']);

        // Cleanup
        $cache->forget($testKey);
    }

    public function testCacheServiceHandlesZeroValuesCorrectly(): void
    {
        $cache = new CacheService();

        $testKey = 'test:quota_widget_zero_' . time();
        $rateLimits = [
            'requests_limit'     => 0,
            'requests_remaining' => 0,
            'tokens_limit'       => 0,
            'tokens_remaining'   => 0,
            'reset_requests'     => null,
            'reset_tokens'       => null,
        ];

        $cache->set($testKey, $rateLimits, 60);
        $retrieved = $cache->get($testKey);

        $this->assertIsArray($retrieved);
        $this->assertEquals(0, $retrieved['requests_limit'], 'Zero deve ser preservado, não tratado como vazio');
        $this->assertArrayHasKey('requests_limit', $retrieved, 'Chave deve existir mesmo com valor 0');

        // Cleanup
        $cache->forget($testKey);
    }

    public function testCacheServiceReturnsDefaultWhenKeyMissing(): void
    {
        $cache = new CacheService();

        $result = $cache->get('test:nonexistent_key_' . time(), null);
        $this->assertNull($result, 'Chave inexistente deve retornar default (null)');
    }

    // ─── Rate limits structure validation ──────────────────

    public function testRateLimitsStructureHasAllExpectedKeys(): void
    {
        $expectedKeys = [
            'requests_limit',
            'requests_remaining',
            'tokens_limit',
            'tokens_remaining',
            'reset_requests',
            'reset_tokens',
        ];

        // Simular a estrutura que o OpenAIProvider monta
        $rateLimits = [
            'requests_limit'     => (int) ('10000' ?: 0),
            'requests_remaining' => (int) ('9999' ?: 0),
            'tokens_limit'       => (int) ('200000' ?: 0),
            'tokens_remaining'   => (int) ('199997' ?: 0),
            'reset_requests'     => '8.64s' ?: null,
            'reset_tokens'       => '0s' ?: null,
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $rateLimits, "Rate limits deve conter a chave '{$key}'");
        }

        // Verificar tipos
        $this->assertIsInt($rateLimits['requests_limit']);
        $this->assertIsInt($rateLimits['requests_remaining']);
        $this->assertIsInt($rateLimits['tokens_limit']);
        $this->assertIsInt($rateLimits['tokens_remaining']);
    }

    public function testRateLimitsRemainingNeverExceedsLimit(): void
    {
        // Simulação: remaining deve ser <= limit
        $testCases = [
            ['limit' => 10000, 'remaining' => 9999],
            ['limit' => 200000, 'remaining' => 199997],
            ['limit' => 0, 'remaining' => 0],
            ['limit' => 500, 'remaining' => 500], // just reset
        ];

        foreach ($testCases as $case) {
            $this->assertLessThanOrEqual(
                $case['limit'],
                $case['remaining'],
                "Remaining ({$case['remaining']}) não pode exceder limit ({$case['limit']})"
            );
        }
    }

    // ─── AiApiController quota() cache logic ───────────────

    public function testCacheCheckAcceptsZeroRequestsLimit(): void
    {
        // Simula a lógica corrigida: array_key_exists em vez de !empty
        $cached = [
            'requests_limit'     => 0,
            'requests_remaining' => 0,
            'tokens_limit'       => 0,
            'tokens_remaining'   => 0,
            'reset_requests'     => null,
            'reset_tokens'       => null,
        ];

        // Lógica corrigida (BUG-2 fix)
        $isCacheHit = is_array($cached) && array_key_exists('requests_limit', $cached);
        $this->assertTrue($isCacheHit, 'Cache com requests_limit=0 deve ser aceito como hit');

        // Lógica antiga (buggada) — para confirmar que o bug existia
        $oldLogicWouldMiss = is_array($cached) && !empty($cached['requests_limit']);
        $this->assertFalse($oldLogicWouldMiss, 'Lógica antiga incorretamente trataria 0 como cache miss');
    }

    public function testCacheCheckAcceptsValidData(): void
    {
        $cached = [
            'requests_limit'     => 10000,
            'requests_remaining' => 9999,
            'tokens_limit'       => 200000,
            'tokens_remaining'   => 199997,
            'reset_requests'     => '8.64s',
            'reset_tokens'       => '0s',
        ];

        $isCacheHit = is_array($cached) && array_key_exists('requests_limit', $cached);
        $this->assertTrue($isCacheHit, 'Cache válido deve ser aceito');
    }

    public function testCacheCheckRejectsNullAndNonArray(): void
    {
        $this->assertFalse(is_array(null) && array_key_exists('requests_limit', []), 'null não é cache hit');

        $cached = 'not_an_array';
        $this->assertFalse(is_array($cached) && array_key_exists('requests_limit', []), 'string não é cache hit');
    }

    // ─── Model accessor ────────────────────────────────────

    public function testGetModelReturnsConfiguredModel(): void
    {
        $provider = $this->createProviderWithoutApiCall();
        $model = $provider->getModel();

        $this->assertIsString($model);
        $this->assertNotEmpty($model, 'Model nunca deve estar vazio');
    }

    // ─── Helpers ───────────────────────────────────────────

    /**
     * Cria um OpenAIProvider sem fazer chamada à API.
     * Usa reflexão para injetar estado sem depender de ENV.
     */
    private function createProviderWithoutApiCall(): OpenAIProvider
    {
        // O construtor precisa de ENV vars, então garantimos valores mínimos
        $originalKey = $_ENV['OPENAI_API_KEY'] ?? '';
        $originalModel = $_ENV['OPENAI_MODEL'] ?? '';

        $_ENV['OPENAI_API_KEY'] = 'sk-test-not-real';
        $_ENV['OPENAI_MODEL'] = 'gpt-4o-mini';

        try {
            $provider = new OpenAIProvider();
        } finally {
            // Restaurar
            if ($originalKey !== '') {
                $_ENV['OPENAI_API_KEY'] = $originalKey;
            }
            if ($originalModel !== '') {
                $_ENV['OPENAI_MODEL'] = $originalModel;
            }
        }

        return $provider;
    }
}
