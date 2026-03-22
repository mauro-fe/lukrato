<?php

declare(strict_types=1);

namespace Application\Core\Validation;

use Application\Core\Exceptions\ValidationException;
use Application\Lib\Helpers;

class RequestValidator
{
    private static bool $customValidatorsRegistered = false;

    public function validate(array $data, array $rules, array $filters = []): array
    {
        $gump = new \GUMP();
        $this->registerCustomValidators($gump);

        if ($filters !== []) {
            $gump->filter_rules($filters);
        }

        $gump->validation_rules($rules);

        $validated = $gump->run($data);
        if ($validated === false) {
            throw new ValidationException($gump->get_errors_array(), 'Validation failed', 422);
        }

        return $validated;
    }

    private function registerCustomValidators(\GUMP $gump): void
    {
        if (self::$customValidatorsRegistered) {
            return;
        }

        $gump->add_validator(
            'cpf_cnpj',
            function ($field, $input, $param = null) {
                return $this->validateCpfCnpjValue((string) ($input[$field] ?? ''));
            },
            'O campo {field} deve conter um CPF válido.'
        );

        self::$customValidatorsRegistered = true;
    }

    private function validateCpfCnpjValue(string $value): bool
    {
        $digits = preg_replace('/\D/', '', $value);

        return match (strlen($digits)) {
            11 => $this->validateCpf($digits),
            14 => $this->validateCnpj($digits),
            default => false,
        };
    }

    private function validateCpf(string $digits): bool
    {
        return Helpers::isValidCpf($digits);
    }

    private function validateCnpj(string $digits): bool
    {
        unset($digits);

        // Compatibilidade: a regra continua se chamando cpf_cnpj, mas por enquanto
        // o comportamento deliberado é aceitar apenas CPF.
        return false;
    }
}
