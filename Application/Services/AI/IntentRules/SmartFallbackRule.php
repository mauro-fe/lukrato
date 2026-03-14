<?php

declare(strict_types=1);

namespace Application\Services\AI\IntentRules;

use Application\DTO\AI\IntentResult;
use Application\Enums\AI\IntentType;

/**
 * Fallback inteligente: captura mensagens que parecem transações financeiras
 * mas que não foram capturadas pelos outros IntentRules.
 *
 * Ativa quando: mensagem tem 5-150 chars + contém número + contém
 * palavras-chave financeiras ou padrões de gasto/receita.
 *
 * Exemplos capturados:
 *  - "50 conto de luz" (coloquial sem verbo)
 *  - "cinema ontem 40" (desc + data + valor)
 *  - "pix pro joão 200" (transferência informal)
 *  - "uns 30 de uber" (valor aproximado)
 *  - "fatura de 800 do nubank" (cartão sem verbo)
 */
class SmartFallbackRule implements IntentRuleInterface
{
    /**
     * Palavras-chave financeiras que indicam transação quando combinadas com número.
     */
    private const FINANCIAL_KEYWORDS =
    'reais|conto[s]?|pila[s]?|real|r\$|paus|mangos?'
        . '|pix|boleto|fatura|parcela|cart[ãa]o|cr[ée]dito|d[ée]bito'
        . '|uber|99|cabify|ifood|i\s*food|rappi|mercado|supermercado|farmácia|farmacia'
        . '|gasolina|combustível|combustivel|posto|estacionamento'
        . '|aluguel|condomínio|condominio|luz|[áa]gua|energia|internet|telefone'
        . '|restaurante|almo[çc]o|jantar|padaria|lanche|café|cafeteria'
        . '|academia|médico|medico|dentista'
        . '|netflix|spotify|disney|hbo|globoplay|amazon|prime'
        . '|salário|salario|freela|freelance|mesada|renda'
        . '|escola|faculdade|curso|livro'
        . '|viagem|hotel|passagem|cinema|show|ingresso'
        . '|roupa|sapato|tênis|tenis|shopping|shein|shopee|magalu|magazine|casas\s*bahia'
        . '|pet|veterinário|veterinario|ra[çc][ãa]o'
        . '|presente|aniversário|aniversario'
        . '|rachar|dividir|vaquinha|multa|taxa|juros|iof'
        . '|mercado\s*livre|aliexpress';

    /**
     * Padrão de valor numérico (com ou sem R$).
     */
    private const VALUE_PATTERN =
    '/(?:r\$\s*)?\d{1,6}(?:[.,]\d{1,2})?\s*(?:reais|conto[s]?|pila[s]?|k\b)?/iu';

    /**
     * Padrão de valor coloquial: "mil reais", "1k", "uns 30"
     */
    private const COLLOQUIAL_PATTERN =
    '/(?:uns?\s+\d+|\d+\s*k\b|\bmil\s*(?:reais)?)/iu';

    /**
     * Contextos não-monetários: número + contexto que claramente não é dinheiro.
     * Ex: "rua 5", "3 episódios", "página 3", "andar 2"
     */
    private const NON_MONETARY_CONTEXT =
    '/(?:\b(?:rua|p[áa]gina|ep[ií]s[óo]dio|cap[íi]tulo|andar|sala|quarto|bloco|apt|apartamento|turma|fase|n[ií]vel|vers[ãa]o|temporada|parte|volume|edi[çc][ãa]o|item|numeros?|n[úu]mero|nota)\s+\d|\b\d+\s*(?:epis[óo]dios?|cap[íi]tulos?|p[áa]ginas?|andares?|horas?|minutos?|segundos?|dias?|meses?|anos?|vezes|pessoas?|amigos?|gatos?|cachorros?))\b/iu';

    public function match(string $message, bool $isWhatsApp = false): ?IntentResult
    {
        $normalized = mb_strtolower(trim($message));
        $length = mb_strlen($normalized);

        // Só para mensagens de 5-250 chars (nem muito curta, nem muito longa)
        if ($length < 5 || $length > 250) {
            return null;
        }

        // Excluir contextos onde números claramente não são valores monetários
        if (preg_match(self::NON_MONETARY_CONTEXT, $normalized)) {
            return null;
        }

        // Precisa ter algum número ou valor coloquial
        $hasValue = preg_match(self::VALUE_PATTERN, $normalized) || preg_match(self::COLLOQUIAL_PATTERN, $normalized);
        if (!$hasValue) {
            return null;
        }

        // Precisa ter pelo menos uma palavra-chave financeira
        if (!preg_match('/(?:' . self::FINANCIAL_KEYWORDS . ')/iu', $normalized)) {
            return null;
        }

        // Evitar conflito com perguntas puras (sem valor financeiro)
        // Perguntas com valor como "como paguei 200 de internet?" devem passar
        if (preg_match('/^(?:quanto|qual|como|quando|onde|quem|por\s*que|porque)\b/iu', $normalized)) {
            // Mas se contém verbo de transação + valor, é transação disfarçada de pergunta
            if (!preg_match('/(?:gastei|paguei|comprei|recebi|cobrou|custou)\s+.*\d/iu', $normalized)) {
                return null;
            }
        }

        // Evitar conflito com confirmações
        if (preg_match('/^(?:sim|n[ãa]o|ok|confirma|cancela)[\s!.]*$/iu', $normalized)) {
            return null;
        }

        return IntentResult::medium(IntentType::EXTRACT_TRANSACTION, 0.65, [
            'source' => 'smart_fallback',
        ]);
    }
}
