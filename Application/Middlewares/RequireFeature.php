<?php

namespace Application\Middlewares;

use Application\Core\Exceptions\HttpResponseException;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\Plan\FeatureGate;

final class RequireFeature
{
    public static function handle(string $feature): void
    {
        $user = Auth::user();
        if (!$user || !FeatureGate::allows($user, $feature)) {
            throw new HttpResponseException(Response::forbiddenResponse('Recurso disponível apenas no plano Pro'));
        }
    }
}
