<?php

declare(strict_types=1);

namespace Application\Services\AI\Media;

/**
 * Resultado de uma transcrição de áudio via Whisper.
 */
readonly class TranscriptionResult
{
    public function __construct(
        public bool    $success,
        public string  $text = '',
        public int     $durationMs = 0,
        public ?string $error = null,
    ) {}
}
