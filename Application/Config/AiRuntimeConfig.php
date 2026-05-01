<?php

declare(strict_types=1);

namespace Application\Config;

final class AiRuntimeConfig
{
    private const DEFAULT_PROVIDER = 'openai';
    private const DEFAULT_OPENAI_MODEL = 'gpt-4o-mini';
    private const DEFAULT_OPENAI_TRANSCRIPTION_MODEL = 'gpt-4o-mini-transcribe';
    private const DEFAULT_OLLAMA_MODEL = 'ollama';
    private const DEFAULT_ADMIN_SERVICE_URL = 'http://127.0.0.1:8002';
    private const DEFAULT_OLLAMA_SERVICE_URL = 'http://localhost:8002';
    private const DEFAULT_MAX_IMAGE_DIMENSION = 1024;

    public function provider(): string
    {
        $provider = strtolower($this->string('AI_PROVIDER', self::DEFAULT_PROVIDER));

        return $provider !== '' ? $provider : self::DEFAULT_PROVIDER;
    }

    public function openAiApiKey(): string
    {
        return $this->string('OPENAI_API_KEY', '');
    }

    public function hasOpenAiApiKey(): bool
    {
        return $this->openAiApiKey() !== '';
    }

    public function configuredOpenAiModel(): ?string
    {
        return $this->nullableString('OPENAI_MODEL');
    }

    public function openAiModel(): string
    {
        return $this->configuredOpenAiModel() ?? self::DEFAULT_OPENAI_MODEL;
    }

    public function openAiVisionModel(): string
    {
        return $this->nullableString('OPENAI_VISION_MODEL')
            ?? $this->nullableString('OPENAI_DOCUMENT_MODEL')
            ?? $this->openAiModel();
    }

    public function openAiTranscriptionModel(): string
    {
        return $this->nullableString('OPENAI_TRANSCRIPTION_MODEL')
            ?? $this->nullableString('OPENAI_AUDIO_MODEL')
            ?? self::DEFAULT_OPENAI_TRANSCRIPTION_MODEL;
    }

    public function mediaLogModel(): string
    {
        return $this->nullableString('OPENAI_VISION_MODEL')
            ?? $this->nullableString('OPENAI_DOCUMENT_MODEL')
            ?? $this->nullableString('OPENAI_TRANSCRIPTION_MODEL')
            ?? $this->nullableString('OPENAI_AUDIO_MODEL')
            ?? $this->openAiModel();
    }

    public function configuredOllamaModel(): ?string
    {
        return $this->nullableString('OLLAMA_MODEL');
    }

    public function ollamaModel(): string
    {
        return $this->configuredOllamaModel() ?? self::DEFAULT_OLLAMA_MODEL;
    }

    public function adminServiceUrl(): string
    {
        return rtrim($this->string('AI_SERVICE_URL', self::DEFAULT_ADMIN_SERVICE_URL), '/');
    }

    public function ollamaServiceUrl(): string
    {
        return rtrim($this->nullableString('AI_SERVICE_URL') ?? self::DEFAULT_OLLAMA_SERVICE_URL, '/');
    }

    public function aiInternalToken(): string
    {
        return $this->string('AI_INTERNAL_TOKEN', '');
    }

    public function visionDetail(): ?string
    {
        $detail = strtolower($this->string('OPENAI_VISION_DETAIL', ''));

        return in_array($detail, ['low', 'auto', 'high'], true) ? $detail : null;
    }

    public function visionMaxDimension(): int
    {
        $configured = (int) $this->value('OPENAI_VISION_MAX_DIMENSION', self::DEFAULT_MAX_IMAGE_DIMENSION);

        return max(768, min(2000, $configured));
    }

    public function ffmpegBinary(): ?string
    {
        return $this->nullableString('FFMPEG_BINARY');
    }

    private function string(string $key, string $default): string
    {
        return trim((string) $this->value($key, $default));
    }

    private function nullableString(string $key): ?string
    {
        $value = trim((string) $this->value($key, ''));

        return $value !== '' ? $value : null;
    }

    private function value(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_ENV) && $_ENV[$key] !== null) {
            return $_ENV[$key];
        }

        $value = getenv($key);

        if ($value !== false) {
            return $value;
        }

        return $default;
    }
}
