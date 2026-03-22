<?php

declare(strict_types=1);

namespace Application\Services\Auth;

class TokenPairService
{
    /**
     * @return array{selector:string,validator:string,token_hash:string}
     */
    public function issue(int $selectorBytes = 8, int $validatorBytes = 32): array
    {
        $selector = bin2hex(random_bytes($selectorBytes));
        $validator = bin2hex(random_bytes($validatorBytes));

        return [
            'selector' => $selector,
            'validator' => $validator,
            'token_hash' => $this->hashValidator($validator),
        ];
    }

    public function hashValidator(string $validator): string
    {
        return hash('sha256', $validator);
    }

    public function matches(string $validator, ?string $tokenHash): bool
    {
        if (!is_string($tokenHash) || $tokenHash === '') {
            return false;
        }

        return hash_equals($tokenHash, $this->hashValidator($validator));
    }
}
