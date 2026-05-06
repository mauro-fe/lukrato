<?php

declare(strict_types=1);

namespace Application\Services\Admin;

use Application\Services\Http\ConfiguredHttpClient;

class OpenAIQuotaHttpClient extends ConfiguredHttpClient
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config + [
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout' => 10,
            'connect_timeout' => 5,
        ]);
    }
}
