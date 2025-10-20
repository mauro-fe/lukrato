<?php

namespace Application\Middlewares;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\FeatureGate;

final class RequireFeature
{
    public static function handle(string $feature): void
    {
        $user = Auth::user();
        if (!$user || !FeatureGate::allows($user, $feature)) {
            Response::forbidden('Recurso disponível apenas no plano Pro'); // 403
        }
    }
}
