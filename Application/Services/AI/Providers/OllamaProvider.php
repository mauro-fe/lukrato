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
    private array $lastMeta = [];

    public function __construct()
    {
        $this->serviceUrl    = rtrim($_ENV['AI_SERVICE_URL'] ?? 'http://localhost:8002', '/');
        $this->internalToken = $_ENV['AI_INTERNAL_TOKEN'] ?? '';

        $this->client = new Client([
            'timeout'         => 120,
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
        $this->extractMeta($data);
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
        $this->extractMeta($data);
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

        $result = json_decode($response->getBody()->getContents(), true) ?? [];
        $this->extractMeta($result);
        return $result;
    }

    private function extractMeta(array $data): void
    {
        $this->lastMeta = [
            'tokens_prompt'     => $data['prompt_eval_count'] ?? $data['tokens_prompt'] ?? 0,
            'tokens_completion' => $data['eval_count'] ?? $data['tokens_completion'] ?? 0,
            'tokens_total'      => ($data['prompt_eval_count'] ?? $data['tokens_prompt'] ?? 0)
                + ($data['eval_count'] ?? $data['tokens_completion'] ?? 0),
        ];
    }

    public function getModel(): string
    {
        return $_ENV['OLLAMA_MODEL'] ?? 'ollama';
    }

    public function getLastMeta(): array
    {
        return $this->lastMeta;
    }

    public function chatWithTools(string $prompt, array $tools, array $options = []): ?array
    {
        // Ollama não suporta function calling nativo.
        // Fallback: enviar prompt pedindo JSON estruturado e parsear a resposta.
        $toolNames = array_map(fn($t) => $t['function']['name'] ?? 'unknown', $tools);
        $toolSchemas = array_map(fn($t) => json_encode($t['function']['parameters'] ?? [], JSON_UNESCAPED_UNICODE), $tools);

        $systemPrompt = $options['system_prompt']
            ?? 'Você é um assistente financeiro. Extraia as informações e retorne APENAS um JSON válido.';

        $schemaHint = '';
        foreach ($tools as $i => $tool) {
            $fname = $tool['function']['name'] ?? 'unknown';
            $fdesc = $tool['function']['description'] ?? '';
            $fparams = json_encode($tool['function']['parameters'] ?? [], JSON_UNESCAPED_UNICODE);
            $schemaHint .= "\nFunção: {$fname}\nDescrição: {$fdesc}\nSchema dos parâmetros: {$fparams}\n";
        }

        $fullPrompt = $systemPrompt
            . "\n\nRetorne APENAS um JSON com os campos extraídos conforme o schema abaixo. Sem texto adicional."
            . $schemaHint
            . "\n\nMensagem do usuário: " . $prompt;

        try {
            $response = $this->client->post("{$this->serviceUrl}/chat", [
                'headers' => $this->headers(),
                'json'    => [
                    'message'       => $fullPrompt,
                    'context'       => new \stdClass(),
                    'system_prompt' => 'Responda APENAS com JSON válido, sem markdown, sem explicações.',
                    'provider'      => 'ollama',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $this->extractMeta($data);

            $content = trim($data['response'] ?? '');

            // Remover possíveis blocos markdown ```json ... ```
            $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);

            $parsed = json_decode($content, true);
            return is_array($parsed) ? $parsed : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
