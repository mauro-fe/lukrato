<?php

declare(strict_types=1);

namespace Application\Services\AI\Media;

/**
 * Tipos normalizados de mídia aceitos pelo pipeline.
 */
final class MediaType
{
    public const AUDIO = 'audio';
    public const IMAGE = 'image';
    public const PDF = 'pdf';
    public const VIDEO = 'video';
    public const DOCUMENT = 'document';
    public const UNSUPPORTED = 'unsupported';

    private function __construct()
    {
    }
}
