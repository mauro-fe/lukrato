<?php

declare(strict_types=1);

namespace Application\Services\AI\Media;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Transcreve audio para texto usando a API de speech-to-text da OpenAI.
 */
class AudioTranscriptionService
{
    private const MAX_FILE_SIZE = 25 * 1024 * 1024; // 25MB
    private const SUPPORTED_FORMATS = ['ogg', 'oga', 'mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm'];

    private Client $client;
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = $_ENV['OPENAI_API_KEY'] ?? '';
        $this->model = $_ENV['OPENAI_TRANSCRIPTION_MODEL']
            ?? $_ENV['OPENAI_AUDIO_MODEL']
            ?? 'gpt-4o-mini-transcribe';

        $this->client = new Client([
            'base_uri'        => 'https://api.openai.com/v1/',
            'timeout'         => 60,
            'connect_timeout' => 10,
        ]);
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
            error_log('[AudioTranscription] Erro Speech API: ' . $e->getMessage());

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
}
