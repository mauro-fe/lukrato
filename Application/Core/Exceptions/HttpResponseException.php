<?php

declare(strict_types=1);

namespace Application\Core\Exceptions;

use Application\Core\Response;

final class HttpResponseException extends \RuntimeException
{
    public function __construct(private readonly Response $response)
    {
        parent::__construct('', $response->getStatusCode());
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}
