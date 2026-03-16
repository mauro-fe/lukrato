<?php

declare(strict_types=1);

namespace Application\Services\AI\IntentRules;

use Application\DTO\AI\IntentResult;
use Application\Enums\AI\IntentType;
use Application\Services\AI\TransactionDetectorService;

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
    private const EXPLICIT_CREATION_GUARD =
    '/\b(?:criar|crie|cadastr(?:ar|e)|adicion(?:ar|e)|novo|nova|definir|registr(?:ar|e|o)|lanc(?:ar|e)|quero\s+criar)\b/iu';

    /**
     * Verbos que indicam registro de transação.
     * Cobre: passado, presente, gerúndio, 3ª pessoa, perífrases e gírias BR.
     */
    private const VERB_PATTERN =
    // Passado 1ª pessoa (mais comum)
    'gastei|paguei|comprei|vendi|transferi|depositei|investi|emprestei|devolvi'
        . '|torrei|larguei|meti|soltei|botei|raspei|queimei|detonei|estourei'
        . '|sacou|saquei|cobrou|cobraram|debitou|debitaram'
        // Presente / infinitivo
        . '|pago|gasto|parcelo'
        // 3ª pessoa passado (ele/ela)
        . '|gastou|pagou|comprou|vendeu|transferiu|custou'
        // Gerúndio / perifrástico
        . '|gastando|pagando|comprando|vendendo'
        // Perífrases verbais (muito comuns em BR informal)
        . '|acabei\s+de\s+(?:pagar|gastar|comprar)|fui\s+(?:pagar|comprar)'
        . '|to\s+(?:pagando|gastando|devendo)|vou\s+(?:pagar|gastar|comprar)'
        // PIX
        . '|mandei\s+pix|fiz\s+pix|recebi\s+(?:um\s+)?pix'
        // Parcelamento
        . '|parcelei|parcelo'
        // Recebimento / entrada
        . '|recebi|ganhei|entrou|caiu(?:\s+na\s+conta|\s+o\s+pix)?|depositaram'
        // Expressões com "deu" + valor
        . '|deu\s+(?:r?\$?\s*)?\d';

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

    /**
     * Contextos não-monetários: número + contexto que claramente não é dinheiro.
     * Ex: "rua 5", "página 3", "3 episódios", "andar 2", "sala 10"
     */
    private const NON_MONETARY_CONTEXT =
    '/(?:\b(?:rua|p[áa]gina|ep[ií]s[óo]dio|cap[íi]tulo|andar|sala|quarto|bloco|apt|apartamento|turma|fase|n[ií]vel|vers[ãa]o|temporada|parte|volume|edi[çc][ãa]o|item|numeros?|n[úu]mero|nota)\s+\d|\b\d+\s*(?:epis[óo]dios?|cap[íi]tulos?|p[áa]ginas?|andares?|horas?|minutos?|segundos?|dias?|meses?|anos?|vezes|pessoas?|amigos?|gatos?|cachorros?))\b/iu';

    public function match(string $message, bool $isWhatsApp = false): ?IntentResult
    {
        $normalized = mb_strtolower(trim($message));
        $entityType = EntityCreationIntentRule::detectEntityType($normalized);

        if ($entityType !== null && $entityType !== 'lancamento') {
            return null;
        }

        if ($entityType === 'lancamento' && preg_match(self::EXPLICIT_CREATION_GUARD, $normalized)) {
            return null;
        }

        // Excluir contextos onde números claramente não são valores monetários
        if (preg_match(self::NON_MONETARY_CONTEXT, $normalized)) {
            return null;
        }

        if (TransactionDetectorService::extract($normalized) !== null) {
            return IntentResult::medium(IntentType::EXTRACT_TRANSACTION, 0.95, ['source' => 'transaction_detector']);
        }

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
