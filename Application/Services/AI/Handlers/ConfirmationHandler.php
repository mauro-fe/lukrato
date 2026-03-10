<?php

declare(strict_types=1);

namespace Application\Services\AI\Handlers;

use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\IntentType;
use Application\Models\PendingAiAction;
use Application\Repositories\ContaRepository;
use Application\Services\AI\Actions\ActionRegistry;
use Application\Services\AI\Contracts\AIProvider;
use Application\Services\AI\IntentRules\ConfirmationIntentRule;
use Application\Services\AI\Rules\CategoryRuleEngine;

/**
 * Handler para confirmar ou rejeitar ações pendentes de IA (PendingAiAction).
 *
 * Fluxo: Handler → ActionRegistry → Action → Service → Repository
 */
class ConfirmationHandler implements AIHandlerInterface
{
    private ?AIProvider $provider = null;
    private ActionRegistry $actionRegistry;

    public function __construct()
    {
        $this->actionRegistry = new ActionRegistry();
    }

    public function setProvider(AIProvider $provider): void
    {
        $this->provider = $provider;
    }

    public function supports(IntentType $intent): bool
    {
        return $intent === IntentType::CONFIRM_ACTION;
    }

    public function handle(AIRequestDTO $request): AIResponseDTO
    {
        $userId  = $request->userId;
        $message = mb_strtolower(trim($request->message));

        if (!$userId) {
            return AIResponseDTO::fail('Usuário não identificado.', IntentType::CONFIRM_ACTION);
        }

        $pending = PendingAiAction::awaiting()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->first();

        if (!$pending) {
            return AIResponseDTO::fail(
                'Não encontrei nenhuma ação pendente para confirmar.',
                IntentType::CONFIRM_ACTION
            );
        }

        if ($pending->isExpired()) {
            $pending->markExpired();
            return AIResponseDTO::fromRule(
                '⏰ A ação expirou. Por favor, inicie o processo novamente.',
                ['action' => 'expired'],
                IntentType::CONFIRM_ACTION
            );
        }

        // Rejeição
        if (!ConfirmationIntentRule::isAffirmative($message)) {
            $pending->reject();
            return AIResponseDTO::fromRule(
                '❌ Ação cancelada com sucesso.',
                ['action' => 'rejected', 'pending_id' => $pending->id],
                IntentType::CONFIRM_ACTION
            );
        }

        // Confirmação — executar a criação via Action Layer
        return $this->executeCreation($pending, $userId);
    }

    private function executeCreation(PendingAiAction $pending, int $userId): AIResponseDTO
    {
        $payload    = $pending->payload;
        $actionType = $pending->action_type;

        // Se lancamento sem conta_id, tentar auto-preencher
        if ($actionType === 'create_lancamento' && empty($payload['conta_id'])) {
            $contaRepo = new ContaRepository();
            $contas = $contaRepo->findActive($userId);

            if ($contas->isEmpty()) {
                $pending->reject();
                return AIResponseDTO::fromRule(
                    '⚠️ Você precisa ter pelo menos uma conta cadastrada para registrar lançamentos.',
                    ['action' => 'creation_failed'],
                    IntentType::CONFIRM_ACTION
                );
            }

            if ($contas->count() === 1) {
                $payload['conta_id'] = $contas->first()->id;
                $pending->payload = $payload;
                $pending->save();
            } else {
                // Múltiplas contas — precisa selecionar via botão
                return AIResponseDTO::fromRule(
                    '⚠️ Você tem mais de uma conta. Por favor, selecione a conta no menu acima e clique em **Confirmar**.',
                    ['action' => 'needs_account', 'pending_id' => $pending->id],
                    IntentType::CONFIRM_ACTION
                );
            }
        }

        $action = $this->actionRegistry->resolve($actionType);

        if ($action === null) {
            $pending->reject();
            return AIResponseDTO::fromRule(
                '⚠️ Tipo de ação desconhecido.',
                ['action' => 'creation_failed'],
                IntentType::CONFIRM_ACTION
            );
        }

        try {
            $result = $action->execute($userId, $payload);

            if (!$result->success) {
                $pending->reject();
                return AIResponseDTO::fromRule(
                    "⚠️ {$result->message}",
                    ['action' => 'creation_failed', 'errors' => $result->errors],
                    IntentType::CONFIRM_ACTION
                );
            }

            $pending->confirm();

            // Aprender categorização quando o usuário confirma um lançamento
            if ($actionType === 'create_lancamento' && !empty($payload['descricao']) && !empty($payload['categoria_id'])) {
                CategoryRuleEngine::learn(
                    $userId,
                    $payload['descricao'],
                    (int) $payload['categoria_id'],
                    !empty($payload['subcategoria_id']) ? (int) $payload['subcategoria_id'] : null,
                    'confirmed'
                );
            }

            return AIResponseDTO::fromRule(
                "✅ {$result->message}",
                ['action' => 'created', 'entity_type' => str_replace('create_', '', $actionType), 'data' => $result->data],
                IntentType::CONFIRM_ACTION
            );
        } catch (\DomainException $e) {
            $pending->reject();
            return AIResponseDTO::fromRule(
                "⚠️ {$e->getMessage()}",
                ['action' => 'creation_failed'],
                IntentType::CONFIRM_ACTION
            );
        } catch (\Throwable $e) {
            return AIResponseDTO::fail(
                '⚠️ Erro ao criar: ' . $e->getMessage(),
                IntentType::CONFIRM_ACTION
            );
        }
    }
}
