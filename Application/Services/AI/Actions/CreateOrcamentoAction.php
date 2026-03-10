<?php

declare(strict_types=1);

namespace Application\Services\AI\Actions;

use Application\Services\Financeiro\OrcamentoService;

class CreateOrcamentoAction implements ActionInterface
{
    public function execute(int $userId, array $payload): ActionResult
    {
        $service = new OrcamentoService();

        $categoriaId = (int) ($payload['categoria_id'] ?? 0);
        $mes = (int) ($payload['mes'] ?? date('m'));
        $ano = (int) ($payload['ano'] ?? date('Y'));

        $orc = $service->salvar($userId, $categoriaId, $mes, $ano, $payload);

        $valor = 'R$ ' . number_format((float) ($payload['valor_limite'] ?? 0), 2, ',', '.');

        return ActionResult::ok(
            "Orçamento de {$valor} criado para {$mes}/{$ano}!",
            $orc
        );
    }
}
