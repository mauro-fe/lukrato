<?php

declare(strict_types=1);

namespace Application\Services\AI\Media;

use Application\Container\ApplicationContainer;

/**
 * Orquestra o pré-processamento multimídia e devolve uma saída única para os canais.
 */
class MediaRouterService
{
    private AudioTranscriptionService $audioTranscriber;
    private ImageAnalysisService $receiptAnalyzer;

    public function __construct(
        ?AudioTranscriptionService $audioTranscriber = null,
        ?ImageAnalysisService $receiptAnalyzer = null
    ) {
        $this->audioTranscriber = ApplicationContainer::resolveOrNew($audioTranscriber, AudioTranscriptionService::class);
        $this->receiptAnalyzer = ApplicationContainer::resolveOrNew($receiptAnalyzer, ImageAnalysisService::class);
    }

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
        $filename = $asset->filename ?? ('audio.' . $asset->extension('ogg'));
        $result = $this->audioTranscriber->transcribe($asset->content, $filename, $asset->promptHint());

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
        $result = $this->receiptAnalyzer->analyzeReceipt(
            $asset->content,
            $asset->mimeType ?? 'application/octet-stream',
            $asset->promptHint(),
            $asset->filename
        );

        return new MediaProcessingResult(
            $result->success,
            $mediaType,
            $mediaType === MediaType::PDF ? 'document_analysis' : 'receipt_analysis',
            $result->rawText,
            $result->data,
            $result->tokensUsed,
            $result->promptTokens,
            $result->completionTokens,
            $result->durationMs,
            $result->error,
        );
    }
}
