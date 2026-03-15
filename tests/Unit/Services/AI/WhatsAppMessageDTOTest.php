<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\DTO\AI\WhatsAppMessageDTO;
use PHPUnit\Framework\TestCase;

class WhatsAppMessageDTOTest extends TestCase
{
    public function testParsesImageMediaPayload(): void
    {
        $dto = WhatsAppMessageDTO::fromMetaPayload([
            'contacts' => [
                ['profile' => ['name' => 'Mauro']],
            ],
            'messages' => [[
                'id' => 'wamid.1',
                'from' => '5511999999999',
                'type' => 'image',
                'image' => [
                    'id' => 'media-1',
                    'mime_type' => 'image/jpeg',
                    'caption' => 'nota do almoco',
                ],
            ]],
        ]);

        $this->assertInstanceOf(WhatsAppMessageDTO::class, $dto);
        $this->assertSame('image', $dto->type);
        $this->assertTrue($dto->isMedia());
        $this->assertTrue($dto->isImage());
        $this->assertSame('media-1', $dto->mediaId);
        $this->assertSame('nota do almoco', $dto->body);
    }

    public function testParsesPdfDocumentPayload(): void
    {
        $dto = WhatsAppMessageDTO::fromMetaPayload([
            'contacts' => [
                ['profile' => ['name' => 'Mauro']],
            ],
            'messages' => [[
                'id' => 'wamid.2',
                'from' => '5511999999999',
                'type' => 'document',
                'document' => [
                    'id' => 'media-2',
                    'mime_type' => 'application/pdf',
                    'filename' => 'comprovante.pdf',
                    'caption' => 'pix banco',
                ],
            ]],
        ]);

        $this->assertInstanceOf(WhatsAppMessageDTO::class, $dto);
        $this->assertTrue($dto->isDocument());
        $this->assertSame('comprovante.pdf', $dto->filename);
        $this->assertSame('application/pdf', $dto->mimeType);
    }

    public function testDetectsLooseTextConfirmation(): void
    {
        $dto = WhatsAppMessageDTO::fromMetaPayload([
            'contacts' => [
                ['profile' => ['name' => 'Mauro']],
            ],
            'messages' => [[
                'id' => 'wamid.3',
                'from' => '5511999999999',
                'type' => 'text',
                'text' => [
                    'body' => 'ok está bom',
                ],
            ]],
        ]);

        $this->assertInstanceOf(WhatsAppMessageDTO::class, $dto);
        $this->assertTrue($dto->isConfirmationReply());
        $this->assertTrue($dto->isAffirmative());
    }
}
