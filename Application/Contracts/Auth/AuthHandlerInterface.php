<?php

declare(strict_types=1);

namespace Application\Contracts\Auth;

use Application\DTO\Auth\CredentialsDTO;

interface AuthHandlerInterface
{
    /**
     * @return array{redirect: string}
     */
    public function handle(CredentialsDTO $credentials): array;
}
