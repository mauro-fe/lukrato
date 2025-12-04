<?php

namespace Application\Middlewares;

class AdminMiddleware
{
    public function handle($request, $next)
    {
        $user = $_SESSION['user'] ?? null;

        if (!$user || $user->is_admin !== 1) {
            header('Location: /login?error=unauthorized');
            exit;
        }

        return $next($request);
    }
}