<?php

declare(strict_types=1);

namespace Application\Services\AI\Media;

/**
 * Resultado da análise de imagem de comprovante/recibo via GPT-4o-mini Vision.
 */
readonly class ReceiptAnalysisResult
{
    public function __construct(
        public bool    $success,
        public array   $data = [],
        public string  $rawText = '',
        public int     $tokensUsed = 0,
        public ?string $error = null,
    ) {}

    /**
     * Verifica se a imagem contém dados financeiros.
     */
    public function isFinancial(): bool
    {
        return ($this->data['tipo'] ?? 'nao_financeiro') !== 'nao_financeiro';
    }

    /**
     * Converte dados extraídos em texto para o pipeline de transações.
     * Ex: "despesa padaria 35.50 pix"
     */
    public function toTransactionText(): string
    {
        if (!$this->isFinancial()) {
            return '';
        }

        $parts = [];

        $tipo = $this->data['tipo'] ?? 'despesa';
        $parts[] = $tipo === 'receita' ? 'recebi' : 'gastei';

        $valor = $this->data['valor'] ?? 0;
        if ($valor > 0) {
            $parts[] = number_format((float) $valor, 2, '.', '');
        }

        $desc = $this->data['descricao'] ?? $this->data['estabelecimento'] ?? '';
        if ($desc !== '') {
            $parts[] = $desc;
        }

        $forma = $this->data['forma_pagamento'] ?? '';
        if ($forma !== '' && $forma !== 'null') {
            $parts[] = $forma;
        }

        return implode(' ', $parts);
    }
}
