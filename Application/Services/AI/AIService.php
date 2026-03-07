<?php

declare(strict_types=1);

namespace Application\Services\AI;

use Application\Services\AI\Contracts\AIProvider;
use Application\Services\AI\Providers\OllamaProvider;
use Application\Services\AI\Providers\OpenAIProvider;
use Application\Services\Infrastructure\LogService;
use Throwable;

/**
 * Gateway de IA — ponto único de acesso para todas as funcionalidades de IA.
 *
 * Uso:
 *   $ai = new AIService();
 *   $resposta  = $ai->chat("Qual foi o mês com mais gastos?", $context);
 *   $categoria = $ai->suggestCategory("ifood alimentação");
 *   $analise   = $ai->analyzeSpending($dados, 'fevereiro/2026');
 *
 * Troca de provedor: defina AI_PROVIDER=ollama|openai no .env do PHP.
 * O fallback é sempre seguro: nenhum método lança exceção para o chamador.
 */
class AIService
{
    private AIProvider $provider;

    public function __construct(?AIProvider $provider = null)
    {
        $this->provider = $provider ?? $this->resolveProvider();
    }

    private function resolveProvider(): AIProvider
    {
        $name = strtolower($_ENV['AI_PROVIDER'] ?? 'openai');

        return match ($name) {
            'ollama' => new OllamaProvider(),
            default  => new OpenAIProvider(),
        };
    }

    /**
     * Chat assistente. Nunca lança exceção — retorna mensagem amigável em caso de falha.
     */
    public function chat(string $prompt, array $context = []): string
    {
        try {
            return $this->provider->chat($prompt, $context);
        } catch (Throwable $e) {
            LogService::error('[AIService] chat() falhou', [
                'error'    => $e->getMessage(),
                'provider' => $_ENV['AI_PROVIDER'] ?? 'openai',
            ]);

            return 'O assistente de IA está indisponível no momento. Tente novamente em instantes.';
        }
    }

    /**
     * Sugestão de categoria. Retorna null silenciosamente em caso de falha.
     */
    public function suggestCategory(string $description, array $availableCategories = []): ?string
    {
        try {
            return $this->provider->suggestCategory($description, $availableCategories);
        } catch (Throwable $e) {
            LogService::error('[AIService] suggestCategory() falhou', [
                'error'       => $e->getMessage(),
                'description' => $description,
            ]);

            return null;
        }
    }

    /**
     * Análise de gastos. Retorna array vazio em caso de falha (UI exibe mensagem de indisponibilidade).
     *
     * @return array ['insights' => string[], 'resumo' => string]
     */
    public function analyzeSpending(array $data, string $period = 'último mês'): array
    {
        try {
            return $this->provider->analyzeSpending($data, $period);
        } catch (Throwable $e) {
            LogService::error('[AIService] analyzeSpending() falhou', [
                'error'  => $e->getMessage(),
                'period' => $period,
            ]);

            return [];
        }
    }
}
