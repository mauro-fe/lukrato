<?php

declare(strict_types=1);

namespace Application\Services\AI\IntentRules;

use Application\Enums\AI\IntentType;

/**
 * Detecta intenção de registro de transação financeira.
 *
 * Padrões cobertos:
 *  - "gastei 40 no uber", "paguei 32.50 de luz"
 *  - "recebi 5000 de salário", "ganhei 1500 freelance"
 *  - "uber 32", "ifood 45", "mercado 120" (descrição + valor)
 *  - "40 uber", "32.50 ifood" (valor + descrição)
 *  - WhatsApp shortcut: mensagens curtas com número
 */
class TransactionIntentRule implements IntentRuleInterface
{
    /**
     * Verbos que indicam registro de transação.
     */
    private const VERB_PATTERN =
        'gastei|paguei|pago|recebi|ganhei|comprei|vendi|transferi|depositei';

    /**
     * Padrão simples: "descrição valor" (ex: "uber 32", "ifood 45.90")
     */
    private const DESC_VALUE_PATTERN =
        '/^[a-zà-ú\s]{2,20}\s+(?:r\$\s*)?\d{1,5}(?:[.,]\d{1,2})?\s*$/iu';

    /**
     * Padrão simples: "valor descrição" (ex: "32 uber", "45.90 ifood")
     */
    private const VALUE_DESC_PATTERN =
        '/^\s*(?:r\$\s*)?\d{1,5}(?:[.,]\d{1,2})?\s+\w/iu';

    /**
     * WhatsApp: mensagens curtas com número (~sempre transação).
     */
    private const WHATSAPP_SHORT_PATTERN =
        '/^\s*(?:(?:gastei|paguei|recebi|comprei|ganhei)\s+)?(?:r\$\s*)?\d+[.,]?\d*\s+/iu';

    public function match(string $message, bool $isWhatsApp = false): ?IntentType
    {
        $normalized = mb_strtolower(trim($message));

        // WhatsApp: mensagens curtas com número são quase sempre transações
        if ($isWhatsApp && mb_strlen($normalized) <= 100) {
            if (preg_match(self::WHATSAPP_SHORT_PATTERN, $normalized)) {
                return IntentType::EXTRACT_TRANSACTION;
            }
        }

        // Verbos de transação: "gastei 40 no uber"
        if (preg_match('/(' . self::VERB_PATTERN . ')/iu', $normalized)) {
            // Verificar se contém algum valor numérico
            if (preg_match('/\b\d{1,5}(?:[.,]\d{1,2})?\b/', $normalized)) {
                return IntentType::EXTRACT_TRANSACTION;
            }
        }

        // "descrição valor": "uber 32", "ifood 45.90", "mercado 120"
        if (preg_match(self::DESC_VALUE_PATTERN, $normalized)) {
            return IntentType::EXTRACT_TRANSACTION;
        }

        // "valor descrição": "32 uber", "45.90 ifood"
        if (preg_match(self::VALUE_DESC_PATTERN, $normalized)) {
            // Evitar falso positivo com frases que começam com número mas não são transações
            // Ex: "2 perguntas sobre..." → ignorar se tem muitas palavras
            $wordCount = str_word_count($normalized);
            if ($wordCount <= 4) {
                return IntentType::EXTRACT_TRANSACTION;
            }
        }

        return null;
    }
}
