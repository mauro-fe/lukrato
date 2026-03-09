<?php

declare(strict_types=1);

namespace Application\Services\AI\Handlers;

use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\IntentType;
use Application\Services\AI\Analysis\FinancialAnalysisPreprocessor;
use Application\Services\AI\Contracts\AIProvider;
use Application\Services\AI\PromptBuilder;
use Application\Services\Infrastructure\CacheService;

/**
 * Handler para análise financeira com insights estruturados.
 * Pré-processa dados para enviar agregados (não registros individuais) ao LLM.
 */
class FinancialAnalysisHandler implements AIHandlerInterface
{
    private CacheService $cache;
    private ?AIProvider $provider = null;

    public function __construct()
    {
        $this->cache = new CacheService();
    }

    public function setProvider(AIProvider $provider): void
    {
        $this->provider = $provider;
    }

    public function supports(IntentType $intent): bool
    {
        return $intent === IntentType::ANALYZE;
    }

    public function handle(AIRequestDTO $request): AIResponseDTO
    {
        $period = $request->meta('period', 'último mês');
        $userId = $request->userId;

        // Checar cache (1 análise por usuário/dia)
        $cacheKey = "ai:analysis:{$userId}:" . date('Y-m-d') . ':' . md5($period);
        $cached = $this->cache->get($cacheKey);
        if (is_array($cached)) {
            return AIResponseDTO::fromCache(
                $cached['resumo'] ?? 'Análise recuperada do cache.',
                $cached,
                IntentType::ANALYZE,
            );
        }

        try {
            // Pré-processar dados financeiros (só agregados, nunca registros individuais)
            $preprocessor = new FinancialAnalysisPreprocessor();
            $aggregatedData = $preprocessor->prepare($userId, $period);

            if (empty($aggregatedData)) {
                return AIResponseDTO::fail(
                    'Não há dados financeiros suficientes para análise neste período.',
                    IntentType::ANALYZE,
                );
            }

            // Chamar LLM com dados agregados
            $result = $this->provider->analyzeSpending($aggregatedData, $period);

            if (empty($result)) {
                return AIResponseDTO::fail(
                    'Análise de IA indisponível no momento.',
                    IntentType::ANALYZE,
                );
            }

            // Enriquecer com metadados
            $result['period'] = $period;
            $result['generated_at'] = now()->toIso8601String();

            // Cachear por 6h
            $this->cache->set($cacheKey, $result, 21600);

            return AIResponseDTO::fromLLM(
                $result['resumo'] ?? 'Análise concluída.',
                $result,
                IntentType::ANALYZE,
            );
        } catch (\Throwable $e) {
            return AIResponseDTO::fail(
                'Erro ao gerar análise financeira.',
                IntentType::ANALYZE,
            );
        }
    }
}
