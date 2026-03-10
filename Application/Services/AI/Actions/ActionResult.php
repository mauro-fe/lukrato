<?php

declare(strict_types=1);

namespace Application\Services\AI\Actions;

/**
 * DTO de resultado de uma Action de criação.
 */
readonly class ActionResult
{
    public function __construct(
        public bool   $success,
        public string $message,
        public array  $data = [],
        public array  $errors = [],
    ) {}

    public static function ok(string $message, array $data = []): self
    {
        return new self(true, $message, $data);
    }

    public static function fail(string $message, array $errors = []): self
    {
        return new self(false, $message, [], $errors);
    }
}
