<?php

declare(strict_types=1);

namespace Application\Services\AI\Media;

/**
 * Orquestra o pré-processamento multimídia e devolve uma saída única para os canais.
 */
class MediaRouterService
{
    public function __construct(
        private readonly ?AudioTranscriptionService $audioTranscriber = null,
        private readonly ?ImageAnalysisService $receiptAnalyzer = null,
        private readonly ?VideoTranscriptionService $videoTranscriber = null,
    ) {}

    public function process(MediaAsset $asset): MediaProcessingResult
    {
        $mediaType = $asset->mediaType();

        return match ($mediaType) {
            MediaType::AUDIO => $this->processAudio($asset),
            MediaType::IMAGE, MediaType::PDF => $this->processReceipt($asset, $mediaType),
            MediaType::VIDEO => new MediaProcessingResult(
                success: false,
                mediaType: MediaType::VIDEO,
                operation: 'unsupported',
                error: 'Videos nao sao suportados. Envie imagem, PDF ou audio.',
            ),
            default => new MediaProcessingResult(
                success: false,
                mediaType: $mediaType,
                operation: 'unsupported',
                error: 'Tipo de arquivo não suportado para processamento automático',
            ),
        };
    }

    private function processAudio(MediaAsset $asset): MediaProcessingResult
    {
        $transcriber = $this->audioTranscriber ?? new AudioTranscriptionService();
        $filename = $asset->filename ?? ('audio.' . $asset->extension('ogg'));
        $result = $transcriber->transcribe($asset->content, $filename, $asset->promptHint());

        return new MediaProcessingResult(
            success: $result->success,
            mediaType: MediaType::AUDIO,
            operation: 'audio_transcription',
            text: $result->text,
            durationMs: $result->durationMs,
            error: $result->error,
        );
    }

    private function processReceipt(MediaAsset $asset, string $mediaType): MediaProcessingResult
    {
        $analyzer = $this->receiptAnalyzer ?? new ImageAnalysisService();
        $result = $analyzer->analyzeReceipt(
            $asset->content,
            $asset->mimeType ?? 'application/octet-stream',
            $asset->promptHint(),
            $asset->filename
        );

        return new MediaProcessingResult(
            success: $result->success,
            mediaType: $mediaType,
            operation: $mediaType === MediaType::PDF ? 'document_analysis' : 'receipt_analysis',
            text: $result->rawText,
            data: $result->data,
            tokensUsed: $result->tokensUsed,
            tokensPrompt: $result->promptTokens,
            tokensCompletion: $result->completionTokens,
            durationMs: $result->durationMs,
            error: $result->error,
        );
    }

}
