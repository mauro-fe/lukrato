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
 * Detecta: "sim", "confirmar", "confirma", "não", "cancelar", "cancela".
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

    private const CONFIRMATION_PATTERN =
    '/^(?:sim|confirmar?|yes|ok|pode|isso|confirma|exato|n[ãa]o|cancelar?|cancel|desistir?|cancela|n[aã]o\s*quero)[\s!.]*$/iu';

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
        return (bool) preg_match('/^(?:sim|confirmar?|yes|ok|pode|isso|confirma|exato)[\s!.]*$/iu', $normalized);
    }
}
