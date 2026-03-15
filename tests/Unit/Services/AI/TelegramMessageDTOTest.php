<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\DTO\AI\TelegramMessageDTO;
use PHPUnit\Framework\TestCase;

class TelegramMessageDTOTest extends TestCase
{
    public function testParsesPdfDocumentAsDocumentMedia(): void
    {
        $dto = TelegramMessageDTO::fromTelegramUpdate([
            'update_id' => 10,
            'message' => [
                'message_id' => 22,
                'chat' => ['id' => 99],
                'from' => ['first_name' => 'Mauro'],
                'caption' => 'comprovante pix',
                'document' => [
                    'file_id' => 'pdf123',
                    'file_name' => 'comprovante.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => 12345,
                ],
            ],
        ]);

        $this->assertInstanceOf(TelegramMessageDTO::class, $dto);
        $this->assertSame('document', $dto->type);
        $this->assertTrue($dto->isDocument());
        $this->assertTrue($dto->isMedia());
        $this->assertSame('comprovante.pdf', $dto->filename);
    }

    public function testParsesVideoNoteAsVideoMedia(): void
    {
        $dto = TelegramMessageDTO::fromTelegramUpdate([
            'update_id' => 11,
            'message' => [
                'message_id' => 23,
                'chat' => ['id' => 99],
                'from' => ['first_name' => 'Mauro'],
                'video_note' => [
                    'file_id' => 'vid123',
                    'file_size' => 54321,
                ],
            ],
        ]);

        $this->assertInstanceOf(TelegramMessageDTO::class, $dto);
        $this->assertSame('video', $dto->type);
        $this->assertTrue($dto->isVideo());
        $this->assertTrue($dto->isMedia());
    }

    public function testDetectsLooseTextConfirmation(): void
    {
        $dto = TelegramMessageDTO::fromTelegramUpdate([
            'update_id' => 12,
            'message' => [
                'message_id' => 24,
                'chat' => ['id' => 99],
                'from' => ['first_name' => 'Mauro'],
                'text' => 'pode pagar',
            ],
        ]);

        $this->assertInstanceOf(TelegramMessageDTO::class, $dto);
        $this->assertTrue($dto->isConfirmationReply());
        $this->assertTrue($dto->isAffirmative());
    }
}
