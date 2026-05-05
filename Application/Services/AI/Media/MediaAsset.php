<?php

declare(strict_types=1);

namespace Application\Services\AI\Media;

/**
 * Representa um anexo já baixado, pronto para classificação e processamento.
 */
readonly class MediaAsset
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $sourceType,
        public string $content,
        public ?string $mimeType = null,
        public ?string $filename = null,
        public ?int $fileSize = null,
        public ?string $caption = null,
        public ?string $remoteId = null,
        public array $metadata = [],
    ) {}

    public function mediaType(): string
    {
        return MediaTypeGuesser::detect(
            $this->sourceType,
            $this->mimeType,
            $this->filename,
            $this->content
        );
    }

    public function extension(string $default = 'bin'): string
    {
        $extension = strtolower((string) pathinfo((string) $this->filename, PATHINFO_EXTENSION));
        return $extension !== '' ? $extension : $default;
    }

    public function promptHint(): ?string
    {
        $caption = trim((string) $this->caption);
        return $caption !== '' ? $caption : null;
    }
}
