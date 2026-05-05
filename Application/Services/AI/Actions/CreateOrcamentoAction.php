<?php

declare(strict_types=1);

namespace Application\Services\AI\Actions;

use Application\Container\ApplicationContainer;
use Application\Services\Orcamentos\OrcamentoService;

class CreateOrcamentoAction implements ActionInterface
{
    private OrcamentoService $service;

    public function __construct(?OrcamentoService $service = null)
    {
        $this->service = ApplicationContainer::resolveOrNew($service, OrcamentoService::class);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int $userId, array $payload): ActionResult
    {
        $categoriaId = (int) ($payload['categoria_id'] ?? 0);
        $mes = (int) ($payload['mes'] ?? date('m'));
        $ano = (int) ($payload['ano'] ?? date('Y'));

        $orc = $this->service->salvar($userId, $categoriaId, $mes, $ano, $payload);

        $valor = 'R$ ' . number_format((float) ($payload['valor_limite'] ?? 0), 2, ',', '.');

        return ActionResult::ok(
            "Orçamento de {$valor} criado para {$mes}/{$ano}!",
            $orc
        );
    }
}
