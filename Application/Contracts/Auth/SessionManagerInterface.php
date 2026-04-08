<?php

declare(strict_types=1);

namespace Application\Contracts\Auth;

use Application\Models\Usuario;

interface SessionManagerInterface
{
    public function createSession(Usuario $user): void;

    public function destroySession(): void;

    public function isValid(): bool;
}
