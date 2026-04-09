<?php

declare(strict_types=1);

namespace Application\Services\AI\WhatsApp;

use Application\Config\WhatsAppRuntimeConfig;
use Application\Container\ApplicationContainer;
use GuzzleHttp\Client;

class WhatsAppMediaHttpClient extends Client
{
    private const API_VERSION = 'v21.0';
    private const BASE_URL = 'https://graph.facebook.com';

    public function __construct(array $config = [])
    {
        $token = ApplicationContainer::resolveOrNew(null, WhatsAppRuntimeConfig::class)->token();

        parent::__construct($config + [
            'base_uri' => self::BASE_URL . '/' . self::API_VERSION . '/',
            'timeout' => 30,
            'connect_timeout' => 10,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);
    }
}
