<?php

declare(strict_types=1);

namespace Application\Services\Billing;

use Application\Services\Infrastructure\CircuitBreakerService;

class AsaasCircuitBreakerService extends CircuitBreakerService
{
    public function __construct()
    {
        parent::__construct('asaas');
    }
}
