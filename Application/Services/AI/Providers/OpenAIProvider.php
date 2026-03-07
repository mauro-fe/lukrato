<?php

declare(strict_types=1);

namespace Application\Services\AI\Providers;

use Application\Services\AI\Contracts\AIProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Chama o microserviço Python (FastAPI) usando o backend OpenAI.
 */
class OpenAIProvider implements AIProvider
{
    private Client $client;
    private string $serviceUrl;
    private string $internalToken;

    public function __construct()
    {
        $this->serviceUrl    = rtrim($_ENV['AI_SERVICE_URL'] ?? 'http://localhost:8001', '/');
        $this->internalToken = $_ENV['AI_INTERNAL_TOKEN'] ?? '';

        $this->client = new Client([
            'timeout'         => 15,
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
                'message'  => $prompt,
                'context'  => $context,
                'provider' => 'openai',
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        return $data['response'] ?? '';
    }

    public function suggestCategory(string $description, array $availableCategories = []): ?string
    {
        $body = ['description' => $description, 'provider' => 'openai'];

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
                'provider'    => 'openai',
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true) ?? [];
    }
}
