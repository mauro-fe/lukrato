<?php

declare(strict_types=1);

namespace Application\Services\AI\Media;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Transcreve áudio para texto usando OpenAI Whisper API.
 *
 * Endpoint: POST /v1/audio/transcriptions (multipart form-data)
 *
 * Env vars:
 *  OPENAI_API_KEY=sk-...
 */
class AudioTranscriptionService
{
    private const MODEL = 'whisper-1';
    private const MAX_FILE_SIZE = 25 * 1024 * 1024; // 25MB (limite Whisper)
    private const SUPPORTED_FORMATS = ['ogg', 'oga', 'mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm'];

    private Client $client;
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = $_ENV['OPENAI_API_KEY'] ?? '';

        $this->client = new Client([
            'base_uri'        => 'https://api.openai.com/v1/',
            'timeout'         => 60,
            'connect_timeout' => 10,
        ]);
    }

    /**
     * Transcreve conteúdo de áudio para texto em português.
     */
    public function transcribe(string $audioContent, string $filename = 'audio.ogg'): TranscriptionResult
    {
        if ($this->apiKey === '') {
            return new TranscriptionResult(
                success: false,
                error: 'OPENAI_API_KEY não configurada',
            );
        }

        if (strlen($audioContent) > self::MAX_FILE_SIZE) {
            return new TranscriptionResult(
                success: false,
                error: 'Arquivo excede limite de 25MB do Whisper',
            );
        }

        $startTime = hrtime(true);

        try {
            $response = $this->client->post('audio/transcriptions', [
                'headers'   => ['Authorization' => "Bearer {$this->apiKey}"],
                'multipart' => [
                    ['name' => 'file', 'contents' => $audioContent, 'filename' => $filename],
                    ['name' => 'model', 'contents' => self::MODEL],
                    ['name' => 'language', 'contents' => 'pt'],
                    ['name' => 'response_format', 'contents' => 'json'],
                ],
            ]);

            $durationMs = (int) ((hrtime(true) - $startTime) / 1_000_000);
            $result = json_decode($response->getBody()->getContents(), true);
            $text = trim($result['text'] ?? '');

            return new TranscriptionResult(
                success: $text !== '',
                text: $text,
                durationMs: $durationMs,
                error: $text === '' ? 'Transcrição retornou vazia' : null,
            );
        } catch (GuzzleException $e) {
            $durationMs = (int) ((hrtime(true) - $startTime) / 1_000_000);
            error_log('[AudioTranscription] Erro Whisper: ' . $e->getMessage());

            return new TranscriptionResult(
                success: false,
                durationMs: $durationMs,
                error: 'Falha na API Whisper: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Verifica se a extensão do arquivo é suportada pelo Whisper.
     */
    public function isFormatSupported(string $extension): bool
    {
        return in_array(strtolower($extension), self::SUPPORTED_FORMATS, true);
    }
}
