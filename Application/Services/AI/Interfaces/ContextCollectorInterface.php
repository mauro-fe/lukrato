<?php

declare(strict_types=1);

namespace Application\Services\AI\Interfaces;

use Application\Services\AI\DTO\ContextPeriod;

/**
 * Contrato para collectors de contexto da IA.
 * Cada implementação coleta dados de um domínio específico.
 */
interface ContextCollectorInterface
{
    /**
     * Coleta dados agregados (read-only) para enriquecer o contexto da IA.
     *
     * @param ContextPeriod $period Período de referência
     * @param int|null      $userId ID do usuário (null = visão admin global)
     * @return array<string, mixed> Associativo ['chave' => dados]
     */
    public function collect(ContextPeriod $period, ?int $userId = null): array;
}
