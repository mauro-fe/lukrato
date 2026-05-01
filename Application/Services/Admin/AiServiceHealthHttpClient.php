<?php

declare(strict_types=1);

namespace Application\Services\Admin;

use Application\Services\Http\ConfiguredHttpClient;

class AiServiceHealthHttpClient extends ConfiguredHttpClient
{
    public function __construct(array $config = [])
    {
        parent::__construct($config + [
            'timeout' => 5,
            'connect_timeout' => 3,
        ]);
    }
}
