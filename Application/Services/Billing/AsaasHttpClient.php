<?php

declare(strict_types=1);

namespace Application\Services\Billing;

use Application\Config\AsaasRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Services\Http\ConfiguredHttpClient;

class AsaasHttpClient extends ConfiguredHttpClient
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $runtimeConfig = ApplicationContainer::resolveOrNew(null, AsaasRuntimeConfig::class);
        $apiKey = $runtimeConfig->apiKey();
        $baseUrl = $runtimeConfig->baseUrl();
        $userAgent = $runtimeConfig->userAgent();

        parent::__construct($config + [
            'base_uri' => rtrim($baseUrl, '/') . '/',
            'timeout' => 10,
            'http_errors' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => $userAgent,
                'access_token' => $apiKey,
            ],
        ]);
    }
}
