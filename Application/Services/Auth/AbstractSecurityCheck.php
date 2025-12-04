<?php
// ========================================
// 3. APPLICATION/SERVICES/AUTH/ (ABSTRATAS)
// ========================================

// Application/Services/Auth/AbstractSecurityCheck.php
namespace Application\Services\Auth;

use Application\Contracts\Auth\SecurityCheckInterface;
use Application\Core\Request;
use Application\Core\Exceptions\ValidationException;
use Application\Services\LogService;

abstract class AbstractSecurityCheck implements SecurityCheckInterface
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    final public function execute(Request $request): void
    {
        try {
            $this->performCheck($request);
        } catch (ValidationException $e) {
            $this->logFailure($e);
            throw $e;
        }
    }

    abstract protected function performCheck(Request $request): void;

    protected function logFailure(ValidationException $e): void
    {
        LogService::warning(
            static::class . ' failed',
            ['error' => $e->getMessage(), 'ip' => $this->request->ip()]
        );
    }

    protected function getIdentifier(): string
    {
        return $this->request->ip() ?? 'unknown';
    }
}
