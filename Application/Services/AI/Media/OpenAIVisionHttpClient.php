<?php

declare(strict_types=1);

namespace Application\Services\AI\Media;

use GuzzleHttp\Client;

class OpenAIVisionHttpClient extends Client
{
    public function __construct(array $config = [])
    {
        parent::__construct($config + [
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout' => 45,
            'connect_timeout' => 10,
        ]);
    }
}
