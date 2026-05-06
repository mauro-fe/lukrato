<?php

declare(strict_types=1);
// Application/Services/Auth/AbstractValidationStrategy.php
namespace Application\Services\Auth;

use Application\Contracts\Auth\ValidationStrategyInterface;
use Application\DTO\Auth\CredentialsDTO;
use Application\Core\Exceptions\ValidationException;

abstract class AbstractValidationStrategy implements ValidationStrategyInterface
{
    /**
     * @var array<string, string>
     */
    protected array $errors = [];

    final public function validate(CredentialsDTO $credentials): void
    {
        $this->errors = [];

        $this->performValidation($credentials);

        if (!empty($this->errors)) {
            throw new ValidationException($this->errors, $this->getErrorMessage());
        }
    }

    abstract protected function performValidation(CredentialsDTO $credentials): void;

    abstract protected function getErrorMessage(): string;

    protected function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }
}
