<?php

namespace Application\Core\Exceptions;

/**
 * Exceção customizada para falhas de autenticação (Login, Sessão).
 */
class AuthException extends \Exception
{
    /**
     * Construtor da Exceção de Autenticação.
     *
     * @param string $message Mensagem da exceção.
     * @param int $code O código de status HTTP (padrão 401).
     * @param \Throwable|null $previous A exceção anterior.
     */
    public function __construct(
        string $message = 'Authentication Required', // Mensagem padrão útil
        int $code = 401, // Código HTTP 401 Unauthorized
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}