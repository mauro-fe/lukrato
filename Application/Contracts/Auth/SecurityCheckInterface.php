<?php

declare(strict_types=1);

namespace Application\Contracts\Auth;

use Application\Core\Request;

interface SecurityCheckInterface
{
    public function execute(Request $request): void;
}
