<?php

declare(strict_types=1);

namespace Application\Services\AI\Media;

/**
 * Extrai o áudio de vídeos com ffmpeg e reaproveita o pipeline de transcrição.
 */
class VideoTranscriptionService
{
    private const MAX_VIDEO_SIZE = 50 * 1024 * 1024; // 50MB

    public function __construct(
        private readonly ?AudioTranscriptionService $transcriber = null,
    ) {}

    public function transcribe(string $videoContent, string $filename = 'video.mp4', ?string $prompt = null): TranscriptionResult
    {
        if (strlen($videoContent) > self::MAX_VIDEO_SIZE) {
            return new TranscriptionResult(
                success: false,
                error: 'Vídeo excede o limite de 50MB para pré-processamento',
            );
        }

        $ffmpeg = $this->resolveFfmpegBinary();
        if ($ffmpeg === null) {
            return new TranscriptionResult(
                success: false,
                error: 'FFmpeg não está disponível para processar vídeos',
            );
        }

        $tempDir = rtrim((string) sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'lukrato-media';
        if (!is_dir($tempDir) && !mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
            return new TranscriptionResult(
                success: false,
                error: 'Não foi possível preparar diretório temporário para vídeo',
            );
        }

        $inputPath = tempnam($tempDir, 'vid_');
        if ($inputPath === false) {
            return new TranscriptionResult(success: false, error: 'Falha ao criar arquivo temporário de vídeo');
        }

        $outputPath = $inputPath . '.mp3';
        file_put_contents($inputPath, $videoContent);

        $command = escapeshellarg($ffmpeg)
            . ' -y -i ' . escapeshellarg($inputPath)
            . ' -vn -acodec libmp3lame -ar 16000 -ac 1 ' . escapeshellarg($outputPath)
            . ' 2>&1';

        $start = hrtime(true);
        exec($command, $output, $exitCode);

        try {
            if ($exitCode !== 0 || !is_file($outputPath)) {
                return new TranscriptionResult(
                    success: false,
                    durationMs: (int) ((hrtime(true) - $start) / 1_000_000),
                    error: 'Falha ao extrair áudio do vídeo com ffmpeg',
                );
            }

            $audioContent = file_get_contents($outputPath);
            if (!is_string($audioContent) || $audioContent === '') {
                return new TranscriptionResult(
                    success: false,
                    durationMs: (int) ((hrtime(true) - $start) / 1_000_000),
                    error: 'Áudio extraído do vídeo está vazio',
                );
            }

            $transcriber = $this->transcriber ?? new AudioTranscriptionService();
            $result = $transcriber->transcribe($audioContent, 'video-audio.mp3', $prompt);

            return new TranscriptionResult(
                success: $result->success,
                text: $result->text,
                durationMs: (int) ((hrtime(true) - $start) / 1_000_000),
                error: $result->error,
            );
        } finally {
            @unlink($inputPath);
            @unlink($outputPath);
        }
    }

    private function resolveFfmpegBinary(): ?string
    {
        $configured = trim((string) ($_ENV['FFMPEG_BINARY'] ?? getenv('FFMPEG_BINARY') ?: ''));
        if ($configured !== '') {
            return $configured;
        }

        $which = strtoupper(substr(PHP_OS_FAMILY, 0, 3)) === 'WIN' ? 'where' : 'which';
        $output = [];
        $exitCode = 1;
        @exec($which . ' ffmpeg', $output, $exitCode);

        if ($exitCode !== 0 || empty($output[0])) {
            return null;
        }

        return trim((string) $output[0]);
    }
}
