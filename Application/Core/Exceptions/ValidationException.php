<?php

namespace Application\Core\Exceptions;


class ValidationException extends \Exception
{
    private array $errors;

    public function __construct(array $errors, int $code = 400) // Adicionado $code
    {
        $this->errors = $errors;
        parent::__construct('Validation failed', $code); // Passa o código para a exceção base
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}