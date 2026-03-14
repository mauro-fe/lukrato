<?php

declare(strict_types=1);

namespace Application\Services\AI\Media;

use Application\Services\AI\PromptBuilder;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Analisa imagens de comprovantes/recibos usando GPT-4o-mini Vision.
 *
 * Usa o endpoint chat/completions com content tipo image_url (base64).
 *
 * Env vars:
 *  OPENAI_API_KEY=sk-...
 *  OPENAI_MODEL=gpt-4o-mini (opcional)
 */
class ImageAnalysisService
{
    private const MAX_IMAGE_SIZE = 20 * 1024 * 1024; // 20MB

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

    /**
     * Analisa imagem de comprovante/recibo e extrai dados financeiros.
     */
    public function analyzeReceipt(string $imageContent, string $mimeType = 'image/jpeg'): ReceiptAnalysisResult
    {
        if ($this->apiKey === '') {
            return new ReceiptAnalysisResult(
                success: false,
                error: 'OPENAI_API_KEY não configurada',
            );
        }

        if (strlen($imageContent) > self::MAX_IMAGE_SIZE) {
            return new ReceiptAnalysisResult(
                success: false,
                error: 'Imagem excede limite de 20MB',
            );
        }

        $mimeType = $this->detectMimeType($imageContent) ?? $mimeType;
        $base64 = base64_encode($imageContent);

        try {
            $response = $this->client->post('chat/completions', [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => $this->model,
                    'temperature' => 0.1,
                    'max_tokens'  => 500,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role'    => 'system',
                            'content' => PromptBuilder::receiptAnalysisSystem(),
                        ],
                        [
                            'role'    => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => PromptBuilder::receiptAnalysisUser(),
                                ],
                                [
                                    'type'      => 'image_url',
                                    'image_url' => [
                                        'url'    => "data:{$mimeType};base64,{$base64}",
                                        'detail' => 'low',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            $usage = $result['usage'] ?? [];
            $tokensUsed = ($usage['total_tokens'] ?? 0);
            $rawText = $result['choices'][0]['message']['content'] ?? '{}';
            $parsed = json_decode($rawText, true);

            if (!is_array($parsed)) {
                return new ReceiptAnalysisResult(
                    success: false,
                    rawText: $rawText,
                    tokensUsed: $tokensUsed,
                    error: 'Resposta não é JSON válido',
                );
            }

            return new ReceiptAnalysisResult(
                success: true,
                data: $parsed,
                rawText: $rawText,
                tokensUsed: $tokensUsed,
            );
        } catch (GuzzleException $e) {
            error_log('[ImageAnalysis] Erro Vision API: ' . $e->getMessage());

            return new ReceiptAnalysisResult(
                success: false,
                error: 'Falha na API Vision: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Detecta MIME type pelos magic bytes da imagem.
     */
    private function detectMimeType(string $content): ?string
    {
        if (strlen($content) < 4) {
            return null;
        }

        $header = substr($content, 0, 4);

        // JPEG: FF D8 FF
        if (str_starts_with($header, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }

        // PNG: 89 50 4E 47
        if (str_starts_with($header, "\x89PNG")) {
            return 'image/png';
        }

        // WebP: RIFF....WEBP
        if (str_starts_with($header, 'RIFF') && strlen($content) >= 12 && substr($content, 8, 4) === 'WEBP') {
            return 'image/webp';
        }

        return null;
    }
}
