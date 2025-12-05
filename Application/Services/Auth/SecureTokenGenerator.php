<?php

namespace Application\Services\Auth;

use Application\Contracts\Auth\TokenGeneratorInterface;

class SecureTokenGenerator implements TokenGeneratorInterface
{
    public function generate(): string
    {
        return bin2hex(random_bytes(32));
    }
}