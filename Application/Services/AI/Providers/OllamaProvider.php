<?php

declare(strict_types=1);

namespace Application\Services\AI\Providers;

use Application\Services\AI\Contracts\AIProvider;
use Application\Services\AI\PromptBuilder;
use GuzzleHttp\Client;

/**
 * Chama o microserviço Python (FastAPI) usando o backend Ollama (LLM local).
 * Para usar: AI_PROVIDER=ollama no .env do PHP.
 * O Ollama deve estar rodando em localhost:11434 com o modelo configurado.
 */
class OllamaProvider implements AIProvider
{
    private Client $client;
    private string $serviceUrl;
    private string $internalToken;

    public function __construct()
    {
        $this->serviceUrl    = rtrim($_ENV['AI_SERVICE_URL'] ?? 'http://localhost:8002', '/');
        $this->internalToken = $_ENV['AI_INTERNAL_TOKEN'] ?? '';

        $this->client = new Client([
            'timeout'         => 120, // Ollama local pode ser mais lento com contexto
            'connect_timeout' => 5,
        ]);
    }

    private function headers(): array
    {
        return $this->internalToken
            ? ['Authorization' => "Bearer {$this->internalToken}"]
            : [];
    }

    public function chat(string $prompt, array $context = []): string
    {
        $response = $this->client->post("{$this->serviceUrl}/chat", [
            'headers' => $this->headers(),
            'json'    => [
                'message'       => $prompt,
                'context'       => empty($context) ? new \stdClass() : $context,
                'system_prompt' => PromptBuilder::chatSystem($context),
                'provider'      => 'ollama',
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        return $data['response'] ?? '';
    }

    public function suggestCategory(string $description, array $availableCategories = []): ?string
    {
        $body = ['description' => $description, 'provider' => 'ollama'];

        if (!empty($availableCategories)) {
            $body['available_categories'] = $availableCategories;
        }

        $response = $this->client->post("{$this->serviceUrl}/suggest/category", [
            'headers' => $this->headers(),
            'json'    => $body,
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        return $data['category'] ?? null;
    }

    public function analyzeSpending(array $data, string $period = 'último mês'): array
    {
        $response = $this->client->post("{$this->serviceUrl}/analyze/spending", [
            'headers' => $this->headers(),
            'json'    => [
                'lancamentos' => $data,
                'periodo'     => $period,
                'provider'    => 'ollama',
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true) ?? [];
    }

    public function getModel(): string
    {
        return $_ENV['OLLAMA_MODEL'] ?? 'ollama';
    }

    public function getLastMeta(): array
    {
        return [];
    }
}
