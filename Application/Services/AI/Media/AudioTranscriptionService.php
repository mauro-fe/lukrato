<?php

declare(strict_types=1);

namespace Application\Services\AI\Media;

use Application\Config\AiRuntimeConfig;
use Application\Container\ApplicationContainer;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Transcreve audio para texto usando a API de speech-to-text da OpenAI.
 */
class AudioTranscriptionService
{
    private const MAX_FILE_SIZE = 25 * 1024 * 1024; // 25MB
    private const SUPPORTED_FORMATS = ['ogg', 'oga', 'mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm'];

    /** Extensions that OpenAI rejects but are aliases for a supported format. */
    private const EXTENSION_ALIASES = [
        'oga' => 'ogg',
    ];

    private OpenAIAudioHttpClient $client;
    private string $apiKey;
    private string $model;
    private AiRuntimeConfig $runtimeConfig;

    public function __construct(?OpenAIAudioHttpClient $client = null, ?AiRuntimeConfig $runtimeConfig = null)
    {
        $this->runtimeConfig = ApplicationContainer::resolveOrNew($runtimeConfig, AiRuntimeConfig::class);
        $this->apiKey = $this->runtimeConfig->openAiApiKey();
        $this->model = $this->runtimeConfig->openAiTranscriptionModel();

        $this->client = ApplicationContainer::resolveOrNew($client, OpenAIAudioHttpClient::class);
    }

    public function transcribe(
        string $audioContent,
        string $filename = 'audio.ogg',
        ?string $prompt = null,
    ): TranscriptionResult {
        if ($this->apiKey === '') {
            return new TranscriptionResult(
                success: false,
                error: 'OPENAI_API_KEY nao configurada',
            );
        }

        if (strlen($audioContent) > self::MAX_FILE_SIZE) {
            return new TranscriptionResult(
                success: false,
                error: 'Arquivo excede limite de 25MB da API de transcricao',
            );
        }

        $startTime = hrtime(true);

        try {
            $filename = $this->normalizeFilename($filename);

            $multipart = [
                ['name' => 'file', 'contents' => $audioContent, 'filename' => $filename],
                ['name' => 'model', 'contents' => $this->model],
                ['name' => 'language', 'contents' => 'pt'],
                ['name' => 'response_format', 'contents' => 'json'],
            ];

            $prompt = trim((string) $prompt);
            if ($prompt !== '') {
                $multipart[] = ['name' => 'prompt', 'contents' => mb_substr($prompt, 0, 500)];
            }

            $response = $this->client->post('audio/transcriptions', [
                'headers'   => ['Authorization' => "Bearer {$this->apiKey}"],
                'multipart' => $multipart,
            ]);

            $durationMs = (int) ((hrtime(true) - $startTime) / 1_000_000);
            $result = json_decode($response->getBody()->getContents(), true);
            $text = trim((string) ($result['text'] ?? ''));

            return new TranscriptionResult(
                success: $text !== '',
                text: $text,
                durationMs: $durationMs,
                error: $text === '' ? 'Transcricao retornou vazia' : null,
            );
        } catch (GuzzleException $e) {
            $durationMs = (int) ((hrtime(true) - $startTime) / 1_000_000);
            \Application\Services\Infrastructure\LogService::safeErrorLog('[AudioTranscription] Erro Speech API: ' . $e->getMessage());

            return new TranscriptionResult(
                success: false,
                durationMs: $durationMs,
                error: 'Falha na API de transcricao: ' . $e->getMessage(),
            );
        }
    }

    public function isFormatSupported(string $extension): bool
    {
        return in_array(strtolower($extension), self::SUPPORTED_FORMATS, true);
    }

    public function isMimeTypeSupported(?string $mimeType): bool
    {
        $mimeType = strtolower(trim((string) $mimeType));

        if ($mimeType === '') {
            return false;
        }

        return str_starts_with($mimeType, 'audio/')
            || in_array($mimeType, ['video/mp4', 'video/webm'], true);
    }

    /**
     * Remapeia extensões que o app aceita mas a OpenAI rejeita (ex: .oga → .ogg).
     */
    private function normalizeFilename(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $alias = self::EXTENSION_ALIASES[$ext] ?? null;

        if ($alias !== null) {
            return preg_replace('/\.[^.]+$/', '.' . $alias, $filename);
        }

        return $filename;
    }
}
