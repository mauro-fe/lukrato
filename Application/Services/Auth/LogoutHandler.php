<?php

declare(strict_types=1);

namespace Application\Services\Auth;

use Application\Container\ApplicationContainer;
use Application\Core\Request;
use Application\Services\Infrastructure\LogService;
use Application\Lib\Auth;
use Application\Lib\Helpers;

class LogoutHandler
{
    private Request $request;
    private SessionManager $sessionManager;

    public function __construct(?Request $request = null, ?SessionManager $sessionManager = null)
    {
        $this->request = ApplicationContainer::resolveOrNew($request, Request::class);
        $this->sessionManager = ApplicationContainer::resolveOrNew($sessionManager, SessionManager::class);
    }

    /**
     * @return array{message: string, redirect: string}
     */
    public function handle(): array
    {
        $id = Auth::id();
        $this->sessionManager->destroySession();

        if ($id) {
            LogService::info('Logout realizado', [
                'user_id' => $id,
                'ip' => $this->request->ip()
            ]);
        }

        return [
            'message' => 'Logout realizado com sucesso.',
            'redirect' => Helpers::baseUrl('login')
        ];
    }
}
