<?php

declare(strict_types=1);

namespace Application\Services\AI\IntentRules;

use Application\DTO\AI\IntentResult;
use Application\Enums\AI\IntentType;
use Application\Models\PendingAiAction;

/**
 * Detecta respostas de confirmação/rejeição quando há ação pendente.
 *
 * Só ativa se o usuário tem um PendingAiAction com status awaiting_confirm.
 * Suporta linguagem informal brasileira, abreviações WhatsApp e gírias.
 */
class ConfirmationIntentRule implements IntentRuleInterface
{
    private ?int $userId;

    public function __construct(?int $userId = null)
    {
        $this->userId = $userId;
    }

    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * Padrão amplo para detectar qualquer resposta de confirmação/rejeição.
     * Sem âncora rígida no final — permite "sim por favor", "ok pode", etc.
     */
    private const CONFIRMATION_PATTERN =
    '/^(?:'
        // Afirmativos
        . 'sim|ss|confirmar?|confirma|yes|ok|pode|isso|exato|claro'
        . '|com\s+certeza|bora|manda|dale|fechou|beleza|blz|show'
        . '|perfeito|tranquilo|ta\s*bom|t[áa]\s*bom|vamos|faz\s+isso'
        . '|segue|aham|uhum|valeu|vlw|manda\s*bala|positivo|opa|isso\s*a[ií]'
        // Negativos
        . '|n[ãa]o|nn|cancel(?:a[r]?|o)?|desistir?|n[ãa]o\s*quero'
        . '|nem|de\s+jeito\s+nenhum|esquece|deixa|deixa\s*pra\s*l[áa]'
        . '|negativo|nah|nope|para|deixa\s*quieto|n[ãa]o\s*precisa'
        . ')[\s!.,]*(?:por\s+favor|pfv|pf)?[\s!.]*$/iu';

    public function match(string $message, bool $isWhatsApp = false): ?IntentResult
    {
        if ($this->userId === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($message));

        // Verificar se a mensagem é uma confirmação/rejeição
        if (!preg_match(self::CONFIRMATION_PATTERN, $normalized)) {
            return null;
        }

        // Só ativar se houver ação pendente para este usuário
        $hasPending = PendingAiAction::where('user_id', $this->userId)
            ->awaiting()
            ->exists();

        if (!$hasPending) {
            return null;
        }

        // Verbo exato + pending ativo = certeza total
        return IntentResult::high(IntentType::CONFIRM_ACTION);
    }

    /**
     * Verifica se a mensagem é afirmativa.
     */
    public static function isAffirmative(string $message): bool
    {
        $normalized = mb_strtolower(trim($message));
        // Strict: palavra-chave exata (com trailing "por favor" opcional)
        if (preg_match(
            '/^(?:sim|ss|confirmar?|confirma|yes|ok|pode|isso|exato|claro'
                . '|com\s+certeza|bora|manda|dale|fechou|beleza|blz|show'
                . '|perfeito|tranquilo|ta\s*bom|t[áa]\s*bom|vamos|faz\s+isso'
                . '|segue|aham|uhum|valeu|vlw|manda\s*bala|positivo|opa|isso\s*a[ií]'
                . ')[\s!.,]*(?:por\s+favor|pfv|pf)?[\s!.]*$/iu',
            $normalized
        )) {
            return true;
        }
        // Loose: palavra-chave forte no início + texto complementar curto
        return (bool) preg_match(
            '/^(?:sim|ss|ok|pode|claro|beleza|show|perfeito|bora|manda|confirma)[,.\s!]+.{1,50}$/iu',
            $normalized
        );
    }

    /**
     * Verifica se a mensagem é negativa/rejeição.
     */
    public static function isNegative(string $message): bool
    {
        $normalized = mb_strtolower(trim($message));
        // Strict
        if (preg_match(
            '/^(?:n[ãa]o|nn|cancel[ao]?[r]?|desistir?|n[ãa]o\s*quero'
                . '|nem|de\s+jeito\s+nenhum|esquece|deixa|deixa\s*pra\s*l[áa]'
                . '|negativo|nah|nope|para|deixa\s*quieto|n[ãa]o\s*precisa'
                . ')[\s!.,]*(?:por\s+favor|pfv|pf)?[\s!.]*$/iu',
            $normalized
        )) {
            return true;
        }
        // Loose: negativa forte + texto complementar curto
        // Excluir frases de indecisão que começam com "não" mas não são negação de ação
        if (preg_match('/^n[ãa]o\s+(?:sei|sei\b.*|tenho\s+certeza|fa[çc]o\s+id[ée]ia|lembro)/iu', $normalized)) {
            return false;
        }
        return (bool) preg_match(
            '/^(?:n[ãa]o|nn|cancela|esquece|deixa|negativo)[,.\s!]+.{1,50}$/iu',
            $normalized
        );
    }
}
