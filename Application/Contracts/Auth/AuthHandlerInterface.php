<?php
// ========================================
// 1. APPLICATION/CONTRACTS/AUTH/
// ========================================

// Application/Contracts/Auth/AuthHandlerInterface.php
namespace Application\Contracts\Auth;

use Application\DTOs\Auth\CredentialsDTO;

interface AuthHandlerInterface
{
    public function handle(CredentialsDTO $credentials): array;
}

// Application/Contracts/Auth/ValidationStrategyInterface.php
namespace Application\Contracts\Auth;

use Application\DTOs\Auth\CredentialsDTO;

interface ValidationStrategyInterface
{
    /**
     * @throws ValidationException
     */
    public function validate(CredentialsDTO $credentials): void;
}

// Application/Contracts/Auth/SessionManagerInterface.php
namespace Application\Contracts\Auth;

use Application\Models\Usuario;

interface SessionManagerInterface
{
    public function createSession(Usuario $user): void;
    public function destroySession(): void;
    public function isValid(): bool;
}

// Application/Contracts/Auth/SecurityCheckInterface.php
namespace Application\Contracts\Auth;

use Application\Core\Request;

interface SecurityCheckInterface
{
    /**
     * @throws ValidationException
     */
    public function execute(Request $request): void;
}
