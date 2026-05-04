<?php

namespace Application\Middlewares;

use Application\Core\Exceptions\HttpResponseException;
use Application\Core\Response;
use Application\Lib\Auth;

final class RequireFeature
{
    public static function handle(string $feature): void
    {
        $user = Auth::user();
        if (!$user || !$user->plan()->allows($feature)) {
            throw new HttpResponseException(Response::forbiddenResponse('Recurso não disponível no seu plano atual.'));
        }
    }
}
