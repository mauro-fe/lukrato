<?php

declare(strict_types=1);

namespace Application\Services\AI\Actions;

use Application\Container\ApplicationContainer;
use Application\Services\Metas\MetaService;

class CreateMetaAction implements ActionInterface
{
    private MetaService $service;

    public function __construct(?MetaService $service = null)
    {
        $this->service = ApplicationContainer::resolveOrNew($service, MetaService::class);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int $userId, array $payload): ActionResult
    {
        $meta = $this->service->criar($userId, $payload);

        return ActionResult::ok(
            "Meta **{$payload['titulo']}** criada com sucesso!",
            $meta
        );
    }
}
