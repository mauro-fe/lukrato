<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Services\AI\Media\AudioTranscriptionService;
use Application\Services\AI\Media\ImageAnalysisService;
use Application\Services\AI\Media\MediaAsset;
use Application\Services\AI\Media\MediaRouterService;
use Application\Services\AI\Media\ReceiptAnalysisResult;
use Application\Services\AI\Media\TranscriptionResult;
use Application\Services\AI\Media\VideoTranscriptionService;
use PHPUnit\Framework\TestCase;

class MediaRouterServiceTest extends TestCase
{
    public function testRoutesAudioToTranscription(): void
    {
        $router = new MediaRouterService(
            audioTranscriber: new class extends AudioTranscriptionService {
                public function __construct() {}

                public function transcribe(string $audioContent, string $filename = 'audio.ogg', ?string $prompt = null): TranscriptionResult
                {
                    return new TranscriptionResult(success: true, text: 'gastei 25 no cafe', durationMs: 12);
                }
            },
            receiptAnalyzer: new class extends ImageAnalysisService {
                public function __construct() {}
            },
            videoTranscriber: new class extends VideoTranscriptionService {
                public function __construct() {}
            },
        );

        $result = $router->process(new MediaAsset(
            sourceType: 'audio',
            content: 'fake-audio',
            mimeType: 'audio/ogg',
            filename: 'audio.ogg',
        ));

        $this->assertTrue($result->success);
        $this->assertSame('audio_transcription', $result->operation);
        $this->assertSame('gastei 25 no cafe', $result->text);
    }

    public function testRoutesPdfToDocumentAnalysis(): void
    {
        $router = new MediaRouterService(
            audioTranscriber: new class extends AudioTranscriptionService {
                public function __construct() {}
            },
            receiptAnalyzer: new class extends ImageAnalysisService {
                public function __construct() {}

                public function analyzeReceipt(
                    string $content,
                    string $mimeType = 'image/jpeg',
                    ?string $contextHint = null,
                    ?string $filename = null,
                ): ReceiptAnalysisResult {
                    return new ReceiptAnalysisResult(
                        success: true,
                        data: [
                            'tipo' => 'despesa',
                            'descricao' => 'Mercado',
                            'valor' => 45.90,
                            'confianca' => 0.92,
                        ],
                        rawText: '{"tipo":"despesa"}',
                        tokensUsed: 123,
                    );
                }
            },
            videoTranscriber: new class extends VideoTranscriptionService {
                public function __construct() {}
            },
        );

        $result = $router->process(new MediaAsset(
            sourceType: 'document',
            content: '%PDF fake',
            mimeType: 'application/pdf',
            filename: 'comprovante.pdf',
        ));

        $this->assertTrue($result->success);
        $this->assertSame('document_analysis', $result->operation);
        $this->assertSame('despesa', $result->data['tipo']);
    }

    public function testRoutesVideoToVideoTranscription(): void
    {
        $router = new MediaRouterService(
            audioTranscriber: new class extends AudioTranscriptionService {
                public function __construct() {}
            },
            receiptAnalyzer: new class extends ImageAnalysisService {
                public function __construct() {}
            },
            videoTranscriber: new class extends VideoTranscriptionService {
                public function __construct() {}

                public function transcribe(string $videoContent, string $filename = 'video.mp4', ?string $prompt = null): TranscriptionResult
                {
                    return new TranscriptionResult(success: true, text: 'recebi 300 do cliente', durationMs: 21);
                }
            },
        );

        $result = $router->process(new MediaAsset(
            sourceType: 'video',
            content: 'fake-video',
            mimeType: 'video/mp4',
            filename: 'video.mp4',
        ));

        $this->assertTrue($result->success);
        $this->assertSame('video_transcription', $result->operation);
        $this->assertSame('recebi 300 do cliente', $result->text);
    }
}
