<?php

declare(strict_types=1);

namespace Application\Services\Importacao;

final class OfxImportTargetDetector
{
    /**
     * @var array<int, string>
     */
    private const CARD_TAGS = [
        'CREDITCARDMSGSRSV1',
        'CCSTMTTRNRS',
        'CCSTMTRS',
        'CCACCTFROM',
    ];

    /**
     * @var array<int, string>
     */
    private const ACCOUNT_TAGS = [
        'BANKMSGSRSV1',
        'STMTTRNRS',
        'STMTRS',
        'BANKACCTFROM',
    ];

    /**
     * @return array{detected_import_target:?string,matched_tags:array<int,string>}
     */
    public function inspect(string $contents): array
    {
        $trimmed = trim($contents);
        if ($trimmed === '') {
            return [
                'detected_import_target' => null,
                'matched_tags' => [],
            ];
        }

        $cardMatches = $this->findMatches($trimmed, self::CARD_TAGS);
        $accountMatches = $this->findMatches($trimmed, self::ACCOUNT_TAGS);

        if ($cardMatches !== [] && $accountMatches === []) {
            return [
                'detected_import_target' => 'cartao',
                'matched_tags' => $cardMatches,
            ];
        }

        if ($accountMatches !== [] && $cardMatches === []) {
            return [
                'detected_import_target' => 'conta',
                'matched_tags' => $accountMatches,
            ];
        }

        return [
            'detected_import_target' => null,
            'matched_tags' => array_values(array_unique(array_merge($cardMatches, $accountMatches))),
        ];
    }

    public function buildMismatchMessage(?string $detectedImportTarget, string $selectedImportTarget): ?string
    {
        $detected = $this->normalizeTarget($detectedImportTarget);
        $selected = $this->normalizeTarget($selectedImportTarget);

        if ($detected === null || $selected === $detected) {
            return null;
        }

        if ($detected === 'cartao') {
            return 'Este OFX parece ser de cartão/fatura. Troque o alvo para Cartão/fatura e selecione o cartão correto antes de confirmar a importação.';
        }

        return 'Este OFX parece ser de conta bancária. Troque o alvo para Conta antes de confirmar a importação.';
    }

    /**
     * @param array<int, string> $tags
     * @return array<int, string>
     */
    private function findMatches(string $contents, array $tags): array
    {
        $matches = [];

        foreach ($tags as $tag) {
            if (preg_match('/<\s*' . preg_quote($tag, '/') . '\b/i', $contents) === 1) {
                $matches[] = $tag;
            }
        }

        return $matches;
    }

    private function normalizeTarget(?string $target): ?string
    {
        $normalized = strtolower(trim((string) $target));

        return in_array($normalized, ['conta', 'cartao'], true) ? $normalized : null;
    }
}
