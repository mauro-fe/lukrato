<?php

namespace Application\DTO\Auth;

use Application\Models\Usuario;

class LoginResultDTO
{
    public function __construct(
        public readonly Usuario $usuario,
        public readonly string $redirect
    ) {}
}
