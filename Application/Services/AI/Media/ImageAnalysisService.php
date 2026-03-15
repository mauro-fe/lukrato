<?php

declare(strict_types=1);

namespace Application\Services\AI\Media;

use Application\Services\AI\PromptBuilder;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

/**
 * Analisa comprovantes em imagem ou PDF e extrai dados financeiros estruturados.
 */
class ImageAnalysisService
{
    private const MAX_IMAGE_SIZE = 20 * 1024 * 1024; // 20MB
    private const MAX_PDF_SIZE = 20 * 1024 * 1024; // 20MB

    private Client $client;
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = $_ENV['OPENAI_API_KEY'] ?? '';
        $this->model  = $_ENV['OPENAI_VISION_MODEL']
            ?? $_ENV['OPENAI_DOCUMENT_MODEL']
            ?? $_ENV['OPENAI_MODEL']
            ?? 'gpt-4o-mini';

        $this->client = new Client([
            'base_uri'        => 'https://api.openai.com/v1/',
            'timeout'         => 45,
            'connect_timeout' => 10,
        ]);
    }

    public function analyzeReceipt(
        string $content,
        string $mimeType = 'image/jpeg',
        ?string $contextHint = null,
        ?string $filename = null,
    ): ReceiptAnalysisResult {
        if ($this->apiKey === '') {
            return new ReceiptAnalysisResult(
                success: false,
                error: 'OPENAI_API_KEY nao configurada',
            );
        }

        $mimeType = $this->detectMimeType($content) ?? strtolower($mimeType);
        $extension = strtolower((string) pathinfo((string) $filename, PATHINFO_EXTENSION));
        $isPdf = $mimeType === 'application/pdf' || $extension === 'pdf';

        if (!$isPdf && strlen($content) > self::MAX_IMAGE_SIZE) {
            return new ReceiptAnalysisResult(
                success: false,
                error: 'Imagem excede limite de 20MB',
            );
        }

        if ($isPdf && strlen($content) > self::MAX_PDF_SIZE) {
            return new ReceiptAnalysisResult(
                success: false,
                error: 'PDF excede limite de 20MB',
            );
        }

        try {
            $response = $isPdf
                ? $this->analyzePdf($content, $contextHint)
                : $this->analyzeImage($content, $mimeType !== '' ? $mimeType : 'image/jpeg', $contextHint);

            $result = json_decode($response->getBody()->getContents(), true);
            $usage = $result['usage'] ?? [];
            $tokensUsed = (int) ($usage['total_tokens'] ?? 0);
            $rawText = $this->extractResponseText($result);
            $parsed = json_decode($rawText, true);

            if (!is_array($parsed)) {
                return new ReceiptAnalysisResult(
                    success: false,
                    rawText: $rawText,
                    tokensUsed: $tokensUsed,
                    error: 'Resposta nao e JSON valido',
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

    private function analyzeImage(string $content, string $mimeType, ?string $contextHint): ResponseInterface
    {
        $base64 = base64_encode($content);

        return $this->client->post('chat/completions', [
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'model'       => $this->model,
                'temperature' => 0.1,
                'max_tokens'  => 700,
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
                                'text' => PromptBuilder::receiptAnalysisUser($contextHint),
                            ],
                            [
                                'type'      => 'image_url',
                                'image_url' => [
                                    'url'    => "data:{$mimeType};base64,{$base64}",
                                    'detail' => 'high',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function analyzePdf(string $content, ?string $contextHint): ResponseInterface
    {
        $base64 = base64_encode($content);

        return $this->client->post('responses', [
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'model' => $this->model,
                'input' => [[
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => PromptBuilder::receiptAnalysisUser($contextHint),
                        ],
                        [
                            'type' => 'input_file',
                            'filename' => 'document.pdf',
                            'file_data' => "data:application/pdf;base64,{$base64}",
                        ],
                    ],
                ]],
                'text' => [
                    'format' => [
                        'type' => 'json_object',
                    ],
                ],
            ],
        ]);
    }

    private function extractResponseText(array $result): string
    {
        $chatText = $result['choices'][0]['message']['content'] ?? null;
        if (is_string($chatText) && trim($chatText) !== '') {
            return $chatText;
        }

        $responseText = $result['output'][0]['content'][0]['text'] ?? null;
        if (is_string($responseText) && trim($responseText) !== '') {
            return $responseText;
        }

        return '{}';
    }

    private function detectMimeType(string $content): ?string
    {
        if (strlen($content) < 4) {
            return null;
        }

        if (str_starts_with($content, '%PDF')) {
            return 'application/pdf';
        }

        $header = substr($content, 0, 4);

        if (str_starts_with($header, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }

        if (str_starts_with($header, "\x89PNG")) {
            return 'image/png';
        }

        if (str_starts_with($header, 'RIFF') && strlen($content) >= 12 && substr($content, 8, 4) === 'WEBP') {
            return 'image/webp';
        }

        return null;
    }
}
