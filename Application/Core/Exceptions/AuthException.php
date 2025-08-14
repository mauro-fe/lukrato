<?php

namespace Application\Core\Exceptions;



class AuthException extends \Exception
{
    // Opcional: Adicione um construtor customizado ou propriedades se precisar de mais detalhes
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}