<?php

declare(strict_types=1);

namespace Application\Services\AI\Providers;

use GuzzleHttp\Client;

class OpenAIHttpClient extends Client
{
    public function __construct(array $config = [])
    {
        parent::__construct($config + [
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }
}
