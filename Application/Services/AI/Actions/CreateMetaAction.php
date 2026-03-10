<?php

declare(strict_types=1);

namespace Application\Services\AI\Actions;

use Application\Services\Financeiro\MetaService;

class CreateMetaAction implements ActionInterface
{
    public function execute(int $userId, array $payload): ActionResult
    {
        $service = new MetaService();
        $meta = $service->criar($userId, $payload);

        return ActionResult::ok(
            "Meta **{$payload['titulo']}** criada com sucesso!",
            $meta
        );
    }
}
