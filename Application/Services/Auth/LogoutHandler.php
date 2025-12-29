<?php

namespace Application\Services\Auth;

use Application\Core\Request;
use Application\Services\LogService;
use Application\Lib\Auth;
use Application\Lib\Helpers;

class LogoutHandler
{
    private SessionManager $sessionManager;

    public function __construct()
    {
        $this->sessionManager = new SessionManager();
    }

    public function handle(): array
    {
        $id = Auth::id();
        $this->sessionManager->destroySession();

        if ($id) {
            LogService::info('Logout realizado', [
                'user_id' => $id,
                'ip' => (new Request())->ip()
            ]);
        }

        return [
            'message' => 'Logout realizado com sucesso.',
            'redirect' => Helpers::baseUrl('login')
        ];
    }
}
