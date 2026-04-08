<?php

declare(strict_types=1);

namespace Application\Services\AI\Actions;

use Application\Container\ApplicationContainer;

/**
 * Resolve a Action correta pelo action_type da PendingAiAction.
 */
class ActionRegistry
{
    /** @var array<string, ActionInterface> */
    private array $actions = [];

    public function __construct()
    {
        $this->actions = [
            'create_lancamento'   => $this->resolveAction(CreateLancamentoAction::class),
            'create_meta'         => $this->resolveAction(CreateMetaAction::class),
            'create_orcamento'    => $this->resolveAction(CreateOrcamentoAction::class),
            'create_categoria'    => $this->resolveAction(CreateCategoriaAction::class),
            'create_subcategoria' => $this->resolveAction(CreateSubcategoriaAction::class),
            'create_conta'        => $this->resolveAction(CreateContaAction::class),
            'pay_fatura'          => $this->resolveAction(PayFaturaAction::class),
        ];
    }

    public function resolve(string $actionType): ?ActionInterface
    {
        return $this->actions[$actionType] ?? null;
    }

    public function has(string $actionType): bool
    {
        return isset($this->actions[$actionType]);
    }

    private function resolveAction(string $actionClass): ActionInterface
    {
        $action = ApplicationContainer::resolveOrNew(null, $actionClass);

        if (!$action instanceof ActionInterface) {
            throw new \RuntimeException('Ação de IA inválida: ' . $actionClass);
        }

        return $action;
    }
}
