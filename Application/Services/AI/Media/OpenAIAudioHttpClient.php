<?php

declare(strict_types=1);

namespace Application\Services\AI\Media;

use Application\Services\Http\ConfiguredHttpClient;

class OpenAIAudioHttpClient extends ConfiguredHttpClient
{
    public function __construct(array $config = [])
    {
        parent::__construct($config + [
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout' => 60,
            'connect_timeout' => 10,
        ]);
    }
}
