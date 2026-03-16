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

    public function testParsesInteractiveConfirmationButton(): void
    {
        $dto = WhatsAppMessageDTO::fromMetaPayload([
            'contacts' => [
                ['profile' => ['name' => 'Mauro']],
            ],
            'messages' => [[
                'id' => 'wamid.4',
                'from' => '5511999999999',
                'type' => 'interactive',
                'interactive' => [
                    'type' => 'button_reply',
                    'button_reply' => [
                        'id' => 'confirm_yes',
                        'title' => 'Sim',
                    ],
                ],
            ]],
        ]);

        $this->assertInstanceOf(WhatsAppMessageDTO::class, $dto);
        $this->assertTrue($dto->isConfirmationReply());
        $this->assertTrue($dto->isAffirmative());
    }

    public function testParsesQuickReplySelection(): void
    {
        $dto = WhatsAppMessageDTO::fromMetaPayload([
            'contacts' => [
                ['profile' => ['name' => 'Mauro']],
            ],
            'messages' => [[
                'id' => 'wamid.5',
                'from' => '5511999999999',
                'type' => 'interactive',
                'interactive' => [
                    'type' => 'button_reply',
                    'button_reply' => [
                        'id' => 'quick_reply_2',
                        'title' => 'Criar meta',
                    ],
                ],
            ]],
        ]);

        $this->assertInstanceOf(WhatsAppMessageDTO::class, $dto);
        $this->assertTrue($dto->isQuickReplySelection());
        $this->assertSame(2, $dto->getSelectedQuickReplyIndex());
    }

    public function testParsesOptionSelection(): void
    {
        $dto = WhatsAppMessageDTO::fromMetaPayload([
            'contacts' => [
                ['profile' => ['name' => 'Mauro']],
            ],
            'messages' => [[
                'id' => 'wamid.6',
                'from' => '5511999999999',
                'type' => 'interactive',
                'interactive' => [
                    'type' => 'button_reply',
                    'button_reply' => [
                        'id' => 'select_option_1',
                        'title' => 'Conta principal',
                    ],
                ],
            ]],
        ]);

        $this->assertInstanceOf(WhatsAppMessageDTO::class, $dto);
        $this->assertTrue($dto->isOptionSelection());
        $this->assertSame(1, $dto->getSelectedOptionIndex());
    }
}
