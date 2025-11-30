<?php

namespace Application\Formatters;


class TelefoneFormatter
{
    public function __construct(
        private DocumentFormatter $documentFormatter
    ) {}


    public function split(?string $value): array
    {
        $digits = $this->documentFormatter->digits($value ?? '');
        $len = strlen($digits);

        if ($len < 10 || $len > 11) {
            return [null, null];
        }

        return [
            substr($digits, 0, 2),
            substr($digits, 2)
        ];
    }


    public function format(?string $ddd, ?string $numero): string
    {
        $dddDigits = $this->documentFormatter->digits($ddd ?? '');
        $numDigits = $this->documentFormatter->digits($numero ?? '');

        if ($numDigits === '') {
            return '';
        }
        $numDigits = substr($numDigits, -9);
        $len = strlen($numDigits);

        if ($len === 9) {
            $masked = substr($numDigits, 0, 5) . '-' . substr($numDigits, 5);
        } elseif ($len === 8) {
            $masked = substr($numDigits, 0, 4) . '-' . substr($numDigits, 4);
        } else {
            $masked = $numDigits;
        }

        return $dddDigits !== ''
            ? sprintf('(%s) %s', $dddDigits, $masked)
            : $masked;
    }
}