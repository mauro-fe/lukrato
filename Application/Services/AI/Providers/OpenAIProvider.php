<?php

declare(strict_types=1);

namespace Application\Services\AI\Providers;

use Application\Services\AI\Contracts\AIProvider;
use Application\Services\AI\PromptBuilder;
use GuzzleHttp\Client;

/**
 * Provider que chama a API da OpenAI diretamente via PHP (sem microserviço Python).
 *
 * .env necessário:
 *   AI_PROVIDER=openai
 *   OPENAI_API_KEY=sk-proj-...
 *   OPENAI_MODEL=gpt-4o-mini   (opcional)
 */
class OpenAIProvider implements AIProvider
{
    private Client $client;
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = $_ENV['OPENAI_API_KEY'] ?? '';
        $this->model  = $_ENV['OPENAI_MODEL'] ?? 'gpt-4o-mini';

        $this->client = new Client([
            'base_uri'        => 'https://api.openai.com/v1/',
            'timeout'         => 30,
            'connect_timeout' => 10,
        ]);
    }

    // ─── Internals ─────────────────────────────────────────────

    private function headers(): array
    {
        return [
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type'  => 'application/json',
        ];
    }

    private function completions(array $messages, float $temperature = 0.7, int $maxTokens = 2000, bool $jsonMode = false): array
    {
        $body = [
            'model'       => $this->model,
            'messages'    => $messages,
            'temperature' => $temperature,
            'max_tokens'  => $maxTokens,
        ];

        if ($jsonMode) {
            $body['response_format'] = ['type' => 'json_object'];
        }

        $response = $this->client->post('chat/completions', [
            'headers' => $this->headers(),
            'json'    => $body,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    // ─── AIProvider ────────────────────────────────────────────

    public function chat(string $prompt, array $context = []): string
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('OPENAI_API_KEY não configurada');
        }

        $result = $this->completions([
            ['role' => 'system', 'content' => PromptBuilder::chatSystem($context)],
            ['role' => 'user',   'content' => $prompt],
        ], temperature: 0.7, maxTokens: 2000);

        return $result['choices'][0]['message']['content'] ?? '';
    }

    public function suggestCategory(string $description, array $availableCategories = []): ?string
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('OPENAI_API_KEY não configurada');
        }

        $categories = $availableCategories ?: PromptBuilder::defaultCategories();

        $result = $this->completions([
            ['role' => 'system', 'content' => PromptBuilder::categorySystem()],
            ['role' => 'user',   'content' => PromptBuilder::categoryUser($description, $categories)],
        ], temperature: 0.1, maxTokens: 20);

        $suggested = trim($result['choices'][0]['message']['content'] ?? '', " \t\n\r\0\x0B.");

        return in_array($suggested, $categories, true) ? $suggested : null;
    }

    public function analyzeSpending(array $data, string $period = 'último mês'): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('OPENAI_API_KEY não configurada');
        }

        $result = $this->completions([
            ['role' => 'system', 'content' => PromptBuilder::analysisSystem()],
            ['role' => 'user',   'content' => PromptBuilder::analysisUser($data, $period)],
        ], temperature: 0.3, maxTokens: 800, jsonMode: true);

        $content = $result['choices'][0]['message']['content'] ?? '{}';
        $parsed  = json_decode($content, true);

        return [
            'insights' => $parsed['insights'] ?? [],
            'resumo'   => $parsed['resumo'] ?? '',
        ];
    }
}
