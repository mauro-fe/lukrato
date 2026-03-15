<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Services\AI\Media\MediaType;
use Application\Services\AI\Media\MediaTypeGuesser;
use PHPUnit\Framework\TestCase;

class MediaTypeGuesserTest extends TestCase
{
    public function testDetectsPdfByMimeType(): void
    {
        $type = MediaTypeGuesser::detect('document', 'application/pdf', 'comprovante.pdf');
        $this->assertSame(MediaType::PDF, $type);
    }

    public function testDetectsImageBySourceType(): void
    {
        $type = MediaTypeGuesser::detect('image', null, 'foto.bin');
        $this->assertSame(MediaType::IMAGE, $type);
    }

    public function testDetectsVideoBySourceType(): void
    {
        $type = MediaTypeGuesser::detect('video', null, 'clip.mp4');
        $this->assertSame(MediaType::VIDEO, $type);
    }

    public function testDetectsImageByMagicBytes(): void
    {
        $type = MediaTypeGuesser::detect('document', null, 'arquivo.bin', "\x89PNGfake");
        $this->assertSame(MediaType::IMAGE, $type);
    }
}
