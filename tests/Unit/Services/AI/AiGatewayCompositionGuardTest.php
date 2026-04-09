<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use PHPUnit\Framework\TestCase;

class AiGatewayCompositionGuardTest extends TestCase
{
    public function testAiGatewayProvidersDoNotReintroduceInlineClientFactories(): void
    {
        $openAiProvider = (string) file_get_contents('Application/Services/AI/Providers/OpenAIProvider.php');

        $this->assertStringNotContainsString(
            'static fn(): Client => new Client(',
            $openAiProvider,
            'OpenAIProvider não deve montar Guzzle Client inline por closure.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+Client\s*\(/',
            $openAiProvider,
            'OpenAIProvider não deve instanciar Guzzle Client diretamente.'
        );

        $quickQueryHandler = (string) file_get_contents('Application/Services/AI/Handlers/QuickQueryHandler.php');

        $this->assertStringNotContainsString(
            'new ChatHandler()',
            $quickQueryHandler,
            'QuickQueryHandler não deve instanciar ChatHandler diretamente.'
        );
    }
}
