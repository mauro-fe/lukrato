<?php

declare(strict_types=1);

namespace Application\Services\AI\IntentRules;

use Application\DTO\AI\IntentResult;
use Application\Enums\AI\IntentType;

/**
 * Detecta intenção de registro de transação financeira.
 *
 * Padrões cobertos:
 *  - "gastei 40 no uber", "paguei 32.50 de luz"
 *  - "recebi 5000 de salário", "ganhei 1500 freelance"
 *  - "uber 32", "ifood 45", "mercado 120" (descrição + valor)
 *  - "40 uber", "32.50 ifood" (valor + descrição)
 *  - "50 conto de luz", "200 reais no mercado" (coloquial)
 *  - "parcelei 3000 em 12x no inter" (cartão parcelado)
 *  - "12x de 99 geladeira" (parcelas + valor unitário)
 *  - "comprei no cartão 500 de sapato" (cartão de crédito)
 *  - WhatsApp shortcut: mensagens curtas com número
 */
class TransactionIntentRule implements IntentRuleInterface
{
    /**
     * Verbos que indicam registro de transação.
     */
    private const VERB_PATTERN =
    'gastei|paguei|pago|recebi|ganhei|comprei|vendi|transferi|depositei'
    . '|torrei|larguei|meti|soltei|botei|investi|emprestei|devolvi'
    . '|mandei\s+pix|fiz\s+pix|parcelei|parcelo';

    /**
     * Padrão simples: "descrição valor" (ex: "uber 32", "ifood 45.90")
     */
    private const DESC_VALUE_PATTERN =
    '/^[a-zà-ú\s]{2,30}\s+(?:r\$\s*)?\d{1,6}(?:[.,]\d{1,2})?\s*(?:reais|conto[s]?|pila[s]?)?\s*$/iu';

    /**
     * Padrão simples: "valor descrição" (ex: "32 uber", "45.90 ifood")
     */
    private const VALUE_DESC_PATTERN =
    '/^\s*(?:r\$\s*)?\d{1,6}(?:[.,]\d{1,2})?\s*(?:reais|conto[s]?|pila[s]?)?\s+\w/iu';

    /**
     * WhatsApp: mensagens curtas com número (~sempre transação).
     */
    private const WHATSAPP_SHORT_PATTERN =
    '/^\s*(?:(?:gastei|paguei|recebi|comprei|ganhei|torrei|parcelei)\s+)?(?:r\$\s*)?\d+[.,]?\d*\s+/iu';

    /**
     * Padrões de cartão de crédito / parcelamento
     */
    private const CARD_PATTERN =
    '/(?:no\s+cart[ãa]o|no\s+cr[ée]dito|no\s+d[ée]bito|parcelei|em\s+\d{1,2}\s*x|\d{1,2}\s*x\s+de)/iu';

    /**
     * Valores coloquiais: "mil reais", "1k", "2k", "50 conto", "200 pila"
     */
    private const COLLOQUIAL_VALUE_PATTERN =
    '/(?:\d+\s*k\b|\bmil\s*(?:reais)?|\d+\s*(?:conto[s]?|pila[s]?|real|reais))/iu';

    public function match(string $message, bool $isWhatsApp = false): ?IntentResult
    {
        $normalized = mb_strtolower(trim($message));

        // WhatsApp: mensagens curtas com número são quase sempre transações
        if ($isWhatsApp && mb_strlen($normalized) <= 100) {
            if (preg_match(self::WHATSAPP_SHORT_PATTERN, $normalized)) {
                return IntentResult::medium(IntentType::EXTRACT_TRANSACTION, 0.9, ['source' => 'whatsapp_short']);
            }
        }

        // Padrão de cartão/parcelamento: "parcelei 3000 em 12x", "comprei no cartão 500"
        if (preg_match(self::CARD_PATTERN, $normalized)) {
            if (preg_match('/\d+/', $normalized)) {
                return IntentResult::medium(IntentType::EXTRACT_TRANSACTION, 0.9, ['source' => 'card_pattern']);
            }
        }

        // Verbos de transação: "gastei 40 no uber", "torrei 200 no shopping"
        if (preg_match('/(' . self::VERB_PATTERN . ')/iu', $normalized)) {
            // Verificar se contém algum valor numérico ou coloquial
            if (preg_match('/\b\d{1,6}(?:[.,]\d{1,2})?\b/', $normalized) || preg_match(self::COLLOQUIAL_VALUE_PATTERN, $normalized)) {
                return IntentResult::medium(IntentType::EXTRACT_TRANSACTION, 0.85, ['source' => 'verb_value']);
            }
        }

        // "descrição valor": "uber 32", "ifood 45.90", "mercado 120", "luz 150 conto"
        if (preg_match(self::DESC_VALUE_PATTERN, $normalized)) {
            return IntentResult::medium(IntentType::EXTRACT_TRANSACTION, 0.8, ['source' => 'desc_value']);
        }

        // "valor descrição": "32 uber", "45.90 ifood", "50 conto de luz"
        if (preg_match(self::VALUE_DESC_PATTERN, $normalized)) {
            // Evitar falso positivo com frases que começam com número mas não são transações
            $wordCount = str_word_count($normalized);
            if ($wordCount <= 6) {
                return IntentResult::medium(IntentType::EXTRACT_TRANSACTION, 0.7, ['source' => 'value_desc']);
            }
        }

        // Valor coloquial sem verbo: "1k mercado", "mil reais de compras"
        if (preg_match(self::COLLOQUIAL_VALUE_PATTERN, $normalized)) {
            $wordCount = str_word_count($normalized);
            if ($wordCount <= 5) {
                return IntentResult::medium(IntentType::EXTRACT_TRANSACTION, 0.7, ['source' => 'colloquial_value']);
            }
        }

        return null;
    }
}
