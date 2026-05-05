<?php

declare(strict_types=1);

namespace Application\Services\AI\Actions;

/**
 * Contrato para Actions de criação de entidades via IA.
 * Handler → Action → Service → Repository
 */
interface ActionInterface
{
    /**
     * Executa a criação da entidade.
     *
     * @param int   $userId  ID do usuário autenticado.
     * @param array<string, mixed> $payload Dados extraídos pela IA.
     * @return ActionResult  Resultado da operação.
     */
    public function execute(int $userId, array $payload): ActionResult;
}
