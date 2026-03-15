<?php

declare(strict_types=1);

namespace Application\Services\AI\Media;

/**
 * Classifica anexos a partir do tipo da mensagem, MIME, extensão e magic bytes.
 */
final class MediaTypeGuesser
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    private const AUDIO_EXTENSIONS = ['ogg', 'oga', 'mp3', 'mpeg', 'mpga', 'm4a', 'wav', 'webm'];
    private const VIDEO_EXTENSIONS = ['mp4', 'mov', 'avi', 'mkv', 'webm'];

    private function __construct()
    {
    }

    public static function detect(
        ?string $sourceType,
        ?string $mimeType,
        ?string $filename = null,
        ?string $content = null,
    ): string {
        $sourceType = strtolower((string) $sourceType);
        $mimeType = strtolower((string) $mimeType);
        $extension = strtolower((string) pathinfo((string) $filename, PATHINFO_EXTENSION));

        if (in_array($sourceType, ['voice', 'audio'], true)) {
            return MediaType::AUDIO;
        }

        if (in_array($sourceType, ['video', 'video_note'], true)) {
            return MediaType::VIDEO;
        }

        if (in_array($sourceType, ['photo', 'image'], true)) {
            return MediaType::IMAGE;
        }

        if ($mimeType !== '') {
            if (str_starts_with($mimeType, 'image/')) {
                return MediaType::IMAGE;
            }

            if (str_starts_with($mimeType, 'audio/')) {
                return MediaType::AUDIO;
            }

            if (str_starts_with($mimeType, 'video/')) {
                return MediaType::VIDEO;
            }

            if ($mimeType === 'application/pdf') {
                return MediaType::PDF;
            }
        }

        if ($extension !== '') {
            if (in_array($extension, self::IMAGE_EXTENSIONS, true)) {
                return MediaType::IMAGE;
            }

            if (in_array($extension, self::AUDIO_EXTENSIONS, true)) {
                return MediaType::AUDIO;
            }

            if (in_array($extension, self::VIDEO_EXTENSIONS, true)) {
                return MediaType::VIDEO;
            }

            if ($extension === 'pdf') {
                return MediaType::PDF;
            }
        }

        if (is_string($content) && $content !== '') {
            if (self::looksLikePdf($content)) {
                return MediaType::PDF;
            }

            if (self::looksLikeImage($content)) {
                return MediaType::IMAGE;
            }
        }

        return MediaType::DOCUMENT;
    }

    private static function looksLikePdf(string $content): bool
    {
        return str_starts_with($content, '%PDF');
    }

    private static function looksLikeImage(string $content): bool
    {
        return str_starts_with($content, "\xFF\xD8\xFF")
            || str_starts_with($content, "\x89PNG")
            || (str_starts_with($content, 'RIFF') && substr($content, 8, 4) === 'WEBP');
    }
}
