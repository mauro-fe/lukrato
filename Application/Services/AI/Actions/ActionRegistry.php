<?php

declare(strict_types=1);

namespace Application\Services\AI\Actions;

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
            'create_lancamento'   => new CreateLancamentoAction(),
            'create_meta'         => new CreateMetaAction(),
            'create_orcamento'    => new CreateOrcamentoAction(),
            'create_categoria'    => new CreateCategoriaAction(),
            'create_subcategoria' => new CreateSubcategoriaAction(),
            'create_conta'        => new CreateContaAction(),
            'pay_fatura'          => new PayFaturaAction(),
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
}
