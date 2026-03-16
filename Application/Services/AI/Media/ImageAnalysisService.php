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
    private const DEFAULT_MAX_IMAGE_DIMENSION = 1024;
    private const FORCE_REENCODE_MIN_BYTES = 250_000;
    private const SMALL_IMAGE_AUTO_MAX_DIMENSION = 720;
    private const SMALL_IMAGE_AUTO_MAX_BYTES = 180_000;
    private const JPEG_QUALITY = 72;

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
        $startedAt = microtime(true);

        if ($this->apiKey === '') {
            return new ReceiptAnalysisResult(
                success: false,
                durationMs: $this->elapsedMs($startedAt),
                error: 'OPENAI_API_KEY nao configurada',
            );
        }

        $mimeType = $this->detectMimeType($content) ?? strtolower($mimeType);
        $extension = strtolower((string) pathinfo((string) $filename, PATHINFO_EXTENSION));
        $isPdf = $mimeType === 'application/pdf' || $extension === 'pdf';
        $imageDetail = 'low';

        if (!$isPdf) {
            $preparedImage = $this->prepareImageForVision($content, $mimeType);
            $content = $preparedImage['content'];
            $mimeType = $preparedImage['mimeType'];
            $imageDetail = $preparedImage['detail'];
        }

        if (!$isPdf && strlen($content) > self::MAX_IMAGE_SIZE) {
            return new ReceiptAnalysisResult(
                success: false,
                durationMs: $this->elapsedMs($startedAt),
                error: 'Imagem excede limite de 20MB',
            );
        }

        if ($isPdf && strlen($content) > self::MAX_PDF_SIZE) {
            return new ReceiptAnalysisResult(
                success: false,
                durationMs: $this->elapsedMs($startedAt),
                error: 'PDF excede limite de 20MB',
            );
        }

        try {
            $response = $isPdf
                ? $this->analyzePdf($content, $contextHint)
                : $this->analyzeImage(
                    $content,
                    $mimeType !== '' ? $mimeType : 'image/jpeg',
                    $contextHint,
                    $imageDetail,
                );

            $result = json_decode($response->getBody()->getContents(), true);
            $usage = $result['usage'] ?? [];
            $tokensPrompt = $this->extractPromptTokens($usage);
            $tokensCompletion = $this->extractCompletionTokens($usage);
            $tokensUsed = (int) ($usage['total_tokens'] ?? ($tokensPrompt + $tokensCompletion));
            $durationMs = $this->elapsedMs($startedAt);
            $rawText = $this->extractResponseText($result);
            $parsed = json_decode($rawText, true);

            if (!is_array($parsed)) {
                return new ReceiptAnalysisResult(
                    success: false,
                    rawText: $rawText,
                    tokensUsed: $tokensUsed,
                    promptTokens: $tokensPrompt,
                    completionTokens: $tokensCompletion,
                    durationMs: $durationMs,
                    error: 'Resposta nao e JSON valido',
                );
            }

            return new ReceiptAnalysisResult(
                success: true,
                data: $parsed,
                rawText: $rawText,
                tokensUsed: $tokensUsed,
                promptTokens: $tokensPrompt,
                completionTokens: $tokensCompletion,
                durationMs: $durationMs,
            );
        } catch (GuzzleException $e) {
            error_log('[ImageAnalysis] Erro Vision API: ' . $e->getMessage());

            return new ReceiptAnalysisResult(
                success: false,
                durationMs: $this->elapsedMs($startedAt),
                error: 'Falha na API Vision: ' . $e->getMessage(),
            );
        }
    }

    private function analyzeImage(
        string $content,
        string $mimeType,
        ?string $contextHint,
        string $detail = 'auto',
    ): ResponseInterface
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
                'max_tokens'  => 350,
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
                                    'detail' => $detail,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @return array{content:string,mimeType:string,detail:string}
     */
    private function prepareImageForVision(string $content, string $mimeType): array
    {
        [$width, $height] = $this->detectImageDimensions($content);
        $detail = $this->resolveImageDetail($width, $height, strlen($content));

        if (!$this->canOptimizeRasterImage($mimeType)) {
            return [
                'content' => $content,
                'mimeType' => $mimeType,
                'detail' => $detail,
            ];
        }

        $maxDimension = $this->visionMaxDimension();
        $currentBytes = strlen($content);
        $shouldOptimize = $mimeType !== 'image/jpeg'
            || $currentBytes >= self::FORCE_REENCODE_MIN_BYTES
            || ($width !== null && $height !== null && max($width, $height) > $maxDimension);

        if (!$shouldOptimize) {
            return [
                'content' => $content,
                'mimeType' => $mimeType,
                'detail' => $detail,
            ];
        }

        $optimized = $this->optimizeImageForVision($content, $maxDimension);
        if ($optimized === null) {
            return [
                'content' => $content,
                'mimeType' => $mimeType,
                'detail' => $detail,
            ];
        }

        return [
            'content' => $optimized['content'],
            'mimeType' => $optimized['mimeType'],
            'detail' => $detail,
        ];
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

    private function extractPromptTokens(array $usage): int
    {
        return (int) ($usage['prompt_tokens'] ?? $usage['input_tokens'] ?? 0);
    }

    private function extractCompletionTokens(array $usage): int
    {
        return (int) ($usage['completion_tokens'] ?? $usage['output_tokens'] ?? 0);
    }

    private function elapsedMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    /**
     * @return array{0:int|null,1:int|null}
     */
    private function detectImageDimensions(string $content): array
    {
        $size = @getimagesizefromstring($content);

        if (!is_array($size) || !isset($size[0], $size[1])) {
            return [null, null];
        }

        return [(int) $size[0], (int) $size[1]];
    }

    private function resolveImageDetail(?int $width, ?int $height, int $bytes): string
    {
        $configured = strtolower(trim((string) ($_ENV['OPENAI_VISION_DETAIL'] ?? '')));
        if (in_array($configured, ['low', 'auto', 'high'], true)) {
            return $configured;
        }

        $maxDimension = max((int) ($width ?? 0), (int) ($height ?? 0));

        if ($maxDimension > 0
            && $maxDimension <= self::SMALL_IMAGE_AUTO_MAX_DIMENSION
            && $bytes <= self::SMALL_IMAGE_AUTO_MAX_BYTES
        ) {
            return 'auto';
        }

        return 'low';
    }

    private function visionMaxDimension(): int
    {
        $configured = (int) ($_ENV['OPENAI_VISION_MAX_DIMENSION'] ?? self::DEFAULT_MAX_IMAGE_DIMENSION);
        return max(768, min(2000, $configured));
    }

    private function canOptimizeRasterImage(string $mimeType): bool
    {
        return in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true);
    }

    /**
     * @return array{content:string,mimeType:string}|null
     */
    private function optimizeImageForVision(string $content, int $maxDimension): ?array
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagescale')) {
            return null;
        }

        $image = @imagecreatefromstring($content);
        if ($image === false) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= 0 || $height <= 0) {
            imagedestroy($image);
            return null;
        }

        $scale = min(1, $maxDimension / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($canvas === false) {
            imagedestroy($image);
            return null;
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        imagedestroy($image);

        ob_start();
        imagejpeg($canvas, null, self::JPEG_QUALITY);
        $optimized = (string) ob_get_clean();
        imagedestroy($canvas);

        if ($optimized === '') {
            return null;
        }

        if (strlen($optimized) >= strlen($content) && max($width, $height) <= $maxDimension) {
            return [
                'content' => $content,
                'mimeType' => $this->detectMimeType($content) ?? 'image/jpeg',
            ];
        }

        return [
            'content' => $optimized,
            'mimeType' => 'image/jpeg',
        ];
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
