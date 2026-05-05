<?php

declare(strict_types=1);

namespace Application\Services\AI\Media;

/**
 * Resultado da análise de comprovante via visão/documento.
 */
readonly class ReceiptAnalysisResult
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public bool $success,
        public array $data = [],
        public string $rawText = '',
        public int $tokensUsed = 0,
        public int $promptTokens = 0,
        public int $completionTokens = 0,
        public int $durationMs = 0,
        public ?string $error = null,
    ) {}

    public function isFinancial(): bool
    {
        $tipo = $this->data['tipo'] ?? 'nao_financeiro';
        if (!in_array($tipo, ['despesa', 'receita'], true)) {
            return false;
        }

        $confidence = (float) ($this->data['confianca'] ?? 0);
        if ($confidence > 0 && $confidence < 0.35) {
            return false;
        }

        $hasDescription = trim((string) ($this->data['descricao'] ?? $this->data['estabelecimento'] ?? '')) !== '';
        $hasAmount = (float) ($this->data['valor'] ?? 0) > 0;

        return $hasDescription || $hasAmount;
    }

    public function toTransactionText(): string
    {
        if (!$this->isFinancial()) {
            return '';
        }

        $parts = [];
        $tipo = $this->data['tipo'] ?? 'despesa';
        $parts[] = $tipo === 'receita' ? 'recebi' : 'gastei';

        $valor = (float) ($this->data['valor'] ?? 0);
        if ($valor > 0) {
            $parts[] = number_format($valor, 2, '.', '');
        }

        $descricao = trim((string) ($this->data['descricao'] ?? $this->data['estabelecimento'] ?? ''));
        if ($descricao !== '') {
            $parts[] = $descricao;
        }

        $formaPagamento = trim((string) ($this->data['forma_pagamento'] ?? ''));
        if ($formaPagamento !== '' && $formaPagamento !== 'null') {
            $parts[] = $formaPagamento;
        }

        return implode(' ', $parts);
    }

    /**
     * @return array{descricao:string,valor:float,tipo:string,data:string,forma_pagamento?:string}
     */
    public function toTransactionData(?string $defaultDate = null): array
    {
        $defaultDate = $defaultDate ?: date('Y-m-d');

        $data = [
            'descricao' => (string) ($this->data['descricao'] ?? $this->data['estabelecimento'] ?? 'Compra'),
            'valor' => (float) ($this->data['valor'] ?? 0),
            'tipo' => ($this->data['tipo'] ?? 'despesa') === 'receita' ? 'receita' : 'despesa',
            'data' => (string) ($this->data['data'] ?? $defaultDate),
        ];

        $formaPagamento = trim((string) ($this->data['forma_pagamento'] ?? ''));
        if ($formaPagamento !== '' && $formaPagamento !== 'null') {
            $data['forma_pagamento'] = $formaPagamento;
        }

        return $data;
    }
}
