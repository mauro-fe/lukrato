<?php

declare(strict_types=1);

namespace Application\Services\AI\Handlers;

use Application\Container\ApplicationContainer;
use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\IntentType;
use Application\Models\PendingAiAction;
use Application\Repositories\ContaRepository;
use Application\Models\Categoria;
use Application\Services\AI\Actions\ActionRegistry;
use Application\Services\AI\Contracts\AIProvider;
use Application\Services\AI\IntentRules\ConfirmationIntentRule;
use Application\Services\AI\Rules\CategoryRuleEngine;
use Application\Services\Infrastructure\LogService;

/**
 * Handler para confirmar ou rejeitar ações pendentes de IA (PendingAiAction).
 *
 * Fluxo: Handler → ActionRegistry → Action → Service → Repository
 */
class ConfirmationHandler implements AIHandlerInterface
{
    private ?AIProvider $provider = null;
    private ActionRegistry $actionRegistry;
    private ContaRepository $contaRepository;

    public function __construct(
        ?ActionRegistry $actionRegistry = null,
        ?ContaRepository $contaRepository = null
    ) {
        $this->actionRegistry = ApplicationContainer::resolveOrNew($actionRegistry, ActionRegistry::class);
        $this->contaRepository = ApplicationContainer::resolveOrNew($contaRepository, ContaRepository::class);
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

        $pendingActions = PendingAiAction::awaiting()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        if ($pendingActions->isEmpty()) {
            return AIResponseDTO::fail(
                'Não encontrei nenhuma ação pendente para confirmar.',
                IntentType::CONFIRM_ACTION
            );
        }

        // Se há múltiplas actions pendentes, confirma/rejeita a mais recente
        // mas avisa o usuário sobre as outras
        $pending = $pendingActions->first();

        if ($pending->isExpired()) {
            $pending->markExpired();
            return AIResponseDTO::fromRule(
                '⏰ A ação expirou. Por favor, inicie o processo novamente.',
                ['action' => 'expired'],
                IntentType::CONFIRM_ACTION
            );
        }

        // Confirmação
        if (ConfirmationIntentRule::isAffirmative($message)) {
            return $this->executeCreation($pending, $userId);
        }

        // Rejeição explícita
        if (ConfirmationIntentRule::isNegative($message)) {
            $pending->reject();
            $msg = '❌ Ação cancelada com sucesso.';
            if ($pendingActions->count() > 1) {
                $remaining = $pendingActions->count() - 1;
                $msg .= "\n\nVocê ainda tem **{$remaining}** ação(ões) pendente(s). Diga **sim** para confirmar a próxima.";
            }
            return AIResponseDTO::fromRule(
                $msg,
                ['action' => 'rejected', 'pending_id' => $pending->id, 'remaining' => $pendingActions->count() - 1],
                IntentType::CONFIRM_ACTION
            );
        }

        // Ambíguo — pedir clarificação ao invés de rejeitar silenciosamente
        return AIResponseDTO::fromRule(
            'Não entendi. Quer confirmar o registro? Responda **sim** ou **não**.',
            ['action' => 'clarify', 'pending_id' => $pending->id],
            IntentType::CONFIRM_ACTION
        );
    }

    private function executeCreation(PendingAiAction $pending, int $userId): AIResponseDTO
    {
        $payload    = $pending->payload;
        $actionType = $pending->action_type;

        // Se lancamento sem conta_id, auto-preencher com primeira conta (cartão de crédito não precisa)
        $isCartao = ($payload['forma_pagamento'] ?? null) === 'cartao_credito';
        if ($actionType === 'create_lancamento' && empty($payload['conta_id']) && !$isCartao) {
            $contas = $this->contaRepository->findActive($userId);

            if ($contas->isEmpty()) {
                $pending->reject();
                return AIResponseDTO::fromRule(
                    '⚠️ Você precisa ter pelo menos uma conta cadastrada para registrar lançamentos.',
                    ['action' => 'creation_failed'],
                    IntentType::CONFIRM_ACTION
                );
            }

            // Auto-preencher com primeira conta (usuário já teve chance de trocar no dropdown)
            $payload['conta_id'] = $contas->first()->id;
            $pending->payload = $payload;
            $pending->save();
        }

        // Resolver categoria_sugerida → categoria_id se ainda não resolvido
        if ($actionType === 'create_lancamento' && empty($payload['categoria_id'])) {
            $descricao = $payload['descricao'] ?? '';
            if ($descricao !== '') {
                $match = CategoryRuleEngine::match(
                    $descricao,
                    $userId,
                    $payload['categoria_contexto'] ?? null
                );
                if ($match !== null && !empty($match['categoria_id'])) {
                    $payload['categoria_id'] = (int) $match['categoria_id'];
                    if (!empty($match['subcategoria_id'])) {
                        $payload['subcategoria_id'] = (int) $match['subcategoria_id'];
                    }
                }
            }
            if (empty($payload['categoria_id'])) {
                $sugerida = $payload['categoria_sugerida'] ?? null;
                if ($sugerida !== null && $sugerida !== '') {
                    $categoria = Categoria::where('user_id', $userId)
                        ->whereRaw('LOWER(nome) LIKE ?', ['%' . mb_strtolower(trim($sugerida)) . '%'])
                        ->first();
                    if ($categoria) {
                        $payload['categoria_id'] = $categoria->id;
                    }
                }
            }
            if (!empty($payload['categoria_id'])) {
                $pending->payload = $payload;
                $pending->save();
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
            LogService::warning('ConfirmationHandler.executeCreation', ['error' => $e->getMessage()]);

            return AIResponseDTO::fail(
                'Erro ao criar o registro. Tente novamente.',
                IntentType::CONFIRM_ACTION
            );
        }
    }
}
