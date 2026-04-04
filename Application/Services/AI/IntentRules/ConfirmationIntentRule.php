<?php

declare(strict_types=1);

namespace Application\Services\AI\IntentRules;

use Application\DTO\AI\IntentResult;
use Application\Enums\AI\IntentType;
use Application\Models\PendingAiAction;

/**
 * Detecta respostas de confirmacao/rejeicao quando ha acao pendente.
 *
 * Só ativa se o usuário tem um PendingAiAction com status awaiting_confirm.
 * Suporta linguagem informal brasileira, abreviacoes WhatsApp e girias.
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

    public function match(string $message, bool $isWhatsApp = false): ?IntentResult
    {
        if ($this->userId === null) {
            return null;
        }

        $normalized = trim($message);

        // Reaproveita os helpers para aceitar confirmacoes mais naturais
        // como "pode pagar" e "ok esta bom".
        if (!self::isAffirmative($normalized) && !self::isNegative($normalized)) {
            return null;
        }

        $hasPending = PendingAiAction::where('user_id', $this->userId)
            ->awaiting()
            ->exists();

        if (!$hasPending) {
            return null;
        }

        return IntentResult::high(IntentType::CONFIRM_ACTION);
    }

    /**
     * Verifica se a mensagem e afirmativa.
     */
    public static function isAffirmative(string $message): bool
    {
        $normalized = mb_strtolower(trim($message));

        if (preg_match(
            '/^(?:sim|ss|confirmar?|confirma|yes|ok|pode|isso|exato|claro'
                . '|com\s+certeza|bora|manda|dale|fechou|beleza|blz|show'
                . '|perfeito|tranquilo|ta\s*bom|t[áa]\s*bom|vamos|faz\s+isso'
                . '|segue|aham|uhum|valeu|vlw|manda\s*bala|positivo|opa|isso\s+a[ií]'
                . ')[\s!.,]*(?:por\s+favor|pfv|pf)?[\s!.]*$/iu',
            $normalized
        )) {
            return true;
        }

        return (bool) preg_match(
            '/^(?:sim|ss|ok|pode|claro|beleza|show|perfeito|bora|manda|confirma)[,.\s!]+.{1,50}$/iu',
            $normalized
        );
    }

    /**
     * Verifica se a mensagem e negativa/rejeicao.
     */
    public static function isNegative(string $message): bool
    {
        $normalized = mb_strtolower(trim($message));

        if (preg_match(
            '/^(?:n[ãa]o|nn|cancel[ao]?[r]?|desistir?|n[ãa]o\s*quero'
                . '|nem|de\s+jeito\s+nenhum|esquece|deixa|deixa\s*pra\s*l[áa]'
                . '|negativo|nah|nope|para|deixa\s*quieto|n[ãa]o\s*precisa'
                . ')[\s!.,]*(?:por\s+favor|pfv|pf)?[\s!.]*$/iu',
            $normalized
        )) {
            return true;
        }

        if (preg_match('/^n[ãa]o\s+(?:sei|sei\b.*|tenho\s+certeza|fa[çc]o\s+id[ée]ia|lembro)/iu', $normalized)) {
            return false;
        }

        return (bool) preg_match(
            '/^(?:n[ãa]o|nn|cancela|esquece|deixa|negativo)[,.\s!]+.{1,50}$/iu',
            $normalized
        );
    }
}
