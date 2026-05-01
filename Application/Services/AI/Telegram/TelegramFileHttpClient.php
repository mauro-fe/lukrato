<?php

declare(strict_types=1);

namespace Application\Services\AI\Telegram;

use Application\Config\TelegramRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Services\Http\ConfiguredHttpClient;

class TelegramFileHttpClient extends ConfiguredHttpClient
{
    private const BASE_URL = 'https://api.telegram.org';

    public function __construct(array $config = [])
    {
        $token = ApplicationContainer::resolveOrNew(null, TelegramRuntimeConfig::class)->botToken();

        parent::__construct($config + [
            'base_uri' => self::BASE_URL . '/bot' . $token . '/',
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }
}
