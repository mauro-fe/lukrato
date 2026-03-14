<?php

declare(strict_types=1);

namespace Application\Services\AI\IntentRules;

use Application\DTO\AI\IntentResult;
use Application\Enums\AI\IntentType;

/**
 * Detecta intenção de pagar fatura de cartão de crédito.
 *
 * Exemplos: "pagar fatura do nubank", "quero quitar a fatura", "pagar cartão"
 */
class PayFaturaIntentRule implements IntentRuleInterface
{
    /**
     * Padrões que indicam intenção de pagamento de fatura.
     * Exigem verbo de pagamento (pagar/quitar) + contexto de fatura/cartão.
     */
    private const PATTERNS = [
        '/(?:pagar?|quitar?|pago|quitei)\s+(?:(?:a|minha|essa|esta|aquela|uma)\s+)?fatura/iu',
        '/(?:pagar?|quitar?)\s+(?:(?:o|meu|esse|este|aquele|um)\s+)?cart[ãa]o/iu',
        '/quero\s+pagar?\s+(?:(?:a|minha|essa|esta)\s+)?fatura/iu',
        '/quero\s+quitar?\s+(?:(?:a|minha|essa|esta)\s+)?fatura/iu',
        '/(?:pagar?|quitar?)\s+(?:(?:a|minha|essa|esta)\s+)?fatura\s+(?:do|da|de)\s+/iu',
    ];

    /**
     * Guard: mensagens com valor numérico explícito são transações, não pagamento de fatura.
     * Ex: "fatura de 800 do nubank" → extract_transaction
     */
    private const HAS_MONETARY_VALUE = '/\b(?:r\$\s*)?\d+(?:[.,]\d+)?\b.*(?:fatura|cart[ãa]o)|(?:fatura|cart[ãa]o).*\b(?:de\s+)?(?:r\$\s*)?\d+(?:[.,]\d+)?\b/iu';

    public function match(string $message, bool $isWhatsApp = false): ?IntentResult
    {
        // Remove formatação básica de markdown se for WhatsApp
        if ($isWhatsApp) {
            $message = str_replace(['*', '_', '~'], '', $message);
        }

        $normalized = mb_strtolower(trim($message));

        // Guard aprimorado: foca em valores com centavos ou precedidos por R$
        // para não confundir "fatura de 2025" (ano) com "fatura de 2025" (valor)
        $hasValue = preg_match('/(?:r\$\s*\d+)|(?:\d+[.,]\d{2})/iu', $normalized);
        if ($hasValue && preg_match('/fatura|cart[ãa]o/iu', $normalized)) {
            return null;
        }

        foreach (self::PATTERNS as $pattern) {
            if (preg_match($pattern, $normalized)) {
                return IntentResult::medium(IntentType::PAY_FATURA, 0.9);
            }
        }

        return null;
    }
}
