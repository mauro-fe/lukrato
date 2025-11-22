<?php
// ========================================
// 4. APPLICATION/SERVICES/AUTH/IMPLEMENTATIONS
// ========================================

// Application/Services/Auth/CsrfSecurityCheck.php
namespace Application\Services\Auth;

use Application\Core\Request;
use Application\Core\Exceptions\ValidationException;
use Application\Middlewares\CsrfMiddleware;

class CsrfSecurityCheck extends AbstractSecurityCheck
{
    private string $tokenName;

    public function __construct(Request $request, string $tokenName = 'login_form')
    {
        parent::__construct($request);
        $this->tokenName = $tokenName;
    }

    protected function performCheck(Request $request): void
    {
        CsrfMiddleware::handle($request, $this->tokenName);
    }
}
