<?php

namespace Application\Formatters;

/**
 * Formatter responsável por formatar telefones.
 */
class TelefoneFormatter
{
    public function __construct(
        private DocumentFormatter $documentFormatter
    ) {}

    /**
     * Extrai DDD e número local do telefone bruto.
     * 
     * @return array{0: string|null, 1: string|null} [DDD, Número Local]
     */
    public function split(?string $value): array
    {
        $digits = $this->documentFormatter->digits($value ?? '');
        $len = strlen($digits);

        if ($len < 10 || $len > 11) {
            return [null, null];
        }

        return [
            substr($digits, 0, 2),  // DDD
            substr($digits, 2)       // Número local
        ];
    }

    /**
     * Formata telefone no padrão (XX) XXXXX-XXXX ou (XX) XXXX-XXXX.
     */
    public function format(?string $ddd, ?string $numero): string
    {
        $dddDigits = $this->documentFormatter->digits($ddd ?? '');
        $numDigits = $this->documentFormatter->digits($numero ?? '');

        if ($numDigits === '') {
            return '';
        }

        // Padroniza para 8 ou 9 dígitos
        $numDigits = substr($numDigits, -9);
        $len = strlen($numDigits);

        // Formata o número local
        if ($len === 9) {
            $masked = substr($numDigits, 0, 5) . '-' . substr($numDigits, 5);
        } elseif ($len === 8) {
            $masked = substr($numDigits, 0, 4) . '-' . substr($numDigits, 4);
        } else {
            $masked = $numDigits;
        }

        // Adiciona DDD se disponível
        return $dddDigits !== ''
            ? sprintf('(%s) %s', $dddDigits, $masked)
            : $masked;
    }
}