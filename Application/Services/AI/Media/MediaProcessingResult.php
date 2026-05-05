<?php

declare(strict_types=1);

namespace Application\Services\AI\Media;

/**
 * Resultado unificado do processamento multimídia.
 */
readonly class MediaProcessingResult
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public bool $success,
        public string $mediaType,
        public string $operation,
        public string $text = '',
        public array $data = [],
        public int $tokensUsed = 0,
        public int $tokensPrompt = 0,
        public int $tokensCompletion = 0,
        public int $durationMs = 0,
        public ?string $error = null,
    ) {}

    public function isUnsupported(): bool
    {
        return $this->operation === 'unsupported';
    }

    public function isReceiptAnalysis(): bool
    {
        return in_array($this->operation, ['receipt_analysis', 'document_analysis'], true);
    }

    public function isFinancialReceipt(): bool
    {
        if (!$this->isReceiptAnalysis()) {
            return false;
        }

        return (new ReceiptAnalysisResult(
            success: $this->success,
            data: $this->data,
            rawText: $this->text,
            tokensUsed: $this->tokensUsed,
            promptTokens: $this->tokensPrompt,
            completionTokens: $this->tokensCompletion,
            durationMs: $this->durationMs,
            error: $this->error,
        ))->isFinancial();
    }
}
