<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\DTO\AI\IntentResult;
use Application\Enums\AI\IntentType;
use Application\Services\AI\IntentRouter;
use PHPUnit\Framework\TestCase;

/**
 * Benchmark de detecção de intent usando fixtures.
 *
 * Roda com: php vendor/bin/phpunit --filter=AIBenchmarkTest
 *
 * Métricas calculadas:
 *  - Taxa de acerto por intent
 *  - Taxa de fallback
 *  - Confidence média por intent
 *  - Falsos positivos e negativos
 */
class AIBenchmarkTest extends TestCase
{
    private IntentRouter $router;
    private array $cases;
    private array $results = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = new IntentRouter();
        $this->cases = require dirname(__DIR__, 3) . '/Fixtures/AI/intent_detection_cases.php';
    }

    /**
     * Roda o benchmark completo e reporta métricas.
     */
    public function testIntentDetectionBenchmark(): void
    {
        $total = count($this->cases);
        $correct = 0;
        $failures = [];
        $byIntent = [];
        $confidences = [];
        $fallbackCount = 0;

        foreach ($this->cases as $i => $case) {
            [$message, $expectedIntent, $minConfidence, $tags, $notes] = $case;

            $result = $this->router->detect($message, in_array('whatsapp', $tags, true));
            $actualIntent = $result->intent->value;

            // Inicializar contadores
            if (!isset($byIntent[$expectedIntent])) {
                $byIntent[$expectedIntent] = ['total' => 0, 'correct' => 0, 'wrong' => []];
            }
            $byIntent[$expectedIntent]['total']++;
            $confidences[] = $result->confidence;

            // Verificar acerto
            $isCorrect = ($actualIntent === $expectedIntent);
            // Para mensagens ambíguas (confidence_min = 0.0), aceitar CHAT como fallback
            if (!$isCorrect && $minConfidence === 0.0 && $actualIntent === 'chat') {
                $isCorrect = true;
            }

            if ($isCorrect) {
                $correct++;
                $byIntent[$expectedIntent]['correct']++;
            } else {
                $failures[] = [
                    'case'     => $i,
                    'message'  => $message,
                    'expected' => $expectedIntent,
                    'actual'   => $actualIntent,
                    'conf'     => $result->confidence,
                    'notes'    => $notes,
                ];
                $byIntent[$expectedIntent]['wrong'][] = $message;
            }

            // Contar fallbacks
            if ($actualIntent === 'chat' && $expectedIntent !== 'chat') {
                $fallbackCount++;
            }

            // Verificar confidence mínima
            if ($isCorrect && $minConfidence > 0) {
                $this->assertGreaterThanOrEqual(
                    $minConfidence,
                    $result->confidence,
                    "Confidence baixa para: \"{$message}\" (esperado >= {$minConfidence}, obtido {$result->confidence})"
                );
            }
        }

        $accuracyRate = $total > 0 ? round(($correct / $total) * 100, 1) : 0;
        $fallbackRate = $total > 0 ? round(($fallbackCount / $total) * 100, 1) : 0;
        $avgConfidence = count($confidences) > 0 ? round(array_sum($confidences) / count($confidences), 3) : 0;

        // Reportar métricas
        $report = "\n╔═══════════════════════════════════════════════╗\n";
        $report .= "║           AI INTENT BENCHMARK REPORT          ║\n";
        $report .= "╠═══════════════════════════════════════════════╣\n";
        $report .= sprintf("║  Total cases:      %-24s  ║\n", $total);
        $report .= sprintf("║  Correct:          %-24s  ║\n", $correct);
        $report .= sprintf("║  Accuracy:         %-24s  ║\n", "{$accuracyRate}%");
        $report .= sprintf("║  Fallback rate:    %-24s  ║\n", "{$fallbackRate}%");
        $report .= sprintf("║  Avg confidence:   %-24s  ║\n", $avgConfidence);
        $report .= "╠═══════════════════════════════════════════════╣\n";

        foreach ($byIntent as $intent => $data) {
            $rate = $data['total'] > 0 ? round(($data['correct'] / $data['total']) * 100, 1) : 0;
            $report .= sprintf("║  %-18s %d/%d (%s%%)  ║\n", $intent . ':', $data['correct'], $data['total'], $rate);
        }

        $report .= "╚═══════════════════════════════════════════════╝\n";

        if (!empty($failures)) {
            $report .= "\nFAILURES:\n";
            foreach ($failures as $f) {
                $report .= "  [{$f['case']}] \"{$f['message']}\" → expected={$f['expected']}, got={$f['actual']} (conf={$f['conf']}) [{$f['notes']}]\n";
            }
        }

        fwrite(STDERR, $report);

        // Threshold de qualidade: mínimo 80% de acurácia
        $this->assertGreaterThanOrEqual(
            80.0,
            $accuracyRate,
            "Acurácia geral abaixo de 80%: {$accuracyRate}%. Revise as regras de intent."
        );

        // Fallback rate máximo de 15%
        $this->assertLessThanOrEqual(
            15.0,
            $fallbackRate,
            "Taxa de fallback muito alta: {$fallbackRate}%. Mensagens legítimas estão caindo no ChatHandler."
        );
    }

    /**
     * Testa especificamente a não-regressão de intents de transação.
     */
    public function testTransactionIntentRegression(): void
    {
        $transactionCases = array_filter($this->cases, fn($c) => $c[1] === 'extract_transaction');

        foreach ($transactionCases as $case) {
            [$message, $expected, $minConf, $tags] = $case;
            $result = $this->router->detect($message, in_array('whatsapp', $tags, true));

            $this->assertEquals(
                $expected,
                $result->intent->value,
                "Regressão em transação: \"{$message}\" detectado como {$result->intent->value}"
            );
        }
    }

    /**
     * Testa que saudações não sejam detectadas como transações.
     */
    public function testChatMessagesNotDetectedAsTransactions(): void
    {
        $chatCases = array_filter($this->cases, fn($c) => $c[1] === 'chat');

        foreach ($chatCases as $case) {
            [$message] = $case;
            $result = $this->router->detect($message, false);

            $this->assertNotEquals(
                IntentType::EXTRACT_TRANSACTION,
                $result->intent,
                "Falso positivo de transação: \"{$message}\" não deveria ser EXTRACT_TRANSACTION"
            );
        }
    }

    /**
     * Testa accuracy de detecção de entity type.
     */
    public function testEntityCreationAccuracy(): void
    {
        $cases = require dirname(__DIR__, 3) . '/Fixtures/AI/entity_creation_cases.php';
        $total = count($cases);
        $correct = 0;
        $failures = [];

        foreach ($cases as $i => $case) {
            [$message, $expectedType, $minConfidence, $tags, $notes] = $case;

            $detectedType = \Application\Services\AI\IntentRules\EntityCreationIntentRule::detectEntityType($message);

            if ($detectedType === $expectedType) {
                $correct++;
            } else {
                $failures[] = "  [{$i}] \"{$message}\" → expected={$expectedType}, got=" . ($detectedType ?? 'null') . " [{$notes}]";
            }
        }

        $rate = $total > 0 ? round(($correct / $total) * 100, 1) : 0;

        $report = "\n╔═════════════════════════════════════════════════╗\n";
        $report .= "║       ENTITY CREATION BENCHMARK                 ║\n";
        $report .= "╠═════════════════════════════════════════════════╣\n";
        $report .= sprintf("║  Total: %-3d  Correct: %-3d  Rate: %s%%  ║\n", $total, $correct, str_pad((string) $rate, 5));
        $report .= "╚═════════════════════════════════════════════════╝\n";

        if (!empty($failures)) {
            $report .= "\nFAILURES:\n" . implode("\n", $failures) . "\n";
        }

        fwrite(STDERR, $report);

        $this->assertGreaterThanOrEqual(75.0, $rate, "Entity creation accuracy below 75%: {$rate}%");
    }

    /**
     * Testa accuracy de detecção de confirmação/negação.
     */
    public function testConfirmationAccuracy(): void
    {
        $cases = require dirname(__DIR__, 3) . '/Fixtures/AI/confirmation_cases.php';
        $total = count($cases);
        $correct = 0;

        foreach ($cases as $i => $case) {
            [$message, $expectedAffirmative, $expectedNegative, $tags, $notes] = $case;

            $isAff = \Application\Services\AI\IntentRules\ConfirmationIntentRule::isAffirmative($message);
            $isNeg = \Application\Services\AI\IntentRules\ConfirmationIntentRule::isNegative($message);

            if ($isAff === $expectedAffirmative && $isNeg === $expectedNegative) {
                $correct++;
            }
        }

        $rate = $total > 0 ? round(($correct / $total) * 100, 1) : 0;

        fwrite(STDERR, "\n--- Confirmation Accuracy: {$correct}/{$total} ({$rate}%) ---\n");

        $this->assertGreaterThanOrEqual(85.0, $rate, "Confirmation accuracy below 85%: {$rate}%");
    }
}
