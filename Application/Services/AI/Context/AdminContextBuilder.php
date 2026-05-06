<?php

declare(strict_types=1);

namespace Application\Services\AI\Context;

use Application\Container\ApplicationContainer;
use Application\Services\AI\SystemContextService;

/**
 * Constrói contexto completo para o admin (todos os collectors).
 * Wrapper semântico sobre SystemContextService com escopo global.
 */
class AdminContextBuilder
{
    private SystemContextService $contextService;

    public function __construct(?SystemContextService $contextService = null)
    {
        $this->contextService = ApplicationContainer::resolveOrNew($contextService, SystemContextService::class);
    }

    /**
     * Coleta contexto completo de todas as métricas do sistema.
     *
     * @return array<string, mixed>
     */
    public function build(): array
    {
        return $this->contextService->gather(null);
    }
}
