<?php

declare(strict_types=1);

namespace Application\Contracts\Auth;

use Application\DTO\Auth\CredentialsDTO;

interface ValidationStrategyInterface
{
    public function validate(CredentialsDTO $credentials): void;
}
