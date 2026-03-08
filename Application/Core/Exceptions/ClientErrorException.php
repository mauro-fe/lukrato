<?php

namespace Application\Core\Exceptions;

/**
 * Exceção para erros de cliente (4xx) da API.
 * Não deve ser contabilizada pelo Circuit Breaker como falha de serviço.
 */
class ClientErrorException extends \RuntimeException
{
    public function __construct(
        public readonly int $statusCode,
        string $message = '',
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
