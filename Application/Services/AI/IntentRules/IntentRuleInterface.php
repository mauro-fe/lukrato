<?php

declare(strict_types=1);

namespace Application\Services\AI\IntentRules;

use Application\Enums\AI\IntentType;

/**
 * Contrato para regras de detecção de intent.
 * Cada regra verifica se uma mensagem corresponde a um IntentType específico.
 */
interface IntentRuleInterface
{
    /**
     * Tenta detectar o intent a partir da mensagem.
     * Retorna o IntentType correspondente ou null se não matchou.
     */
    public function match(string $message, bool $isWhatsApp = false): ?IntentType;
}
