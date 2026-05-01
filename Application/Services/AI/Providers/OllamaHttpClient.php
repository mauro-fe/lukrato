<?php

declare(strict_types=1);

namespace Application\Services\AI\Providers;

use Application\Services\Http\ConfiguredHttpClient;

class OllamaHttpClient extends ConfiguredHttpClient
{
    public function __construct(array $config = [])
    {
        parent::__construct($config + [
            'timeout' => 120,
            'connect_timeout' => 5,
        ]);
    }
}
