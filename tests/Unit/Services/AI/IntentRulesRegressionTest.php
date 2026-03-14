<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\DTO\AI\IntentResult;
use Application\Enums\AI\IntentType;
use Application\Services\AI\IntentRules\TransactionIntentRule;
use Application\Services\AI\IntentRules\QuickQueryIntentRule;
use Application\Services\AI\IntentRules\AnalysisIntentRule;
use Application\Services\AI\IntentRules\SmartFallbackRule;
use Application\Services\AI\IntentRules\ConfirmationIntentRule;
use Application\Services\AI\IntentRules\EntityCreationIntentRule;
use PHPUnit\Framework\TestCase;

/**
 * Testes de regressão para todas as intent rules individuais.
 * Foco: comportamento observável, edge cases, falsos positivos.
 */
class IntentRulesRegressionTest extends TestCase
{
    // ═══════════════════════════════════════════════════════════════
    // TransactionIntentRule
    // ═══════════════════════════════════════════════════════════════

    private TransactionIntentRule $transactionRule;
    private QuickQueryIntentRule $quickQueryRule;
    private AnalysisIntentRule $analysisRule;
    private SmartFallbackRule $fallbackRule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transactionRule = new TransactionIntentRule();
        $this->quickQueryRule = new QuickQueryIntentRule();
        $this->analysisRule = new AnalysisIntentRule();
        $this->fallbackRule = new SmartFallbackRule();
    }

    // ─── TransactionIntentRule: Positivos ─────────────────────────

    public function testTransactionVerbValue(): void
    {
        $cases = [
            'gastei 40 no uber',
            'paguei 32 de luz',
            'comprei 120 de mercado',
            'torrei 200 no shopping',
            'recebi 5000 de salário',
            'ganhei 1500 freelance',
        ];

        foreach ($cases as $msg) {
            $result = $this->transactionRule->match($msg);
            $this->assertNotNull($result, "TransactionRule should match: '{$msg}'");
            $this->assertEquals(IntentType::EXTRACT_TRANSACTION, $result->intent);
            $this->assertGreaterThanOrEqual(0.7, $result->confidence, "Low confidence for: '{$msg}'");
        }
    }

    public function testTransactionDescValue(): void
    {
        $cases = ['uber 32', 'ifood 45', 'mercado 120', 'gasolina 80'];

        foreach ($cases as $msg) {
            $result = $this->transactionRule->match($msg);
            $this->assertNotNull($result, "TransactionRule should match desc+val: '{$msg}'");
        }
    }

    public function testTransactionCardPattern(): void
    {
        $cases = [
            'parcelei 3000 em 12x',
            'comprei no cartão 500',
            '10x de 100 geladeira',
        ];

        foreach ($cases as $msg) {
            $result = $this->transactionRule->match($msg);
            $this->assertNotNull($result, "TransactionRule should match card pattern: '{$msg}'");
            $this->assertGreaterThanOrEqual(0.8, $result->confidence);
        }
    }

    public function testTransactionWhatsAppShort(): void
    {
        $result = $this->transactionRule->match('gastei 50 uber', true);
        $this->assertNotNull($result);
        $this->assertEquals(0.9, $result->confidence);
    }

    // ─── TransactionIntentRule: Negativos ─────────────────────────

    public function testTransactionRejectsNonFinancial(): void
    {
        $negatives = [
            'olá bom dia',
            'como vai você',
            'obrigado pela ajuda',
            'o que você faz',
        ];

        foreach ($negatives as $msg) {
            $result = $this->transactionRule->match($msg);
            $this->assertNull($result, "TransactionRule should NOT match: '{$msg}'");
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // QuickQueryIntentRule
    // ═══════════════════════════════════════════════════════════════

    public function testQuickQueryPositives(): void
    {
        $cases = [
            'quanto gastei esse mês',
            'quanto recebi',
            'qual meu saldo',
            'quanto tenho',
            'quantos lançamentos tenho',
            'qual meu maior gasto',
            'gastos do mês',
            'me mostra meu saldo',
            'sobrou quanto',
            'quanto eu devo',
            'contas a pagar',
            'lista meus gastos',
        ];

        foreach ($cases as $msg) {
            $result = $this->quickQueryRule->match($msg);
            $this->assertNotNull($result, "QuickQuery should match: '{$msg}'");
            $this->assertEquals(IntentType::QUICK_QUERY, $result->intent);
        }
    }

    public function testQuickQueryNegatives(): void
    {
        $negatives = [
            'olá',
            'gastei 50 no uber',
            'criar meta de viagem',
            'analise meus gastos',
        ];

        foreach ($negatives as $msg) {
            $result = $this->quickQueryRule->match($msg);
            $this->assertNull($result, "QuickQuery should NOT match: '{$msg}'");
        }
    }

    public function testQuickQuerySupportsAbbreviatedHighestSpendQuestion(): void
    {
        $result = $this->quickQueryRule->match('oq eu gasto mais');

        $this->assertNotNull($result);
        $this->assertEquals(IntentType::QUICK_QUERY, $result->intent);
    }

    // ═══════════════════════════════════════════════════════════════
    // AnalysisIntentRule
    // ═══════════════════════════════════════════════════════════════

    public function testAnalysisPositives(): void
    {
        $cases = [
            'analise meus gastos',
            'quero uma análise financeira',
            'como posso economizar',
            'relatório do mês',
            'resumo financeiro',
            'to no vermelho',
            'minha saúde financeira',
            'previsão dos gastos',
        ];

        foreach ($cases as $msg) {
            $result = $this->analysisRule->match($msg);
            $this->assertNotNull($result, "Analysis should match: '{$msg}'");
            $this->assertEquals(IntentType::ANALYZE, $result->intent);
        }
    }

    public function testAnalysisNegatives(): void
    {
        $negatives = [
            'olá bom dia',
            'gastei 50 no uber',
            'sim',
            'quanto gastei',
        ];

        foreach ($negatives as $msg) {
            $result = $this->analysisRule->match($msg);
            $this->assertNull($result, "Analysis should NOT match: '{$msg}'");
        }
    }

    public function testAnalysisRejectsOffTopicForecast(): void
    {
        $result = $this->analysisRule->match('qual a previsão do tempo');

        $this->assertNull($result);
    }

    // ═══════════════════════════════════════════════════════════════
    // SmartFallbackRule
    // ═══════════════════════════════════════════════════════════════

    public function testFallbackCatchesFinancialWithValue(): void
    {
        $cases = [
            'cinema ontem 40',
            'pix pro joão 200',
            'fatura de 800 do nubank',
        ];

        foreach ($cases as $msg) {
            $result = $this->fallbackRule->match($msg);
            $this->assertNotNull($result, "Fallback should catch: '{$msg}'");
            $this->assertEquals(0.65, $result->confidence);
        }
    }

    public function testFallbackRejectsShortMessages(): void
    {
        $this->assertNull($this->fallbackRule->match('oi'));
        $this->assertNull($this->fallbackRule->match('sim'));
    }

    public function testFallbackRejectsConfirmations(): void
    {
        $this->assertNull($this->fallbackRule->match('sim'));
        $this->assertNull($this->fallbackRule->match('não'));
        $this->assertNull($this->fallbackRule->match('ok'));
        $this->assertNull($this->fallbackRule->match('cancela'));
    }

    public function testFallbackRejectsLongMessages(): void
    {
        $long = str_repeat('a ', 130); // 260 chars
        $this->assertNull($this->fallbackRule->match($long));
    }

    // ═══════════════════════════════════════════════════════════════
    // ConfirmationIntentRule — static methods only
    // ═══════════════════════════════════════════════════════════════

    public function testIsAffirmativeCoversAllPatterns(): void
    {
        $affirmatives = [
            'sim',
            'ss',
            'confirma',
            'yes',
            'ok',
            'pode',
            'isso',
            'exato',
            'claro',
            'com certeza',
            'bora',
            'manda',
            'dale',
            'fechou',
            'beleza',
            'blz',
            'show',
            'perfeito',
            'tranquilo',
            'ta bom',
            'vamos',
            'faz isso',
            'segue',
            'aham',
            'uhum',
            'sim por favor',
        ];

        foreach ($affirmatives as $msg) {
            $this->assertTrue(
                ConfirmationIntentRule::isAffirmative($msg),
                "Should be affirmative: '{$msg}'"
            );
        }
    }

    public function testIsNegativeCoversAllPatterns(): void
    {
        $negatives = [
            'não',
            'nn',
            'cancela',
            'cancelo',
            'desistir',
            'não quero',
            'nem',
            'de jeito nenhum',
            'esquece',
            'deixa',
            'deixa pra lá',
            'negativo',
            'nah',
            'nope',
            'para',
            'não precisa',
        ];

        foreach ($negatives as $msg) {
            $this->assertTrue(
                ConfirmationIntentRule::isNegative($msg),
                "Should be negative: '{$msg}'"
            );
        }
    }

    public function testAffirmativeDoesNotMatchNegative(): void
    {
        $this->assertFalse(ConfirmationIntentRule::isAffirmative('não'));
        $this->assertFalse(ConfirmationIntentRule::isAffirmative('cancela'));
    }

    public function testNegativeDoesNotMatchAffirmative(): void
    {
        $this->assertFalse(ConfirmationIntentRule::isNegative('sim'));
        $this->assertFalse(ConfirmationIntentRule::isNegative('ok'));
    }

    // ═══════════════════════════════════════════════════════════════
    // EntityCreationIntentRule — entity type detection
    // ═══════════════════════════════════════════════════════════════

    public function testEntityTypeDetection(): void
    {
        $cases = [
            ['criar meta de viagem', 'meta'],
            ['registrar despesa de 100', 'lancamento'],
            ['definir orçamento de 800', 'orcamento'],
            ['adicionar categoria Pets', 'categoria'],
            ['criar subcategoria Ração', 'subcategoria'],
            ['criar conta no Nubank', 'conta'],
        ];

        foreach ($cases as [$msg, $expected]) {
            $type = EntityCreationIntentRule::detectEntityType($msg);
            $this->assertEquals($expected, $type, "Entity type for '{$msg}' should be '{$expected}'");
        }
    }

    public function testEntityTypeReturnsNullForUnknown(): void
    {
        $this->assertNull(EntityCreationIntentRule::detectEntityType('olá bom dia'));
        $this->assertNull(EntityCreationIntentRule::detectEntityType('gastei 50'));
        $this->assertNull(EntityCreationIntentRule::detectEntityType('quanto tenho'));
    }

    public function testEntityTypeDetectsGenericLimitBudget(): void
    {
        $type = EntityCreationIntentRule::detectEntityType('estabelecer limite de 2000 para lazer');

        $this->assertEquals('orcamento', $type);
    }

    // ═══════════════════════════════════════════════════════════════
    // Cross-rule: conflitos entre regras
    // ═══════════════════════════════════════════════════════════════

    public function testTransactionVsEntityCreation(): void
    {
        // "criar despesa" deve ser EntityCreation, não Transaction
        $entityRule = new EntityCreationIntentRule();
        $entityResult = $entityRule->match('criar despesa de 100 de supermercado');

        $txResult = $this->transactionRule->match('criar despesa de 100 de supermercado');

        $this->assertNotNull($entityResult, '"criar despesa..." deve ser reconhecido como criação de entidade');

        // EntityCreation deve ter confidence >= Transaction (ou Transaction deve ser null)
        if ($txResult !== null && $entityResult !== null) {
            $this->assertGreaterThanOrEqual(
                $txResult->confidence,
                $entityResult->confidence,
                '"criar despesa..." → EntityCreation deve ganhar de Transaction'
            );
        }
    }

    public function testQuickQueryCountDoesNotBecomeEntityCreation(): void
    {
        $entityRule = new EntityCreationIntentRule();

        $result = $entityRule->match('quantos lançamentos tenho');

        $this->assertNull($result);
    }

    public function testQueryVsAnalysis(): void
    {
        // "como andam meus gastos" pode ser ambíguo entre QuickQuery e Analysis
        $qr = $this->quickQueryRule->match('como andam meus gastos');
        $ar = $this->analysisRule->match('como andam meus gastos');

        // Ambos podem retornar, o IntentRouter decide pelo confidence
        // Analysis retorna 0.75, QuickQuery retorna 0.8 → QuickQuery ganha pela prioridade + confidence
        if ($qr !== null && $ar !== null) {
            $this->assertGreaterThanOrEqual($ar->confidence, $qr->confidence, 'QuickQuery confidence should be >= Analysis confidence');
        }
    }

    // ═══ Regression: Confirmation loose (Fix 2.1) ═══════════════

    public function testConfirmationLooseAcceptsTrailingText(): void
    {
        $this->assertTrue(
            ConfirmationIntentRule::isAffirmative('sim, pode registrar'),
            '"sim, pode registrar" should be affirmative with loose match'
        );
        $this->assertTrue(
            ConfirmationIntentRule::isAffirmative('ok está bom'),
            '"ok está bom" should be affirmative with loose match'
        );
    }

    // ═══ Regression: Analysis confidence < QuickQuery (Fix 2.5) ═══

    public function testAnalysisConfidenceLowerThanQuickQuery(): void
    {
        // "como andam meus gastos" matches both rules
        $qr = $this->quickQueryRule->match('como andam meus gastos');
        $ar = $this->analysisRule->match('como andam meus gastos');

        if ($qr !== null && $ar !== null) {
            $this->assertGreaterThan(
                $ar->confidence,
                $qr->confidence,
                'QuickQuery confidence should be higher than Analysis for ambiguous cases'
            );
        }
    }

    // ═══ Regression: Entity creation implicit ═══════════════════

    public function testEntityCreationImplicit(): void
    {
        $entityRule = new EntityCreationIntentRule();

        $implicitCases = [
            'preciso pagar aluguel de 1500',
            'quero juntar 10000 pra viagem',
        ];

        foreach ($implicitCases as $msg) {
            $result = $entityRule->match($msg);
            $this->assertNotNull($result, "EntityCreation should match implicit: '{$msg}'");
            $this->assertEquals(IntentType::CREATE_ENTITY, $result->intent);
        }
    }
}
