<?php

declare(strict_types=1);

namespace Application\Services\AI\IntentRules;

use Application\DTO\AI\IntentResult;

/**
 * Contrato para regras de detecção de intent.
 * Cada regra verifica se uma mensagem corresponde a um IntentType específico,
 * retornando IntentResult com confidence score.
 */
interface IntentRuleInterface
{
    /**
     * Tenta detectar o intent a partir da mensagem.
     * Retorna IntentResult com confidence score ou null se não matchou.
     */
    public function match(string $message, bool $isWhatsApp = false): ?IntentResult;
}
