<?php

declare(strict_types=1);
// ========================================
// 4. APPLICATION/SERVICES/AUTH/IMPLEMENTATIONS
// ========================================

// Application/Services/Auth/CsrfSecurityCheck.php
namespace Application\Services\Auth;

use Application\Container\ApplicationContainer;
use Application\Core\Request;
use Application\Core\Exceptions\ValidationException;
use Application\Middlewares\CsrfMiddleware;

class CsrfSecurityCheck extends AbstractSecurityCheck
{
    private string $tokenName;

    public function __construct(?Request $request = null, string $tokenName = 'login_form')
    {
        parent::__construct(ApplicationContainer::resolveOrNew($request, Request::class));
        $this->tokenName = $tokenName;
    }

    protected function performCheck(Request $request): void
    {
        CsrfMiddleware::handle($request, $this->tokenName);
    }
}
