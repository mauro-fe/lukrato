<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use PHPUnit\Framework\TestCase;

class AiTransportCompositionGuardTest extends TestCase
{
    public function testAiTransportServicesDoNotInstantiateHttpClientsInline(): void
    {
        $files = [
            'Application/Services/AI/WhatsApp/WhatsAppService.php',
            'Application/Services/AI/WhatsApp/WhatsAppMediaDownloader.php',
            'Application/Services/AI/Telegram/TelegramService.php',
            'Application/Services/AI/Telegram/TelegramFileDownloader.php',
            'Application/Services/AI/Media/AudioTranscriptionService.php',
            'Application/Services/AI/Media/ImageAnalysisService.php',
            'Application/Services/AI/Providers/OllamaProvider.php',
        ];

        foreach ($files as $filePath) {
            $content = (string) file_get_contents($filePath);

            $this->assertDoesNotMatchRegularExpression(
                '/new\s+Client\s*\(/',
                $content,
                "Serviço de transporte AI não deve instanciar Guzzle Client diretamente: {$filePath}"
            );
        }
    }
}
